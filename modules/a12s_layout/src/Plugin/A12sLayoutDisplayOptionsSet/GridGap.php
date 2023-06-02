<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;

/**
 * Plugin implementation of Display Options Set for Grid settings.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "grid_gap",
 *   label = @Translation("Grid gap"),
 *   description = @Translation("@todo"),
 *   category = @Translation("Size and spacing"),
 *   applies_to = {"layout"},
 *   target_template = "layout"
 * )
 */
class GridGap extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'gap' => '',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    parent::preprocessVariables($variables, $configuration);

    if (!empty($configuration['gap'])) {
      $this->addClasses($variables['attributes'], $configuration['gap']);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    $form['gap'] = [
      '#type' => 'select',
      '#title' => $this->t('Gap'),
      '#empty_option' => $this->t('- Default -'),
      '#default_value' => $values['gap'] ?? '',
      '#options' => [
        'row-gap-0' => $this->t('No gap'),
      ],
    ];

    return $form;
  }

}
