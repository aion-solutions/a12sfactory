<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\a12sfactory\Form\A12sDisplayBehaviorFormTrait;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
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
 */
class A12sCardsBehavior extends ParagraphsBehaviorBase {

  use A12sDisplayBehaviorFormTrait;
  use A12sBehaviorTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() === 'cards';
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) { }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {
    parent::preprocess($variables);

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];

    $variables['card_group_type'] = $paragraph->getBehaviorSetting($this->getPluginId(), ['cards', 'type'], 'card-group');
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $settings = $paragraph->getBehaviorSetting($this->getPluginId(), ['cards'], []);
    $settings += [
      'type' => 'div',
      'header_tag' => 'div',
      'width' => '',
    ];

    $form['#weight'] = -10;

    $form['cards'] = [
      '#type' => 'details',
      '#title' => $this->t('Card group settings'),
    ];

    $description = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Use card groups to render cards as a single, attached element with equal width and height columns.'),
        $this->t('Use card decks if you need a set of equal width and height cards that are not attached to one another.'),
        $this->t('Cards are ordered from top to bottom and left to right.'),
      ],
    ];

    $form['cards']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $description,
      '#options' => [
        'card-group' => $this->t('Group'),
        'card-deck' => $this->t('Deck'),
        'card-columns' => $this->t('Columns'),
      ],
      '#default_value' => $settings['type'],
      '#required' => TRUE,
    ];

    // @todo allow to choose title level/class.

    // @todo how to handle column-count property from CSS grid?

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [$this->t('Cards group')];
  }

}
