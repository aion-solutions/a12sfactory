<?php

namespace Drupal\a12sfactory\Hook;

use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for Drupal core.
 */
class CoreHooks {

  /**
   * Allow a simple text field to use a textarea to enter multilines text.
   *
   * @see hook_field_widget_info_alter()
   */
  #[Hook('field_widget_info_alter')]
  public function fieldWidgetInfoAlter(array &$info): void {
    $info['text_textarea']['field_types'][] = 'text';
    $info['string_textarea']['field_types'][] = 'string';
  }

  /**
   * @see hook_page_top()
   *
   * Remove the administration toolbar from the top of the page.
   */
  #[Hook('page_top', order: Order::Last)]
  public function pageTop(array &$page_top): void {
    if (!empty($page_top['toolbar']) && (bool) \Drupal::request()->query->get('hide_toolbar') === TRUE) {
      $page_top['toolbar']['#access'] = FALSE;
    }
  }

}
