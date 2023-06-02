<?php

namespace Drupal\a12s_layout\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginManager;
use Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display Options instance form.
 *
 * @property \Drupal\a12s_layout\Entity\DisplayOptionsInstanceInterface $entity
 */
class DisplayOptionsInstanceForm extends EntityForm {

  /**
   * Constructs a display options instance object.
   *
   * @param \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginManager $displayTemplatePluginManager
   *   The Display Options plugin manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $displayOptionsInstanceStorage
   *   The entity storage class for "display options instance".
   * @param \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginManager $displayOptionsSetPluginManager
   *   The Display options set plugin manager.
   */
  public function __construct(
    protected DisplayTemplatePluginManager $displayTemplatePluginManager,
    protected EntityStorageInterface $displayOptionsInstanceStorage,
    protected DisplayOptionsSetPluginManager $displayOptionsSetPluginManager
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.a12s_layout_display_template'),
      $container->get('entity_type.manager')->getStorage('a12s_layout_display_options'),
      $container->get('plugin.manager.a12s_layout_display_options_set')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $options = [];
    $pluginDefinitions = $this->displayTemplatePluginManager->getDefinitions();
    $instanceIds = $this->displayOptionsInstanceStorage->getQuery()
      ->accessCheck(FALSE)
      ->execute();

    foreach ($pluginDefinitions as $plugin => $definition) {
      $instanceId = strtr($plugin, [':' => '__']);
      // Only create one instance per plugin.
      if (in_array($instanceId, $instanceIds, TRUE) && $this->entity->isNew()) {
        continue;
      }

      $optgroup = [(string) $definition['category']];
      if (isset($definition['subcategory'])) {
        $optgroup[] = (string) $definition['subcategory'];
      }

      $options[implode(': ', $optgroup)][$plugin] = $definition['label'];
    }

    $plugin = $this->entity->get('plugin');
    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#default_value' => $plugin,
      '#options' => $options,
      '#empty_option' => $this->t('Select a plugin'),
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
      '#ajax' => [
        'callback' => '::updateOptionsSets',
        'wrapper' => 'field-display-options-sets-wrapper',
      ],
    ];

    if (!$this->entity->isNew() && !isset($pluginDefinitions[$plugin])) {
      $this->messenger()->addWarning($this->t('The plugin %name does not exist. You should delete the current instance or enable the module which manage this plugin.', [
        '%name' => $plugin,
      ]));
      $form['plugin']['#default_value'] = NULL;
      $form['plugin']['#required'] = FALSE;
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    if (empty($plugin)) {
      $plugin = NestedArray::getValue($form_state->getUserInput(), ['plugin']);
    }

    $optionsSets = [];
    foreach ($this->displayOptionsSetPluginManager->getDefinitions() as $id => $optionsSetDefinition) {
      if (isset($plugin) && !empty($optionsSetDefinition['applies_to'])) {
        $split = explode(':', $plugin, 2);

        if (!in_array($split[0], $optionsSetDefinition['applies_to'])) {
          continue;
        }
      }

      $optGroup = (string) $optionsSetDefinition['category'];
      if (!isset($optionsSets[$optGroup])) {
        $optionsSets[$optGroup] = [];
      }

      $optionsSets[$optGroup][$id] = $optionsSetDefinition['label'];
    }

    $form['optionsSets'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Options sets'),
      '#options' => $optionsSets,
      '#default_value' => $this->entity->get('optionsSets') ?: [],
      '#prefix' => '<div class="field-display-options-sets-wrapper">',
      '#suffix' => '</div>',
      '#size' => 10,
    ];

    return $form;
  }

  /**
   * Ajax callback to update the options of the "options sets" field .
   */
  public static function updateOptionsSets($form, FormStateInterface $formState) {
    return $form['optionsSets'];
  }

  /**
   * {@inheritDoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $optionsSets = &$form_state->getValue('optionsSets');
    $optionsSets = array_values($optionsSets);

    $entity = parent::buildEntity($form, $form_state);

    if ($pluginId = $form_state->getValue('plugin')) {
      $entity->set('id', strtr($pluginId, [':' => '__']));
    }

    return $entity;
  }

  /**
   * {@inheritDoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new display options instance %label.', $message_args)
      : $this->t('Updated display options instance %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
