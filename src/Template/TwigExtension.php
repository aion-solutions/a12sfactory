<?php

namespace Drupal\a12sfactory\Template;

use Drupal\a12sfactory\Utility\EntityHelperInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\media\MediaInterface;
use Drupal\twig_tweak\TwigTweakExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * A class providing helpful Twig extensions.
 */
class TwigExtension extends AbstractExtension {

  /**
   * The Entity Helper service.
   */
  protected EntityHelperInterface $entityHelper;

  /**
   * The TWIG Tweak extension.
   */
  protected TwigTweakExtension $twigTweakExtension;

  /**
   * Constructs \Drupal\Core\Template\TwigExtension.
   *
   * @param \Drupal\a12sfactory\Utility\EntityHelperInterface $entityHelper
   *   The Entity Helper service.
   */
  public function __construct(EntityHelperInterface $entityHelper, TwigTweakExtension $twigTweakExtension) {
    $this->entityHelper = $entityHelper;
    $this->twigTweakExtension = $twigTweakExtension;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('entity_display_field', [$this, 'entityDisplayField']),
      new TwigFunction('entity_display_field_text', [$this, 'entityDisplayFieldText']),
      new TwigFunction('entity_display_field_reference', [$this, 'entityDisplayEntityReference']),
      new TwigFunction('entity_display_field_reference_revision', [$this, 'entityDisplayEntityReferenceRevision']),
      new TwigFunction('media_image_format', [$this, 'mediaImageFormat']),
      new TwigFunction('media_image_format_responsive', [$this, 'mediaImageFormatResponsive']),
    ];
  }

  /**
   * Display a rendered field.
   *
   * @code
   * {{ entity_display_field(node, "body", "teaser") }}
   * @endcode
   *
   * Instead of providing a view mode, you can specify the display settings.
   * @code
   * {{ entity_display_field(node, "body", {
   *     "type": "text_default",
   *     "label": "hidden",
   *   })
   * }}
   * @endcode
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity instance.
   * @param string $fieldName
   *   The entity reference field name.
   * @param int|string $delta
   *   The delta of the field item.
   * @param array|string  $display
   *   Can be either the name of a view mode, or an array of display settings.
   * @param ?string $langCode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   * @param ?bool $checkAccess
   *   (optional) Indicates that access check for an entity is required.
   *
   * @return array|null
   *   The render array for field, or NULL if not applicable.
   */
  public function entityDisplayField(FieldableEntityInterface $entity, string $fieldName, int|string $delta = 0, array|string $display = 'default', ?string $langCode = NULL, bool $checkAccess = TRUE): ?array {
    if (is_numeric($delta) && ($item = $this->entityHelper->entityGetField($entity, $fieldName)?->get($delta))) {
      return $this->twigTweakExtension->viewFilter($item, $display, $langCode, $checkAccess);
    }

    return NULL;
  }

  /**
   * Display a rendered field as text.
   *
   * @see \Drupal\a12sfactory\Template\TwigExtension::entityDisplayField()
   */
  public function entityDisplayFieldText(FieldableEntityInterface $entity, string $fieldName, int|string $delta = 0, ?string $langCode = NULL, bool $checkAccess = TRUE): ?array {
    $display = [
      'label' => 'hidden',
      'type' => 'text_default',
    ];
    return $this->entityDisplayField($entity, $fieldName, $delta, $display, $langCode, $checkAccess);
  }

  /**
   * Display an entity referenced in a field, using a view mode.
   *
   * @param string $viewMode
   *   The view mode for rendering the referenced entity.
   * @param string $formatterType
   *   The formatter used to display the referenced entity.
   *
   * @see \Drupal\a12sfactory\Template\TwigExtension::entityDisplayField()
   */
  public function entityDisplayEntityReference(FieldableEntityInterface $entity, string $fieldName, string $viewMode = 'default', int|string $delta = 0, string $formatterType = 'entity_reference_entity_view', ?string $langCode = NULL, bool $checkAccess = TRUE): ?array {
    $display = [
      'label' => 'hidden',
      'type' => $formatterType,
      'settings' => [
        'view_mode' => $viewMode,
      ],
    ];
    return $this->entityDisplayField($entity, $fieldName, $delta, $display, $langCode, $checkAccess);
  }

  /**
   * Display a specific revision of an entity referenced in a field, using a
   * view mode.
   *
   * @param string $viewMode
   *   The view mode for rendering the referenced entity.
   *
   * @see \Drupal\a12sfactory\Template\TwigExtension::entityDisplayField()
   */
  public function entityDisplayEntityReferenceRevision(FieldableEntityInterface $entity, string $fieldName, string $viewMode = 'default', int|string $delta = 0, ?string $langCode = NULL, bool $checkAccess = TRUE): ?array {
    return $this->entityDisplayEntityReference($entity, $fieldName, $viewMode, $delta, 'entity_reference_revisions_entity_view', $langCode, $checkAccess);
  }

  /**
   * Display an image from a media field, using the given display.
   *
   * @see \Drupal\a12sfactory\Template\TwigExtension::entityDisplayField()
   */
  public function mediaImageFormat(FieldableEntityInterface $entity, string $fieldName, int|string $delta = 0, array|string $display = 'default', ?string $langCode = NULL, bool $checkAccess = TRUE): ?array {
    $mediaField = $this->entityHelper->entityGetField($entity, $fieldName)?->get($delta);

    if (!empty($mediaField->entity) && $mediaField->entity instanceof MediaInterface) {
      return $this->entityDisplayField($mediaField->entity, 'field_image', 0, $display, $langCode, $checkAccess);
    }

    return NULL;
  }

  /**
   * Display an image from a media field, using a responsive style.
   *
   * @param string  $responsiveStyle
   *   The ID of the responsive image style.
   *
   * @see \Drupal\a12sfactory\Template\TwigExtension::entityDisplayField()
   */
  public function mediaImageFormatResponsive(FieldableEntityInterface $entity, string $fieldName, string $responsiveStyle = 'nocrop_half', int|string $delta = 0, ?string $langCode = NULL, bool $checkAccess = TRUE): ?array {
    $display = [
      'label' => 'hidden',
      'type' => 'responsive_image',
      'settings' => [
        'responsive_image_style' => $responsiveStyle,
      ],
    ];
    return $this->mediaImageFormat($entity, $fieldName, $delta, $display, $langCode, $checkAccess);
  }

}
