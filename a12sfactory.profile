<?php
/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

use Drupal\paragraphs\ParagraphInterface;

/**
 * Implements hook_preprocess_html().
 */
function a12sfactory_preprocess_html(&$variables): void {
  if (!empty($variables['page_top']['toolbar']) && (bool) \Drupal::request()->query->get('hide_toolbar') === TRUE) {
    $variables['page_top']['toolbar']['#access'] = FALSE;
  }
}

/**
 * @see hook_slick_skins_info()
 */
function a12sfactory_slick_skins_info(): string {
  return '\\Drupal\\a12sfactory\\Slick\\A12sfactorySlickSkin';
}

/**
 * Forked from the issue below. Integrated in the profile as it makes the
 * upgrade to the final way of handling translations that will be chosen by the
 * maintainers of Paragraph and ERR modules.
 *
 * This is a simplified version, as we always want to synchronise translations
 * for all translatable paragraphs. Having a single and defined workflow ensure
 * compatibility with upgrades.
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph entity.
 *
 * @see https://www.drupal.org/project/paragraphs/issues/2887353
 *
 * @ingroup "Paragraph symmetric translations"
 *
 * @todo Use the new patch from added on April 4, 2023? It defines a new module
 *   that handles the expected feature, but it would need a hook_update_N()
 *   function to install this module and enable the synchronization in all
 *   existing paragraph types, so the existing behavior is kept.
 *   https://www.drupal.org/project/paragraphs/issues/2887353#comment-14997517
 *
 * @see hook_ENTITY_TYPE_insert()
 */
function a12sfactory_paragraph_insert(ParagraphInterface $paragraph): void {
  \Drupal::service('a12sfactory.paragraphs_translation_synchronization')->deferSync($paragraph);
}

/**
 * Allow simple text field to use a textarea to enter multilines text.
 *
 * @see hook_field_widget_info_alter()
 */
function a12sfactory_field_widget_info_alter(array &$info): void {
  $info['text_textarea']['field_types'][] = 'text';
  $info['string_textarea']['field_types'][] = 'string';
}
