# A12S LAYOUT

## REQUIREMENTS

This module requires at least PHP 8.0.

## INSTALLATION

Install this module as any other Drupal module, see the documentation on
[Drupal.org](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

## CONFIGURATION

@todo

## COMMANDS

### Create a new "paragraph_layout" field

Creates the new "paragraph_layout" field, based on an existing paragraphs reference field and an existing source field.

`drush a12s_layout:create_paragraph_layout_field entity_type bundle source_field`

### Migrate from View Mode Selector to Paragraph View Mode

`drush a12s_layout:migrate_view_mode_selector_to_paragraph_view_mode`

### Migrate paragraphs

`drush a12s_layout:migrate_paragraphs entity_type bundle source_field`

The command can be executed for a single entity :

`drush a12s_layout:migrate_paragraphs entity_type bundle source_field entity_id`
