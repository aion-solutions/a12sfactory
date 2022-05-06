<?php

namespace Drupal\a12sfactory\Utility;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Condition\ConditionInterface;

/**
 * Interface ThemeManagerInterface
 */
interface PageMatchInterface {

  /**
   * Creates a new PageMatch instance.
   *
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $pluginFactory
   *   The plugin factory.
   */
  public function __construct(FactoryInterface $pluginFactory);

  /**
   * Get the "request_path" condition plugin instance.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getRequestPathCondition(): ConditionInterface;

  /**
   * Check whether the current page match any of the specified pages.
   *
   * @param string $pages
   * @param bool $negate
   *
   * @return bool
   */
  public function pageMatchEvaluate(string $pages, bool $negate = FALSE): bool;

}
