<?php

namespace Drupal\a12s_layout\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for display options global settings.
 */
class DisplayOptionsSettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginManager $displayOptionsSetPluginManager
   *   The Display Options Set plugin manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected DisplayOptionsSetPluginManager $displayOptionsSetPluginManager)
  {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.a12s_layout_display_options_set')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'a12s_layout_display_options_settings';
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames(): array {
    return ['a12s_layout.display_options'];
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('a12s_layout.display_options');
    $form['#tree'] = TRUE;

    if (!isset($form['#plugin_definitions'])) {
      $form['#plugin_definitions'] = $this->displayOptionsSetPluginManager->getDefinitions();
    }

    $form['display_options_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => t('Display options'),
    ];

    foreach ($form['#plugin_definitions'] as $id => $definition) {
      try {
        // First ensure the plugin ca be loaded.
        /** @var \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetInterface $plugin */
        $plugin = $this->displayOptionsSetPluginManager->createInstance($id);
        $subFormKey = $plugin->getMachineName();
        $subForm = [];
        $subformState = SubformState::createForSubform($subForm, $form, $form_state);
        $pluginConfig = $config->get($subFormKey) ?? [];
        $plugin->globalSettingsForm($subForm, $subformState, $pluginConfig);

        if (!empty($subForm)) {
          if (!isset($form[$definition['category_id']])) {
            $form[$definition['category_id']]['#type'] = 'details';
            $form[$definition['category_id']]['#title'] = $definition['category'];
            $form[$definition['category_id']]['#group'] = 'display_options_tabs';
          }

          $subForm['#type'] = 'details';
          $subForm['#title'] = $plugin->label();
          $subForm['#open'] = TRUE;
          $form[$definition['category_id']][$subFormKey] = $subForm;
        }
      }
      catch (PluginException $e) {
        unset($form['plugin_definitions'][$id]);
        watchdog_exception('a12s_layout', $e);
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($form['#plugin_definitions'] as $id => $definition) {
      /** @var \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetInterface $plugin */
      $plugin = $this->displayOptionsSetPluginManager->createInstance($id);
      $subForm = &$form[$definition['category_id']][$plugin->getMachineName()];

      if (isset($subForm)) {
        $subformState = SubformState::createForSubform($subForm, $form, $form_state);
        $plugin->validateGlobalSettingsForm($subForm, $subformState);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('a12s_layout.display_options');
    $config->setData([]);

    foreach ($form['#plugin_definitions'] as $id => $definition) {
      /** @var \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetInterface $plugin */
      $plugin = $this->displayOptionsSetPluginManager->createInstance($id);
      $subFormKey = $plugin->getMachineName();
      $subForm = &$form[$definition['category_id']][$subFormKey];

      if (isset($subForm)) {
        $subformState = SubformState::createForSubform($subForm, $form, $form_state);
        $plugin->submitGlobalSettingsForm($subForm, $subformState);
        $config->set($subFormKey, $form_state->getValue([$definition['category_id'], $subFormKey], []));
      }
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
