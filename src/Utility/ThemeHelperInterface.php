<?php

namespace Drupal\a12sfactory\Utility;

/**
 * Interface ThemeManagerInterface
 */
interface ThemeHelperInterface {

  /**
   * Return an input selector built from its parents.
   *
   * This is helpful for the States API for example.
   *
   * @param string $inputKey
   * @param array $parents
   *
   * @return string
   */
  public function getInputSelectorFromParents(string $inputKey, array $parents = []): string;

  /**
   * Try to get the region whose the block is assigned to.
   *
   * @param array $variables
   *   An associative array of variables passed to the theme hook.
   *
   * @return string|null
   *   The region name when applicable.
   *
   * @see hook_preprocess_HOOK()
   */
  public function getBlockRegion(array $variables): ?string;

  /**
   * Add suggestions based on the region to each suggestions in the list.
   *
   * @param array $suggestions
   *   An array of theme suggestions.
   * @param string $base_hook
   *   The base hook, for example "block" id adding suggestions based on the
   *   block.html.twig template.
   * @param string $injectName
   *   The name of the .
   * @param string $injectValue
   *   The region ID.
   * @param array $variables
   *   An associative array of variables passed to the theme hook.
   *
   * @example
   * // Inside a hook_theme_suggestions_HOOK_alter() function for "block":
   * \Drupal::service('a12sfactory.theme_helper')->injectSuggestions($suggestions, 'block', 'region', 'header', $variables);
   *
   * @see hook_theme_suggestions_HOOK_alter()
   */
  public function injectSuggestions(array &$suggestions, string $base_hook, string $injectName, string $injectValue, array $variables);

  /**
   * Get the value of a nested property of the theme settings.
   *
   * @param array $parents
   *   The theme settings target element.
   * @param string|NULL $default
   *   The default setting.
   *
   * @return mixed
   *   The nested value, or NULL if not exists.
   */
  public function getThemeSettingValue(array $parents, string $default = NULL): mixed;

}
