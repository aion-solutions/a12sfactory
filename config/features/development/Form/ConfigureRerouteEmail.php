<?php

namespace Drupal\a12sfactory\features\development\Form;

use Drupal\a12sfactory\features\FeatureFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * ConfigureRerouteEmail class.
 */
class ConfigureRerouteEmail extends FeatureFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'a12s_factory_development_configure_reroute_email';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
  public function batchOperations(array $settings = []): array {
    $operations = [];

    ['addresses' => $addresses] = $settings;
    $operations[] = [[$this, 'configureRerouteEmail'], [$addresses]];

    return $operations;
  }

  /**
   * Configures the reroute email addresses and saves them in the default mailer policy configuration.
   *
   * @param string $addresses
   *   The comma-separated email addresses to reroute the emails to.
   * @param array $context
   *   (Reference) The context array passed to the function.
   */
  public function configureRerouteEmail(string $addresses, array &$context): void {
    $defaultPolicy = \Drupal::configFactory()->getEditable('symfony_mailer.mailer_policy._');

    $rerouteConfig = [
      'addresses' => [],
      'show_message' => 1,
      'add_mail_description' => 1,
    ];

    foreach (explode(',', $addresses) as $address) {
      $address = trim($address);
      $rerouteConfig['addresses'][] = [
        'value' => $address,
        'display' => $address,
      ];
    }

    $configuration = $defaultPolicy->get('configuration');
    $configuration['reroute_email'] = $rerouteConfig;
    $defaultPolicy->set('configuration', $configuration);

    $defaultPolicy->save(TRUE);
  }

}
