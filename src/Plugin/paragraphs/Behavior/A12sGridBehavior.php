<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * A12s grid plugin for paragraph entities.
 *
 * @ParagraphsBehavior(
 *   id = "a12sfactory_paragraph_grid",
 *   label = @Translation("Grid"),
 *   description = @Translation("Allow the paragraphs to work as a grid row and/or column."),
 *   weight = 1,
 * )
 *
 * @deprecated This file is kept to allow migration from old behaviors to new ones.
 */
class A12sGridBehavior extends ParagraphsBehaviorBase {

  use A12sBehaviorTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return !in_array($paragraphs_type->id(), ['card', 'cards', 'card_list', 'card_body']);
  }

}
