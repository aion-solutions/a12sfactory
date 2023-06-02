<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;

/**
 * Plugin implementation of Display Options Set for Grid Layout.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "grid_layout",
 *   label = @Translation("Grid layout"),
 *   description = @Translation("Allow to define the grid layout settings."),
 *   category = @Translation("Grid settings"),
 *   applies_to = {"layout"},
 *   target_template = "layout"
 * )
 */
class GridLayout extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'column_breakpoint' => '',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    parent::preprocessVariables($variables, $configuration);

    // @todo manage column_breakpoint
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    $form['column_breakpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Column breakpoint'),
      '#description' => $this->t(''),
      '#empty_option' => $this->t('- Use default value -'),
      '#default_value' => $values['column_breakpoint'] ?? '',
      '#options' => [
        '_all' => $this->t('Break all'),
        'xs' => $this->t('XS'),
        'sm' => $this->t('SM'),
        'md' => $this->t('MD'),
        'lg' => $this->t('LG'),
      ],
    ];

    return $form;
  }

}
