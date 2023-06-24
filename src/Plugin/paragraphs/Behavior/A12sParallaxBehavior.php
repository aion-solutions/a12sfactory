<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * A12s behavior plugin for paragraph entities.
 *
 * @ParagraphsBehavior(
 *   id = "a12sfactory_paragraph_parallax",
 *   label = @Translation("Parallax"),
 *   description = @Translation("Provides option for parallax effects."),
 *   weight = 10,
 * )
 *
 * @deprecated This file is kept to allow migration from old behaviors to new ones.
 */
class A12sParallaxBehavior extends ParagraphsBehaviorBase {

  use A12sBehaviorTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return !in_array($paragraphs_type->id(), ['card', 'cards', 'card_list', 'card_body']);
  }

}
