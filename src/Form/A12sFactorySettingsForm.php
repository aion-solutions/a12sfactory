<?php

namespace Drupal\a12sfactory\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for managing module settings.
 */
class A12sFactorySettingsForm extends ConfigFormBase {

  use A12sDisplayBehaviorFormTrait;

  /**
   * Get the from ID.
   */
  public function getFormId(): string {
    return 'a12sfactory_settings';
  }

  /**
   * Get the editable config names.
   */
  protected function getEditableConfigNames(): array {
    return ['a12sfactory.settings'];
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('a12sfactory.settings');
    $form['settings']['background_styles'] = [
      '#type' => 'textarea',
      '#default_value' => $config->get('background_styles'),
      '#title' => t('Available CSS classes for paragraph background styling'),
      '#description' => $this->t('Enter one value per line, in the format <b>key|label</b> where <em>key</em> is the CSS class name (without the .), and <em>label</em> is the human readable name of the style in administration forms.'),
      '#cols' => 60,
      '#rows' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate Form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $this->text2KeyValue($form_state->getValue('background_styles'));

    if (!is_array($values)) {
      $form_state->setErrorByName('background_styles', t('Allowed values list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($values as $key => $value) {
        if (mb_strlen($key) > 255) {
          $form_state->setErrorByName('background_styles', t('Allowed values list: each key must be a string at most 255 characters long.'));
          break;
        }
      }
    }
  }

}
