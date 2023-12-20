<?php
/**
 * @file
 * Post update functions for the A12S Factory profile.
 */

/**
 * Install field storage for the new paragraphs fields.
 *
 * @see a12sfactory_update_8004()
 */
function a12sfactory_post_update_8004(): void {
  \Drupal::moduleHandler()->loadInclude('a12sfactory', 'install');
  a12sfactory_update_field_storage(
    'paragraph',
    [
      'field_card_footer',
      'field_card_header',
      'field_card_links',
      'field_card_list_items',
      'field_card_paragraphs',
      'field_card_title',
      'field_cards',
      'field_cards_title',
    ]
  );
}

/**
 * Install field storage for the new Image link field for Bootstrap card.
 *
 * @see a12sfactory_update_8005()
 */
function a12sfactory_post_update_8005(): void {
  \Drupal::moduleHandler()->loadInclude('a12sfactory', 'install');
  a12sfactory_update_field_storage('paragraph', ['field_card_image_link']);
}

/**
 * Handle Blazy update to 2.x.
 */
function a12sfactory_post_update_8006(): void {
  // Clear cache, otherwise config changes are not applied.
  drupal_flush_all_caches();
}
