<?php

namespace Drupal\a12s_layout\DisplayOptions;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for Display Options Set plugins.
 */
interface DisplayOptionsSetInterface extends ContainerFactoryPluginInterface{

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Get the default values.
   *
   * @return array
   */
  public function defaultValues(): array;

  /**
   * Merge the given configuration with the default values.
   *
   * @param array $config
   *   The stored configuration.
   *
   * @return array
   *   The merged values.
   */
  public function mergeConfigWithDefaults(array $config = []): array;

  /**
   * Whether the plugin applies to the given template.
   *
   * @param string $name
   *   The template/theme name.
   *
   * @return bool
   */
  public function appliesToTemplate(string $name): bool;

  /**
   * Preprocess the template variables.
   *
   * @param array $variables
   * @param array $configuration
   *
   * @return void
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void;

  /**
   * Build the global settings form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $config
   *   The stored configuration.
   */
  public function globalSettingsForm(array &$form, FormStateInterface $formState, array $config = []): void;

  /**
   * Global settings subform validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   */
  public function validateGlobalSettingsForm(array $form, FormStateInterface $formState);

  /**
   * Global settings subform submit handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   */
  public function submitGlobalSettingsForm(array $form, FormStateInterface $formState);

  /**
   * Build the widget form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $values
   *   The already stored values.
   * @param array $parents
   *   The path to the subform relative to the complete form.
   *
   * @return array
   *   The processed form.
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array;

  /**
   * Validate the widget form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface $formState): void;

  /**
   * Get the plugin machine name.
   *
   * This is required to sanitize the plugin ID, which may contain special
   * characters when using derivatives.
   * This name is used for data storage in configuration objects, or for
   * defining keys in forms.
   *
   * @return string
   */
  public function getMachineName(): string;

}
