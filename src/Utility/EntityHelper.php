<?php

namespace Drupal\a12sfactory\Utility;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Class ThemeManager
 */
class EntityHelper implements EntityHelperInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * @inheritDoc
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $routeMatch) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * @inheritDoc
   */
  public function entityGetField(FieldableEntityInterface $entity, string $fieldName): ?FieldItemListInterface {
    if ($entity->hasField($fieldName) && !$entity->get($fieldName)->isEmpty()) {
      return $entity->get($fieldName);
    }

    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function entityGetFieldReferenceEntity(FieldableEntityInterface $entity, string $fieldName, int $delta = 0): ?EntityInterface {
    $erField = $this->entityGetField($entity, $fieldName);

    if ($erField && $erField->offsetExists($delta)) {
      try {
        return $erField->get($delta)->entity;
      }
      catch (MissingDataException $e) {
        watchdog_exception('a12s_microbase', $e);
      }
    }

    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function entityGetFieldReferenceSubField(FieldableEntityInterface $entity, string $erFieldName, string $fieldName, int $erDelta = 0): ?FieldItemListInterface {
    $subEntity = $this->entityGetFieldReferenceEntity($entity, $erFieldName, $erDelta);
    return $subEntity instanceof FieldableEntityInterface ? $this->entityGetField($subEntity, $fieldName) : NULL;
  }

  /**
   * @inheritDoc
   */
  public function entityGetFieldValue(FieldableEntityInterface $entity, string $fieldName, int $delta = 0, string $property = NULL) {
    if ($item_list = $this->entityGetField($entity, $fieldName)) {
      try {
        $value = $item_list->get($delta)->getValue();

        if (!isset($property)) {
          return $value;
        }

        if (is_array($value) && array_key_exists($property, $value)) {
          return $value[$property];
        }
      }
      catch (MissingDataException $e) {
        watchdog_exception('a12s_microbase', $e);
      }
    }

    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function renderEntity(EntityInterface $entity, $viewMode = 'full', $langCode = NULL): array {
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    return $view_builder->view($entity, $viewMode, $langCode);
  }

  /**
   * @inheritDoc
   */
  public function renderEntityIfViewModeEnabled(EntityInterface $entity, $viewMode = 'full', $langCode = NULL): array {
    $display = $this->loadEntityViewDisplay($entity->getEntityTypeId(), $entity->bundle(), 'page_title');

    if ($display && $display->status()) {
      return $this->renderEntity($entity, 'page_title');
    }

    return [];
  }

  /**
   * @inheritDoc
   */
  public function loadEntityViewDisplay($entityType, $bundle, $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    try {
      return $this->entityTypeManager
        ->getStorage('entity_view_display')
        ->load($entityType . '.' . $bundle . '.' . $viewMode);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * @inheritDoc
   */
  public function getEntityFromRoute(): ?ContentEntityBase {
    static $drupalStaticFast;

    if (!isset($drupalStaticFast)) {
      // Initialize to FALSE and not NULL, as NULL is an expected return of this
      // method and can be stored in the static variable.
      $drupalStaticFast['entity'] = &drupal_static(__FUNCTION__, FALSE);
    }

    if ($drupalStaticFast['entity'] === FALSE) {
      $drupalStaticFast['entity'] = NULL;

      // Are we displaying an entity canonical page?
      if ($entityType = $this->getEntityTypeFromRoute($this->routeMatch->getRouteName())) {
        // Look for a fieldable entity of the expected type.
        foreach ($this->routeMatch->getParameters() as $parameter) {
          if ($parameter instanceof ContentEntityBase && $parameter->getEntityTypeId() === $entityType) {
            $drupalStaticFast['entity'] = $parameter;
            break;
          }
        }
      }
    }

    return $drupalStaticFast['entity'];
  }

  /**
   * @inheritDoc
   */
  public function getEntityTypeFromRoute($routeName): ?string {
    $match = [];
    if (preg_match('/^entity\.(?P<entity_type>[^\.]+)\.canonical$/', $routeName, $match)) {
      return $match['entity_type'];
    }

    return NULL;
  }

}
