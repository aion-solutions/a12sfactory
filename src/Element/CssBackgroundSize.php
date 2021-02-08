<?php

namespace Drupal\a12sfactory\Element;

/**
 * Provides a form element for CSS property "background-size".
 *
 * @FormElement("css_background_size")
 */
class CssBackgroundSize extends CssBackgroundBase {

  /**
   * A regex that validates the value of the background-size property.
   */
  const REGEX = '^(?:(?:(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|auto)(?:\s+(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|auto)?))|cover|contain)$';

  /**
   * {@inheritdoc}
   */
  public static function getOptions() {
    return [
      'cover' => t('Cover'),
      'contain' => t('Contain'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCssPropertyName() {
    return 'background-size';
  }

}
