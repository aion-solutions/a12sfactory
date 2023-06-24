<?php

namespace Drupal\a12s_layout\DisplayOptions;

use Drupal\a12s_layout\Entity\DisplayOptionsInstanceInterface;
use Drupal\Component\Plugin\DependentPluginInterface;

/**
 * Interface for Display Template plugins.
 */
interface DisplayTemplatePluginInterface extends DependentPluginInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Get the related template object.
   *
   * It may be a Layout instance, a Paragraph entity type.
   *
   * @return mixed
   */
  public function getTemplateObject(): mixed;

  /**
   * Get the Display Options instance for the given template.
   *
   * @return \Drupal\a12s_layout\Entity\DisplayOptionsInstanceInterface|null
   */
  public function getDisplayOptionsInstance(): ?DisplayOptionsInstanceInterface;

  /**
   * Get a list of "options set" plugin instances.
   *
   * @return \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetInterface[]
   */
  public function getOptionsSets(): array;

  /**
   * Preprocess variables for a specific template.
   *
   * @param string $name
   *   The template/theme name.
   * @param array $displayOptions
   *   The stored display options for the given template.
   * @param array $variables
   *   The variables to preprocess.
   *
   * @return void
   */
  public function executeTemplatePreprocessing(string $name, array $displayOptions, array &$variables): void;

}
