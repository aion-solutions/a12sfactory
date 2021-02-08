<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * A12s grid plugin for paragraph entities.
 *
 * @ParagraphsBehavior(
 *   id = "a12sfactory_paragraph_grid",
 *   label = @Translation("Grid"),
 *   description = @Translation("Allow the paragraphs to work as a grid row and/or column."),
 *   weight = 1,
 * )
 */
class A12sGridBehavior extends ParagraphsBehaviorBase {

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
      'is_row' => 0,
      'row_type' => '',
      'column_breakpoint' => 'lg',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['is_row'] = [
      '#title' => $this->t('The paragraph is a row containing columns'),
      '#type' => 'checkbox',
      '#default_value' => $config['is_row'],
    ];

    $form['row_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Grid layout'),
      '#options' => [
        'equal' => $this->t('Equal multiple columns'),
        'two_uneven' => $this->t('Two uneven columns'),
        'three_uneven' => $this->t('Three uneven columns'),
      ],
      '#default_value' => $config['row_type'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="behavior_plugins[' . $this->getPluginId() . '][settings][is_row]"]' => ['checked' => TRUE],
        ],
      ],
      '#empty_option' => $this->t('No layout'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['is_row'] = $form_state->getValue('is_row');
    $configuration['row_type'] = $form_state->getValue('row_type');
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $config = $this->getConfiguration();

    if ($config['is_row']) {
      $build['#theme'] = 'paragraph__grid_row';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {
    parent::preprocess($variables);

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];
    $config = $this->getConfiguration();

    if ($config['is_row']) {
      $variables['container_attributes'] = new Attribute();
      $variables['row_attributes'] = new Attribute();
      $rowSettings = $paragraph->getBehaviorSetting($this->getPluginId(), 'row', []);

      if (!empty($rowSettings['align_items'])) {
        $variables['row_attributes']->addClass($rowSettings['align_items']);
      }

      if (!empty($rowSettings['no_gutters'])) {
        $variables['row_attributes']->addClass('no-gutters');
      }

      $classMap = [
        'container_class' => &$variables['container_attributes'],
        'row_class' => &$variables['row_attributes'],
      ];

      foreach ($classMap as $key => &$attributes) {
        $value = $paragraph->getBehaviorSetting($this->getPluginId(), ['row', $key], '');

        if (!empty($value)) {
          foreach (preg_split('/\s+/', $value) as $class) {
            $attributes->addClass($class);
          }
        }
      }
    }

    $width = $paragraph->getBehaviorSetting($this->getPluginId(), ['column', 'width']);

    if ($width) {
      // @todo Make this configurable.
      $width_map = [
        'tiny' => 'col-md-4 offset-md-4 col-sm-8 offset-sm-2',
        'narrow' => 'col-md-6 offset-md-3 col-sm-10 offset-sm-1',
        'medium' => 'col-md-8 offset-md-2',
        'wide' => 'col-md-10 offset-md-1',
        //'full' => 'col-12',
        //'edge2edge' => 'col-12',
      ];

      $variables['column_attributes'] = new Attribute();
      $variables['column_attributes']->addClass($width_map[$width] ?? 'col-12');

      // When dealing with rows, we cannot apply the width (this uses col-* ) to
      // the paragraph columns and need some extra processing.
      // This only takes sense when the $width is defined in the map.
      if (!empty($config['is_row']) && !empty($width_map[$width])) {
        $variables['wrap_with_grid'] = TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $this->initBehaviorForm($form, $this->getPluginDefinition());

    $isRow = $config['is_row'];
    $isColumn = $this->paragraphIsColumn($paragraph);

    $form['column'] = [
      '#type' => 'details',
      '#title' => $this->t('Column settings'),
      '#group' => $form['#tab_group'],
    ];

    if ($isRow) {
      $form['row'] = [
        '#type' => 'details',
        '#title' => $this->t('Row settings'),
        '#group' => $form['#tab_group'],
        '#weight' => -10,
      ];

      if ($rowLayouts = $this->getRowTypeStyle($config['row_type'], 'label')) {
        $form['row']['row_layout'] = [
          '#type' => 'select',
          '#title' => $this->t('Row style'),
          '#options' => $rowLayouts,
          '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['row', 'row_layout'], key($rowLayouts)),
        ];
      }
      else {
        $form['row']['row_layout'] = [
          '#type' => 'value',
          '#value' => $config['row_type'] ?? '',
        ];
      }

      $form['row']['column_breakpoint'] = [
        '#type' => 'select',
        '#title' => $this->t('Column breakpoint'),
        '#options' => ['' => $this->t('None'), '_all' => $this->t('Break all')] + $this->getGlobalConfig('row.column_breakpoints') ?? [],
        // Use "lg" as default for backward compatibility.
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['row', 'column_breakpoint'], 'lg'),
      ];

      $form['row']['align_items'] = [
        '#type' => 'select',
        '#title' => $this->t('Vertical alignment'),
        '#options' => [
          'align-items-start' => $this->t('Start'),
          'align-items-end' => $this->t('End'),
          'align-items-center' => $this->t('Center'),
          'align-items-baseline' => $this->t('Baseline'),
          'align-items-stretch' => $this->t('Stretch (browser default)'),
        ],
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['row', 'align_items']),
        '#empty_value' => '',
      ];

      /*$form['row']['justify_content'] = [
        '#type' => 'select',
        '#title' => $this->t('Horizontal alignment'),
        '#options' => [
          'justify-content-start' => $this->t('Start (browser default)'),
          'justify-content-end' => $this->t('End'),
          'justify-content-center' => $this->t('Center'),
          'justify-content-between' => $this->t('Space between'),
          'justify-content-around' => $this->t('Space around'),
        ],
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['row', 'justify_content']),
        '#empty_value' => '',
      ];*/

      $form['row']['no_gutters'] = [
        '#title' => $this->t('Remove gutters'),
        '#type' => 'checkbox',
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['row', 'no_gutters'], 0),
        '#states' => [
          'invisible' => [
            'select[name="' . $form['#base_group'] . '[column][width]"]' => ['value' => 'edge2edge'],
          ],
        ],
      ];

      $form['row']['container_class'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Container classes'),
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['row', 'container_class'], ''),
      ];

      $form['row']['row_class'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Row classes'),
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['row', 'row_class'], ''),
      ];
    }

    $form['column']['width'] = [
      '#type' => 'select',
      '#title' => $this->t('Width'),
      // @todo add Global settings for this.
      '#options' => [
        'tiny' => $this->t('Tiny'),
        'narrow' => $this->t('Narrow'),
        'medium' => $this->t('Medium'),
        'wide' => $this->t('Wide'),
        'full' => $this->t('Full width'),
        'edge2edge' => $this->t('Edge to edge'),
      ],
      '#empty_option' => $this->t('Default'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['column', 'width']),
      '#wrapper_attributes' => ['class' => ['field--label-inline']],
      '#label_attributes' => ['class' => ['field__label']],
      '#attributes' => ['class' => ['field__item']],
      '#field_prefix' => '&nbsp;',
    ];

    // Applicable if column (may also be row when nested rows).
    if ($isColumn) {
      $form['column']['align_self'] = [
        '#type' => 'select',
        '#title' => $this->t('Vertical alignment'),
        '#options' => [
          'align-self-start' => $this->t('Start'),
          'align-self-end' => $this->t('End'),
          'align-self-center' => $this->t('Center'),
          'align-self-baseline' => $this->t('Baseline'),
          'align-self-stretch' => $this->t('Stretch (browser default)'),
        ],
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['column', 'align_self']),
        '#empty_value' => '',
      ];

      $form['column']['order'] = [
        '#type' => 'select',
        '#title' => $this->t('Column order'),
        '#options' => [
          'order-lg-first' => $this->t('Order first'),
          'order-lg-1' => $this->t('Order @count', ['@count' => 1]),
          'order-lg-2' => $this->t('Order @count', ['@count' => 2]),
          'order-lg-3' => $this->t('Order @count', ['@count' => 3]),
          'order-lg-4' => $this->t('Order @count', ['@count' => 4]),
          'order-lg-5' => $this->t('Order @count', ['@count' => 5]),
          'order-lg-6' => $this->t('Order @count', ['@count' => 6]),
          'order-lg-7' => $this->t('Order @count', ['@count' => 7]),
          'order-lg-8' => $this->t('Order @count', ['@count' => 8]),
          'order-lg-9' => $this->t('Order @count', ['@count' => 9]),
          'order-lg-10' => $this->t('Order @count', ['@count' => 10]),
          'order-lg-11' => $this->t('Order @count', ['@count' => 11]),
          'order-lg-12' => $this->t('Order @count', ['@count' => 12]),
          'order-lg-last' => $this->t('Order last'),
        ],
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['column', 'order']),
        '#empty_value' => '',
      ];

      $form['column']['class'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Column extra classes'),
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['column', 'class'], ''),
      ];

      if (in_array($paragraph->bundle(), ['image', 'video'], TRUE)) {
        $form['column']['cover'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Cover the full column'),
          '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['column', 'cover'], FALSE),
        ];
      }
    }

    return $this->filterEmptyDetailsElements($form);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [$this->t('Grid features')];
  }

  /**
   * Whether the parent of the given paragraph is a row.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *
   * @return bool
   */
  public function paragraphIsColumn(ParagraphInterface $paragraph) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $parent = $paragraph->getParentEntity();

    if ($parent && $parent->getEntityTypeId() === 'paragraph') {
      return a12sfactory_paragraph_is_row($parent);
    }

    return FALSE;
  }

  /**
   * Whether the parent of the given paragraph is a not another paragraph.
   *
   * Root paragraphs should have some extra options.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *
   * @return bool
   */
  public function paragraphIsRoot(ParagraphInterface $paragraph) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $parent = $paragraph->getParentEntity();
    return !($parent instanceof ParagraphInterface);
  }

  /**
   * Parse the "order" setting to get a value for calculation.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *
   * @return int|string
   */
  public function parseColumnOrder(ParagraphInterface $paragraph) {
    $orderSetting = $paragraph->getBehaviorSetting($this->getPluginId(), ['column', 'order']);
    $order = 0;

    if (!empty($orderSetting)) {
      $matches = [];

      if (preg_match('/^order-lg-(?<order>[\d]+)$/', $orderSetting, $matches)) {
        $order = (int) $matches['order'];
      }
      elseif (preg_match('/^order-lg-(?<order>\w+)$/', $orderSetting, $matches)) {
        $order = $matches['order'];
      }
    }

    return $order;
  }

  /**
   * Get the columns definition for the given paragraph, if applicable.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   * @param int $columnsCount
   *
   * @return array
   */
  public function getRowLayout(ParagraphInterface $paragraph, $columnsCount = 0): array {
    $config = $this->getConfiguration();
    $layoutColumns = [];

    // Use the breakpoint defined in the behavior. We set "lg" as default
    // for backward compatibility.
    $columnBreakpoint = $paragraph->getBehaviorSetting('a12sfactory_paragraph_grid', ['row', 'column_breakpoint'], 'lg');

    if ($config['is_row']) {
      $rowLayout = $paragraph->getBehaviorSetting('a12sfactory_paragraph_grid', ['row', 'row_layout'], '');
      $rowLayouts = $this->getRowTypeStyle($config['row_type'], 'columns', $columnBreakpoint);

      // The layout exists.
      if ($rowLayouts && isset($rowLayouts[$rowLayout])) {
        return $rowLayouts[$rowLayout];
      }
      // The layout is not defined, fallback to "equal".
      elseif ($config['row_type'] === 'equal' && $columnsCount) {
        $equalClasses = [];

        if (empty($columnBreakpoint)) {
          $equalClasses[] = 'col-12';
        }
        else {
          $size = ((12 % $columnsCount) == 0) ? '-' . (12 / $columnsCount) : '';

          if ($columnBreakpoint === '_all') {
            $equalClasses[] = 'col' . $size;
          }
          else {
            $equalClasses[] = 'col-12';
            $equalClasses[] = 'col-' . $columnBreakpoint . $size;
          }
        }

        $equalClassesString = implode(' ', $equalClasses);

        for ($i = 0; $i < $columnsCount; $i++) {
          $layoutColumns[] = $equalClassesString;
        }
      }
      else {
        for ($i = 0; $i < $columnsCount; $i++) {
          $layoutColumns[] = 'col-12';
        }
      }
    }

    return $layoutColumns;
  }

  /**
   * Get details about the given row type style.
   *
   * @param $row_type
   *   The row type.
   * @param string $key
   *   The expected property. May be one of:
   *   - label
   *   - columns
   *   If not specified, the full style definitions is returned.
   * @param string $columnBreakpoint
   *   The desired breakpoint.
   *
   * @return array
   */
  protected function getRowTypeStyle($row_type, $key = NULL, string $columnBreakpoint = 'lg'): array {
    $rowStyles = $this->getRowTypeStyles($columnBreakpoint);

    if (isset($rowStyles[$row_type])) {
      if (!isset($key)) {
        return $rowStyles[$row_type];
      }

      return array_map(function($layout) use ($key) { return $layout[$key] ?? NULL; }, $rowStyles[$row_type]);
    }

    return [];
  }

  /**
   * Get the style definitions for complex row types.
   *
   * @param string $columnBreakpoint
   *   The desired breakpoint.
   *
   * @return array
   */
  protected function getRowTypeStyles(string $columnBreakpoint = 'lg'): array {
    return [
      'two_uneven' => [
        '75-25' => [
          'label' => '3/4 - 1/4',
          'columns' => ["col-12 col-$columnBreakpoint-9", "col-12 col-$columnBreakpoint-3"],
        ],
        '66-33' => [
          'label' => '2/3 - 1/3',
          'columns' => ["col-12 col-$columnBreakpoint-8", "col-12 col-$columnBreakpoint-4"],
        ],
        '25-75' => [
          'label' => '1/4 - 3/4',
          'columns' => ["col-12 col-$columnBreakpoint-3", "col-12 col-$columnBreakpoint-9"],
        ],
        '33-66' => [
          'label' => '1/3 - 2/3',
          'columns' => ["col-12 col-$columnBreakpoint-4", "col-12 col-$columnBreakpoint-8"],
        ],
      ],
      'three_uneven' => [
        '25-50-25' => [
          'label' => '1/4 - 1/2 - 1/4',
          'columns' => ["col-6 col-$columnBreakpoint-3 order-2 order-$columnBreakpoint-0", "col-12 col-$columnBreakpoint-6 order-1", "col-6 col-$columnBreakpoint-3 order-3"],
        ],
        '50-25-25' => [
          'label' => '1/2 - 1/4 - 1/4',
          'columns' => ["col-12 col-$columnBreakpoint-6", "col-6 col-$columnBreakpoint-3", "col-6 col-$columnBreakpoint-3"],
        ],
        '25-25-50' => [
          'label' => '1/4 - 1/4 - 1/2',
          'columns' => ["col-6 col-$columnBreakpoint-3", "col-6 col-$columnBreakpoint-3", "col-12 col-$columnBreakpoint-6"],
        ],
        '16-66-16' => [
          'label' => '1/6 - 2/3 - 1/6',
          'columns' => ["col-6 col-$columnBreakpoint-2 order-2 order-$columnBreakpoint-0", "col-12 col-$columnBreakpoint-8 order-1", "col-6 col-$columnBreakpoint-2 order-3"],
        ],
        '66-16-16' => [
          'label' => '2/3 - 1/6 - 1/6',
          'columns' => ["col-12 col-$columnBreakpoint-8", "col-6 col-$columnBreakpoint-2", "col-6 col-$columnBreakpoint-2"],
        ],
        '16-16-66' => [
          'label' => '1/6 - 1/6 - 2/3',
          'columns' => ["col-6 col-$columnBreakpoint-2", "col-6 col-$columnBreakpoint-2", "col-12 col-$columnBreakpoint-8"],
        ],
      ],
    ];
  }

}
