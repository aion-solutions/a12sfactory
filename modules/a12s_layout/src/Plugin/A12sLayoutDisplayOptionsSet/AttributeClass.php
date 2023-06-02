<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;

/**
 * Plugin implementation of Display Options Set for attribute classes.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "attribute_class",
 *   label = @Translation("Classes"),
 *   description = @Translation("Allow to define extra classes."),
 *   category = @Translation("Attributes"),
 *   applies_to = {"layout", "paragraph"},
 *   target_template = "paragraph"
 * )
 */
class AttributeClass extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'classes' => '',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    if (!empty($configuration['classes'])) {
      foreach (preg_split('/\s+/', $configuration['classes']) as $class) {
        if (!isset($variables['attributes']['class']) || !in_array($class, $variables['attributes']['class'])) {
          $variables['attributes']['class'][] = $class;
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    $form['#type'] = 'container';

    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra classes'),
      '#description' => $this->t('Enter a list of classes, separated by a space.'),
      '#default_value' => $values['classes'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
//  public function validateForm(array &$form, FormStateInterface $formState): void {
//    // @todo validate using regex ? Clean-up the values (trim + unique).
//    parent::validateForm($form, $formState);
//  }

}
