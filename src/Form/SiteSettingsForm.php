<?php

namespace Drupal\a12sfactory\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\Form\SiteSettingsForm as SiteSettingsFormBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Site\SettingsEditor;

/**
 * Override the default SiteSettingsForm .
 *
 * @internal
 */
class SiteSettingsForm extends SiteSettingsFormBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $databaseInfo = Database::getConnectionInfo();
    $database = $form_state->get('database');
    $keepExistingDatabaseDefinition = FALSE;

    if (!empty($databaseInfo['default']['database'])) {
      $diff = array_diff_assoc($database, $databaseInfo['default']);

      if (count($diff) === 1 && isset($diff['password']) && $diff['password'] === '') {
        $keepExistingDatabaseDefinition = TRUE;
      }
    }

    // We don't have existing values in settings.php, use Drupal standard way.
    if (!$keepExistingDatabaseDefinition) {
      parent::submitForm($form, $form_state);
      return;
    }

    // We want to keep the existing database definition in the settings.php
    // file, so we have to save the settings like in the parent process, except
    // for the database...
    global $install_state;
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Update global settings array and save.
    $settings = [];
    $settings['settings']['hash_salt'] = (object) [
      'value'    => Crypt::randomBytesBase64(55),
      'required' => TRUE,
    ];

    if (empty(Settings::get('config_sync_directory'))) {
      $settings['settings']['config_sync_directory'] = (object) [
        'value' => empty($install_state['config_install_path']) ? $this->createRandomConfigDirectory() : $install_state['config_install_path'],
        'required' => TRUE,
      ];
    }

    SettingsEditor::rewrite($this->sitePath . '/settings.php', $settings);
    $install_state['settings_verified'] = TRUE;
    $install_state['config_verified'] = TRUE;
    $install_state['database_verified'] = TRUE;
    $install_state['completed_task'] = install_verify_completed_task();
  }

}
