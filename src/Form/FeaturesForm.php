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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    global $install_state;
    $helper = InstallationHelper::instance();

    $settings = [];
    $features = $helper->getFeatures();

    foreach ($form_state->getValue('extra_features') as $featureId => $feature) {
      if ($feature['enabled']) {
        $settings[$featureId] = [];

        if (!empty($features[$featureId]['forms'])) {
          foreach ($features[$featureId]['forms'] as $formClass) {
            $featureForm = new $formClass;

            $subForm = &$form['extra_features'][$featureId]['config'];
            $subformState = SubformState::createForSubform($subForm, $form, $form_state);

            $featureForm->submitForm($subForm, $subformState);

            $settings[$featureId] = $form_state->getValue(['extra_features', $featureId, 'config']);
          }
        }
      }
    }

    $install_state['a12sfactory_features'] = $settings;
  }

}
