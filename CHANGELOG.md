# 5.0.0-alpha1

- The module `media_thumbnail_formatters` has been removed, as it is a fossiled
dependency. Please use the `Blazy` formatters instead.
- The remaining `Slick` related elements have been removed.

## Aïon specific configuration

All files related to Aïon specific configuration have been moved to the `5.x`
branch of the https://github.com/aion-solutions/a12sfactory-project repository,
so the `a12sfactory` profile is totally independent.

All Aïon specific scaffolding files and configurations have been removed and
delegated to the above repository.

## Packages removed from composer

- `drupal/access_unpublished`
- `drupal/ace_editor`
- `drupal/admin_toolbar`
- `drupal/antibot`
- `drupal/better_exposed_filters`
- `drupal/blazy`
- `drupal/block_field`
- `drupal/captcha`
- `drupal/ckeditor_media_embed`
- `drupal/components`
- `drupal/config_ignore`
- `drupal/config_perms`
- `drupal/crop`
- `drupal/diff`
- `drupal/draggableviews`
- `drupal/dropzonejs`
- `drupal/email_registration`
- `drupal/embed`
- `drupal/entity`
- `drupal/entity_browser`
- `drupal/entity_browser_enhanced`
- `drupal/entity_embed`
- `drupal/entity_reference_revisions`
- `drupal/eu_cookie_compliance`
- `drupal/extlink`
- `drupal/facets`
- `drupal/field_group`
- `drupal/focal_point`
- `drupal/honeypot`
- `drupal/imagemagick`
- `drupal/inline_entity_form`
- `drupal/layout_paragraphs`
- `drupal/libraries`
- `drupal/linkit`
- `drupal/login_destination`
- `drupal/masquerade`
- `drupal/maxlength`
- `drupal/menu_breadcrumb`
- `drupal/menu_item_extras`
- `drupal/menu_link_attributes`
- `drupal/metatag`
- `drupal/node_edit_protection`
- `drupal/oembed_providers`
- `drupal/override_node_options`
- `drupal/paragraph_view_mode`
- `drupal/paragraphs`
- `drupal/password_policy`
- `drupal/pathauto`
- `drupal/pathologic`
- `drupal/rabbit_hole`
- `drupal/realname`
- `drupal/recaptcha`
- `drupal/redirect`
- `drupal/robotstxt`
- `drupal/roleassign`
- `drupal/schema_metatag`
- `drupal/search_api`
- `drupal/select_or_other`
- `drupal/simple_sitemap`
- `drupal/smart_trim`
- `drupal/spamspan`
- `drupal/swiper_formatter`
- `drupal/taxonomy_access_fix`
- `drupal/twig_tweak`
- `drupal/userprotect`
- `drupal/views_bulk_edit`
- `drupal/views_bulk_operations`
- `drupal/views_field_formatter`
- `drupal/views_infinite_scroll`
- `drupal/viewsreference`
- `drupal/webform`
- `drupal/webp`
- `npm-asset/blazy`
- `npm-asset/dropzone`
- `npm-asset/imagesloaded`
- `npm-asset/jquery.easing`
- `webflo/drupal-finder`
- `wikimedia/composer-merge-plugin`

The `merge` plugin has been removed, as it is abandoned for a while. This means
that the dependencies usually installed for the Webform module using its
`composer.libraries.json` file need to be handled in another way.

## Upgrade notes

Before upgrading to 5.0.0-alpha1, you need to update all displays and views that
use the formatter provided by the `media_thumbnail_formatters` module. Then you
need to uninstall the `media_thumbnail_formatters` module from your site.

# 4.0.0-alpha2

- Drop the `auto_entitylabel` module, which ECA can replace.
- The "SEO Manager" role is no more created.
- Remove the `Slick` and `Sliwk views` modules, so if you depend on it, you
  should require `drupal/slick` and `drupal/slick_views` in the root composer
  file. Note that Slick relies on jQuery, which is going to be removed by Drupal
  core in the future.
  - Remove the `slider` block type.
  - Remove the `slider_media_browser` entity browser.
  - Remove the `slick` and `hero_slide` view modes for media entity.
- Add a new dependency on [Swiper formatter](https://www.drupal.org/project/swiper_formatter),
  as a Carousel engine.


# Migrate to 4.x

Important: you need to migrate from the 3.x version first!
The 3.x version takes care of migrating the deprecated paragraph behaviors to
the paragraph layout system.


# Migrate to 3.x

The 3.x version introduces big changes in the way the paragraphs are used, as it
relies now on the Layout module.

## Behavior migration

The old behaviors should be converted to the new display options. For this, you
first need to create and configure the
display options for each paragraph type and define the global configuration.
Once done, you can run the following commands, according to your real
configuration:

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
- `mailsystem`
- `structure_sync`
- `swiftmailer`
- `view_mode_selector`

You may need to add those to your root `composer.json` file.

### Administration theme

The "a12sfactory_admin" theme has been removed and should be replaced by "claro"
or any other theme of your choice.

### Paragraph behaviors

All the old paragraph behaviors have been removed:
- card
- card_body
- cards
- display
- grid
- parallax

So before moving to this version, you need to ensure this will not break
existing features.

### Form elements

The following form elements have been removed:
- css_background_position
- css_background_size
- select_default_custom

### Background images

The "Background image" service has been removed and all related features too.
This implies updating or removing all code that may rely on this service.

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
