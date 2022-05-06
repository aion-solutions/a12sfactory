<?php

namespace Drupal\a12sfactory\Utility;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Interface ThemeManagerInterface
 */
interface EntityHelperInterface {

  /**
   * Creates a new ThemeManager instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $route_match);

  /**
   * Get an entity field if it exists and is not empty.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity instance.
   * @param string $fieldName
   *   The entity reference field name.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   The field item list, containing the field items.
   */
  public function entityGetField(FieldableEntityInterface $entity, string $fieldName): ?FieldItemListInterface;

  /**
   * Get an entity from an entity reference field of the given entity,
   * if it exists and is not null.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity instance.
   * @param string $fieldName
   *   The entity reference field name.
   * @param int $delta
   *   The delta of the field reference item.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function entityGetFieldReferenceEntity(FieldableEntityInterface $entity, string $fieldName, int $delta = 0): ?EntityInterface;

  /**
   * Get a field item list from an entity reference field of the given entity,
   * if it exists and is not null.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity instance.
   * @param string $erFieldName
   *   The entity reference field name.
   * @param string $fieldName
   *   The field name.
   * @param int $erDelta
   *   The delta of the field reference item.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   */
  public function entityGetFieldReferenceSubField(FieldableEntityInterface $entity, string $erFieldName, string $fieldName, int $erDelta = 0): ?FieldItemListInterface;

  /**
   * Try to get the value from the given field of the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity instance.
   * @param string $fieldName
   *   The field name.
   * @param int $delta
   *   (optional) The delta of the field reference item. Default to 0 (first item).
   * @param string|NULL $property
   *   (optional) A key identifier of the value array.
   *
   * @return mixed|null
   */
  public function entityGetFieldValue(FieldableEntityInterface $entity, string $fieldName, int $delta = 0, string $property = NULL);

  /**
   * Builds the render array for the provided entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   * @param string $viewMode
   *   (optional) The view mode that should be used to render the entity.
   * @param null $langCode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return array
   *   A render array for the entity.
   */
  public function renderEntity(EntityInterface $entity, $viewMode = 'full', $langCode = NULL): array;

  /**
   * Builds the render array for the provided entity, only if the view mode
   * is explicitly enabled.
   *
   * @param  \Drupal\Core\Entity\EntityInterface  $entity
   *   The entity to render.
   * @param  string  $viewMode
   *   (optional) The view mode that should be used to render the entity.
   * @param  null  $langCode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return array
   *
   * @see self::renderEntity()
   */
  public function renderEntityIfViewModeEnabled(EntityInterface $entity, $viewMode = 'full', $langCode = NULL): array;

  /**
   * Try to load an entity view display.
   *
   * @param $entityType
   *   The entity type.
   * @param $bundle
   *   The bundle.
   * @param string $viewMode
   *   (optional) The view mode
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface|\Drupal\Core\Entity\EntityInterface|null
   */
  public function loadEntityViewDisplay($entityType, $bundle, $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE);

  /**
   * Get displayed entity from the current route.
   *
   * @return ContentEntityBase|null
   *   Either an entity or FALSE if the current route is not a canonical
   *   entity page.
   */
  public function getEntityFromRoute(): ?ContentEntityBase;

  /**
   * Get the entity type from a route name, if applicable.
   *
   * @param string $routeName
   *   The route name.
   *
   * @return string|null
   *   The entity type.
   */
  public function getEntityTypeFromRoute($routeName): ?string;

}
