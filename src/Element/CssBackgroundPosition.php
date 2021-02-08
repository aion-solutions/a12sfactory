<?php

namespace Drupal\a12sfactory\Element;

/**
 * Provides a form element for CSS property "background-position".
 *
 * @FormElement("css_background_position")
 */
class CssBackgroundPosition extends CssBackgroundBase {

  /**
   * A regex that validates the value of the background-size property.
   *
   * Note that this allows some wrong values like "left left", but those are so
   * obvious errors that we can ignore it.
   */
  const REGEX = '^(?:(?:(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|0|top|bottom|left|right|center)(?:\h+(?:(?:\d+)(?:%|r?em|px|cm|ch|vw|vh)|0|top|bottom|left|right|center))?)|inherit|initial|unset)$';

  /**
   * {@inheritdoc}
   */
  public static function getOptions() {
    return [
      'center' => t('Center'),
      'top' => t('Center Top'),
      'bottom' => t('Center Bottom'),
      'left' => t('Left Center'),
      'right' => t('Right Center'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCssPropertyName() {
    return 'background-position';
  }

}
