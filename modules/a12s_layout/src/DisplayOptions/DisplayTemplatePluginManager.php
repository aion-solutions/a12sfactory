<?php

namespace Drupal\a12s_layout\DisplayOptions;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Display Template plugin manager.
 */
class DisplayTemplatePluginManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * The Display Options storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $thDisplayOptionsStorage;

  /**
   * Constructs "a12s layout display options" PluginManager object.
   *
   * {@inheritDoc}
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $thDisplayOptionsStorage
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler/*,
    EntityTypeManagerInterface $entityTypeManager*/
  ) {
    parent::__construct(
      'Plugin/A12sLayoutDisplayTemplate',
      $namespaces,
      $module_handler,
      'Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface',
      'Drupal\a12s_layout\Annotation\A12sLayoutDisplayTemplate'
    );
    $this->alterInfo('a12s_layout_display_template_info');
    $this->setCacheBackend($cache_backend, 'a12s_layout_display_template_plugins');
    //$this->thDisplayOptionsStorage = $entityTypeManager->getStorage('a12s_layout_display_options');
  }

  /**
   * {@inheritDoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $this->processDefinitionCategory($definition);
  }

  /**
   * {@inheritDoc}
   */
  protected function processDefinitionCategory(&$definition) {
    // Ensure that every plugin has a category.
    if (empty($definition['category'])) {
      // Default to the label.
      $definition['category'] = $definition['label'];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface
   *   A fully configured plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = []): DisplayTemplatePluginInterface {
    $instance = parent::createInstance($plugin_id, $configuration);

    if ($instance instanceof DisplayTemplatePluginInterface) {
      return $instance;
    }

    throw new PluginException(sprintf('The plugin (%s) did not implement the expected interface "%s".', $plugin_id, DisplayTemplatePluginInterface::class));
  }

  /**
   * Preprocess variables for a specific template.
   *
   * @param string $name
   *   The template/theme name.
   * @param string $pluginId
   *   The Display Template plugin ID.
   * @param array $displayOptions
   *   The stored display options for the given template.
   * @param array $variables
   *   The variables to preprocess.
   *
   * @return void
   */
  public static function preprocessVariables(string $pluginId, string $name, array $displayOptions, array &$variables): void {
    /** @var \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginManager $thDisplayTemplateManager */
    $thDisplayTemplateManager = \Drupal::service('plugin.manager.a12s_layout_display_template');

    try {
      if ($layoutDisplayTemplate = $thDisplayTemplateManager->createInstance($pluginId)) {
        $layoutDisplayTemplate->executeTemplatePreprocessing($name, $displayOptions, $variables);
      }
    }
    catch (PluginException $e) {
      watchdog_exception('a12s_layout', $e);
    }
  }

}
