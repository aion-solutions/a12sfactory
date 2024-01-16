<?php

namespace Drupal\a12sfactory\features;

use Drupal\Core\Form\FormInterface;

interface FeatureFormInterface extends FormInterface {

  /**
   * Returns an array of batch operations.
   *
   * @param array $settings
   *   An optional array of settings to be passed to the batch operations.
   *
   * @return array
   *   An array containing the batch operations.
   */
  public function batchOperations(array $settings = []): array;

}
