<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;

/**
 * Plugin implementation of Display Options Set for Spacing.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "spacing",
 *   label = @Translation("Spacing"),
 *   description = @Translation("Provides options for spacing."),
 *   category = @Translation("Size and spacing"),
 *   deriver = "Drupal\a12s_layout\Plugin\Derivative\Spacing",
 *   applies_to = {"layout", "paragraph"},
 *   target_template = "paragraph"
 * )
 */
class Spacing extends DisplayOptionsSetPluginBase {

  private const PROPERTY_TREE = [
    'horizontal' => [
      'key' => 'x',
      'values' => [
        'l' => 'left',
        'r' => 'right',
      ],
    ],
    'vertical' => [
      'key' => 'y',
      'values' => [
        't' => 'top',
        'b' => 'bottom',
      ],
    ],
  ];

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'levels' => '5',
      'enable_horizontal' => 1,
      'enable_vertical' => 1,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    $key = $this->getDerivativeId();

    foreach (self::PROPERTY_TREE as $axis => $item) {
      $name = $key . '_' . $axis;

      if (empty($configuration[$name . '_override']) && isset($configuration[$name])) {
        if (strlen($configuration[$name])) {
          $variables['attributes']['class'][] = $configuration[$name];
        }
      }
      else {
        foreach ($item['values'] as $property) {
          $name = $key . '_' . $property;

          if (isset($configuration[$axis][$name]) && strlen($configuration[$axis][$name])) {
            $variables['attributes']['class'][] = $configuration[$axis][$name];
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function globalSettingsForm(array &$form, FormStateInterface $formState, array $config = []): void {
    $default = $this->mergeConfigWithDefaults($config);
    $key = $this->getDerivativeId();
    $levels = range(1, 10);
    $form['levels'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum levels for the %name property', ['%name' => $key]),
      '#description' => $this->t('Each level is declined in a specific CSS class. Increasing the number of levels also increase the CSS file weight.'),
      '#options' => array_combine($levels, $levels),
      '#default_value' => $default['levels'],
    ];

    // @todo handle breakpoints.

    $form['enable_horizontal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable horizontal setting for the %name property', ['%name' => $key]),
      '#description' => $this->t('This means that a list of CSS classes handles both left and right spacing.'),
      '#default_value' => $default['enable_horizontal'],
    ];

    $form['enable_vertical'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable vertical setting for the %name property', ['%name' => $key]),
      '#description' => $this->t('This means that a list of CSS classes handles both top and bottom spacing.'),
      '#default_value' => $default['enable_vertical'],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    $key = $this->getDerivativeId();
    $form['#attributes']['class'][] = 'spacing-container';
    $form['#attached']['library'][] = 'a12s_layout/spacing-container';

    foreach (self::PROPERTY_TREE as $axis => $item) {
      $properties = $item['values'];
      $name = $key . '_' . $axis;

      $form[$axis] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['spacing-container-item']]
      ];

      if ($this->globalConfiguration['enable_' . $axis]) {
        $form[$axis][$name] = [
          '#type' => 'radios',
          '#title' => $this->getTranslation($name),
          '#options' => $this->getOptions($key, $item['key']),
          '#empty_option' => $this->t('- Default -'),
          '#default_value' => $values[$name] ?? '',
          '#parents' => array_merge($parents, [$name]),
          '#attributes' => ['class' => ['radios-range-slider']],
          '#attached' => [
            'library' => ['a12s_layout/radios-range-slider'],
          ],
        ];

        $name = $key . '_' . $axis . '_override';

        $form[$axis][$name] = [
          '#type' => 'checkbox',
          '#title' => $this->getTranslation($name),
          '#default_value' => $values[$name] ?? FALSE,
          '#parents' => array_merge($parents, [$name]),
        ];
      }

      foreach ($properties as $prefix => $property) {
        $name = $key . '_' . $property;

        $form[$axis][$name] = [
          '#type' => 'radios',
          '#title' => $this->getTranslation($name),
          '#options' => $this->getOptions($key, $prefix),
          '#default_value' => $values[$axis][$name] ?? '',
          '#attributes' => ['class' => ['radios-range-slider']],
          '#attached' => [
            'library' => ['a12s_layout/radios-range-slider'],
          ],
        ];

        if ($this->globalConfiguration['enable_' . $axis]) {
          $form[$axis][$name]['#states'] = [
            'visible' => [
              $this->getInputNameFromPath(':input', $parents, $key . '_' . $axis . '_override') => ['checked' => TRUE],
            ],
          ];
        }
      }
    }

    return $form;
  }

  /**
   * @param string $property
   * @param string $prefix
   *
   * @return array|string[]
   */
  protected function getOptions(string $property, string $prefix): array {
    $options = [
      '' => $this->t('Default'),
    ];

    if ($property === 'margin') {
      $options["{$property[0]}$prefix-auto"] = 'auto';
    }

    if (!empty($this->globalConfiguration['levels'])) {
      for ($i = 0; $i <= $this->globalConfiguration['levels']; $i++) {
        $options["{$property[0]}$prefix-$i"] = $i;
      }
    }

    return $options;
  }

  /**
   * Get the translation of the given property.
   *
   * @param string $property
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   */
  protected function getTranslation(string $property): ?TranslatableMarkup {
    $translations = [
      'margin' => $this->t('Margin'),
      'margin_horizontal' => $this->t('Horizontal margin'),
      'margin_vertical' => $this->t('Vertical margin'),
      'margin_horizontal_override' => $this->t('Specify different left and right margin'),
      'margin_vertical_override' => $this->t('Specify different top and bottom margin'),
      'margin_left' => $this->t('Left margin'),
      'margin_right' => $this->t('Right margin'),
      'margin_top' => $this->t('Top margin'),
      'margin_bottom' => $this->t('Bottom margin'),
      'padding' => $this->t('Padding'),
      'padding_horizontal' => $this->t('Horizontal padding'),
      'padding_vertical' => $this->t('Vertical padding'),
      'padding_horizontal_override' => $this->t('Specify different left and right padding'),
      'padding_vertical_override' => $this->t('Specify different top and bottom padding'),
      'padding_left' => $this->t('Left padding'),
      'padding_right' => $this->t('Right padding'),
      'padding_top' => $this->t('Top padding'),
      'padding_bottom' => $this->t('Bottom padding'),
    ];

    return $translations[$property] ?? NULL;
  }

}
