<?php

namespace Drupal\a12s_layout\DisplayOptions;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\a12s_layout\Annotation\A12sLayoutDisplayOptionsSet;

/**
 * A12sLayoutDisplayOptionsSet plugin manager.
 */
class DisplayOptionsSetPluginManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * Constructs A12sLayoutDisplayOptionsSetPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    protected TransliterationInterface $transliteration)
  {
    parent::__construct('Plugin/A12sLayoutDisplayOptionsSet', $namespaces, $module_handler, DisplayOptionsSetInterface::class, A12sLayoutDisplayOptionsSet::class);
    $this->alterInfo('a12s_layout_display_options_set_info');
    $this->setCacheBackend($cache_backend, 'a12s_layout_display_options_set_plugins');
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

    if (empty($definition['category_id'])) {
      $value = $this->transliteration->transliterate((string) $definition['category'], 'en', '_');
      $value = strtolower($value);
      $value = preg_replace('/[^a-z\d_]+/', '_', $value);
      $value = trim($value, '_');
      $definition['category_id'] = preg_replace('/_+/', '_', $value);
    }
  }

}
