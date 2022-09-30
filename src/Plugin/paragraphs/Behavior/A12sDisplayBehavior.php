<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\a12sfactory\Form\A12sDisplayBehaviorFormTrait;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A12s behavior plugin for paragraph entities.
 *
 * @ParagraphsBehavior(
 *   id = "a12sfactory_paragraph_display",
 *   label = @Translation("Advanced display"),
 *   description = @Translation("Provides advanced features for paragraphs display."),
 *   weight = 0,
 * )
 */
class A12sDisplayBehavior extends ParagraphsBehaviorBase {

  use A12sDisplayBehaviorFormTrait;
  use A12sBehaviorTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $file_usage;

  /**
   * Constructs a ParagraphsBehaviorBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, FileUsageInterface $file_usage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->file_usage = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('file.usage')
    );
  }

  protected function getDisplayFeatures() {
    return [
      'background_image' => $this->t('Background image'),
      'background_style' => $this->t('Background styles'),
      'attributes' => $this->t('Attributes'),
      'spacing' => $this->t('Spacing'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return !in_array($paragraphs_type->id(), ['card_list', 'card_body']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'features' => [
        'background_image' => 'background_image',
        'background_style' => 'background_style',
        'attributes' => 'attributes',
        'spacing' => 'spacing',
      ],
      'spacing' => [
        'margin' => 'margin',
        'padding' => 'padding',
      ],
      'attributes' => [
        'class' => 'class',
        'id' => 0,
      ],
      'background_image' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['features'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled features'),
      '#options' => $this->getDisplayFeatures(),
      '#default_value' => $config['features'],
    ];

    $form['spacing'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Spacing options'),
      '#options' => [
        'margin' => $this->t('Margin'),
        'padding' => $this->t('Padding'),
      ],
      '#default_value' => $config['spacing'],
      '#states' => [
        'visible' => [
          ':input[name="behavior_plugins[' . $this->getPluginId() . '][settings][features][spacing]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed attributes'),
      '#options' => [
        'class' => $this->t('Class'),
        'id' => $this->t('Id'),
        // @todo handle data
      ],
      '#default_value' => $config['attributes'],
      '#states' => [
        'visible' => [
          ':input[name="behavior_plugins[' . $this->getPluginId() . '][settings][features][attributes]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['background_image'] = [
      '#type' => 'details',
      '#title' => t('Background image settings'),
      '#states' => [
        'visible' => [
          ':input[name="behavior_plugins[' . $this->getPluginId() . '][settings][features][background_image]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $background_image_default = $this->getGlobalConfig('display.background_image') ?? [];
    $background_image_default = array_filter($background_image_default);

    // Remove the default values that may use the "global" option.
    // This takes sense for some properties like "background-size", so a global
    // change will also update all default properties that are set to "global".
    // But for some properties like the image file stream wrapper or validators,
    // a global change should not have an impact on the existing settings.
    $background_image_globals = ['background_size', 'background_position'];
    $background_image_default = array_diff_key($background_image_default, array_flip($background_image_globals));

    $background_image = $config['background_image'] + $background_image_default + $this->getBackgroundImageDefaultValues();
    $this->buildBackgroundImageSubform($form['background_image'], $background_image);

    $form['background_image']['background_size']['#empty_option'] = $this->t('Use global value');
    $form['background_image']['background_size']['#empty_value'] = 'global';
    $form['background_image']['background_position']['#empty_option'] = $this->t('Use global value');
    $form['background_image']['background_position']['#empty_value'] = 'global';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['features'] = $form_state->getValue('features');
    $configuration['background_image'] = $form_state->getValue('background_image');
    $configuration['attributes'] = $form_state->getValue('attributes');
    $configuration['spacing'] = $form_state->getValue('spacing');

    if (!array_filter($configuration['features'])) {
      $configuration['enabled'] = FALSE;
    }

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $background_settings = $paragraph->getBehaviorSetting($this->getPluginId(), 'background', []);
    $content_settings = $paragraph->getBehaviorSetting($this->getPluginId(), 'content', []);
    $attributes_settings = $paragraph->getBehaviorSetting($this->getPluginId(), 'attributes', []);

    if (!empty($background_settings['style'])) {
      $build['#attributes']['class'][] = $background_settings['style'];
    }

    if ($background_image = $this->getBackgroundImageFile($paragraph)) {
      $file_uri = $background_image->getFileUri();
      $style = ImageStyle::load('de2e');
      $file_path = $style->buildUrl($file_uri);

      $style = $build['#attributes']['style'] ?? [];
      $style[] = "background-image:url($file_path);";

      // @todo use responsive background handler.
      $repeat = !empty($background_settings['background_repeat']) ? 'repeat' : 'no-repeat';
      $style[] = "background-repeat:$repeat;";
      $style = array_merge($style, $this->getBackgroundSizeAndPosition($background_settings));
      $build['#attributes']['style'] = $style;
    }

    foreach (['margin', 'padding'] as $spacing) {
      foreach (['top', 'bottom'] as $position) {
        if (!empty($content_settings[$spacing][$position])) {
          $build['#attributes']['class'][] = $content_settings[$spacing][$position];
        }
      }
    }

    if (!empty($attributes_settings['class'])) {
      foreach (preg_split('/\s+/', $attributes_settings['class']) as $class) {
        $build['#attributes']['class'][] = $class;
      }
    }

    if (!empty($attributes_settings['id'])) {
      $build['#attributes']['id'] = $attributes_settings['id'];
    }
  }

  /**
   * Build the background-size and background-position properties from the
   * provided settings.
   *
   * @param array $settings
   * @param string $default
   *   By default, is uses the global value. If the property should be skipped,
   *   this variable may e set to NULL.
   *
   * @return array
   */
  public function getBackgroundSizeAndPosition(array $settings = [], $default = 'global'): array {
    $config = $this->getConfiguration();
    $style = [];

    foreach (['background_size', 'background_position'] as $css_property) {
      $css_property_name = strtr($css_property, '_', '-');
      $option = $default;

      if (!empty($settings[$css_property]['option'])) {
        $option = $settings[$css_property]['option'];
        $custom_value = $settings[$css_property]['custom'] ?? '';

        if ($settings[$css_property]['option'] === 'default') {
          $option = $config['background_image'][$css_property]['option'] ?? NULL;
          $custom_value = $config['background_image'][$css_property]['custom'] ?? NULL;

          if ($option === 'global') {
            $option = $this->getGlobalConfig('display.background_image.' . $css_property . '.option');
            $custom_value = $this->getGlobalConfig('display.background_image.' . $css_property . '.custom');
          }
        }
      }

      switch ($option) {
        case NULL:
          // Ignore...
          break;
        case 'custom':
          if (!empty($custom_value)) {
            $style[] = $css_property_name . ':' . $custom_value . ';';
          }
          break;

        default:
          $style[] = $css_property_name . ':' . $option . ';';
      }
    }

    return $style;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $this->initBehaviorForm($form, $this->getPluginDefinition());

    $background_settings = $paragraph->getBehaviorSetting($this->getPluginId(), 'background', []);
    $content_settings = $paragraph->getBehaviorSetting($this->getPluginId(), 'content', []);
    $attributes_settings = $paragraph->getBehaviorSetting($this->getPluginId(), 'attributes', []);

    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Content settings'),
      '#group' => $form['#tab_group'],
    ];

    $form['background'] = [
      '#type' => 'details',
      '#title' => $this->t('Background settings'),
      '#group' => $form['#tab_group'],
    ];

    $form['attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Attributes'),
      '#group' => $form['#tab_group'],
    ];

    if (!empty($config['features']['background_image'])) {
      // Ensure the image still exists.
      $background_image_file = $this->getBackgroundImageFile($paragraph);

      $max_filesize = min(Bytes::toInt($config['background_image']['max_size']), Environment::getUploadMaxSize());
      $max_dimensions = 0;
      if (!empty($config['background_image']['max_dimensions']['width']) || !empty($config['background_image']['max_dimensions']['height'])) {
        $max_dimensions = $config['background_image']['max_dimensions']['width'] . 'x' . $config['background_image']['max_dimensions']['height'];
      }

      $form['background']['image'] = [
        '#type' => 'managed_file',
        '#title' => t('Background image'),
        '#default_value' => !empty($background_image_file) ? [$background_image_file->id()] : NULL,
        '#upload_location' => $config['background_image']['scheme'] . '://' . $config['background_image']['directory'],
        '#upload_validators' => [
          'file_validate_extensions' => ['gif png jpg jpeg'],
          'file_validate_size' => [$max_filesize],
          'file_validate_image_resolution' => [$max_dimensions],
        ],
      ];

      $form['background']['background_size'] = [
        '#type' => 'css_background_size',
        '#title' => t('Background size'),
        '#default_value' => $background_settings['background_size'] ?? NULL,
        '#empty_option' => $this->t('Use default value'),
        '#empty_value' => 'default',
      ];

      $form['background']['background_position'] = [
        '#type' => 'css_background_position',
        '#title' => t('Background position'),
        '#default_value' => $background_settings['background_position'] ?? NULL,
        '#empty_option' => $this->t('Use default value'),
        '#empty_value' => 'default',
      ];

      $form['background']['background_repeat'] = [
        '#type' => 'checkbox',
        '#title' => t('Background repeat'),
        '#default_value' => $background_settings['background_repeat'] ?? 0,
      ];
    }

    if (!empty($config['features']['background_style'])) {
      $form['background']['style'] = $this->backgroundStyleElement(NULL, $background_settings['style'] ?? NULL);
    }

    if (!empty($config['features']['spacing'])) {
      $margin_top = $this->getGlobalConfig('display.spacing.margin_top');
      $margin_bottom = $this->getGlobalConfig('display.spacing.margin_bottom');
      $padding_top = $this->getGlobalConfig('display.spacing.padding_top');
      $padding_bottom = $this->getGlobalConfig('display.spacing.padding_bottom');

      $spacing_base = [
        '#type' => 'select',
        '#empty_option' => $this->t('Default'),
      ];

      if (!empty($config['spacing']['margin']) && ($margin_top || $margin_bottom)) {
        $form['content']['margin'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['container-inline'],
          ],
        ];

        if ($margin_top) {
          $form['content']['margin']['top'] = [
            '#title' => $this->t('Margin top'),
            '#options' => $margin_top,
            '#default_value' => $content_settings['margin']['top'] ?? NULL,
          ] + $spacing_base;
        }

        if ($margin_bottom) {
          $form['content']['margin']['bottom'] = [
            '#title' => $this->t('Margin bottom'),
            '#options' => $margin_bottom,
            '#default_value' => $content_settings['margin']['bottom'] ?? NULL,
          ] + $spacing_base;
        }
      }

      if (!empty($config['spacing']['padding']) && ($padding_top || $padding_bottom)) {
        $form['content']['padding'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['container-inline'],
          ],
        ];

        if ($padding_top) {
          $form['content']['padding']['top'] = [
            '#title' => $this->t('Padding top'),
            '#options' => $padding_top,
            '#default_value' => $content_settings['padding']['top'] ?? NULL,
          ] + $spacing_base;
        }

        if ($padding_bottom) {
          $form['content']['padding']['bottom'] = [
            '#title' => $this->t('Padding bottom'),
            '#options' => $padding_bottom,
            '#default_value' => $content_settings['padding']['bottom'] ?? NULL,
          ] + $spacing_base;
        }
      }
    }

    if (!empty($config['features']['attributes'])) {
      if (!empty($config['attributes']['class'])) {
        $form['attributes']['class'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Extra classes'),
          '#default_value' => $attributes_settings['class'] ?? '',
        ];
      }

      if (!empty($config['attributes']['id'])) {
        $form['attributes']['id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Identifier'),
          '#description' => $this->t('Use with caution: a DOM ID should be unique on a page.'),
          '#default_value' => $attributes_settings['id'] ?? '',
        ];
      }
    }

    return $this->filterEmptyDetailsElements($form);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [$this->t('Display features')];
  }

  /**
   * Get the background file of a paragraph, if any.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *
   * @return \Drupal\file\Entity\File|null
   */
  public function getBackgroundImageFile(ParagraphInterface $paragraph) {
    $background_image_id = $paragraph->getBehaviorSetting($this->getPluginId(), ['background', 'image', 0]);

    if ($background_image_id) {
      try {
        return $this->entityTypeManager->getStorage('file')->load($background_image_id);
      }
      catch (\Exception $e) {
        watchdog_exception('a12sfactory', $e);
      }
    }

    return NULL;
  }

  /**
   * Add file usage of files referenced by background images.
   *
   * Every referenced file that does not yet have the FILE_STATUS_PERMANENT
   * state, will be given that state.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to inspect for file references on behavior plugins.
   */
  public function addFileUsage(ParagraphInterface $paragraph) {
    if ($file = $this->getBackgroundImageFile($paragraph)) {
      if ($file->get('status')->value !== FILE_STATUS_PERMANENT) {
        $file->set('status', FILE_STATUS_PERMANENT);

        try {
          $file->save();
        }
        catch (EntityStorageException $e) {
          watchdog_exception('a12sfactory', $e);
        }
      }
      $this->file_usage->add($file, 'a12sfactory', $paragraph->getEntityTypeId(), $paragraph->id());
    }
  }

  /**
   * Merge file usage of files referenced by background images.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to inspect for file references on behavior plugins.
   */
  public function mergeFileUsage(ParagraphInterface $paragraph) {
    if (!empty($paragraph->original)) {
      // On new revisions, all files are considered to be a new usage and no
      // deletion of previous file usages are necessary.
      if ($paragraph->getRevisionId() != $paragraph->original->getRevisionId()) {
        $this->addFileUsage($paragraph);
      }
      // On modified revisions, detect which file references have been added (and
      // record their usage) and which ones have been removed (delete their usage).
      // File references that existed both in the previous version of the revision
      // and in the new one don't need their usage to be updated.
      else {
        $original_file = $this->getBackgroundImageFile($paragraph->original);
        $file = $this->getBackgroundImageFile($paragraph);

        if ($original_file && (empty($file) || $original_file->uuid() != $file->uuid())) {
          $this->deleteFileUsage($paragraph->original);
        }

        if ($file && (empty($original_file) || $original_file->uuid() != $file->uuid())) {
          $this->addFileUsage($paragraph);
        }
      }
    }
    else {
      $this->addFileUsage($paragraph);
    }
  }

  /**
   * Deletes file usage of files referenced by background images.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to inspect for file references on behavior plugins.
   * @param  int  $count
   *   The number of references to delete. Should be 1 when deleting a single
   *   revision and 0 when deleting an entity entirely.
   *
   * @see \Drupal\file\FileUsage\FileUsageInterface::delete()
   */
  public function deleteFileUsage(ParagraphInterface $paragraph, int $count = 1) {
    if ($file = $this->getBackgroundImageFile($paragraph)) {
      $this->file_usage->delete($file, 'a12sfactory', $paragraph->getEntityTypeId(), $paragraph->id(), $count);
    }
  }

}
