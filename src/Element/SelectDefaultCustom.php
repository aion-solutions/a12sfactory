<?php

namespace Drupal\a12sfactory\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Base class for select with custom input form elements.
 *
 * Properties:
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 *
 * @FormElement("select_default_custom")
 */
class SelectDefaultCustom extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processSelectCustom'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#tree' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $element += ['#default_value' => []];
      return (array) $element['#default_value'] + ['option' => '', 'custom' => ''];
    }

    $value = ['option' => '', 'custom' => ''];
    // Throw out all invalid array keys; we only allow "option" and "custom".
    foreach ($value as $allowed_key => $default) {
      // These should be strings, but allow other scalars since they might be
      // valid input in programmatic form submissions. Any nested array values
      // are ignored.
      if (isset($input[$allowed_key]) && is_scalar($input[$allowed_key])) {
        $value[$allowed_key] = (string) $input[$allowed_key];
      }
    }
    return $value;
  }

  /**
   * Expand a CSS background property field into select and textfield.
   */
  public static function processSelectCustom(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#element_validate'] = [[get_called_class(), 'validateSelectCustom']];
    $options = [];

    if (isset($element['#options'])) {
      $options = $element['#options'];
      // Remove the #option key, to avoid conflict with form validation.
      // @see \Drupal\Core\Form\performRequiredValidation
      unset($element['#options']);
    }

    $options['custom'] = t('Custom value');

    $element['option'] = [
      '#type' => 'select',
      '#title' => t('Value'),
      '#title_display' => 'invisible',
      '#value' => $element['#value']['option'] ?? NULL,
      '#options' => $options,
      '#required' => $element['#required'],
    ];

    $element['custom'] = $element['#custom_element'] ?? [];
    $element['custom'] += [
      '#type' => 'textfield',
      '#title_display' => 'invisible',
      '#title' => t('Custom value'),
      '#value' => $element['#value']['custom'] ?? NULL,
      '#states' => [
        'visible' => [
          'select[name="' . $element['#name'] . '[option]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    if (empty($element['custom']['#pattern']) && defined(get_called_class() . '::REGEX')) {
      $element['custom']['#pattern'] = constant(get_called_class() . '::REGEX');
    }

    if (isset($element['#empty_option'])) {
      $element['option']['#empty_option'] = $element['#empty_option'];
    }

    if (isset($element['#empty_value'])) {
      $element['option']['#empty_value'] = $element['#empty_value'];
    }

    if (isset($element['#size'])) {
      $element['option']['#size'] = $element['custom']['#size'] = $element['#size'];
    }

    return $element;
  }

  /**
   * Validates a SelectCustom property element.
   */
  public static function validateSelectCustom(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['option']['#value'];

    if ($value === 'custom') {
      if ($element['custom']['#value'] === '') {
        $form_state->setError($element, t('Custom value is required.'));
        $form_state->setError($element, t('%name field: custom value is required.', ['%name' => $element['#title']]));
      }
    }
    else {
      $form_state->setValueForElement($element['custom'], NULL);
    }

    if ($element['#required'] && (empty($value) || $value === (string) $element['#empty_value'])) {
      $form_state->setError($element, t('%name field is required.', ['%name' => $element['#title']]));
    }

    return $element;
  }

}
