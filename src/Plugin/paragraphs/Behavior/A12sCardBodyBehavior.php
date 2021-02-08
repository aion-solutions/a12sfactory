<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\a12sfactory\Form\A12sDisplayBehaviorFormTrait;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * A12s behavior plugin for paragraph entities.
 *
 * @ParagraphsBehavior(
 *   id = "a12sfactory_card_body",
 *   label = @Translation("Card body"),
 *   description = @Translation("Provides features for a card body."),
 *   weight = 0,
 * )
 */
class A12sCardBodyBehavior extends ParagraphsBehaviorBase {

  use A12sDisplayBehaviorFormTrait;
  use A12sBehaviorTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() === 'card_body';
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
    $title_tag = $paragraph->getBehaviorSetting($this->getPluginId(), 'title_tag', 'div');

    if (!empty($variables['content']['field_card_title'])) {
      $variables['content']['field_card_title']['#prefix'] = '<' . $title_tag . ' class="card-title">';
      $variables['content']['field_card_title']['#suffix'] = '</' . $title_tag . '>';
    }

    if (!empty($variables['content']['field_card_links'])) {
      foreach (Element::children($variables['content']['field_card_links']) as $delta) {
        $item = &$variables['content']['field_card_links'][$delta];
        $item['#options']['attributes']['class'][] = 'card-link';
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['title_tag'] = [
      '#type' => 'select',
      '#title' => $this->t('Title tag'),
      '#description' => $this->t('The HTML tag to use for the card title.'),
      '#options' => $this->headingOptions(),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'title_tag', 'div'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [$this->t('Card body')];
  }

}
