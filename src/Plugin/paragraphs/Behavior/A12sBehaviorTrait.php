<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

Trait A12sBehaviorTrait {

  use StringTranslationTrait;

  /**
   * Initialize the behavior form and create a vertical tab if applicable.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param array $plugin_definition
   *   The behavior plugin definition.
   */
  protected function initBehaviorForm(array &$form, $plugin_definition) {
    $form['#attached']['library'][] = 'a12sfactory/paragraph-admin';

    // Guess the parents of the subform.
    if (isset($form['#group']) && preg_match('/^(?<parents>.+\]\[)subform\]\[paragraph_behavior$/', $form['#group'], $matches)) {
      $form['#base_group'] = preg_replace('/\]\[/', '[', $matches['parents'], 1) . 'behavior_plugins][' . $plugin_definition['id'] . ']';
      $form['#tab_group'] = $matches['parents'] . 'behavior_plugins][' . $plugin_definition['id'] . '][a12s_behaviors';

      $form['a12s_behaviors'] = [
        '#type' => 'vertical_tabs',
        '#title' => $plugin_definition['label'],
      ];
    }
  }

  /**
   * Filter the empty "details" elements at the root of the form.
   *
   * @param array $form
   *
   * @return array
   *   The filtered form.
   */
  protected function filterEmptyDetailsElements(array $form) {
    foreach (Element::children($form) as $key) {
      if (!empty($form[$key]) && $form[$key] === 'details' && empty(Element::children($form[$key]))) {
        $form[$key]['#access'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * Gets data from the global configuration object.
   *
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   The data that was requested.
   */
  protected function getGlobalConfig($key = '') {
    return \Drupal::configFactory()->get('a12sfactory.paragraphs.behavior')->get($key);
  }

  /**
   * Build a form element to select a background style.
   *
   * @param string|NULL $title
   *   The field title.
   * @param string|null $default_value
   *   The default value.
   *
   * @return array
   *   A form element.
   */
  protected function backgroundStyleElement(string $title = NULL, string $default_value = NULL): array {
    if (!isset($title)) {
      $title = $this->t('Background style');
    }

    $element = [
      '#type' => 'radios',
      '#title' => $title,
      '#options' => ['' => $this->t('N/A')] + $this->getGlobalConfig('display.background_styles'),
      '#process' => [
        ['Drupal\\Core\\Render\\Element\\Radios', 'processRadios'],
        [static::class, 'processBackgroundStyleRadios'],
      ],
      '#attributes' => [
        'class' => ['a12s-display-behavior-background-styles'],
      ],
      '#default_value' => $default_value,
    ];

    $this->attachBackgroundStyleLibrary($element);
    return $element;
  }

  /**
   * Attach the background style library to the given render array
   *
   * @param array &$build
   *   A render array.
   */
  protected function attachBackgroundStyleLibrary(array &$build) {
    // Get the default active theme for the site.
    $default_active_theme_name = \Drupal::configFactory()->get('system.theme')->get('default');
    $default_active_theme_libraries = \Drupal::service('library.discovery')->getLibrariesByExtension($default_active_theme_name);

    // If the default active theme has got the background-styles library use it.
    if (isset($default_active_theme_libraries['background-styles'])) {
      $build['#attached']['library'][] = $default_active_theme_name . '/background-styles';
    }
    else {
      $build['#attached']['library'][] = 'a12sfactory/background-styles';
    }
  }

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processBackgroundStyleRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    foreach (Element::children($element) as $key) {
      $element[$key]['#label_attributes']['class'][] = $key ?: 'bg-none';
    }

    return $element;
  }

  /**
   * Build heading options for select element.
   *
   * @param int $min
   *   The minimum level for headings.
   * @param int $max
   *   The maximum level for headings.
   * @param bool $add_div
   *   Whether to prepend a DIV option.
   *
   * @return array
   */
  protected function headingOptions(int $min = 1, int $max = 5, $add_div = TRUE): array {
    $options = [];

    if ($add_div) {
      $options['div'] = 'DIV';
    }

    for ($i = $min; $i <= $max; $i++) {
      $options['h' . $i] = 'H' . $i;
    }

    return $options;
  }

}
