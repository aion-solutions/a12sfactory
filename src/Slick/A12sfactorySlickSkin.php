<?php

namespace Drupal\a12sfactory\Slick;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\slick\SlickSkinInterface;

/**
 * Provide a clear and simple skin.
 */
class A12sfactorySlickSkin implements SlickSkinInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function skins() {
    return [
      'a12sfactory_clear' => [
        'name' => 'A12S: clear',
        'description' => $this->t('Provides a clear and simple display for sliders.'),
        'group' => 'main',
        'provider' => 'a12sfactory',
        'css' => [],
        'js' => [],
        'dependencies' => [
          'a12sfactory/slick-clear',
        ],
      ],
    ];
  }

}
