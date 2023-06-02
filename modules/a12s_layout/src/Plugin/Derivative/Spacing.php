<?php

namespace Drupal\a12s_layout\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides "display options set" plugin definitions for spacing.
 *
 * @see \Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet\Spacing
 */
class Spacing extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $types = [
      'margin' => $this->t('Margin'),
      'padding' => $this->t('Padding'),
    ];

    foreach ($types as $key => $label) {
      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['label'] = $label;
    }

    return $this->derivatives;
  }

}
