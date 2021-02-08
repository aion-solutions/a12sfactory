<?php

namespace Drupal\a12sfactory\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Base class for CSS background property form elements.
 *
 * The default description is ignored when another is specified or if the
 * #description property is set to NULL.
 *
 * Properties: inherited from SelectCustom.
 *
 * @see \Drupal\a12sfactory\Element\SelectCustom
 */
abstract class CssBackgroundBase extends SelectDefaultCustom {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processCssBackgroundProperty'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#tree' => TRUE,
    ];
  }

  /**
   * Expand a CSS background property field into select and textfield.
   */
  public static function processCssBackgroundProperty(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#options'] = static::getOptions();

    parent::processSelectCustom($element, $form_state, $complete_form);

    $description = static::getDefaultDescription();
    if ($description && !array_key_exists('#description', $element)) {
      $element['#description'] = $description;
    }

    return $element;
  }

  /**
   * Get the available options for the given CSS background property.
   *
   * @return array
   */
  abstract public static function getOptions();

  /**
   * Get the CSS background property name.
   *
   * @return string
   */
  abstract public static function getCssPropertyName();

  /**
   * Get the default description to be used with the current CSS property.
   *
   * @return string|null
   */
  public static function getDefaultDescription() {
    return t('You may find further details about the @name CSS property on <a href=":url">this page</a>.', [
      '@name' => static::getCssPropertyName(),
      ':url' => 'https://developer.mozilla.org/fr/docs/Web/CSS/' . static::getCssPropertyName(),
    ]);
  }

}
