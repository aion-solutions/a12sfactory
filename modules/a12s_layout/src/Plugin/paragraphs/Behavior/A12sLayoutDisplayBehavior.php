<?php

namespace Drupal\a12s_layout\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginManager;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetsFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Behavior plugin for display options.
 *
 * @ParagraphsBehavior(
 *   id = "a12s_layout_display_options",
 *   label = @Translation("Display options"),
 *   description = @Translation("Add options for display. This plugin is not compatible with Layout Paragraphs, as <a href=':url'>options for layouts are defined apart</a>.", arguments = {":url" = "internal:/admin/appearance/a12s-layout-display-options/instance"}),
 *   weight = 0,
 * )
 */
class A12sLayoutDisplayBehavior extends ParagraphsBehaviorBase {

  use DisplayOptionsSetsFormTrait;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginManager $displayOptionsSetPluginManager
   *   The Display options set plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFieldManager $entity_field_manager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected DisplayOptionsSetPluginManager $displayOptionsSetPluginManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.a12s_layout_display_options_set')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'options_sets' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    foreach ($this->displayOptionsSetPluginManager->getDefinitions() as $id => $optionsSetDefinition) {
      if (!empty($optionsSetDefinition['applies_to']) && !in_array('paragraph', $optionsSetDefinition['applies_to'])) {
        continue;
      }

      $category = (string) $optionsSetDefinition['category'];
      $categoryName = Html::cleanCssIdentifier($category);

      if (!isset($form['options_sets'][$categoryName])) {
        $form['options_sets'][$categoryName] = [
          '#type' => 'fieldset',
          '#title' => $category,
        ];
      }

      $form['options_sets'][$categoryName][$id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $optionsSetDefinition['label'],
        '#default_value' => $config['options_sets'][$id]['enabled'] ?? FALSE,
        '#parents' => ['behavior_plugins', $this->getPluginId(), 'settings', 'options_sets', $id, 'enabled'],
      ];

      // @todo add subform if applicable.
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, SubformStateInterface|FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    // Disable this behavior if the "layout_paragraphs" behavior is enabled.
    if ($form_state instanceof SubformStateInterface && $form_state->getCompleteFormState()->getValue(['behavior_plugins', 'layout_paragraphs', 'enabled'])) {
      $configuration['enabled'] = FALSE;
    }
    else {
      $configuration['options_sets'] = array_filter($form_state->getValue('options_sets', []), fn($v) => !empty($v['enabled']));

      if (!$configuration['options_sets']) {
        $configuration['enabled'] = FALSE;
      }
    }

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {}

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {
    $paragraph = $variables['paragraph'];
    $displayOptions = $paragraph->getBehaviorSetting($this->getPluginId(), 'display_options', []);
    $config = $this->getConfiguration();
    $optionsSets = $this->getOptionsSets($config['options_sets'] ?? [], ['paragraph' => $paragraph]);

    foreach ($optionsSets as $optionsSetId => $optionsSet) {
      if ($optionsSet->appliesToTemplate('paragraph')) {
        $optionsSet->preprocessVariables($variables, $displayOptions[$optionsSetId] ?? []);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    if ($optionsSets = $this->getOptionsSets($config['options_sets'] ?? [], ['paragraph' => $paragraph])) {
      $values = $paragraph->getBehaviorSetting($this->getPluginId(), 'display_options', []);
      $parents = $form['#parents'] ?? [];
      $form = self::layoutParagraphsDisplayOptionsForm($form, $optionsSets, $parents, $values, $form_state, $form);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $optionsSets = $this->getOptionsSets($config['options_sets'] ?? [], ['paragraph' => $paragraph]);
    $this->validateLayoutParagraphsDisplayOptionsForm($optionsSets, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [$this->t('Display options')];
  }

  /**
   * Form #process callback; alter the layout paragraphs component form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form array.
   *
   * @return array
   *   The processed element.
   */
  public static function alterLayoutParagraphsComponentForm(array $element, FormStateInterface $formState, array &$form): array {
    if (empty($form['#display_options'])) {
      $element['a12s_layout_display_options']['#access'] = FALSE;
    }
    else {
      unset($element['#theme_wrappers']);
      static::denyFormElementAccess($element, ['a12s_layout_display_options']);
    }

    return $element;
  }

}
