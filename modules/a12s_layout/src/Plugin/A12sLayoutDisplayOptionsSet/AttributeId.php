<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;

/**
 * Plugin implementation of Display Options Set for attribute id.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "attribute_id",
 *   label = @Translation("ID"),
 *   description = @Translation("Allow to define the element ID."),
 *   category = @Translation("Attributes"),
 *   applies_to = {"layout", "paragraph"},
 *   target_template = "paragraph"
 * )
 */
class AttributeId extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'id' => '',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    if (!empty($configuration['id']) && empty($variables['attributes']['id'])) {
      $variables['attributes']['id'] = $configuration['id'];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    $form['#type'] = 'container';

    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element ID'),
      '#description' => $this->t('Enter the element unique identifier.'),
      '#default_value' => $values['id'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
//  public function validateForm(array &$form, FormStateInterface $formState): void {
//    // @todo validate using regex ? Clean-up the values (trim).
//
//    parent::validateGlobalSettingsForm($form, $formState);
//  }

}
