<?php

namespace Drupal\a12s_layout\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines Display Template annotation object.
 *
 * @Annotation
 */
class A12sLayoutDisplayTemplate extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * The description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The human-readable category.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @see \Drupal\Component\Plugin\CategorizingPluginManagerInterface
   *
   * @ingroup plugin_translatable
   */
  public Translation $category;

}
