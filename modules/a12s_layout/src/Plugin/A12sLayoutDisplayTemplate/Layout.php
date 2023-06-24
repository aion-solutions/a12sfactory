<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayTemplate;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_paragraphs\LayoutParagraphsSection;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginManager;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetsFormTrait;
use Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginBase;
use Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Display Template for layouts.
 *
 * @A12sLayoutDisplayTemplate(
 *   id = "layout",
 *   label = @Translation("Layout"),
 *   description = @Translation("Provides integration with Layout Paragraphs."),
 *   deriver = "Drupal\a12s_layout\Plugin\Derivative\Layout"
 * )
 */
class Layout extends DisplayTemplatePluginBase implements ContainerFactoryPluginInterface {

  use DisplayOptionsSetsFormTrait {
    getOptionsSets as getOptionsSetsBase;
  }

  /**
   * The related Layout plugin instance.
   *
   * @var \Drupal\Core\Layout\LayoutInterface|null
   */
  protected ?LayoutInterface $layout;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityStorageInterface $thDisplayOptionsStorage,
    protected DisplayOptionsSetPluginManager $optionsSetPluginManager,
    protected LayoutPluginManagerInterface $layoutPluginManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    try {
      if ($layoutId = $this->getPluginDefinition()['layout']) {
        $this->layout = $this->layoutPluginManager->createInstance($layoutId, $configuration['layout'] ?? []);
      }
    }
    catch (PluginException $e) {
      watchdog_exception('a12s_layout', $e);
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('a12s_layout_display_options'),
      $container->get('plugin.manager.a12s_layout_display_options_set'),
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritDoc}
   *
   * @return \Drupal\Core\Layout\LayoutInterface
   */
  public function getTemplateObject(): ?LayoutInterface {
    return $this->layout;
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
    $template = self::getDisplayTemplateFromElement($element, $form);
    $layout = $template?->getTemplateObject();

    if ($layout instanceof LayoutInterface && ($optionsSets = $template->getOptionsSets())) {
      $parents = $element['#parents'] ?? [];
      $parents[] = 'config';
      $subformState = SubformState::createForSubform($element['config'], $form, $formState);
      $element['config'] = self::layoutParagraphsDisplayOptionsForm(
        $element['config'],
        $optionsSets,
        $parents,
        $layout->getConfiguration()['display_options'] ?? [],
        $subformState,
        $form);

      if (empty($element['config']['display_options'])) {
        unset($element['config']['display_options_tabs']);
      }
    }

    if (empty($form['#display_options'])) {
      $element['config']['display_options']['#access'] = FALSE;
    }
    else {
      $element['config']['#type'] = 'container';
      static::denyFormElementAccess($element, ['config']);
      static::denyFormElementAccess($element['config'], ['display_options', 'display_options_tabs']);
    }

    return $element;
  }

  /**
   * Validation callback for a Paragraph Layout element.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param array $form
   *   The complete form structure.
   *
   * @return void
   *
   * @see alterLayoutParagraphsComponentForm()
   */
  public static function validateLayoutParagraphsComponentForm(array &$element, FormStateInterface $formState, array &$form): void {
    $template = self::getDisplayTemplateFromElement($element, $form);

    if ($optionsSets = $template?->getOptionsSets()) {
      $template->validateLayoutParagraphsDisplayOptionsForm($optionsSets, $element['config'], $formState);
    }

    // Remove "display_options_tabs" key.
    $subformState = SubformState::createForSubform($element['config'], $form, $formState);
    $subformState->unsetValue('display_options_tabs');
  }

  /**
   * Try to get a Display Template plugin instance from the given form element.
   *
   * @param array $element
   *   The Layout Paragraph element.
   * @param array $form
   *   The complete form array.
   *
   * @return \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface|null
   */
  public static function getDisplayTemplateFromElement(array $element, array $form = []): ?DisplayTemplatePluginInterface {
    $paragraph = &$form['#paragraph'];

    if ($paragraph instanceof ParagraphInterface) {
      return static::getDisplayTemplate($paragraph, $element['layout']['#default_value']);
    }

    return NULL;
  }

  /**
   * Try to get a Display Template plugin instance for the given paragraph.
   *
   * @param ParagraphInterface $paragraph
   *   The Paragraph instance.
   * @param string $layoutId
   *   The layout ID.
   *
   * @return \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginInterface|null
   */
  public static function getDisplayTemplate(ParagraphInterface $paragraph, string $layoutId): ?DisplayTemplatePluginInterface {
    if ($paragraph->getParagraphType()->hasEnabledBehaviorPlugin('layout_paragraphs')) {
      $layoutParagraphsSection = new LayoutParagraphsSection($paragraph);
      $layoutSettings = $layoutParagraphsSection->getSetting('config') ?? [];
      /** @var \Drupal\a12s_layout\DisplayOptions\DisplayTemplatePluginManager $thDisplayTemplateManager */
      $thDisplayTemplateManager = \Drupal::service('plugin.manager.a12s_layout_display_template');

      try {
        /** @var self $layoutDisplayTemplate */
        return $thDisplayTemplateManager->createInstance(
          'layout:' . $layoutId,
          [
            'layout' => $layoutSettings,
            'paragraph' => $paragraph,
          ]
        );
      }
      catch (PluginException $e) {
        watchdog_exception('a12s_layout', $e);
      }
    }

    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getOptionsSets(): array {
    if ($doInstance = $this->getDisplayOptionsInstance()) {
      // @todo Handle plugin configurations and remove the flip.
      $settings = array_flip($doInstance?->get('optionsSets') ?? []);

      return $this->getOptionsSetsBase($settings, [
        'template' => $this,
        'instance' => $doInstance,
        'paragraph' => $this->configuration['paragraph'] ?? NULL,
      ]);
    }

    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies(): array {
    if (isset($this->layout)) {
      $pluginDefinition = $this->layout->getPluginDefinition();
      return $pluginDefinition->getConfigDependencies();
    }

    return [];
  }

}
