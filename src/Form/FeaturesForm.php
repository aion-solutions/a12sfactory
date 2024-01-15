<?php

namespace Drupal\a12sfactory\Form;

use Drupal\a12sfactory\Utility\InstallationHelper;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for enabling and configuring extra features.
 */
class FeaturesForm extends FormBase {

  /**
   * The Drupal application root.
   *
   * @var string
   */
  protected $root;

  /**
   * The info parser service.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * Assembler Form constructor.
   *
   * @param string $root
   *   The Drupal application root.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translator
   *   The string translation service.
   */
  public function __construct($root, InfoParserInterface $info_parser, TranslationInterface $translator) {
    $this->root = $root;
    $this->infoParser = $info_parser;
    $this->stringTranslation = $translator;
  }

  /**
   * {@inheritdoc}
   * @noinspection PhpParamsInspection
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->getParameter('app.root'),
      $container->get('info_parser'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'a12sfactory_features_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL): array {
    $form['#title'] = $this->t('Extra components');

    $form['extra_components_introduction'] = [
      '#weight' => -1,
      '#prefix' => '<p>',
      '#markup' => $this->t("Install additional ready-to-use features in your site."),
      '#suffix' => '</p>',
    ];

    $helper = InstallationHelper::instance();

    $form['extra_features'] = [
      '#tree' => TRUE,
    ];

    $extraFeatures = $helper->getFeatures();
    if (!empty($extraFeatures)) {
      foreach ($extraFeatures as $extraFeatureKey => $extraFeatureInfo) {
        $form['extra_features'][$extraFeatureKey] = [
          '#type' => 'fieldset',
        ];

        $form['extra_features'][$extraFeatureKey]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $extraFeatureInfo['name'] ?? $extraFeatureKey,
          '#description' => $extraFeatureInfo['description'] ?? NULL,
        ];

        if (!empty($extraFeatureInfo['forms'])) {
          $form['extra_features'][$extraFeatureKey]['config'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Configuration'),
            '#states' => [
              'visible' => [
                ':input[name="extra_features[' . $extraFeatureKey . '][enabled]"]' => ['checked' => TRUE],
              ],
            ],
          ];

          /*
          foreach ($extraFeatureInfo['form'] as $elementId => $element) {
            foreach ($element as $k => $v) {
              $element['#' . $k] = $v;
              unset($element[$k]);
            }

            $form['extra_features'][$extraFeatureKey]['config'][$elementId] = $element;
          }
          */

          // @todo better way to do this...
          // Include the needed classes.
          foreach (glob($helper->getFeaturesFolder() . '/' . $extraFeatureKey . '/src/*.php') as $filename) {
            include $filename;
          }

          foreach ($extraFeatureInfo['forms'] as $formClass) {
            $featureForm = new $formClass;

            $subForm = [];
            $subformState = SubformState::createForSubform($subForm, $form, $form_state);

            $form['extra_features'][$extraFeatureKey]['config'] += $featureForm->buildForm($subForm, $subformState);
          }
        }
      }
    }

    $form['actions'] = [
      'continue' => [
        '#type' => 'submit',
        '#value' => $this->t('Assemble and install'),
        '#button_type' => 'primary',
      ],
      '#type' => 'actions',
      '#weight' => 5,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $install_state;

    $extraFeatures = $form_state->getValue('extra_features');
    $features = [];
    foreach ($extraFeatures as $featureId => $feature) {
      if ($feature['enabled']) {
        $features[$featureId] = $feature['config'] ?? [];
      }
    }

    $install_state['a12sfactory_features'] = $features;


    // Extra Features.
    //$extraFeatures = ConfigBit::getList('configbit/extra.components.varbase.bit.yml', 'show_extra_components', TRUE, 'dependencies', 'profile', 'varbase');
    /*
    $extraFeatures = [];
    if (count($extraFeatures)) {
      $extra_features_values = [];

      foreach ($extraFeatures as $extraFeatureKey => $extraFeatureInfo) {

        // If form state has got value for this extra feature.
        if ($form_state->hasValue($extraFeatureKey)) {
          $extra_features_values[$extraFeatureKey] = $form_state->getValue($extraFeatureKey);
        }

        if (isset($extraFeatureInfo['config_form']) && $extraFeatureInfo['config_form'] == TRUE) {
          $formbit_file_name = \Drupal::service('extension.list.profile')->getPath('varbase') . '/' . $extraFeatureInfo['formbit'];
          if (file_exists($formbit_file_name)) {

            include_once $formbit_file_name;
            $extra_features_editable_configs = call_user_func_array($extraFeatureKey . "_get_editable_config_names", []);

            if (count($extra_features_editable_configs)) {
              foreach ($extra_features_editable_configs as $extra_features_editable_config_key => $extra_features_editable_config) {
                foreach ($extra_features_editable_config as $extra_features_config_item_key => $extra_features_config_item_value) {
                  if ($form_state->hasValue($extra_features_config_item_key)) {
                    $extra_features_editable_configs[$extra_features_editable_config_key][$extra_features_config_item_key] = $form_state->getValue($extra_features_config_item_key);
                  }
                }
              }
            }

            $GLOBALS['install_state']['varbase']['extra_features_configs'] = $extra_features_editable_configs;
          }
        }
      }

      $GLOBALS['install_state']['varbase']['extra_features_values'] = $extra_features_values;
    }
    */
  }

}
