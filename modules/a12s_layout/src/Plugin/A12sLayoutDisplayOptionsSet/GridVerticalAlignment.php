<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;
use Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface;

/**
 * Plugin implementation of Display Options Set for grid vertical alignment.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "grid_vertical_alignment",
 *   label = @Translation("Grid vertical alignment"),
 *   description = @Translation("Allow to define vertical alignment of the grid layout."),
 *   category = @Translation("Grid settings"),
 *   applies_to = {"layout"},
 *   target_template = "layout"
 * )
 */
class GridVerticalAlignment extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'grid' => '',
      'regions_override' => FALSE,
      'regions' => [],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    parent::preprocessVariables($variables, $configuration);

    if (!empty($configuration['grid'])) {
      $this->addClasses($variables['attributes'], $configuration['grid']);
    }

    if (!empty($configuration['regions_override'])) {
      foreach ($variables['region_attributes'] ?? [] as $regionId => $regionAttributes) {
        if (!empty($configuration['regions'][$regionId])) {
          $this->addClasses($regionAttributes, $configuration['regions'][$regionId]);
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    // @todo make the class names configurable.
    // @todo allow to define the default value per DO instance.
    $verticalAlignmentOptions = [
      'align-items-start' => $this->t('Start'),
      'align-items-end' => $this->t('End'),
      'align-items-center' => $this->t('Center'),
      'align-items-baseline' => $this->t('Baseline'),
      'align-items-stretch' => $this->t('Stretch (browser default)'),
    ];

    $form['grid'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical alignment'),
      '#empty_option' => $this->t('- Use default value -'),
      '#default_value' => $values['grid'] ?? '',
      '#options' => $verticalAlignmentOptions,
    ];

    $template = $this->configuration['template'] ?? NULL;

    if ($template instanceof DisplayTemplatePluginInterface) {
      $layout = $template->getTemplateObject();

      if ($layout instanceof LayoutInterface) {
        $regions = $layout->getPluginDefinition()->getRegions();

        if (count($regions) > 1) {
          $form['regions_override'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Override alignment per region'),
            '#default_value' => $values['regions_override'] ?? FALSE,
          ];

          // @todo make the class names configurable.
          $verticalAlignmentOptions = [
            'align-self-start' => $this->t('Start'),
            'align-self-end' => $this->t('End'),
            'align-self-center' => $this->t('Center'),
            'align-self-baseline' => $this->t('Baseline'),
            'align-self-stretch' => $this->t('Stretch (browser default)'),
          ];

          foreach ($regions as $regionId => $region) {
            $form['regions'][$regionId] = [
              '#type' => 'select',
              '#title' => $this->t('Vertical alignment for region %name', ['%name' => $region['label']]),
              '#empty_option' => $this->t('- Ignore -'),
              '#default_value' => $values['regions'][$regionId] ?? NULL,
              '#options' => $verticalAlignmentOptions,
              '#states' => [
                'visible' => [
                  $this->getInputNameFromPath(':input', $parents, 'regions_override') => ['checked' => TRUE],
                ],
              ],
            ];
          }
        }
      }
    }

    return $form;
  }

}
