<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;

/**
 * Plugin implementation of Display Options Set for Grid settings.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "width",
 *   label = @Translation("Width"),
 *   description = @Translation("Define an element width."),
 *   category = @Translation("Size and spacing"),
 *   applies_to = {"layout", "paragraph"},
 *   target_template = "paragraph"
 * )
 */
class Width extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'container' => '',
      'container_remove_padding' => FALSE,
      //'offset' => '',
      'width' => '',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    parent::preprocessVariables($variables, $configuration);
    $configuration += ['width' => ''];

    $hasWidth = !empty($configuration['width']);
    /** @var ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];

    if (!isset($configuration['container']) || $configuration['container'] === '') {
      $configuration['container'] = $this->isParagraphNested($paragraph) ? 'no' : 'container';
    }

    // Special case: no container, but a grid (related to "width" option).
    // @todo Apply only on root paragraphs? Or check the full hierarchy?
    if ($configuration['container'] === 'no' && $hasWidth) {
      $configuration['container'] = 'container-full';
    }

    $variables['add_container'] = $configuration['container'] !== 'no';

    if ($configuration['container'] !== 'no') {
      $variables['container_attributes']['class'][] = $configuration['container'];

      if (!empty($configuration['container_remove_padding'])) {
        // @todo allow to configure the class! And to override this per
        //   breakpoint.
        $variables['container_attributes']['class'][] = 'px-0';
      }
    }

    if ($hasWidth) {
      // @todo make this configurable and use breakpoints!
      // @todo allow to configure "offset".
      $classes = match ($configuration['width']) {
        'tiny' => ['col-sm-8', 'offset-sm-2', 'col-md-4', 'offset-md-4'],
        'narrow' => ['col-sm-10', 'offset-sm-1', 'col-md-6', 'offset-md-3'],
        'medium' => ['col-md-8', 'offset-md-2'],
        'wide' => ['col-md-10', 'offset-md-1'],
        default => [],
      };

      if ($classes) {
        $variables += ['add_container' => TRUE, 'column_attributes' => []];
        // @todo Remove this after a12sfactory behaviors are deleted..
        if ($variables['column_attributes'] instanceof \Drupal\Core\Template\Attribute) {
          $variables['column_attributes'] = $variables['column_attributes']->toArray();
        }

        $variables['column_attributes'] += ['class' => []];
        $variables['add_grid'] = TRUE;
        $variables['column_attributes']['class'] = array_merge($variables['column_attributes']['class'], $classes);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    $form['#type'] = 'container';
    $paragraph = &$this->configuration['paragraph'];
    $paragraphIsNested = $this->isParagraphNested($paragraph);
    $emptyOption = $paragraphIsNested ? $this->t('Default (no container)') : $this->t('Default (add container)');

    $form['container'] = [
      '#type' => 'select',
      '#title' => $this->t('Container size'),
      '#empty_option' => $emptyOption,
      '#default_value' => $values['container'] ?? '',
      '#options' => [
        'no' => $this->t('No container'),
        'container' => $this->t('Default container'),
        'container-medium' => $this->t('Medium container'),
        'container-wide' => $this->t('Wide container'),
        'container-full' => $this->t('Full container'),
      ],
    ];

    $form['container_remove_padding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove container horizontal padding'),
      '#default_value' => $values['container_remove_padding'] ?? FALSE,
    ];

    $form['width'] = [
      '#type' => 'select',
      '#title' => $this->t('Width'),
      '#empty_option' => $this->t('- Default -'),
      '#default_value' => $values['width'] ?? '',
      '#options' => [
        'tiny' => $this->t('Tiny'),
        'narrow' => $this->t('Narrow'),
        'medium' => $this->t('Medium'),
        'wide' => $this->t('Wide'),
        'full' => $this->t('Full width'),
        //'edge2edge' => $this->t('Edge to edge'),
      ],
    ];

    return $form;
  }


  protected function isParagraphNested(mixed $paragraph): bool {
    $paragraphIsNested = $paragraph instanceof ParagraphInterface && $paragraph->getParentEntity() instanceof ParagraphInterface;
    return $paragraphIsNested || $paragraph->getBehaviorSetting('layout_paragraphs', 'parent_uuid') !== NULL;
  }

}
