<?php

/**
 * @file
 * Gathers hooks and helpers related to profile installation.
 */

use Drupal\a12sfactory\Form\ConfigureMultilingualForm;
use Drupal\a12sfactory\Form\FeaturesForm;
use Drupal\a12sfactory\Utility\InstallationHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\Yaml\Yaml;

/**
 * Add custom tasks when installing Drupal.
 *
 * @see hook_install_tasks()
 */
function a12sfactory_install_tasks(&$install_state): array {
  return [
    'a12sfactory_configure_multilingual' => [
      'display_name' => t('Multilingual configuration'),
      'type' => 'form',
      'function' => ConfigureMultilingualForm::class,
    ],
    'a12sfactory_install_multilingual' => [
      'display_name' => t('Multilingual installation'),
      'type' => 'batch',
    ],
    'a12sfactory_configure_features' => [
      'display_name' => t('Extra features configuration'),
      'type' => 'form',
      'function' => FeaturesForm::class,
    ],
    'a12sfactory_install_features' => [
      'display_name' => t('Extra features installation'),
      'type' => 'batch',
    ],
  ];
}

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
function a12sfactory_install_hide_non_error_messages(): void {
  if (isset($_SESSION['messages']) && is_array($_SESSION['messages'])) {
    $_SESSION['messages'] = array_intersect_key($_SESSION['messages'], array_flip(['error']));
  }
}

/**
 * Installation callback; add multilingual support.
 */
function a12sfactory_install_multilingual(array $install_state): array {
  $batch = [];

  if (!empty($install_state['a12sfactory_multilingual_enabled'])) {
    $helper = InstallationHelper::instance();
    $modules = ['language', 'locale', 'config_translation', 'content_translation'];
    $batch['title'] = t('Adding multilingual support');
    $batch['operations'][] = [[$helper, 'installModules'], [$modules]];
    $helper->installPermissions('multilingual');

    if (!empty($install_state['a12sfactory_multilingual_languages'])) {
      $batch['operations'][] = [
        [$helper, 'installLanguages'],
        [$install_state['a12sfactory_multilingual_languages']],
      ];
    }
  }

  return $batch;
}

/**
 * @param array $install_state
 * @return array
 */
function a12sfactory_install_features(array $install_state): array {
  $batch = [];

  if (!empty($install_state['a12sfactory_features'])) {
    $helper = InstallationHelper::instance();
    $featuresFolder = $helper->getFeaturesFolder();
    $features = $helper->getFeatures();

    $batch['title'] = t('Adding features');

    foreach ($install_state['a12sfactory_features'] as $featureId => $config) {
      $feature = $features[$featureId];

      // Install dependencies.
      if (!empty($feature['dependencies'])) {
        $batch['operations'][] = [[$helper, 'installModules'], [$feature['dependencies']]];
      }

      // Import configuration.
      $path = $featuresFolder . '/' . $featureId .'/config';
      if (file_exists($path)) {
        $yamlFiles = glob($path . '/*.yml');
        foreach ($yamlFiles as $file) {
          $batch['operations'][] = [[$helper, 'writeConfig'], [basename($file), Yaml::parse(file_get_contents($file))]];
        }
      }
    }
  }

  return $batch;
}

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

  // Default user 1 username should be 'Webmaster'.
  $form['admin_account']['account']['name']['#default_value'] = 'Webmaster';
  $form['admin_account']['account']['name']['#attributes']['disabled'] = TRUE;
  unset($form['admin_account']['account']['name']['#description']);

  $form['admin_account']['account']['mail']['#default_value'] = 'dev@a12s.io';
  $form['admin_account']['account']['mail']['#attributes']['disabled'] = TRUE;

  $form['regional_settings']['date_default_timezone']['#default_value'] = 'Europe/Paris';

  $form['update_notifications']['enable_update_status_emails']['#default_value'] = 0;
  $form['update_notifications']['enable_update_status_module']['#default_value'] = 0;
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
