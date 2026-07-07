<?php

/**
 * @file
 * Gathers hooks and helpers related to profile installation.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Add custom tasks when installing Drupal.
 *
 * @see hook_install_tasks()
 */
//function a12sfactory_install_tasks(&$install_state): array {
//  return [
//    'a12sfactory_install_features' => [
//      'display_name' => t('Extra features installation'),
//      'type' => 'batch',
//    ],
//  ];
//}

/**
 * Override the "install_profile_modules" batch.
 */
function a12sfactory_install_profile_modules(array &$install_state): array {
  $batch = install_profile_modules($install_state);
  // Hide messages, as some modules display some status messages after their
  // installation.
  $batch['operations'][] = ['a12sfactory_install_hide_non_error_messages', []];
  return $batch;
}

/**
 * Batch operation callback; hide all messages, except errors.
 */
//function a12sfactory_install_hide_non_error_messages(): void {
//  if (isset($_SESSION['messages']) && is_array($_SESSION['messages'])) {
//    $_SESSION['messages'] = array_intersect_key($_SESSION['messages'], array_flip(['error']));
//  }
//}

/**
 * Alter the installation tasks.
 *
 * @see hook_install_tasks_alter()
 */
function a12sfactory_install_tasks_alter(array &$tasks, array $install_state): void {
  // The class loader is not ready here, so we need to ensure that our form
  // class can be instantiated.
  include_once dirname(__DIR__) . '/src/Form/SiteSettingsForm.php';
  $tasks['install_settings_form']['function'] = 'Drupal\a12sfactory\Form\SiteSettingsForm';
  $tasks['install_profile_modules']['function'] = 'a12sfactory_install_profile_modules';
}

/**
 * Alter the site configuration form.
 *
 * @see hook_form_FORM_ID_alter()
 */
function a12sfactory_form_install_configure_form_alter(&$form, FormStateInterface $formState): void {
  $form['site_information']['site_name']['#attributes']['placeholder'] = t('Specify the site name');

  // Specify a default value for the main user's name.
  if ($adminName = getenv('DRUPAL_INSTALL_ADMIN_ACCOUNT_NAME')) {
    $form['admin_account']['account']['name']['#default_value'] = $adminName;
    $form['admin_account']['account']['name']['#attributes']['disabled'] = TRUE;
  }

  if ($adminMail = getenv('DRUPAL_INSTALL_ADMIN_ACCOUNT_MAIL')) {
    $form['admin_account']['account']['mail']['#default_value'] = $adminMail;
    $form['admin_account']['account']['mail']['#attributes']['disabled'] = TRUE;
  }

  if ($timezone = getenv('DRUPAL_INSTALL_DEFAULT_TIMEZONE')) {
    $form['regional_settings']['date_default_timezone']['#default_value'] = $timezone;
  }

  if (getenv('DRUPAL_INSTALL_DISABLE_UPDATE')) {
    $form['update_notifications']['enable_update_status_emails']['#default_value'] = 0;
    $form['update_notifications']['enable_update_status_module']['#default_value'] = 0;
  }
}

/**
 * Implements hook_preprocess_install_page().
 */
function a12sfactory_preprocess_install_page(&$variables): void {
  // This library is already added by the installation process, except on batch
  // error pages...
  $variables['#attached']['library'][] = 'claro/maintenance-page';
  $variables['#attached']['library'][] = 'a12sfactory/install-page';
  $profilePath = \Drupal::service('extension.path.resolver')->getPath('module', 'a12sfactory');
  $siteName = !empty($variables['site_name']) && is_string($variables['site_name']) ? $variables['site_name'] : '';
  $filePath = base_path() . $profilePath . '/assets/images/a12sfactory-medium-logo.png';

  $variables['site_name'] = [
    '#markup' => Markup::create('<img src="' . $filePath . '" class="installer-logo" title="' . $siteName . '" />'),
  ];

  $variables['page']['page_bottom']['credit'] = [
    '#type' => 'markup',
    '#markup' => Markup::create('<div id="credit" class="clearfix">
        <div class="message">Powered by</div>
        <div class="logo">
          <a href="https://www.aion-solutions.lu" target="_blank">Aïon Solutions</a>
        </div>
      </div>'),
  ];
}
