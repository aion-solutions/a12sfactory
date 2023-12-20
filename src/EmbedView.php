<?php

namespace Drupal\a12sfactory;

use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Class EmbedView
 */
class EmbedView {

  /**
   * The view ID.
   *
   * @var string
   */
  protected string $view_id;

  /**
   * The display ID.
   *
   * @var string
   */
  protected string $display_id;

  /**
   * The view arguments.
   *
   * @var array
   */
  protected array $arguments;

  /**
   * EmbedView constructor.
   *
   * @param $view_id
   *   The view ID to load.
   * @param string $display_id
   *   The display id to embed. If unsure, use 'default', as it will always be
   *   valid. But things like 'page' or 'block' should work here.
   * @param ...
   *   Any additional parameters will be passed as view arguments.
   */
  public function __construct($view_id, string $display_id = 'default') {
    $this->view_id = $view_id;
    $this->display_id = $display_id;

    // Remove $view_id and $display_id from the arguments.
    $args = func_get_args();
    unset($args[0], $args[1]);

    $this->arguments = $args;
  }

  /**
   * Get the view instance.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   A view executable instance, from the loaded entity.
   */
  protected function getView(): ?ViewExecutable {
    if (\Drupal::service('module_handler')->moduleExists('views')) {
      return Views::getView($this->view_id);
    }

    return NULL;
  }

  /**
   * Loads a view from configuration and returns a render array if applicable.
   *
   * @return array|null
   *   A renderable array containing the view title and output or NULL if the
   *   display ID of the view to be executed doesn't exist.
   */
  public function build(): ?array {
    $view = $this->getView();

    if ($view && $view->access($this->display_id)) {
      // Set display, so we get the expected title.
      $view->setDisplay($this->display_id);

      // Execute the view to get the total rows count.
      $view->setArguments($this->arguments);
      $title = $view->getTitle();

      if ($view->execute()) {
        $build = [
          'content' => [
            '#type' => 'view',
            '#name' => $this->view_id,
            '#display_id' => $this->display_id,
            '#arguments' => $this->arguments,
          ],
          '#count' => count($view->result),
        ];

        $build['title'] = $title ? ['#markup' => $title] : NULL;
        return $build;
      }
    }

    return NULL;
  }

}
