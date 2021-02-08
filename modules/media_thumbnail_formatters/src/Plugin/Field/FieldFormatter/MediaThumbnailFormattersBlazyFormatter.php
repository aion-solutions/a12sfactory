<?php

namespace Drupal\media_thumbnail_formatters\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatter;

/**
 * Plugin implementation of the 'media_thumbnail_formatters_blazy' formatter.
 *
 * @FieldFormatter(
 *   id = "media_thumbnail_formatters_blazy",
 *   label = @Translation("Blazy thumbnail (deprecated, use Blazy)"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 *
 * @deprecated change all existing display to use "Blazy" formatter instead, as
 * the latest version handles correctly the Media entity.
 */
class MediaThumbnailFormattersBlazyFormatter extends BlazyMediaFormatter { }
