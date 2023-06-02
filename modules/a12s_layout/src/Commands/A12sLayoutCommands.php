<?php

namespace Drupal\a12s_layout\Commands;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\paragraph_view_mode\StorageManagerInterface;
use Drupal\a12s_layout\Batch\BatchService;
use Drupal\a12s_layout\Exception\A12sLayoutException;
use Drupal\a12s_layout\Service\MigrationManager;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class A12sLayoutCommands extends DrushCommands {

  /**
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * Migrate the old paragraphs to the new paragraph layout field.
   *
   * @usage a12s_layout:migrate_paragraphs entity_type bundle source_field
   *
   * @command a12s_layout:migrate_paragraphs
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param string $sourceField
   *   The source paragraphs field.
   *
   * @throws \Drupal\a12s_layout\Exception\A12sLayoutException
   */
  public function migrateParagraphs(string $entityType, string $bundle, string $sourceField, $entityId = NULL) {
    // First, ensure that the fields exists.
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);

    $fields = [$sourceField, MigrationManager::PARAGRAPH_LAYOUT_FIELD];
    foreach ($fields as $field) {
      if (empty($fieldDefinitions[$field])) {
        throw new A12sLayoutException("Cannot find field definition for the $field field on the $entityType entity type / $bundle bundle.");
      }
    }

    $batchBuilder = new BatchBuilder();
    $type = $this->entityTypeManager->getDefinition($entityType);

    if (!is_null($entityId)) {
      $ids = [$entityId];
    }
    // Find all entities.
    else {
      $query = \Drupal::entityQuery($entityType)->accessCheck(FALSE);

      if ($bundleKey = $type->getKey('bundle')) {
        $query->condition($bundleKey, $bundle);
      }

      $ids = $query->execute();
    }

    foreach ($ids as $id) {
      $batchBuilder->addOperation(
        BatchService::class . "::migrateParagraphs",
        [$entityType, $id, $sourceField],
      );
    }

    batch_set($batchBuilder->toArray());

    $this->output()->writeln("Start migration...");
    drush_backend_batch_process();
  }

  /**
   * Create a new "paragraph_layout" field.
   *
   * This creates the field storage, instance, and configure the entity form
   * display, by using the configuration of a source field.
   *
   * @usage a12s_layout:create_paragraph_layout_field entity_type bundle
   *   source_field
   *
   * @command a12s_layout:create_paragraph_layout_field
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param string $sourceField
   *   The source paragraphs field.
   *
   * @throws \Drupal\a12s_layout\Exception\A12sLayoutException|\Drupal\Core\Entity\EntityStorageException
   */
  public function createParagraphLayoutField(string $entityType, string $bundle, string $sourceField) {
    // First, get the source field storage definition.
    $storageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityType);
    if (empty($storageDefinitions[$sourceField])) {
      throw new A12sLayoutException("Cannot find storage for the $sourceField field on the $entityType entity type.");
    }

    /** @var \Drupal\field\Entity\FieldStorageConfig $sourceStorage */
    $sourceStorage = $storageDefinitions[$sourceField];

    // And get the source field instance definition.
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    if (empty($fieldDefinitions[$sourceField])) {
      throw new A12sLayoutException("Cannot find field definition for the $sourceField field on the $entityType entity type / $bundle bundle.");
    }

    /** @var \Drupal\field\Entity\FieldConfig $sourceDefinition */
    $sourceDefinition = $fieldDefinitions[$sourceField];

    // Check if the storage definition exists for the paragraph layout field.
    if (empty($storageDefinitions[MigrationManager::PARAGRAPH_LAYOUT_FIELD])) {
      // Duplicate the source field storage and update it.
      $paragraphLayoutStorage = $sourceStorage->createDuplicate();

      // Update field name and id.
      $paragraphLayoutStorage->set('field_name', MigrationManager::PARAGRAPH_LAYOUT_FIELD);
      $paragraphLayoutStorage->set('id', $entityType . '.' . MigrationManager::PARAGRAPH_LAYOUT_FIELD);
      $paragraphLayoutStorage->save();
    }
    else {
      $paragraphLayoutStorage = $storageDefinitions[MigrationManager::PARAGRAPH_LAYOUT_FIELD];
    }

    // Check if the field instance already exists.
    if (empty($fieldDefinitions[MigrationManager::PARAGRAPH_LAYOUT_FIELD])) {
      $paragraphLayoutDefinition = $sourceDefinition->createDuplicate();

      // Update field name and id.
      $paragraphLayoutDefinition->set('field_name', MigrationManager::PARAGRAPH_LAYOUT_FIELD);
      $paragraphLayoutDefinition->set('id', $entityType . '.' . $bundle . '.' . MigrationManager::PARAGRAPH_LAYOUT_FIELD);
      $paragraphLayoutDefinition->set('label', 'Content');
      $paragraphLayoutDefinition->set('fieldStorage', $paragraphLayoutStorage);
    }
    else {
      $paragraphLayoutDefinition = $fieldDefinitions[MigrationManager::PARAGRAPH_LAYOUT_FIELD];
    }

    // Finally, update the paragraph layout definition to disallow referencing containers and columns paragraphs references.
    $handlerSettings = $paragraphLayoutDefinition->getSetting('handler_settings');
    if (!empty($handlerSettings['target_bundles'])) {
      foreach (array_keys(MigrationManager::PARAGRAPH_COLUMN_TYPES) as $bundleToRemove) {
        unset($handlerSettings['target_bundles'][$bundleToRemove]);
        if (!empty($handlerSettings['target_bundles_drag_drop'][$bundleToRemove])) {
          $handlerSettings['target_bundles_drag_drop'][$bundleToRemove]['enabled'] = FALSE;
        }
      }
    }

    // Update the settings.
    $paragraphLayoutDefinition->setSetting('handler_settings', $handlerSettings);
    $paragraphLayoutDefinition->save();

    // Now that we have our field, configure the display / form display.
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $formDisplay */
    $formDisplay = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($entityType . '.' . $bundle . '.default');

    $formDisplay->setComponent(MigrationManager::PARAGRAPH_LAYOUT_FIELD, [
      'type' => 'layout_paragraphs',
      'settings' => ['nesting_depth' => 3],
    ]);

    $formDisplay->save();
  }

  /**
   * Migrate from View Mode Selector to Paragraph View Mode.
   *
   * @usage a12s_layout:migrate_view_mode_selector_to_paragraph_view_mode
   *
   * @command a12s_layout:migrate_view_mode_selector_to_paragraph_view_mode
   *
   * @throws \Drupal\a12s_layout\Exception\A12sLayoutException
   */
  public function migrateViewModeSelectorToParagraphViewMode() {
    // First, ensure that the two modules are enabled.
    if (!$this->moduleHandler->moduleExists('view_mode_selector')) {
      throw new A12sLayoutException('The view mode selector module is not enabled');
    }
    if (!$this->moduleHandler->moduleExists('paragraph_view_mode')) {
      throw new A12sLayoutException('The paragraph view mode module is not enabled');
    }

    // Prepare an array with the paragraph types to processed, and the corresponding
    // view mode selector field name.
    $paragraphTypes = [];

    // Get all paragraph types with paragraph view mode enabled, that have a view mode selector field.
    foreach ($this->entityTypeBundleInfo->getBundleInfo('paragraph') as $bundleName => $bundleInfo) {
      $fields = $this->entityFieldManager->getFieldDefinitions('paragraph', $bundleName);

      // Check if the paragraph view mode is enabled.
      if (empty($fields[StorageManagerInterface::FIELD_NAME])) {
        continue;
      }

      // Look for the view mode selector field.
      foreach ($fields as $field) {
        if ($field->getType() === 'view_mode_selector') {
          $paragraphTypes[$bundleName] = $field->getName();
          break;
        }
      }
    }

    if (empty($paragraphTypes)) {
      $this->output()->writeln('Nothing to migrate.');
      exit();
    }

    $batchBuilder = new BatchBuilder();

    foreach ($paragraphTypes as $paragraphType => $viewModeSelectorField) {
      $pids = \Drupal::entityQuery('paragraph')
        ->condition('type', $paragraphType)
        ->allRevisions()
        ->accessCheck(FALSE)
        ->execute();

      // Loop through the revision ids.
      foreach (array_keys($pids) as $revisionId) {
        $batchBuilder->addOperation(
          BatchService::class . "::migrateViewModeSelectorToParagraphViewMode",
          [$revisionId, $viewModeSelectorField],
        );
      }
    }

    batch_set($batchBuilder->toArray());

    $this->output()->writeln("Start migration...");
    drush_backend_batch_process();
  }

}
