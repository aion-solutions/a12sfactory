<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;
use Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface;

/**
 * Plugin implementation of Display Options Set for grid layout classes.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "grid_attribute_class",
 *   label = @Translation("Grid classes"),
 *   description = @Translation("Allow to define extra classes to the grid layout."),
 *   category = @Translation("Grid settings"),
 *   applies_to = {"layout"},
 *   target_template = "layout"
 * )
 */
class GridAttributeClass extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'grid_classes' => '',
      'region_classes' => '',
      'region_classes_override' => FALSE,
      'region_classes_regions' => [],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    parent::preprocessVariables($variables, $configuration);

    if (!empty($configuration['grid_classes'])) {
      $this->addClasses($variables['attributes'], $configuration['grid_classes']);
    }

    if (!empty($configuration['region_classes'])) {
      foreach ($variables['region_attributes'] ?? [] as $regionAttributes) {
        $this->addClasses($regionAttributes, $configuration['region_classes']);
      }
    }

    if (!empty($configuration['region_classes_override'])) {
      foreach ($variables['region_attributes'] ?? [] as $regionId => $regionAttributes) {
        if (!empty($configuration['region_classes_regions'][$regionId])) {
          $this->addClasses($regionAttributes, $configuration['region_classes_regions'][$regionId]);
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    $form['grid_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grid classes'),
      '#description' => $this->t('Enter a list of classes, separated by a space.'),
      '#default_value' => $values['grid_classes'] ?? '',
    ];

    $form['region_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Region classes'),
      '#description' => $this->t('Enter a list of classes, separated by a space. All classes are applied to all regions.'),
      '#default_value' => $values['region_classes'] ?? '',
    ];

    $template = $this->configuration['template'] ?? NULL;

    if ($template instanceof DisplayTemplatePluginInterface) {
      $layout = $template->getTemplateObject();

      if ($layout instanceof LayoutInterface) {
        $regions = $layout->getPluginDefinition()->getRegions();

        if (count($regions) > 1) {
          $form['region_classes_override'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Add classes to specific regions'),
            '#default_value' => $values['region_classes_override'] ?? FALSE,
          ];

          foreach ($regions as $regionId => $region) {
            $inputParents = array_merge($parents, ['region_classes_override']);
            $inputNameRoot = array_shift($inputParents);
            $inputName = $inputNameRoot . ($inputParents ? '[' . implode('][', $inputParents) . ']' : '');

            $form['region_classes_regions'][$regionId] = [
              '#type' => 'textfield',
              '#title' => $this->t('Region classes for region %name', ['%name' => $region['label']]),
              '#empty_option' => $this->t('- Ignore -'),
              '#default_value' => $values['region_classes_regions'][$regionId] ?? NULL,
              '#states' => [
                'visible' => [
                  ':input[name="' . $inputName . '"]' => ['checked' => TRUE],
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
