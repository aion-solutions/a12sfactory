<?php

namespace Drupal\a12s_layout\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface;

/**
 * Provides an interface defining a display options instance entity type.
 */
interface DisplayOptionsInstanceInterface extends ConfigEntityInterface {

  /**
   * Whether the instance has a defined plugin.
   *
   * @return bool
   */
  public function hasPlugin(): bool;

  /**
   * Get the plugin ID.
   *
   * @return string
   *   The plugin ID for this configuration.
   */
  public function getPluginId(): string;

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface
   *   The plugin instance for this configuration.
   */
  public function getPlugin(): DisplayTemplatePluginInterface;

  /**
   * Get the related Display Template, if applicable.
   *
   * @return \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface|null
   */
  public function getTemplate(): DisplayTemplatePluginInterface|null;

}
