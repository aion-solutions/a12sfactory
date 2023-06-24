<?php

namespace Drupal\a12s_layout\Batch;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraph_view_mode\StorageManagerInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\a12s_layout\Exception\A12sLayoutException;
use Drupal\a12s_layout\Service\MigrationManager;

/**
 * Custom class that handles batch callbacks.
 */
class BatchService {

  /**
   * @param int $paragraphId
   *   The paragraph id.
   * @param string $viewModeField
   *   The view mode selector field name.
   *
   * @throws \Drupal\a12s_layout\Exception\A12sLayoutException
   */
  public static function migrateViewModeSelectorToParagraphViewMode(int $paragraphId, string $viewModeField): void {
    /** @var Paragraph $paragraph */
    $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->loadRevision($paragraphId);

    if (!$paragraph) {
      throw new A12sLayoutException("Cannot load paragraph with id $paragraphId");
    }

    /** @var ModuleHandlerInterface $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');

    // The view mode field should exist.
    if (!$paragraph->hasField($viewModeField)) {
      throw new A12sLayoutException("Paragraph revision $paragraphId of type {$paragraph->bundle()} does not have a $viewModeField field");
    }

    // This must not happen too.
    if (!$moduleHandler->moduleExists('paragraph_view_mode')) {
      throw new A12sLayoutException("The paragraph_view_mode module is not enabled.");
    }

    $paragraphViewModeField = StorageManagerInterface::FIELD_NAME;
    if (!$paragraph->hasField($paragraphViewModeField)) {
      throw new A12sLayoutException("Paragraph revision $paragraphId of type {$paragraph->bundle()} does not have a $paragraphViewModeField field");
    }

    // Check if we have a current view mode.
    $viewMode = $paragraph->get($viewModeField);
    if (!$viewMode->isEmpty()) {
      // Copy the view mode.
      $paragraph->set($paragraphViewModeField, $viewMode->value);
      $paragraph->save();
    }
  }

  /**
   * Migrate paragraphs for a given entity.
   *
   * @param $entityType
   *   The entity type.
   * @param $entityId
   *   The entity id.
   *
   * @throws \Drupal\a12s_layout\Exception\A12sLayoutException
   */
  public static function migrateParagraphs($entityType, $entityId, string $sourceField) {
    $entity = \Drupal::entityTypeManager()->getStorage($entityType)->load($entityId);

    /** @var MigrationManager $migrationManager */
    $migrationManager = \Drupal::service('a12s_layout.migration_manager');

    if (!$entity) {
      throw new A12sLayoutException("Cannot load $entityType entity with id $entityId");
    }

    $targetField = MigrationManager::PARAGRAPH_LAYOUT_FIELD;

    // Reset the target field, just in case.
    $entity->{$targetField} = [];

    \Drupal::logger('a12s_layout')->notice("Migrating paragraphs for {$entity->getEntityTypeId()} {$entity->id()}");

    foreach ($entity->get($sourceField) as $paragraphField) {
      /** @var Paragraph $paragraph */
      $paragraph = $paragraphField->entity;
      if (is_null($paragraph)) {
        continue;
      }

      $migrationManager->migrateParagraph($entity, $paragraph);
    }

    $entity->save();
  }

}
