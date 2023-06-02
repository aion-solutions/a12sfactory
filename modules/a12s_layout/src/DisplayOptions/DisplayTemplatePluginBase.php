<?php

namespace Drupal\a12s_layout\DisplayOptions;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase as BasePlugin;
use Drupal\a12s_layout\Entity\DisplayOptionsInstanceInterface;

/**
 * Base class for display template plugins.
 */
abstract class DisplayTemplatePluginBase extends BasePlugin implements DisplayTemplatePluginInterface {

  /**
   * The related Display Options instance.
   *
   * @var \Drupal\a12s_layout\Entity\DisplayOptionsInstanceInterface|null|false
   */
  protected DisplayOptionsInstanceInterface|false|null $doInstance;

  /**
   * {@inheritDoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * Get the Display Options instance for the given layout.
   *
   * @return \Drupal\a12s_layout\Entity\DisplayOptionsInstanceInterface|null
   */
  public function getDisplayOptionsInstance(): ?DisplayOptionsInstanceInterface {
    if (!isset($this->doInstance)) {
      /** @var \Drupal\a12s_layout\Entity\DisplayOptionsInstanceInterface[] $doInstances */
      $doInstances = $this->thDisplayOptionsStorage->loadByProperties(['plugin' => $this->getPluginId()]);
      // We can only have one instance per plugin.
      $this->doInstance = reset($doInstances);
    }

    return $this->doInstance ?: NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function executeTemplatePreprocessing(string $name, array $displayOptions, array &$variables): void {
    foreach ($this->getOptionsSets() as $optionsSetId => $optionsSet) {
      if ($optionsSet->appliesToTemplate($name)) {
        $optionsSet->preprocessVariables($variables, $displayOptions[$optionsSetId] ?? []);
      }
    }
  }

}
