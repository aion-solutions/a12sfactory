<?php

namespace Drupal\a12s_layout\Entity;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface;

/**
 * Defines the display options instance entity type.
 *
 * @ConfigEntityType(
 *   id = "a12s_layout_display_options",
 *   label = @Translation("Display Options instance"),
 *   label_collection = @Translation("Display Options instances"),
 *   label_singular = @Translation("display options instance"),
 *   label_plural = @Translation("display options instances"),
 *   label_count = @PluralTranslation(
 *     singular = "@count display options instance",
 *     plural = "@count display options instances",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\a12s_layout\DisplayOptions\InstanceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\a12s_layout\Form\DisplayOptionsInstanceForm",
 *       "edit" = "Drupal\a12s_layout\Form\DisplayOptionsInstanceForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "a12s_layout_display_options",
 *   admin_permission = "administer a12s layout display options",
 *   links = {
 *     "collection" = "/admin/structure/a12s-layout-display-options-instance",
 *     "add-form" = "/admin/structure/a12s-layout-display-options-instance/add",
 *     "edit-form" = "/admin/structure/a12s-layout-display-options-instance/{a12s_layout_display_options}",
 *     "delete-form" = "/admin/structure/a12s-layout-display-options-instance/{a12s_layout_display_options}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "provider",
 *     "plugin",
 *     "optionsSets",
 *     "settings"
 *   }
 * )
 */
class DisplayOptionsInstance extends ConfigEntityBase implements DisplayOptionsInstanceInterface {

  /**
   * The display options instance ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The display options instance status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected string $plugin;

  /**
   * The list of display options sets.
   *
   * @var array
   */
  protected array $optionsSets = [];

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected array $settings = [];

  /**
   * The plugin collection that holds the plugins for this entity.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected DefaultSingleLazyPluginCollection $pluginCollection;

  /**
   * The related display template.
   *
   * @var \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface|null|false
   */
  protected DisplayTemplatePluginInterface|false|null $template;

  /**
   * {@inheritDoc}
   */
  public function label() {
    $definition = $this->getPlugin()->getPluginDefinition();
    return implode(': ', [
      (string) $definition['category'],
      (string) $definition['label']
    ]);
  }

  /**
   * {@inheritDoc}
   */
  public function hasPlugin(): bool {
    return !empty($this->plugin);
  }

  /**
   * {@inheritDoc}
   */
  public function getPluginId(): string {
    return $this->plugin;
  }

  /**
   * {@inheritDoc}
   */
  public function getPlugin(): DisplayTemplatePluginInterface {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritDoc}
   */
  public function getTemplate(): DisplayTemplatePluginInterface|null {
    if (!isset($this->template)) {
      $this->template = FALSE;

      if ($this->hasPlugin()) {
        try {
          // @todo wait for https://www.drupal.org/project/drupal/issues/2142515 in
          //   order to use dependency injection in entities.
          /** @var \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginManager $thDisplayTemplateManager */
          $thDisplayTemplateManager = \Drupal::service('plugin.manager.a12s_layout_display_template');
          $this->template = $thDisplayTemplateManager->createInstance($this->getPluginId());
        }
        catch (PluginException $e) {
          watchdog_exception('a12s_layout', $e);
        }
      }
    }

    return $this->template ?: NULL;
  }

  /**
   * Encapsulates the creation of the display options' LazyPluginCollection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The plugin collection.
   */
  protected function getPluginCollection(): DefaultSingleLazyPluginCollection {
    if (!isset($this->pluginCollection)) {
      $this->pluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.a12s_layout_display_template'), $this->plugin, $this->get('settings'));
    }
    return $this->pluginCollection;
  }

}
