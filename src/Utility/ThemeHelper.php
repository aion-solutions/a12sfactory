<?php

namespace Drupal\a12sfactory\Utility;

use Drupal\block\Entity\Block;
use Drupal\Component\Utility\NestedArray;

/**
 * Class ThemeHelper
 */
class ThemeHelper implements ThemeHelperInterface {

  /**
   * @inheritDoc
   */
  public function getInputSelectorFromParents(string $inputKey, array $parents = []): string {
    if ($parents) {
      $parents[] = $inputKey;
      $first = array_shift($parents);
      $inputKey = $first . '[' . implode('][', $parents) . ']';
    }

    return $inputKey;
  }

  /**
   * @inheritDoc
   */
  public function getBlockRegion(array $variables): ?string {
    $region = NULL;

    if (!empty($variables['elements']['#id'])) {
      $region = Block::load($variables['elements']['#id'])?->getRegion();
    }
    // Try to find region when using page_manager module.
    elseif (isset($variables['elements']['#configuration']['region'])) {
      $region = $variables['elements']['#configuration']['region'];
    }

    return $region;
  }

  /**
   * @inheritDoc
   */
  public function injectSuggestions(array &$suggestions, string $base_hook, string $injectName, string $injectValue, array $variables) {
    $base_suggestions = $suggestions;
    $suggestions = [$base_hook . '__' . $injectName . '_' . $injectValue];

    foreach ($base_suggestions as $suggestion) {
      if (!in_array($suggestion, $suggestions)) {
        $suggestions[] = $suggestion;
      }

      $match = [];
      // Insert just after the base hook.
      if (preg_match('/^(?P<start>' . preg_quote($base_hook, '/') . ')(?P<end>__.*)$/', $suggestion, $match)) {
        $suggestions[] = $match['start'] . '__' . $injectName . '_' . $injectValue . $match['end'];
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getThemeSettingValue(array $parents, string $default = NULL): mixed {
    $value = NULL;
    $property = array_shift($parents);

    if ($property && ($settings = theme_get_setting($property))) {
      if ($parents) {
        if (is_array($settings)) {
          $value = NestedArray::getValue($settings, $parents);
        }
      }
      else {
        $value = $settings;
      }

      if ($value === NULL && $default !== NULL) {
        $value = $default;
      }
    }

    return $value;
  }

}
