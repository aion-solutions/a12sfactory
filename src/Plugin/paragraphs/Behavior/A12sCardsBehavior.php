<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * A12s behavior plugin for paragraph entities.
 *
 * @ParagraphsBehavior(
 *   id = "a12sfactory_cards",
 *   label = @Translation("Cards group"),
 *   description = @Translation("Provides features for a group of cards."),
 *   weight = 0,
 * )
 *
 * @deprecated This file is kept to allow migration from old behaviors to new ones.
 */
class A12sCardsBehavior extends ParagraphsBehaviorBase {

  use A12sBehaviorTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() === 'cards';
  }

}
