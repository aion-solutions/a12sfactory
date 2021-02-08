<?php

namespace Drupal\a12sfactory\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Provides form for managing global settings related to Paragraphs behaviors.
 */
class A12sFactoryParagraphsBehaviorSettingsForm extends ConfigFormBase {

  use A12sDisplayBehaviorFormTrait;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager) {
    parent::__construct($config_factory);
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'a12sfactory_paragraphs_behavior_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['a12sfactory.paragraphs.behavior'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('a12sfactory.paragraphs.behavior');
    $display_settings = $config->get('display') ?? [];
    $display_settings += [
      'background_styles' => [],
      'background_image' => [],
      'spacing' => [],
    ];
    $display_settings['background_image'] += $this->getBackgroundImageDefaultValues();

    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display global settings'),
      '#description' => $this->t('The specified values are the default values for the a12s display behavior. Some of those values may be overridden for each paragraph type.'),
      '#tree' => TRUE,
      '#open' => TRUE,
      '#group' => 'settings',
    ];

    $form['display']['background_styles'] = [
      '#type' => 'textarea',
      '#default_value' => !empty($display_settings['background_styles']) ? $this->keyValue2Text($display_settings['background_styles']) : '',
      '#title' => $this->t('Available CSS classes for @property', ['@property' => 'background styling']),
      '#description' => $this->getCssKeyValueDescription(),
      '#cols' => 60,
      '#rows' => 10,
    ];

    $form['display']['background_image'] = [
      '#type' => 'details',
      '#title' => t('Background image settings'),
    ];

    $this->buildBackgroundImageSubform($form['display']['background_image'], $display_settings['background_image']);

    $form['display']['spacing'] = [
      '#type' => 'details',
      '#title' => t('Spacing settings'),
    ];

    foreach (['margin', 'padding'] as $spacing) {
      foreach (['top', 'bottom'] as $position) {
        $key = $spacing . '_' . $position;
        $form['display']['spacing'][$key] = [
          '#type' => 'textarea',
          '#default_value' => !empty($display_settings['spacing'][$key]) ? $this->keyValue2Text($display_settings['spacing'][$key]) : '',
          '#title' => $this->t('Available CSS classes for @property', ['@property' => $key]),
          '#description' => $this->getCssKeyValueDescription(),
          '#cols' => 60,
          '#rows' => 10,
        ];
      }
    }

    $row_settings = $config->get('row') ?? [];
    $row_settings += [
      'column_breakpoints' => [],
    ];

    $form['row'] = [
      '#type' => 'details',
      '#title' => $this->t('Row global settings'),
      '#description' => $this->t('The specified values are the default values for the a12s row behavior. Some of those values may be overridden for each paragraph type.'),
      '#tree' => TRUE,
      '#open' => FALSE,
      '#group' => 'settings',
    ];

    $form['row']['column_breakpoints'] = [
      '#type' => 'textarea',
      '#default_value' => !empty($row_settings['column_breakpoints']) ? $this->keyValue2Text($row_settings['column_breakpoints']) : '',
      '#title' => $this->t('List of breakpoints'),
      '#description' => $this->t('Enter one value per line, in the format <b>key|label</b> where <em>key</em> is the breakpoint identifier and <em>label</em> is the human readable name of the breakpoint in administration forms.'),
      '#cols' => 60,
      '#rows' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $error_message = 'Allowed values list for property @property: each key must be a string at most 255 characters long.';
    $background_styles = $this->text2KeyValue($form_state->getValue(['display', 'background_styles'], ''));

    if (!$this->validateKeyValue($background_styles)) {
      $form_state->setErrorByName('display][background_styles', $this->t($error_message, ['property' => 'background_styles']));
    }

    foreach (['margin', 'padding'] as $spacing) {
      foreach (['top', 'bottom'] as $position) {
        $key = $spacing . '_' . $position;
        $values = $this->text2KeyValue($form_state->getValue(['display', 'spacing', $key], ''));

        if (!$this->validateKeyValue($values)) {
          $form_state->setErrorByName('display][spacing][' . $key, $this->t($error_message, ['property' => $key]));
        }
      }
    }

    $column_breakpoints = $this->text2KeyValue($form_state->getValue(['row', 'column_breakpoints'], ''));

    if (!$this->validateKeyValue($column_breakpoints)) {
      $form_state->setErrorByName('row][column_breakpoints', $this->t($error_message, ['property' => 'column_breakpoints']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('a12sfactory.paragraphs.behavior');
    $config->set('display', []);

    $background_styles = $this->text2KeyValue($form_state->getValue(['display', 'background_styles'], ''));
    $config->set('display.background_styles', $background_styles);

    $background_image = $form_state->getValue(['display', 'background_image'], []);
    $config->set('display.background_image', $background_image);

    foreach (['margin', 'padding'] as $spacing) {
      foreach (['top', 'bottom'] as $position) {
        $key = $spacing . '_' . $position;
        $values = $this->text2KeyValue($form_state->getValue(['display', 'spacing', $key], ''));
        $config->set('display.spacing.' . $key, $values);
      }
    }

    $config->set('row', []);

    $column_breakpoints = $this->text2KeyValue($form_state->getValue(['row', 'column_breakpoints'], ''));
    $config->set('row.column_breakpoints', $column_breakpoints);

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Get the generic description of Key/Value pairs for CSS classes.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getCssKeyValueDescription() {
    return $this->t('Enter one value per line, in the format <b>key|label</b> where <em>key</em> is the CSS class name (without the .), and <em>label</em> is the human readable name of the style in administration forms.');
  }

}
