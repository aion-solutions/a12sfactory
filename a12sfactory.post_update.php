<?php
/**
 * @file
 * Post update functions for the A12S Factory profile.
 */

/**
 * @see hook_removed_post_updates()
 */
function a12sfactory_removed_post_updates(): array {
  return [
    'a12sfactory_post_update_8004' => '5.0.0-alpha1',
    'a12sfactory_post_update_8005' => '5.0.0-alpha1',
    'a12sfactory_post_update_8006' => '5.0.0-alpha1',
  ];
}
