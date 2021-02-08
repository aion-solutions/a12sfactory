<?php
/**
 * @file
 * Provides a RouteSubscriber.
 */

namespace Drupal\a12sfactory\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Defines the Hero Sliders ordering page as an admin page.
    if ($route = $collection->get('view.hero_sliders.admin_page')) {
      $route->setOption('_admin_route', TRUE);
    }
  }

}
