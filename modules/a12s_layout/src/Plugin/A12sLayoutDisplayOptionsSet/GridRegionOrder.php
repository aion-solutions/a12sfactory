<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;
use Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface;

/**
 * Plugin implementation of Display Options Set for grid region order.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "grid_region_order",
 *   label = @Translation("Grid region order"),
 *   description = @Translation("Allow re-ordering of the grid regions."),
 *   category = @Translation("Grid settings"),
 *   applies_to = {"layout"},
 *   target_template = "layout"
 * )
 */
class GridRegionOrder extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'regions' => [],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    parent::preprocessVariables($variables, $configuration);

    if (!empty($configuration['regions'])) {
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
    // @todo allow to define the breakpoints (with multiple value).
    $orderOptions = ['order-lg-first' => $this->t('Order first')];
    for ($i = 1; $i <= 12; $i++) {
      $orderOptions["order-lg-$i"] = $this->t('Order %i', ['%i' => $i]);
    }
    $orderOptions['order-lg-last'] = $this->t('Order last');

    $template = $this->configuration['template'] ?? NULL;

    if ($template instanceof DisplayTemplatePluginInterface) {
      $layout = $template->getTemplateObject();

      if ($layout instanceof LayoutInterface) {
        $regions = $layout->getPluginDefinition()->getRegions();

        if (count($regions) > 1) {
          foreach ($regions as $regionId => $region) {
            $form['regions'][$regionId] = [
              '#type' => 'select',
              '#title' => $this->t('Column order for region %name', ['%name' => $region['label']]),
              '#description' => $this->t(''),
              '#empty_option' => $this->t('- None -'),
              '#default_value' => $values['regions'][$regionId] ?? '',
              '#options' => $orderOptions
            ];
          }
        }
      }
    }

    return $form;
  }

}
