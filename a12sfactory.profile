<?php
/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\SettingsEditor;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Alter the install settings form.
 *
 * @see hook_form_FORM_ID_alter()
 */
function a12sfactory_form_install_settings_form_alter(&$form, FormStateInterface $formState): void {
  $database = Database::getConnectionInfo();

  if (!empty($database['default'])) {
    $handlers = [
      &$form['#submit'],
      &$form['actions']['save']['#submit'],
    ];

    foreach ($handlers as &$handler) {
      if (isset($handler)) {
        array_unshift($handler, 'a12sfactory_form_install_settings_form_submit');

        if ($key = array_search('::submitForm', $handler)) {
          unset($handler[$key]);
          $form['#has_submit_callback'] = TRUE;
        }
      }
    }
  }

  $form['settings'][$database['default']['driver']]['password']['#description'] = t('Leave empty to use the password that is defined in the <em>settings.php</em> file.');
}

/**
 * Form submit callback; force the database password if applicable.
 *
 * @throws \Exception
 */
function a12sfactory_form_install_settings_form_submit(array &$form, FormStateInterface $form_state): void {
  // Take care if the database connexion
  $database = Database::getConnectionInfo();

  if (!empty($database['default'])) {
    $user_values = $form_state->get('database');
    $diff = array_diff($user_values, $database['default']);

    if (!isset($diff['database']) && isset($diff['password']) && $diff['password'] === '') {
      global $installState;

      // Perform same tasks as in @see SiteSettingsForm::submitForm(), without
      // writing the database definition to the settings as it already exists.
//      $settings = [];
//      $settings['settings']['hash_salt'] = (object) [
//        'value'    => Crypt::randomBytesBase64(55),
//        'required' => TRUE,
//      ];
//      // Remember the profile which was used.
//      $settings['settings']['install_profile'] = (object) [
//        'value' => $installState['parameters']['profile'],
//        'required' => TRUE,
//      ];
//
//      SettingsEditor::rewrite($settings);
      $installState['settings_verified'] = TRUE;
      $installState['config_verified'] = TRUE;
      $installState['database_verified'] = TRUE;
      $installState['completed_task'] = install_verify_completed_task();
      return;
    }
  }

  // Fallback to default behavior.
  if (!empty($form['#has_submit_callback'])) {
    call_user_func_array($form_state->prepareCallback('::submitForm'), [&$form, &$form_state]);
  }
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
 * Implements hook_preprocess_html().
 */
function a12sfactory_preprocess_html(&$variables): void {
  if (!empty($variables['page_top']['toolbar']) && (bool) \Drupal::request()->query->get('hide_toolbar') === TRUE) {
    $variables['page_top']['toolbar']['#access'] = FALSE;
  }
}

/**
 * @see hook_slick_skins_info()
 */
function a12sfactory_slick_skins_info(): string {
  return '\\Drupal\\a12sfactory\\Slick\\A12sfactorySlickSkin';
}

/**
 * Forked from the issue below. Integrated in the profile as it makes the
 * upgrade to the final way of handling translations that will be chosen by the
 * maintainers of Paragraph and ERR modules.
 *
 * This is a simplified version, as we always want to synchronise translations
 * for all translatable paragraphs. Having a single and defined workflow ensure
 * compatibility with upgrades.
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph entity.
 *
 * @see https://www.drupal.org/project/paragraphs/issues/2887353
 *
 * @ingroup "Paragraph symmetric translations"
 *
 * @todo Use the new patch from added on April 4, 2023? It defines a new module
 *   that handles the expected feature, but it would need a hook_update_N()
 *   function to install this module and enable the synchronization in all
 *   existing paragraph types, so the existing behavior is kept.
 *   https://www.drupal.org/project/paragraphs/issues/2887353#comment-14997517
 *
 * @see hook_ENTITY_TYPE_insert()
 */
function a12sfactory_paragraph_insert(ParagraphInterface $paragraph): void {
  \Drupal::service('a12sfactory.paragraphs_translation_synchronization')->deferSync($paragraph);
}

/**
 * Allow simple text field to use a textarea to enter multilines text.
 *
 * @see hook_field_widget_info_alter()
 */
function a12sfactory_field_widget_info_alter(array &$info): void {
  $info['text_textarea']['field_types'][] = 'text';
  $info['string_textarea']['field_types'][] = 'string';
}
