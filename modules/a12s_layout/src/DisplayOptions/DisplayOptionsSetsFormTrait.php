<?php

namespace Drupal\a12s_layout\DisplayOptions;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;

/**
 * Trait
 */
Trait DisplayOptionsSetsFormTrait {

  /**
   * Get the Display Options Set plugin manager.
   *
   * @return \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginManager
   */
  public function pluginManagerDisplayOptionsSet(): DisplayOptionsSetPluginManager {
    if (!isset($this->pluginManagerDisplayOptionsSet)) {
      return \Drupal::service('plugin.manager.a12s_layout_display_options_set');
    }

    return $this->pluginManagerDisplayOptionsSet;
  }

  /**
   * Render an element containing the display options form, according to the
   * given options sets.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetInterface[] $optionsSets
   *   The list of "options set" plugin instances.
   * @param array $parents
   *   The element parents.
   * @param array $values
   *   The current values.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The sub-form state.
   * @param array $form
   *   The complete form array.
   *
   * @return array
   */
  public static function layoutParagraphsDisplayOptionsForm(array $element, array $optionsSets, array $parents, array $values, FormStateInterface $formState, array &$form): array {
    $weight = 100;
    $element['display_options_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => t('Display options'),
      '#title_display' => 'invisible',
      '#weight' => $weight++,
    ];

    /** @var \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase $optionsSet */
    foreach ($optionsSets as $optionsSetId => $optionsSet) {
      $subForm = [];
      $subformState = SubformState::createForSubform($subForm, $form, $formState);
      $subForm = $optionsSet->form($subForm, $subformState, $values[$optionsSetId] ?? [], array_merge($parents, ['display_options', $optionsSetId]));

      if (!empty($subForm)) {
        $category = (string) $optionsSet->getPluginDefinition()['category'];

        if (!isset($element['display_options'][$category])) {
          $element['display_options'][$category] = [
            '#type' => 'details',
            '#title' => $category,
            '#weight' => $weight++,
            '#group' => implode('][', array_merge($parents, ['display_options_tabs'])),
          ];
        }

        $element['display_options'][$optionsSetId] = $subForm + [
            '#type' => 'fieldset',
            '#title' => $optionsSet->label(),
            '#group' => implode('][', array_merge($parents, ['display_options', $category])),
            '#weight' => $weight++,
          ];
      }
    }

    if (empty($element['display_options'])) {
      unset($element['display_options_tabs']);
    }

    return $element;
  }

  /**
   * Validation callback for a Paragraph Layout element.
   *
   * @param \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetInterface[] $optionsSets
   *   The list of "options set" plugin instances.
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   *
   * @return void
   *
   * @see alterLayoutParagraphsComponentForm()
   */
  public function validateLayoutParagraphsDisplayOptionsForm(array $optionsSets, array &$element, FormStateInterface $formState): void {
    try {
      /** @var \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase $optionsSet */
      foreach ($optionsSets as $optionsSetId => $optionsSet) {
        $subForm = &$element['display_options'][$optionsSetId];

        if (isset($subForm)) {
          $subformState = SubformState::createForSubform($subForm, $element, $formState);
          $optionsSet->validateForm($subForm, $subformState);
        }
      }
    }
    catch (PluginNotFoundException $e) {
      watchdog_exception('a12s_layout', $e);
    }

    // Remove "display_options_tabs" key.
    $formState->unsetValue('display_options_tabs');
  }

  /**
   * Get a list of "options set" plugin instances.
   *
   * @param array $settings
   *   An associative array defining the settings of all options sets, whose
   *   keys are plugin IDs and values the plugin configuration.
   * @param array $context
   *   The specific context which ay be used by the plugins.
   *
   * @return \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetInterface[]
   */
  protected function getOptionsSets(array $settings, array $context = []): array {
    $instances = [];

    foreach ($settings as $pluginId => $setting) {
      // @todo Handle plugin configuration ($setting).
      try {
        $configuration = ['id' => $pluginId] + $context;
        $instances[$pluginId] = $this->pluginManagerDisplayOptionsSet()->createInstance($pluginId, $configuration);
      }
      catch (PluginException $e) {
        watchdog_exception('a12s_layout', $e);
      }
    }

    return $instances;
  }

  /**
   * Deny access to all form elements, except those in the given allowed list.
   *
   * @param array $element
   *   The form element.
   * @param array $ignoredKeys
   *   A list of allowed keys.
   *
   * @return void
   */
  public static function denyFormElementAccess(array &$element, array $ignoredKeys = []): void {
    foreach (Element::children($element) as $key) {
      if (!in_array($key, $ignoredKeys)) {
        $element[$key]['#access'] = FALSE;
      }
    }
  }

}

