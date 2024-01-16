<?php

namespace Drupal\a12sfactory\features;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

abstract class FeatureFormBase extends FormBase implements FeatureFormInterface {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function batchOperations(array $settings = []): array {
    return [];
  }

}
