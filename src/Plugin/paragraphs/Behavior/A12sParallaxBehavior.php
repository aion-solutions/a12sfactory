<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;

/**
 * A12s behavior plugin for paragraph entities.
 *
 * @ParagraphsBehavior(
 *   id = "a12sfactory_paragraph_parallax",
 *   label = @Translation("Parallax"),
 *   description = @Translation("Provides option for parallax effects."),
 *   weight = 10,
 * )
 */
class A12sParallaxBehavior extends ParagraphsBehaviorBase {

  use A12sBehaviorTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return !in_array($paragraphs_type->id(), ['card', 'cards', 'card_list', 'card_body']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'factor' => [
        'default' => 0,
        'breakpoints' => array_fill_keys(array_keys($this->getBreakpoints()), ['enabled' => FALSE, 'value' => 0]),
      ],
      'type' => "background",
      'direction' => 'vertical',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default type'),
      '#options' => [
        'background' => $this->t('Background'),
        'foreground' => $this->t('Foreground'),
      ],
      '#default_value' => $config['type'] ?? 'background',
    ];

    $form['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Default direction'),
      '#options' => [
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ],
      '#default_value' => $config['direction'] ?? 'vertical',
    ];

    $form['factor'] = [
      '#type' => 'item',
      '#title' => $this->t('Default parallax factor'),
    ];

    $form['factor']['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t("Sets speed and distance of element's parallax effect on scroll. Value can be positive (0.3) or negative (-0.3). Less means slower. Different sign (+/-) means different direction (up/down, left/right)."),
      '#suffix' => '<div class="messages messages--warning">' . $this->t('Since factor is multiplier it must be set for paroller.js to have parallax effect.') . '</div>',
    ];

    $form['factor']['default'] = [
      '#title' => $this->t('Default value'),
      '#description' => $this->t('This value may be overridden for specific screen resolutions, if specified below.'),
      '#default_value' => $config['factor']['default'] ?? 0,
      '#prefix' => '<div class="container-inline clearfix">',
      '#suffix' => '</div>',
    ] + $this->factorElementBase();

    $form['factor']['breakpoints'] = [
      '#type' => 'details',
      '#title' => $this->t('Breakpoints'),
    ];

    $breakpoints_enabled = FALSE;

    foreach ($this->getBreakpoints() as $key => $breakpoint_title) {
      if (!empty($config['factor']['breakpoints'][$key]['enabled'])) {
        $breakpoints_enabled = TRUE;
      }

      $form['factor']['breakpoints'][$key] = [
        '#type' => 'item',
        '#title' => $breakpoint_title,
        '#title_display' => 'invisible',
      ];

      $form['factor']['breakpoints'][$key]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $breakpoint_title,
        '#default_value' => $config['factor']['breakpoints'][$key]['enabled'] ?? 0,
      ];

      $form['factor']['breakpoints'][$key]['value'] = [
        '#title' => $this->t('Default value'),
        '#default_value' => $config['factor']['breakpoints'][$key]['value'] ?? 0,
        '#field_prefix' => '<div class="indentation"></div>',
        '#prefix' => '<div class="container-inline clearfix">',
        '#suffix' => '</div>',
        '#states' => [
          'visible' => [
            ':input[name="behavior_plugins[' . $this->getPluginId() . '][settings][factor][breakpoints][' . $key . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ] + $this->factorElementBase();
    }

    if ($breakpoints_enabled) {
      $form['factor']['breakpoints']['#open'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['factor'] = $form_state->getValue('factor');
    $configuration['type'] = $form_state->getValue('type');
    $configuration['direction'] = $form_state->getValue('direction');
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    if (!$paragraph->getBehaviorSetting($this->getPluginId(), ['settings', 'enabled'], 0)) {
      return;
    }

    $build['#attached']['library'][] = 'a12sfactory/parallax';
    $build['#attributes']['class'][] = 'a12sfactory-paragraph-parallax';

    $config = $this->getConfiguration();
    $data_prefix = 'data-paroller-';

    $factor_settings = $paragraph->getBehaviorSetting($this->getPluginId(), ['settings', 'factor'], 0);
    $factor = 0;

    if ($factor_settings['option'] === 'default') {
      $factor = $config['factor']['default'] ?? 0;
    }
    elseif ($factor_settings['option'] === 'custom') {
      $factor = $factor_settings['custom'];
    }

    $build['#attributes'][$data_prefix . 'factor'] = $factor;

    foreach (['type', 'direction'] as $key) {
      $value = $paragraph->getBehaviorSetting($this->getPluginId(), ['settings', $key]);
      if ($value === 'default') {
        $value = $config[$key] ?? NULL;
      }

      if ($value) {
        $build['#attributes'][$data_prefix . $key] = $value;
      }
    }

    // @todo handle breakpoints
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $this->initBehaviorForm($form, $this->getPluginDefinition());

    /*$form['a12s_behaviors']['#states'] = [
      'visible' => [
        ':input[name="' . $form['#base_group'] . '[settings][enabled]"]' => ['checked' => TRUE],
      ],
    ];*/

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#group' => $form['#tab_group'],
    ];

    $form['breakpoints'] = [
      '#type' => 'details',
      '#title' => $this->t('Breakpoints'),
      '#group' => $form['#tab_group'],
    ];

    $form['settings']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable parallax'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['settings', 'enabled'], 0),
    ];

    $form['settings']['factor'] = [
      '#type' => 'select_default_custom',
      '#title' => $this->t('Parallax factor'),
      '#description' => $this->t("Sets speed and distance of element's parallax effect on scroll. Value can be positive (0.3) or negative (-0.3). Less means slower. Different sign (+/-) means different direction (up/down, left/right)."),
      '#field_suffix' => '<div class="messages messages--warning">' . $this->t('Since factor is multiplier it must be set for paroller.js to have parallax effect.') . '</div>',
      '#custom_element' => $this->factorElementBase(),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['settings', 'factor']),
      '#empty_option' => $this->t('Use default value'),
      '#empty_value' => 'default',
    ];

    $form['settings']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'background' => $this->t('Background'),
        'foreground' => $this->t('Foreground'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['settings', 'type']),
      '#empty_option' => $this->t('Use default value'),
      '#empty_value' => 'default',
    ];

    $form['settings']['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Direction'),
      '#options' => [
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['settings', 'direction']),
      '#empty_option' => $this->t('Use default value'),
      '#empty_value' => 'default',
    ];

    foreach ($this->getBreakpoints() as $key => $breakpoint_title) {
      $form['breakpoints'][$key] = [
        '#type' => 'item',
        '#title' => $breakpoint_title,
        '#field_prefix' => '<div class="container-inline clearfix">',
        '#field_suffix' => '</div>',
      ];

      $form['breakpoints'][$key]['default'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use default value'),
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['breakpoints', $key,  'default'], 1),
      ];

      $form['breakpoints'][$key]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['breakpoints', $key,  'enabled'], 0),
        '#states' => [
          'visible' => [
            ':input[name="' . $form['#base_group'] . '[breakpoints][' . $key . '][default]"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['breakpoints'][$key]['value'] = [
          '#title' => $this->t('Value'),
          '#description' => $this->t("Sets speed and distance of element's parallax effect on scroll. Value can be positive (0.3) or negative (-0.3). Less means slower. Different sign (+/-) means different direction (up/down, left/right)."),
          '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['breakpoints', $key,  'value'], 0),
          '#states' => [
            'visible' => [
              ':input[name="' . $form['#base_group'] . '[breakpoints][' . $key . '][default]"]' => ['checked' => FALSE],
              ':input[name="' . $form['#base_group'] . '[breakpoints][' . $key . '][enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ] + $this->factorElementBase();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [$this->t('Parallax')];
  }

  /**
   * Get the default properties of a "factor" form element (number).
   *
   * @return array
   */
  protected function factorElementBase() {
    return  [
      '#type' => 'number',
      '#size' => 8,
      '#maxlength' => 8,
      '#step' => 0.1,
      '#min' => -9999,
      '#max' => 99999,
    ];
  }

  /**
   * Get the breakpoint definitions.
   *
   * @return array
   */
  protected function getBreakpoints() {
    return [
      'xs' => $this->t('@breakpoint <em class="description">@value</em>', [
        '@breakpoint' => $this->t('Extra small', [], ['context' => 'a12sfactory_breakpoint']),
        '@value' => '<576px']
      ),
      'sm' => $this->t('@breakpoint <em class="description">@value</em>', [
        '@breakpoint' => $this->t('Small', [], ['context' => 'a12sfactory_breakpoint']),
        '@value' => '<=768px']
      ),
      'md' => $this->t('@breakpoint <em class="description">@value</em>', [
        '@breakpoint' => $this->t('Medium', [], ['context' => 'a12sfactory_breakpoint']),
        '@value' => '<=1024px']
      ),
      'lg' => $this->t('@breakpoint <em class="description">@value</em>', [
        '@breakpoint' => $this->t('Large', [], ['context' => 'a12sfactory_breakpoint']),
        '@value' => '<=1200px']
      ),
      'xl' => $this->t('@breakpoint <em class="description">@value</em>', [
        '@breakpoint' => $this->t('Extra Large', [], ['context' => 'a12sfactory_breakpoint']),
        '@value' => '<=1920px']
      ),
    ];
  }

}
