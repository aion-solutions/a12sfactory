<?php

namespace Drupal\a12s_layout\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides display options plugin definitions for a layout.
 *
 * @see \Drupal\a12s_layout\Plugin\A12sLayoutDisplayTemplate\Layout
 */
class Layout extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The layout plugin manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected LayoutPluginManagerInterface $layoutPluginManager;

  /**
   * Constructs new Layout instance.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layoutPluginManager
   *   The layout discovery service.
   */
  public function __construct(LayoutPluginManagerInterface $layoutPluginManager) {
    $this->layoutPluginManager = $layoutPluginManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    foreach ($this->layoutPluginManager->getGroupedDefinitions() as $category => $layoutDefinitions) {
      foreach ($layoutDefinitions as $name => $layoutDefinition) {
        if ($name === 'layout_builder_blank') {
          continue;
        }

        $this->derivatives[$name] = $base_plugin_definition;
        $this->derivatives[$name]['label'] = $layoutDefinition->getLabel();
        $this->derivatives[$name]['category'] = $base_plugin_definition['label'];
        $this->derivatives[$name]['subcategory'] = $category;
        $this->derivatives[$name]['layout'] = $name;
      }
    }

    return $this->derivatives;
  }

}
