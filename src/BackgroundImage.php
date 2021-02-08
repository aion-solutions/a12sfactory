<?php

namespace Drupal\a12sfactory;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;

/**
 * Create inline CSS for responsive background images.
 *
 * @deprecated use the service "a12sfactory.background_image_css" instead.
 * This will be removed when all features are moved and refactored into the new
 * service.
 */
class BackgroundImage {

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * BackgroundImage constructor.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field that contains the images.
   * @param array $settings
   *   The settings to be applied when building the inline CSS code.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(FieldDefinitionInterface $field_definition, array $settings) {
    $this->imageStyleStorage = \Drupal::entityTypeManager()->getStorage('image_style');
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings + [
      'selector' => NULL,
      'style' => NULL,
      'breakpoint_group' => NULL,
      'breakpoints' => [],
    ];

    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    if (!in_array($target_type, ['file' ,'media'])) {
      throw new \InvalidArgumentException('The provided field is not a reference to a file or a media.');
    }
  }

  /**
   * Get the CSS code to include in the page.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param $field_name
   * @param array $settings
   *
   * @return string
   */
  public static function getCode(FieldableEntityInterface $entity, $field_name, array $settings = []) {
    try {
      $items = $entity->get($field_name);
      $bgImage = new static($items->getFieldDefinition(), $settings);
      return $bgImage->getCssCode($entity);

    }
    catch (\Exception $e) {
      return '';
    }
  }

  /**
   * Get the files from the given field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   * @param string $langcode
   *
   * @return array
   */
  protected function getFilesFromItems(FieldItemListInterface $items, $langcode) {
    $files = [];

    if ($items->isEmpty()) {
      // &todo retrieve default image if any.
    }
    else {
      foreach ($items as $delta => $item) {
        // Standard file data.
        if ($item instanceof FileItem) {
          if (!$item->isDisplayed()) {
            continue;
          }

          $files[] = $item->entity;
        }
        // Media entity.
        else {
          /** @var \Drupal\media\Entity\Media $entity */
          $entity = $item->entity;

          if (!$entity instanceof Media) {
            continue;
          }

          // Set the entity in the correct language for display.
          if ($entity instanceof TranslatableInterface) {
            $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);
          }

          if ($this->checkAccess($entity)->isAllowed()) {
            $type_configuration = $entity->getSource()->getConfiguration();

            if (isset($type_configuration['source_field'])) {
              foreach ($entity->get($type_configuration['source_field']) as $image_item) {
                // Get each file entity.
                $files[] = $image_item->entity;
              }
            }
          }
        }
      }
    }

    return $files;
  }

  /**
   * Checks access to the given entity.
   *
   * By default, entity 'view' access is checked. However, a subclass can choose
   * to exclude certain items from entity access checking by immediately
   * granting access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool|\Drupal\Core\Access\AccessResult
   *   A cacheable access result.
   */
  protected function checkAccess(EntityInterface $entity) {
    // Only check access if the current file access control handler explicitly
    // opts in by implementing FileAccessFormatterControlHandlerInterface.
    $access_handler_class = $entity->getEntityType()->getHandlerClass('access');
    if (is_subclass_of($access_handler_class, '\Drupal\file\FileAccessFormatterControlHandlerInterface')) {
      return $entity->access('view', NULL, TRUE);
    }
    else {
      return AccessResult::allowed();
    }
  }


  /**
   *
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   */
  public function getCssCode(FieldableEntityInterface $entity) {
    $items = $entity->get($this->fieldDefinition->getName());
    $items->filterEmptyItems();
    $css = [];

    if ($entity instanceof TranslatableInterface && $entity->isTranslatable()) {
      $langcode = $entity->language()->getId();
    }
    else {
      $langcode = NULL;
    }

    $files = $this->getFilesFromItems($items, $langcode);

    // @todo add cache tags.

    // Get the first file. Currently there is no sense with using several files
    // for the same background selector, but if we think to something that makes
    // sense, we could make this evolve.
    if ($file = reset($files)) {
      // No selector means that we do not define a default background image.
      if (!empty($this->settings['selector'])) {
        $url = NULL;

        if ($this->settings['style']) {
          $image_style = ImageStyle::load($this->settings['style']);

          if ($image_style) {
            $url = $image_style->buildUrl($file->uri->value);
          }
        }
        else {
          $url = file_create_url($file->uri->value);
        }

        if ($url) {
          $css[] = $this->settings['selector'] . ' { background-image: url("' . $url . '"); }';
        }
      }

      if ($this->settings['breakpoint_group'] && !empty($this->settings['breakpoints'])) {
        $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup($this->settings['breakpoint_group']);

        foreach ($this->settings['breakpoints'] as $breakpoint => $breakpoint_settings) {
          if (!isset($breakpoints[$breakpoint])) {
            continue;
          }

          if (empty($breakpoint_settings['style']) && empty($breakpoint_settings['selector'])) {
            continue;
          }

          $selector = $breakpoint_settings['selector'] ?? $this->settings['selector'];
          $style = $breakpoint_settings['style'] ?? $this->settings['style'];
          $url = NULL;

          if ($style) {
            $image_style = ImageStyle::load($style);

            if ($image_style) {
              $url = ImageStyle::load($style)->buildUrl($file->uri->value);
            }
          }
          else {
            $url = file_create_url($file->uri->value);
          }

          if ($url) {
            $query = $breakpoints[$breakpoint]->getMediaQuery();

            if ($query != '') {
              $css[] = '@media ' . $query . ' { ';
            }

            // @todo something to deal with multipliers?

            $css[] = $selector . ' { background-image: url("' . file_url_transform_relative($url) . '"); }';

            if ($query != '') {
              $css[] = '}';
            }
          }
        }
      }

      return \Drupal\Core\Render\Markup::create(implode("\n", $css));
    }
  }

  /**
   * Build the inline css style based on a set of files and a selector.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $files
   *   The array of referenced files to display, keyed by delta.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity the field belongs to. Used for token replacement in the
   *   selector.
   *
   * @return array
   */
  protected function build_element($files, $entity) {
    $elements = [];
    $css = '';

    $selector = $this->getSetting('selector');
    $selector = \Drupal::token()->replace($selector, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);

    foreach ($files as $file) {
      $css .= $this->generate_background_css($file, $responsive_image_style, $selector);
    }

    if (!empty($css)) {
      // Use the selector in the id to avoid collisions with multiple background
      // formatters on the same page.
      $id = 'picture-background-formatter-' . $selector;
      $elements['#attached']['html_head'][] = [[
        '#tag' => 'style',
        '#value' => \Drupal\Core\Render\Markup::create($css),
      ], $id];
    }

    return $elements;
  }
  /**
   * CSS Generator Helper Function.
   *
   * @param ImageItem $image
   *   URI of the field image.
   * @param array $responsive_image_style
   *   Desired picture mapping to generate CSS.
   * @param string $selector
   *   CSS selector to target.
   *
   * @return string
   *   Generated background image CSS.
   *
   */
  protected function generate_background_css($image, $responsive_image_style, $selector) {
    $css = "";

    $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup($responsive_image_style->getBreakpointGroup());
    foreach (array_reverse($responsive_image_style->getKeyedImageStyleMappings()) as $breakpoint_id => $multipliers) {
      if (isset($breakpoints[$breakpoint_id])) {

        $multipliers = array_reverse($multipliers);

        $query = $breakpoints[$breakpoint_id]->getMediaQuery();
        if ($query != "") {
          $css .= ' @media ' . $query . ' {';
        }

        foreach ($multipliers as $multiplier => $mapping) {
          $multiplier = rtrim($multiplier, "x");

          if($mapping['image_mapping_type'] != 'image_style') {
            continue;
          }

          if ($mapping['image_mapping'] == "_original image_") {
            $url = file_create_url($image->getFileUri());
          }
          else {
            $url = ImageStyle::load($mapping['image_mapping'])->buildUrl($image->getFileUri());
          }

          if ($multiplier != 1) {
            $css .= ' @media (-webkit-min-device-pixel-ratio: ' . $multiplier . '), (min-resolution: ' . $multiplier * 96 . 'dpi), (min-resolution: ' . $multiplier . 'dppx) {';
          }

          $css .= $selector . ' {background-image: url(' . $url . ');}';

          if ($multiplier != 1) {
            $css .= '}';
          }
        }

        if ($query != "") {
          $css .= '}';
        }
      }
    }

    return $css;
  }
}
