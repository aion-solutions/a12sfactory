<?php

namespace Drupal\a12sfactory\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Provides form for managing global settings related to Paragraphs behaviors.
 *
 * The stream wrapper manager service has to be initialized by the child class.
 */
trait A12sDisplayBehaviorFormTrait {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Get the default values for background image settings.
   *
   * @return array
   */
  protected function getBackgroundImageDefaultValues() {
    return [
      'background_size' => ['option' => 'global'],
      'background_position' => ['option' => 'global'],
      'scheme' => file_default_scheme(),
      'directory' => 'background-images',
      'max_size' => '',
      'max_dimensions' => ['width' => '', 'height' => ''],
    ];
  }

  /**
   * Build the subform elements related to background image.
   *
   * @param array $subform
   *   The subform to which attach the elements.
   * @param array $default
   *   The default values.
   */
  protected function buildBackgroundImageSubform(array &$subform, array $default = []) {
    $subform['background_size'] = [
      '#type' => 'css_background_size',
      '#title' => t('Background size'),
      '#default_value' => $default['background_size'] ?? NULL,
    ];

    $subform['background_position'] = [
      '#type' => 'css_background_position',
      '#title' => t('Background position'),
      '#default_value' => $default['background_position'] ?? NULL,
    ];

    // Any visible, writable wrapper can potentially be used for uploads,
    // including a remote file system that integrates with a CDN.
    $options = $this->streamWrapperManager->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);
    if (!empty($options)) {
      $subform['scheme'] = [
        '#type' => 'radios',
        '#title' => t('File storage'),
        '#default_value' => $default['scheme'],
        '#options' => $options,
        '#access' => count($options) > 1,
      ];
    }

    $subform['directory'] = [
      '#type' => 'textfield',
      '#default_value' => $default['directory'],
      '#title' => t('Upload directory'),
      '#description' => t("A directory relative to Drupal's files directory where uploaded images will be stored."),
    ];

    $default_max_size = format_size(file_upload_max_size());
    $subform['max_size'] = [
      '#type' => 'textfield',
      '#default_value' => $default['max_size'],
      '#title' => t('Maximum file size'),
      '#description' => t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', ['@size' => $default_max_size]),
      '#maxlength' => 20,
      '#size' => 10,
      '#placeholder' => $default_max_size,
    ];

    $subform['max_dimensions'] = [
      '#type' => 'item',
      '#title' => t('Maximum dimensions'),
      '#field_prefix' => '<div class="container-inline clearfix">',
      '#field_suffix' => '</div>',
      '#description' => t('Images larger than these dimensions will be scaled down.'),
    ];
    $subform['max_dimensions']['width'] = [
      '#title' => t('Width'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => $default['max_dimensions']['width'] ?? '',
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => t('width'),
      '#field_suffix' => ' x ',
    ];
    $subform['max_dimensions']['height'] = [
      '#title' => t('Height'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => $default['max_dimensions']['height'] ?? '',
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => t('height'),
      '#field_suffix' => t('pixels'),
    ];
  }

  /**
   * Convert array values to a string to be used in a textarea.
   *
   * @param array $values
   *   The values list.
   *
   * @return string
   */
  protected function keyValue2Text(array $values) {
    $text = '';

    foreach ($values as $key => $value) {
      $text .= "$key|$value" . PHP_EOL;
    }

    return rtrim($text, PHP_EOL);
  }

  /**
   * Convert a string containing a list of options to a keyed array.
   *
   * @param string $string
   *   The string should be a list of key|value pairs separated by a line break.
   *
   * @return array
   */
  protected function text2KeyValue($string) {
    $values = [];
    $matches = [];

    if (preg_match_all('/\s*(?P<key>[^\|\s]+)\s*\|\s*(?P<value>[^\r\n]+)/m', $string, $matches)) {
      $values = array_combine($matches['key'], $matches['value']);
    }

    return $values;
  }

  /**
   * Validates the key/value pairs.
   *
   * @param array $array
   *   The array to validate.
   *
   * @return bool
   *   Whether the pairs are valid or not.
   */
  protected function validateKeyValue(array $array) {
    foreach ($array as $key => $value) {
      if (Unicode::strlen($key) > 255) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
