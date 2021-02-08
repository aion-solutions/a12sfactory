<?php

namespace Drupal\a12sfactory\Plugin\paragraphs\Behavior;

use Drupal\a12sfactory\Form\A12sDisplayBehaviorFormTrait;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * A12s behavior plugin for paragraph entities.
 *
 * @ParagraphsBehavior(
 *   id = "a12sfactory_card",
 *   label = @Translation("Card component"),
 *   description = @Translation("Provides features for a card component."),
 *   weight = 0,
 * )
 */
class A12sCardBehavior extends ParagraphsBehaviorBase {

  use A12sDisplayBehaviorFormTrait;
  use A12sBehaviorTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() === 'card';
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $settings = $paragraph->getBehaviorSetting($this->getPluginId(), ['card'], []);

    $build['#attributes']['class'][] = 'card';

    if (!empty($settings['background'])) {
      $build['#attributes']['class'][] = $settings['background'];
    }

    if (!empty($settings['border'])) {
      $build['#attributes']['class'][] = strtr($settings['border'], ['bg-' => 'border-']);
    }

    if (!empty($settings['text_color'])) {
      $build['#attributes']['class'][] = strtr($settings['text_color'], ['bg-' => 'text-']);
    }

    if (!empty($settings['text_align'])) {
      $build['#attributes']['class'][] = $settings['text_align'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {
    parent::preprocess($variables);

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];
    $settings = $this->getSettings($paragraph);

    if (!empty($variables['content']['field_card_header'][0])) {
      $variables['content']['field_card_header']['#prefix'] = '<' . $settings['header_tag'] . ' class="card-header">';
      $variables['content']['field_card_header']['#suffix'] = '</' . $settings['header_tag'] . '>';
    }

    if (!empty($variables['content']['field_card_footer'][0])) {
      $variables['content']['field_card_footer']['#prefix'] = '<div class="card-footer">';
      $variables['content']['field_card_footer']['#suffix'] = '</div>';
    }

    $variables['image_position'] = $settings['image_position'] ?? 'top';

    if (!empty($variables['content']['image_field'][0])) {
      switch ($settings['image_position']) {
        case 'overlay':
          $variables['content']['image_field']['#attributes']['class'][] = 'card-img';
          $variables['image_overlay_attributes'] = new Attribute(['class' => 'card-img-overlay']);

          if (!empty($settings['image_overlay_classes'])) {
            $image_overlay_classes = array_map('trim', preg_split('/s+/', $settings['image_overlay_classes']));

            foreach (array_filter($image_overlay_classes) as $class) {
              $variables['image_overlay_attributes']->addClass($class);
            }
          }
          break;
        case 'bottom':
          $variables['content']['image_field']['#attributes']['class'][] = 'card-img-bottom';
          break;
        case 'top':
        default:
          $variables['content']['image_field']['#attributes']['class'][] = 'card-img-top';
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings($paragraph);

    $form['card'] = [
      '#type' => 'details',
      '#title' => $this->t('Card settings'),
    ];

    $form['card']['header_tag'] = [
      '#type' => 'select',
      '#title' => $this->t('Header tag'),
      '#description' => $this->t('The HTML tag to use for the card header.'),
      '#options' => $this->headingOptions(),
      '#default_value' => $settings['header_tag'],
      '#required' => TRUE,
    ];

    $form['card']['width'] = [
      '#type' => 'select',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Force the card width.'),
      '#options' => [
        'w-25' => '25%',
        'w-50' => '50%',
        'w-75' => '75%',
        'w-100' => '100%',
      ],
      '#empty_option' => $this->t('Flexible width'),
      '#default_value' => $settings['title_tag'],
    ];

    $form['card']['text_align'] = [
      '#type' => 'select',
      '#title' => $this->t('Text alignment'),
      '#description' => $this->t('Force text alignment for the all card.'),
      '#options' => [
        'text-left' => $this->t('Align left', [], ['context' => 'Text']),
        'text-center' => $this->t('Center text', [], ['context' => 'Text']),
        'text-right' => $this->t('Align right', [], ['context' => 'Text']),
        'text-justify' => $this->t('Justify text', [], ['context' => 'Text']),
      ],
      '#empty_option' => $this->t('Inherit'),
      '#default_value' => $settings['text_align'],
    ];

    $form['card']['image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image position'),
      '#description' => $this->t('Defines the position of the image inside the card.'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
        'overlay' => $this->t('Overlay'),
      ],
      '#default_value' => $settings['image_position'],
      '#required' => TRUE,
    ];

    $form['card']['image_overlay_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image overlay classes'),
      '#description' => $this->t('Defines the extra classes for the image overlay wrapper.'),
      '#default_value' => $settings['image_overlay_classes'],
    ];

    // @todo allow to choose image formatter.
    $form['card']['background'] = $this->backgroundStyleElement(NULL, $settings['background']);
    $form['card']['border'] = $this->backgroundStyleElement($this->t('Border color'), $settings['border']);
    $form['card']['text_color'] = $this->backgroundStyleElement($this->t('Text color'), $settings['text_color']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [$this->t('Card component')];
  }

  /**
   * Get the settings attached to given paragraph, completed by default values.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *
   * @return array
   */
  protected function getSettings(ParagraphInterface $paragraph): array {
    return $paragraph->getBehaviorSetting($this->getPluginId(), ['card'], []) + [
        'header_tag' => 'div',
        'width' => '',
        'text_align' => '',
        'image_position' => 'top',
        'image_overlay_classes' => '',
        'background' => '',
        'border' => '',
        'text_color' => '',
      ];
  }

}
