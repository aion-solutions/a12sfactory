<?php

namespace Drupal\a12sfactory\features\development;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigureRerouteEmail extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'a12s_factory.development_configure_reroute_email';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['addresses'] = [
      '#type' => 'email',
      '#title' => t('Email addresses'),
      '#description' => t('The email addresses to reroute the email to. Values separated by commas.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    var_dump($form_state->getValue('addresses')); die;
  }
}
