<?php

namespace Drupal\a12s_layout\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_paragraphs\Form\EditComponentForm;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;

/**
 * Class LayoutParagraphEditDisplayOptionsForm.
 *
 * Builds the edit form for the display options of a paragraphs paragraph entity.
 */
class LayoutParagraphEditDisplayOptionsForm extends EditComponentForm {

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, LayoutParagraphsLayout $layout_paragraphs_layout = NULL, string $component_uuid = NULL): array {
    $form['#display_options'] = TRUE;
    return parent::buildForm($form, $form_state, $layout_paragraphs_layout, $component_uuid);
  }

  /**
   * {@inheritDoc}
   */
  protected function formTitle(): TranslatableMarkup|string {
    return $this->t('Edit display options');
  }

  /**
   * {@inheritDoc}
   */
  protected function buildComponentForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildComponentForm($form, $form_state);

    if (!empty($form['#display_options'])) {
      $display = EntityFormDisplay::collectRenderDisplay($this->paragraph, 'default');

      foreach ($display->getComponents() as $key => $component) {
        //unset($form[$key]);
        if (isset($form[$key])) {
          $form[$key]['#access'] = FALSE;
        }
      }
    }

    return $form;
  }

}
