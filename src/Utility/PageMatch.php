<?php

namespace Drupal\a12sfactory\Utility;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Condition\ConditionInterface;

/**
 * Class ThemeManager
 */
class PageMatch implements PageMatchInterface {

  /**
   * The plugin factory.
   */
  protected FactoryInterface $pluginFactory;

  /**
   * The "request_path" condition plugin instance.
   */
  protected ConditionInterface $requestPathCondition;

  /**
   * @inheritDoc
   */
  public function __construct(FactoryInterface $pluginFactory) {
    $this->pluginFactory = $pluginFactory;
  }

  /**
   * @inheritDoc
   */
  public function getRequestPathCondition(): ConditionInterface {
    if (!isset($this->requestPathCondition)) {
      $this->requestPathCondition = $this->pluginFactory->createInstance('request_path');
    }

    return $this->requestPathCondition;
  }

  /**
   * @inheritDoc
   */
  public function pageMatchEvaluate(string $pages, bool $negate = FALSE): bool {
    try {
      $condition = $this->getRequestPathCondition();
      $condition->setConfiguration(['pages' => $pages, 'negate' => $negate]);
      return $condition->evaluate();
    }
    catch (PluginException $e) {
      watchdog_exception('a12sfactory', $e);
    }

    return FALSE;
  }

}
