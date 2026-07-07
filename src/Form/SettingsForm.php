<?php

namespace Drupal\a12sfactory\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected StateInterface $state
  ) {
    parent::__construct($config_factory,  $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   * @noinspection PhpParamsInspection
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'a12sfactory_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['a12sfactory.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function  buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('a12sfactory.settings');

    if ($this->state->get('a12sfactory.i18n.installed', FALSE)) {
      $form['recipe_i18n_install_optional_configuration'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Install optional configuration for i18n recipe'),
        '#description' => $this->t('When enabled, the system will try to alter the newly installed configuration from other recipes to handle i18n features.'),
        '#default_value' => $config->get('recipe_i18n_install_optional_configuration') ?? TRUE,
        '#config_target' => 'a12sfactory.settings:recipe_i18n_install_optional_configuration',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

}
