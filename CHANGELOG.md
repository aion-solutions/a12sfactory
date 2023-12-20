# Migrate to 4.x

Important: you need to migrate from the 3.x version first!
The 3.x version takes care of migrating the deprecated paragraph behaviors to the paragraph layout system.


# Migrate to 3.x

The 3.x version introduces big changes in the way paragraph are used, as it relies now on Layout module.

## Behavior migration

The old behaviors should be converted to the new display options. For this, you first need to create and configure the
display options for each paragraph types, and define the global configuration.
Once done, you can run the following commands, according to your real configuration:

```shell
drush a12s_layout:migrate_view_mode_selector_to_paragraph_view_mode

drush a12s_layout:create_paragraph_layout_field node page field_page_paragraphs
drush a12s_layout:migrate_paragraphs node page field_page_paragraphs

drush a12s_layout:create_paragraph_layout_field node news field_page_paragraphs
drush a12s_layout:migrate_paragraphs node news field_page_paragraphs

drush a12s_layout:create_paragraph_layout_field node hero_slide field_page_paragraphs
drush a12s_layout:migrate_paragraphs node hero_slide field_page_paragraphs

drush a12s_layout:create_paragraph_layout_field block_content complex field_paragraphs
drush a12s_layout:migrate_paragraphs block_content complex field_paragraphs
```

## Breaking changes

### Removed dependencies

The following modules have been removed from the profile dependencies:
- mailsystem
- structure_sync
- swiftmailer
- view_mode_selector

You may need to add those to your root `composer.json` file.

### Administration theme

The "a12sfactory_admin" theme has been removed, and should be replaced by "claro" or any other theme of your choice.

### Paragraph behaviors

All the old paragraph behaviors have been removed:
- card
- card_body
- cards
- display
- grid
- parallax

So before moving to this version, you need to ensure this will not break existing features.

### Form elements

The following form elements have been removed:
- css_background_position
- css_background_size
- select_default_custom

### Background images

The "Background image" service has been removed and all related features too. This implies to update or remove all
code which may rely on this service.

## Removed features

### Paragraph types

The following paragraph types have been removed:
- accordion
- accordion_section
- card
- card_body
- cards
- columns
- columns_single
- columns_three_uneven
- columns_two_uneven
- modal

## Required updates in custom themes

You need to change the namespace "@a12sfactory" to "@a12s_layout" in all TWIG templates.

Boostrap framework is abandoned, so several paragraph types will no more work:
- paragraph__cards
- paragraph__card
- paragraph__card_body
- paragraph__grid_row

The following field template overrides are also removed:
- field__field_card_list_items
- field__field_card_links

@todo
  - Remove behavior configurations?
