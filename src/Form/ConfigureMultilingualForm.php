<?php

namespace Drupal\a12sfactory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Form dedicated to multilingual configuration.
 */
class ConfigureMultilingualForm extends FormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'a12sfactory_configure_multilingual_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL): array {
    $defaultLangCode = $install_state['parameters']['langcode'] ?? 'en';
    $languages = array_map(fn($info) => $info[0], LanguageManager::getStandardLanguageList());
    $defaultLanguageName = $languages[$defaultLangCode] ?? $defaultLangCode;
    unset($languages[$defaultLangCode]);
    asort($languages);

    $form['#title'] = $this->t('Multilingual configuration');
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable multilingual features'),
      '#description' => $this->t('This allows to manage several languages and translate the interface, configuration and content.'),
    ];

    $form['languages'] = [
      '#type' => 'select',
      '#title' => $this->t('Additional languages'),
      '#description' => $this->t('The selected languages will be installed during the next step. You can also add extra language after the installation if you prefer. The default language is currently %language_name.', [
        '%language_name' => $defaultLanguageName,
      ]),
      '#options' => $languages,
      '#multiple' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="enable_multilingual"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    global $install_state;

    $install_state['a12sfactory_multilingual_enabled'] = $form_state->getValue('enable', FALSE);
    $install_state['a12sfactory_multilingual_languages'] = array_filter($form_state->getValue('languages', []));
  }

}
