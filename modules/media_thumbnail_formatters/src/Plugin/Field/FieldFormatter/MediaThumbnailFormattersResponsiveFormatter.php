<?php

namespace Drupal\media_thumbnail_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'media_thumbnail_formatters_responsive' formatter.
 *
 * @FieldFormatter(
 *   id = "media_thumbnail_formatters_responsive",
 *   label = @Translation("Responsive thumbnail"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaThumbnailFormattersResponsiveFormatter extends ResponsiveImageFormatter {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   The responsive image style storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $responsive_image_style_storage, EntityStorageInterface $image_style_storage, LinkGeneratorInterface $link_generator, AccountInterface $current_user, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $responsive_image_style_storage, $image_style_storage, $link_generator, $current_user);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('responsive_image_style'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('link_generator'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   *
   * This has to be overriden because FileFormatterBase expects $item to be
   * of type \Drupal\file\Plugin\Field\FieldType\FileItem and calls
   * isDisplayed() which is not in FieldItemInterface.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $link_types = [
      'content' => $this->t('Content'),
      'media' => $this->t('Media entity'),
    ];
    $element['image_link']['#options'] = $link_types;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $link_types = [
      // 'content' is already handled by parent class.
      'media' => $this->t('Linked to media entity'),
    ];
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $media = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($media)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if ($langcode && $entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
      if (!$entity->isNew()) {
        $url = $entity->toUrl('canonical', ['language' => ConfigurableLanguage::load($langcode)]);
      }
    }
    elseif ($image_link_setting == 'media') {
      $link_media = TRUE;
    }

    // Collect cache tags to be added for each item in the field.
    /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $responsive_image_style */
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));

    $image_styles_to_load = [];
    $cache_tags = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    if (!empty($image_styles_to_load)) {
      $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);

      foreach ($image_styles as $image_style) {
        $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
      }
    }

    /** @var \Drupal\media_entity\MediaInterface $media_item */
    foreach ($media as $delta => $media_item) {
      $bundle = $media_item->bundle->entity;
      $item_key = isset($bundle->getSource()->configuration['source_field'])
        ? $bundle->getSource()->configuration['source_field']
        : 'thumbnail';

      $elements[$delta] = [
        '#theme' => 'responsive_image_formatter',
        '#item' => $media_item->get($item_key),
        '#item_attributes' => [],
        '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];

      if (isset($link_media)) {
        try {
          $url = $media_item->toUrl();
        } catch (EntityMalformedException $e) {
          // Error getting the media URL...
        }
      }

      $elements[$delta]['#url'] = $url;

      // Collect cache tags to be added for each item in the field.
      $this->renderer->addCacheableDependency($elements[$delta], $media_item);
    }

    $image_styles_to_load = [];
    if ($responsive_image_style) {
      $this->renderer->addCacheableDependency($elements, $responsive_image_style);
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $this->renderer->addCacheableDependency($elements, $image_style);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that reference
    // media entities.
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    return $target_type == 'media';
  }

}
