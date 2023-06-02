<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\responsive_image\ResponsiveImageStyleInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of Display Options Set for Background Image.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "background_image",
 *   label = @Translation("Background image"),
 *   description = @Translation("Provides options for background image."),
 *   category = @Translation("Background"),
 *   applies_to = {"layout", "paragraph"},
 *   target_template = "paragraph"
 * )
 *
 * @todo Manage file usage.
 */
class BackgroundImage extends DisplayOptionsSetPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $fileStorage
   *   The file storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsiveImageStyleStorage
   *   The responsive image style storage.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\breakpoint\BreakpointManagerInterface $breakpoint_manager
   *   The breakpoint manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $configFactory,
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected EntityStorageInterface $fileStorage,
    protected EntityStorageInterface $responsiveImageStyleStorage,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected BreakpointManagerInterface $breakpointManager,
    protected ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $configFactory);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): DisplayOptionsSetInterface {
    $entityTypeManager = $container->get('entity_type.manager');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $entityTypeManager->getStorage('file'),
      $entityTypeManager->getStorage('responsive_image_style'),
      $container->get('file_url_generator'),
      $container->get('breakpoint.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'background_size' => '',
      'background_position' => '',
      'responsive_image_style' => '',
      'scheme' => \Drupal::config('system.file')->get('default_scheme'),
      'directory' => 'background-images',
      'max_size' => '',
      'max_dimensions' => ['width' => '', 'height' => ''],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    parent::preprocessVariables($variables, $configuration);

    if (!empty($configuration['background_image'])) {
      $responsiveImageStyleId = $configuration['responsive_image_style'] ?? $this->globalConfiguration['responsive_image_style'] ?? NULL;

      if ($responsiveImageStyleId) {
        $fid = reset($configuration['background_image']);

        try {
          $file = $this->fileStorage->load($fid);

          if ($file instanceof FileInterface) {
            if (isset($variables['attributes']['style'])) {
              unset($variables['attributes']['style']);
            }

            $hash = sha1($fid . '-' . $responsiveImageStyleId);
            $cssSelector = 'background-image-' . $hash;
            $style = $this->buildBackgroundImageCss('html.loaded .' . $cssSelector . ':before', $file, $responsiveImageStyleId, $configuration);

            if ($style) {
              $variables['attributes']['class'][] = $cssSelector;
              $variables['attributes']['class'][] = 'background-image';
              $variables['content']['#attached']['html_head'][] = [
                ['#tag' => 'style', '#value' => $style],
                'display-options--background-image--' . $hash,
              ];
            }
          }
        }
        catch (\Exception $e) {
          watchdog_exception('a12s_layout', $e);
        }
      }
    }
  }

  /**
   * Build the CSS code for the given background image, using a responsive image
   * style.
   *
   * @param string $cssSelector
   *   The CSS selector.
   * @param \Drupal\file\fileInterface $file
   *   The file instance.
   * @param string $responsiveImageStyleId
   *   The responsive image style ID.
   * @param array $options
   *   The extra options.
   *
   * @return string
   */
  public function buildBackgroundImageCss(string $cssSelector, FileInterface $file, string $responsiveImageStyleId, array $options = []): string {
    if (empty($cssSelector)) {
      return '';
    }

    $options += [
      'background_repeat' => 'no-repeat',
      'separator' => "\n",
    ];

    $uri = $file->getFileUri();
    $css = [];

    if ($responsiveImageStyle = ResponsiveImageStyle::load($responsiveImageStyleId)) {
      $fallbackStyleId = $responsiveImageStyle->getFallbackImageStyle();

      // Build CSS for the fallback image, if defined.
      if ($fallbackStyleId !== ResponsiveImageStyleInterface::EMPTY_IMAGE) {
        if ($fallbackStyleId && ($fallbackStyle = ImageStyle::load($fallbackStyleId))) {
          $url = $fallbackStyle->buildUrl($uri);
        }
        else {
          $url = $this->fileUrlGenerator->generateAbsoluteString($uri);
        }

        if ($url) {
          $css[] = $cssSelector . ' { background-image: url("' . $url . '"); }';
        }
      }

      // Then build CSS for each defined breakpoint.
      $breakpoints = $this->breakpointManager->getBreakpointsByGroup($responsiveImageStyle->getBreakpointGroup());

      foreach (array_reverse($responsiveImageStyle->getKeyedImageStyleMappings()) as $bid => $multipliers) {
        if (isset($breakpoints[$bid])) {
          foreach ($multipliers as $multiplier => $mapping) {
            // "image_mapping" may be either string or array.
            $styles = (array) ($mapping['image_mapping'] ?? []);
            $styleId = reset($styles);

            if ($styleId && $styleId !== ResponsiveImageStyleInterface::EMPTY_IMAGE) {
              $url = ImageStyle::load($styleId)?->buildUrl($uri);

              if ($url) {
                $query = $breakpoints[$bid]->getMediaQuery();
                $query = $this->multiplierMediaQuerySelectors($query, $multiplier);

                // @todo should we add the URL without media query? This sounds
                //   wrong, as for this we already use the fallback image...
                if ($query !== '') {
                  //$url = $this->fileUrlGenerator->generateAbsoluteString($url);
                  $css[] = "@media $query { $cssSelector { background-image: url('$url'); } }";
                }
              }
            }
          }
        }
      }
    }

    if ($css) {
      $sizePositionRepeatArray = $this->getBackgroundSizeAndPosition($options);
      $sizePositionRepeatArray[] = "background-repeat:{$options['background_repeat']};";
      $sizePositionRepeat = implode(' ', $sizePositionRepeatArray);
      $css[] = "$cssSelector:before { $sizePositionRepeat }";
    }

    return implode($options['separator'], $css);
  }

  /**
   * Add multipliers to a media query.
   *
   * @param string $query
   *   The base media query (may be an empty string).
   * @param string $multiplier
   *
   * @return string
   */
  protected function multiplierMediaQuerySelectors(string $query, string $multiplier): string {
    $multiplier = (float) $multiplier;

    if ($multiplier && $multiplier !== 1.) {
      $selector = [];
      $rules = [
        [
          'property' => '-webkit-min-device-pixel-ratio',
          'string' => '@multiplier',
        ],
        [
          'property' => 'min--moz-device-pixel-ratio',
          'string' => '@multiplier',
        ],
        [
          'property' => '-o-min-device-pixel-ratio',
          'string'> '@multiplier/1',
        ],
        [
          'property' => 'min-device-pixel-ratio',
          'string' => '@multiplier',
        ],
        [
          'property' => 'min-resolution',
          'string' => '192dpi',
          'callback' => fn(float $multiplier) => $multiplier * 96,
        ],
        [
          'property' => 'min-resolution',
          'string' => '@multiplierdppx',
        ],
      ];

      foreach ($rules as $rule) {
        $placeholders = [
          '@multiplier' => (string) $multiplier,
          '@value' => (string) isset($rule['callback']) ? $rule['callback']($multiplier) : $multiplier,
        ];

        $value = new FormattableMarkup($rule['string'], $placeholders);
        $multiplerQuery = "(${$rule['property']}: ${$value})";
        $selector[] = $query ? ($query . ' and ' . $multiplerQuery) : $multiplerQuery;
      }

      $query = implode(',', $selector);
    }

    return $query;
  }

  /**
   * Build the background-size and background-position properties from the
   * provided settings.
   *
   * @param array $settings
   * @param string $default
   *   By default, is uses the default value. If the property should be skipped,
   *   this variable may be set to NULL.
   *
   * @return array
   */
  protected function getBackgroundSizeAndPosition(array $settings = [], string $default = ''): array {
    $style = [];

    foreach (['background_size', 'background_position'] as $cssProperty) {
      $cssProperty_name = strtr($cssProperty, '_', '-');
      $option = $default;

      if (!empty($settings[$cssProperty]['option'])) {
        $option = $settings[$cssProperty]['option'];
        $custom_value = $settings[$cssProperty]['custom'] ?? '';
      }

      if ($option === '') {
        $option = $this->globalConfiguration[$cssProperty] ?? NULL;
        $custom_value = $this->globalConfiguration[$cssProperty] ?? NULL;
      }

      switch ($option) {
        case NULL:
          // Ignore...
          break;
        case 'custom':
          if (!empty($custom_value)) {
            $style[] = $cssProperty_name . ':' . $custom_value . ';';
          }
          break;

        default:
          $style[] = $cssProperty_name . ':' . $option . ';';
      }
    }

    return $style;
  }

  /**
   * {@inheritDoc}
   */
  public function globalSettingsForm(array &$form, FormStateInterface $formState, array $config = []): void {
    $default = $this->mergeConfigWithDefaults($config);

    $form['background_size'] = [
      '#type' => 'select_or_other_select',
      '#title' => $this->t('Background size'),
      '#description' => $this->t('You may find further details about the @name CSS property on <a href=":url">this page</a>.', [
        '@name' => 'background-size',
        ':url' => 'https://developer.mozilla.org/fr/docs/Web/CSS/background-size',
      ]),
      '#options' => [
        'cover' => $this->t('Cover'),
        'contain' => $this->t('Contain'),
      ],
      '#regex' => '^(?:(?:(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|auto)(?:\s+(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|auto)?))|cover|contain)$',
      '#element_validate' => [[static::class, 'validateSelectOrOtherRegex']],
      '#default_value' => NULL, // @todo create issue for select_or_other, as the module forces to define a default_value even if not necessary (@see ElementBase::addSelectField()).
    ];

    $form['background_position'] = [
      '#type' => 'select_or_other_select',
      '#title' => $this->t('Background position'),
      '#description' => $this->t('You may find further details about the @name CSS property on <a href=":url">this page</a>.', [
        '@name' => 'background-position',
        ':url' => 'https://developer.mozilla.org/fr/docs/Web/CSS/background-position',
      ]),
      '#options' => [
        'center' => $this->t('Center'),
        'top' => $this->t('Center Top'),
        'bottom' => $this->t('Center Bottom'),
        'left' => $this->t('Left Center'),
        'right' => $this->t('Right Center'),
      ],
      // Note that this allows some wrong values like "left left", but those are
      // so obvious errors that we can ignore it.
      '#regex' => '^(?:(?:(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|0|top|bottom|left|right|center)(?:\h+(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|0|top|bottom|left|right|center))?)|inherit|initial|unset)$',
      '#element_validate' => [[static::class, 'validateSelectOrOtherRegex']],
      '#default_value' => NULL,
    ];

    // Handle default values for "select_or_other".
    foreach (['background_size', 'background_position'] as $key) {
      $value = $default[$key];
      $options = $form[$key]['#options'];

      if (array_key_exists($value, $options)) {
        $form[$key]['#default_value'] = [$value];
      }
      elseif (!empty($value)) {
        $form[$key]['#other_options'] = $value;
      }
    }

    // Any visible, writable wrapper can potentially be used for uploads,
    // including a remote file system that integrates with a CDN.
    $options = $this->streamWrapperManager->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);
    if (!empty($options)) {
      $form['scheme'] = [
        '#type' => 'radios',
        '#title' => $this->t('File storage'),
        '#default_value' => $default['scheme'],
        '#options' => $options,
        '#access' => count($options) > 1,
      ];
    }
    else {
      $form['scheme'] = ['#type' => 'value', '#value' => $default['scheme']];
    }

    $form['directory'] = [
      '#type' => 'textfield',
      '#default_value' => $default['directory'],
      '#title' => $this->t('Upload directory'),
      '#description' => $this->t("A directory relative to Drupal's files directory where uploaded images are stored."),
    ];

    $default_max_size = format_size(Environment::getUploadMaxSize());
    $form['max_size'] = [
      '#type' => 'textfield',
      '#default_value' => $default['max_size'],
      '#title' => $this->t('Maximum file size'),
      '#description' => $this->t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', ['@size' => $default_max_size]),
      '#maxlength' => 20,
      '#size' => 10,
      '#placeholder' => $default_max_size,
    ];

    $form['max_dimensions'] = [
      '#type' => 'item',
      '#title' => $this->t('Maximum dimensions'),
      '#field_prefix' => '<div class="container-inline clearfix">',
      '#field_suffix' => '</div>',
      '#description' => $this->t('Images larger than these dimensions will be scaled down.'),
    ];

    $form['max_dimensions']['width'] = [
      '#title' => $this->t('Width'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => $default['max_dimensions']['width'],
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => $this->t('width'),
      '#field_suffix' => ' x ',
    ];

    $form['max_dimensions']['height'] = [
      '#title' => $this->t('Height'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => $default['max_dimensions']['height'],
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => $this->t('height'),
      '#field_suffix' => $this->t('pixels'),
    ];

    $options = $this->getResponsiveImageOptions();
    $form['responsive_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Default responsive image style'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $default['responsive_image_style'] ?? '',
      '#options' => $options,
      '#access' => !empty($options),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function submitGlobalSettingsForm(array $form, FormStateInterface $formState) {
    // Transform "select_or_other" values.
    foreach (['background_size', 'background_position'] as $key) {
      if ($formState->hasValue($key)) {
        $value = $formState->getValue($key);

        if (!empty($value['other']) && ($value['select'] ?? '') === 'select_or_other') {
          $value = $value['other'];
        }
        elseif (!empty($value['select'])) {
          $value = $value['select'];
        }
        else {
          $value = '';
        }

        $formState->setValue($key, $value);
      }
    }

    parent::submitGlobalSettingsForm($form, $formState);
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    $form['#type'] = 'details';
    $form['#open'] = !empty($values['background_image']);

    $max_filesize = min(Bytes::toNumber($this->globalConfiguration['max_size']), Environment::getUploadMaxSize());
    $max_dimensions = '0';

    if (!empty($this->globalConfiguration['max_dimensions']['width']) || !empty($this->globalConfiguration['max_dimensions']['height'])) {
      $max_dimensions = $this->globalConfiguration['max_dimensions']['width'] . 'x' . $this->globalConfiguration['max_dimensions']['height'];
    }

    $form['background_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Background image'),
      '#default_value' => $values['background_image'] ?? NULL,
      '#upload_location' => $this->globalConfiguration['scheme'] . '://' . $this->globalConfiguration['directory'],
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [$max_filesize],
        'file_validate_image_resolution' => [$max_dimensions],
      ],
    ];

    $form['background_size'] = [
      '#type' => 'select_or_other_select',
      '#title' => $this->t('Background size'),
      '#empty_option' => $this->t('- Use default value -'),
      '#default_value' => $values['background_size'] ?? NULL,
      '#description' => $this->t('You may find further details about the @name CSS property on <a href=":url">this page</a>.', [
        '@name' => 'background-size',
        ':url' => 'https://developer.mozilla.org/fr/docs/Web/CSS/background-size',
      ]),
      '#options' => [
        'cover' => $this->t('Cover'),
        'contain' => $this->t('Contain'),
      ],
      '#regex' => '^(?:(?:(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|auto)(?:\s+(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|auto)?))|cover|contain)$',
      '#element_validate' => [[static::class, 'validateSelectOrOtherRegex']],
      '#default_value' => NULL,
    ];

    $form['background_position'] = [
      '#type' => 'select_or_other_select',
      '#title' => $this->t('Background position'),
      '#empty_option' => $this->t('- Use default value -'),
      '#default_value' => $values['background_position'] ?? NULL,
      '#description' => $this->t('You may find further details about the @name CSS property on <a href=":url">this page</a>.', [
        '@name' => 'background-position',
        ':url' => 'https://developer.mozilla.org/fr/docs/Web/CSS/background-position',
      ]),
      '#options' => [
        'center' => $this->t('Center'),
        'top' => $this->t('Center Top'),
        'bottom' => $this->t('Center Bottom'),
        'left' => $this->t('Left Center'),
        'right' => $this->t('Right Center'),
      ],
      // Note that this allows some wrong values like "left left", but those are
      // so obvious errors that we can ignore it.
      '#regex' => '^(?:(?:(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|0|top|bottom|left|right|center)(?:\h+(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|0|top|bottom|left|right|center))?)|inherit|initial|unset)$',
      '#element_validate' => [[static::class, 'validateSelectOrOtherRegex']],
      '#default_value' => NULL,
    ];

    $form['background_repeat'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Background repeat'),
      '#default_value' => $values['background_repeat'] ?? FALSE,
    ];

    $options = $this->getResponsiveImageOptions();
    // @todo check if the global value is set. If not, add a warning, or simply
    //   do not add the empty option.
    $form['responsive_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive image style'),
      '#empty_option' => $this->t('- Use default value -'),
      '#default_value' => $values['responsive_image_style'] ?? '',
      '#options' => $options,
      '#access' => !empty($options),
    ];

    return $form;
  }

  /**
   * Form API callback "after_build": handle regex validation for "other" textfield.
   */
  public static function validateSelectOrOtherRegex($element, FormStateInterface $formState, array $completeForm) {
    if (isset($element['#regex']) && isset($element['other'])) {
      $element['other']['#pattern'] = $element['#regex'];
      // Title is required in FormElement::validatePattern().
      $element['other']['#title'] = $element['#title'];
      FormElement::validatePattern($element['other'], $formState, $completeForm);
    }
  }

  /**
   * Returns Responsive image for select options.
   */
  protected function getResponsiveImageOptions(): array {
    $options = [];

    if ($this->moduleHandler->moduleExists('responsive_image')) {
      foreach ($this->responsiveImageStyleStorage->loadMultiple() as $name => $imageStyle) {
        if ($imageStyle->hasImageStyleMappings()) {
          $options[$name] = Html::escape($imageStyle->label());
        }
      }
    }

    return $options;
  }

}
