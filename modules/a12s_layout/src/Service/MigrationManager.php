<?php

namespace Drupal\a12s_layout\Service;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;

class MigrationManager {

  /**
   * The machine name of the paragraph layout field.
   */
  public const PARAGRAPH_LAYOUT_FIELD = 'field_paragraph_layout';

  /**
   * Paragraphs type that correspond to a container / column paragraph, and the
   * related reference field.
   */
  public const PARAGRAPH_COLUMN_TYPES = [
    'columns' => 'column_content',
    'columns_two_uneven' => 'column_content_2',
    'columns_three_uneven'  => 'column_content_3',
    'columns_single'  => 'column_content_1',
    'hp_duo' => ['field_first_column', 'field_second_column'],
  ];

  /**
   * The regions name.
   */
  public const PARAGRAPH_LAYOUT_REGIONS = ['first', 'second', 'third'];

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   * @param string $targetField
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function migrateParagraph(EntityInterface $entity, Paragraph $paragraph, string $targetField = self::PARAGRAPH_LAYOUT_FIELD) {
    if (!$paragraph->isPublished()) {
      return;
    }

    // Check the paragraph type.
    if (in_array($paragraph->bundle(), array_keys(self::PARAGRAPH_COLUMN_TYPES))) {
      // Specific cases.
      switch ($paragraph->bundle()) {
        case 'columns':
          // We have to count the children.
          $count = $paragraph->get('column_content')->count();
          if ($count === 1) {
            // No need to create a layout here.
            foreach ($paragraph->get('column_content') as $childField) {
              $child = $childField->entity;
              if (is_null($child)) {
                continue;
              }

              $entity->{$targetField}[] = [
                'target_id' => $child->id(),
                'target_revision_id' => $child->getRevisionId(),
              ];
            }
          }
          elseif ($count > 1 && $count <= 3) {
            $this->manageLayoutParagraph(
              $entity,
              $paragraph,
            );
          }
          else {
            // Cannot migrate this.
            \Drupal::logger('a12s_layout')->warning("Found $count child in the entity {$entity->getEntityTypeId()} {$entity->id()}");
          }

          break;

        case 'columns_three_uneven':
        case 'columns_two_uneven':
        case 'hp_duo':
          $this->manageLayoutParagraph(
            $entity,
            $paragraph,
          );
          break;

        case 'columns_single':
          foreach ($paragraph->get('column_content_1') as $columnField) {
            $columnParagraph = $columnField->entity;
            if (!is_null($columnParagraph)) {
              $this->migrateParagraph($entity, $columnParagraph);
            }
          }
          break;
      }
    }
    else {
      // Nothing to do, we'll reuse the current paragraph.
      $paragraph = $this->convertContentParagraph($paragraph);

      if ($paragraph->getParagraphType()->hasEnabledBehaviorPlugin('a12s_layout_display_options')) {
        // Move behaviors for a paragraph outside a layout.
        $doBehavior = $paragraph->getParagraphType()->getBehaviorPlugin('a12s_layout_display_options');
        // Copy behaviors.
        $newBehaviors = $this->migrateBehaviors($paragraph);
        // @todo filter also the paragraph layouts with the following process.
        $optionsSets = $doBehavior->getConfiguration()['options_sets'] ?? [];
        $newBehaviors = array_intersect_key($newBehaviors, $optionsSets);

        // And set the layout behaviors.
        $paragraph->setBehaviorSettings('a12s_layout_display_options', ['display_options' => $newBehaviors]);
      }

      $paragraph->setBehaviorSettings('layout_paragraphs', []);
      $paragraph->save();

      // Reuse the current paragraph.
      $entity->{$targetField}[] = [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }
  }

  /**
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createLayoutParagraph(ParagraphInterface $paragraph): ParagraphInterface {
    $behaviorSettings = $paragraph->getAllBehaviorSettings();

    $columnWidths = '100';
    if (!empty($behaviorSettings['a12sfactory_paragraph_grid']['row']['row_layout'])) {
      $columnWidths = $behaviorSettings['a12sfactory_paragraph_grid']['row']['row_layout'];
    }

    // Determine the layout id...
    $layoutId = match ($paragraph->bundle()) {
      'columns' => $paragraph->get('column_content')->count() === 2 ? 'layout_twocol_section' : 'layout_threecol_section',
      'columns_two_uneven', 'hp_duo' => 'layout_twocol_section',
      'columns_three_uneven' => 'layout_threecol_section',
      default => 'layout_onecol',
    };

    // ... and the column widths.
    $columnWidths = match ($paragraph->bundle()) {
      'columns' => $paragraph->get('column_content')->count() === 2 ? '50-50' : '33-34-33',
      'columns_two_uneven' => str_replace('66', '67', $columnWidths),
      'columns_three_uneven' => str_replace(['66', '16'], ['50', '25'], $columnWidths),
      'hp_duo' => '50-50',
      default => $columnWidths,
    };

    $layoutParagraph = Paragraph::create(['type' => 'layout']);

    $newBehaviors = [
      'layout' => $layoutId,
      'config' => [
        'column_widths' => $columnWidths,
      ],
    ];

    // Copy behaviors.
    if ($layoutParagraph->getParagraphType()->hasEnabledBehaviorPlugin('layout_paragraphs')) {
      $newBehaviors['config']['display_options'] = $this->migrateBehaviors($paragraph);
    }

    // And set the layout behaviors.
    $layoutParagraph->setBehaviorSettings('layout_paragraphs', $newBehaviors);
    $layoutParagraph->save();

    return $layoutParagraph;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   * @param string $targetField
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function manageLayoutParagraph(EntityInterface $entity, ParagraphInterface $paragraph, string $targetField = self::PARAGRAPH_LAYOUT_FIELD) {
    // Create a new layout paragraph.
    $layoutParagraph = $this->createLayoutParagraph($paragraph);
    $layoutParagraph->setParentEntity($entity, self::PARAGRAPH_LAYOUT_FIELD);

    // Initialize the layout.
    $layout = new LayoutParagraphsLayout($entity->{$targetField});
    $layout->appendComponent($layoutParagraph);

    $this->addParagraphToLayout($entity, $layoutParagraph, $layout, $paragraph);

    $layout->getEntity()->save();
  }

  /**
   * Try to convert old behaviors to the new ones, for the given paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *
   * @return array
   */
  protected function migrateBehaviors(ParagraphInterface $paragraph): array {
    $displayOptionsBehaviors = [];
    $edge2edge = FALSE;

    $behaviors = $paragraph->getAllBehaviorSettings();
    if (!empty($behaviors['a12sfactory_paragraph_display'])) {
      unset($behaviors['a12sfactory_paragraph_display']['a12s_behaviors']);
      foreach ([$behaviors['a12sfactory_paragraph_display'], $behaviors['a12sfactory_paragraph_grid']] as $behaviorGroup) {
        foreach ($behaviorGroup as $_bGroup => $_behaviors) {
          foreach ($_behaviors as $bName => $bValues) {
            $bGroup = $_bGroup;

            // Here comes the SPECIAL CASES!!!
            switch ($bGroup) {
              case 'row':
                $bGroup = 'grid_layout';

                switch ($bName) {
                  case 'align_items':
                    $bName = 'grid';
                    $bGroup = 'grid_vertical_alignment';
                    break;
                  case 'container_class':
                    $bName = 'grid_classes';
                    $bGroup = 'grid_attribute_class';
                    break;
                  case 'row_class':
                    $bName = 'region_classes';
                    $bGroup = 'grid_attribute_class';
                    break;
                  case 'no_gutters':
                    $bName = 'gap';
                    $bGroup = 'grid_gap';

                    if ($bValues) {
                      $bValues = 'row-gap-0';
                    }
                }
                break;

              case 'column':
                if ($bName === 'width') {
                  $bGroup = 'width';

                  // Special case if the width is egde to edge.
                  if ($bValues === 'edge2edge') {
                    $edge2edge = TRUE;
                    $bValues = '';
                  }
                }
                else {
                  $bGroup = '';

                  switch ($bName) {
                    case 'align_self':
                      // @todo move to layout instead of paragraph.
                      $bName = 'vertical_alignment'; // @todo Wrong, we need to use "regions_override" and "regions[$regionId]"
                      $bGroup = 'grid_vertical_alignment';
                      break;
                    case 'order':
                      // @todo move to layout instead of paragraph.
                      $bName = 'column_order'; // @todo Wrong, we need to use "regions[$regionId]"
                      $bGroup = 'grid_region_order';
                      break;
                    case 'class':
                      // @todo check if this is really used. For paragraphs,
                      //   it takes sense, however for layout I'm not sure...
                      $bGroup = 'attribute_class';
                      $bName = 'classes';
                      break;
                  }
                }
                break;

              default:
                switch ($bName) {
                  case 'margin':
                  case 'padding':
                    $bGroup = 'spacing:' . $bName;
                    $displayOptionsBehaviors[$bGroup] = [];

                    foreach ($bValues as $bKey => $bValue) {
                      $displayOptionsBehaviors[$bGroup][$bName . '_' . $bKey] = $bValue;
                    }

                    // Force override.
                    if (!empty($displayOptionsBehaviors['spacing:margin'])) {
                      $displayOptionsBehaviors[$bGroup][$bName . '_vertical_override'] = TRUE;
                    }
                    break;

                  case 'image':
                    $bName = 'background_image';
                    $bValues = ['fids' => reset($bValues)];
                    break;

                  case 'id':
                    $bGroup = 'attribute_id';
                    $bName = 'id';
                    break;

                  case 'class':
                    $bGroup = 'attribute_class';
                    $bName = 'classes';
                    break;

                  case 'style':
                    if ($bGroup === 'background_image') {
                      $bGroup = $bName = 'background_color';
                    }
                }
            }

            // Alter the "group" name if it's still needed.
            if ($bGroup === 'background') {
              $bGroup = 'background_image';
            }

            if (!isset($displayOptionsBehaviors[$bGroup])) {
              $displayOptionsBehaviors[$bGroup] = [];
            }

            $displayOptionsBehaviors[$bGroup][$bName] = $bValues;

            // Remove 'default' values.
            $defaultCleanUp = [
              ['background_image', 'background_position', 'option'],
              ['background_image', 'background_size', 'option'],
            ];

            foreach ($defaultCleanUp as $parents) {
              if (NestedArray::getValue($displayOptionsBehaviors, $parents) === 'default') {
                NestedArray::unsetValue($displayOptionsBehaviors, $parents);

                do {
                  array_pop($parents);

                  if (NestedArray::getValue($displayOptionsBehaviors, $parents) === []) {
                    NestedArray::unsetValue($displayOptionsBehaviors, $parents);
                  }
                }
                while ($parents);
              }
            }
          }
        }
      }
    }

    // Manage the special cases of the edge to edge width.
    if ($edge2edge) {
      $displayOptionsBehaviors['width']['container'] = 'no';
      $displayOptionsBehaviors['width']['container_remove_padding'] = TRUE;
    }

    // Remove useless information.
    unset($displayOptionsBehaviors['a12s_behaviors']);
    return $displayOptionsBehaviors;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\paragraphs\ParagraphInterface $layoutParagraph
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   * @param \Drupal\paragraphs\ParagraphInterface $sourceParagraph
   */
  protected function addParagraphToLayout(EntityInterface $entity, ParagraphInterface $layoutParagraph, LayoutParagraphsLayout $layout, ParagraphInterface $sourceParagraph): void {
    $i = 0;

    // @todo change this... And load regions of the matching layout.
    $regions = $sourceParagraph->bundle() === 'columns_single' ? ['content'] : self::PARAGRAPH_LAYOUT_REGIONS;

    if (isset(self::PARAGRAPH_COLUMN_TYPES[$sourceParagraph->bundle()])) {
      $fieldNames = self::PARAGRAPH_COLUMN_TYPES[$sourceParagraph->bundle()];
      if (!is_array($fieldNames)) {
        $fieldNames = [$fieldNames];
      }

      foreach ($fieldNames as $fieldName) {
        foreach ($sourceParagraph->get($fieldName) as $childField) {
          /** @var ParagraphInterface $child */
          $child = $childField->entity;
          if (is_null($child)) {
            continue;
          }

          if (in_array($child->bundle(), array_keys(self::PARAGRAPH_COLUMN_TYPES))) {
            if ($child->bundle() === 'columns_single') {
              // Remove the useless parent container.
              foreach ($child->get(self::PARAGRAPH_COLUMN_TYPES[$child->bundle()]) as $subChildField) {
                $subChild = $subChildField->entity;
                if (!is_null($subChild)) {
                  $layout->insertIntoRegion($layoutParagraph->uuid(), $regions[$i], $subChild);
                  $this->migrateBehaviorsToParent($layoutParagraph, $subChild, $regions[$i]);
                }
              }
            }
            else {
              // Create the new layout paragraph and add it to the current layout.
              $childLayoutParagraph = $this->createLayoutParagraph($child);
              $childLayoutParagraph->setParentEntity($entity, self::PARAGRAPH_LAYOUT_FIELD);

              $layout->insertIntoRegion($layoutParagraph->uuid(), $regions[$i], $childLayoutParagraph);
              $this->migrateBehaviorsToParent($layoutParagraph, $childLayoutParagraph, $regions[$i]);

              $this->addParagraphToLayout($entity, $childLayoutParagraph, $layout, $child);
            }
          }
          else {
            $child = $this->convertContentParagraph($child);
            $layout->insertIntoRegion($layoutParagraph->uuid(), $regions[$i], $child);
            $this->migrateBehaviorsToParent($layoutParagraph, $child, $regions[$i]);
          }

          // "Stack" paragraphs on "columns_single".
          if ($sourceParagraph->bundle() !== 'columns_single') {
            $i++;
          }
        }
      }
    }
    else {
      $sourceParagraph = $this->convertContentParagraph($sourceParagraph);
      $layout->insertIntoRegion($layoutParagraph->uuid(), $regions[$i], $sourceParagraph);
      $this->migrateBehaviorsToParent($layoutParagraph, $sourceParagraph, $regions[$i]);
    }
  }

  /**
   * Converts content paragraphs (ie non-column paragraphs) if needed.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function convertContentParagraph(ParagraphInterface $paragraph): ParagraphInterface {
    if ($paragraph->bundle() === 'hp_text') {
      $newParagraph = Paragraph::create(['type' => 'text']);
      $fieldsToCopy = ['text', 'link', 'field_title'];

      foreach ($fieldsToCopy as $name) {
        if ($newParagraph->hasField($name) && $paragraph->hasField($name) && !$paragraph->get($name)->isEmpty()) {
          $newParagraph->set($name, $paragraph->get($name)->getValue());
        }
      }

      // Copy behaviors.
      $newParagraph->setAllBehaviorSettings($paragraph->getAllBehaviorSettings());
      $newParagraph->save();

      // Manage translations.
      foreach (\Drupal::languageManager()->getLanguages() as $language) {
        $langcode = $language->getId();
        if ($paragraph->hasTranslation($langcode)) {
          $translatedParagraph = $paragraph->getTranslation($langcode);

          if (!$newParagraph->hasTranslation($langcode)) {
            $newParagraph->addTranslation($langcode);
          }

          $translatedNewParagraph = $newParagraph->getTranslation($langcode);

          foreach ($fieldsToCopy as $name) {
            if ($translatedNewParagraph->hasField($name) && $translatedParagraph->hasField($name) && !$translatedParagraph->get($name)->isEmpty()) {
              $translatedNewParagraph->set($name, $translatedParagraph->get($name)->getValue());
            }
          }

          $translatedNewParagraph->save();
        }
      }

      return $newParagraph;
    }

    // For HP media paragraphs, we want to convert them to "standard"
    // media paragraphs.
    if ($paragraph->bundle() === 'hp_media') {
      // Get the media.
      if ($paragraph->hasField('field_media') && !$paragraph->get('field_media')->isEmpty()) {
        /** @var \Drupal\media\MediaInterface $media */
        $media = $paragraph->get('field_media')->entity;

        if (!is_null($media)) {
          $bundle = $media->bundle();

          // Here, we assume that we'll have a paragraph type of the same bundle
          // as the media, and with a reference field with the form "$bundle . '_field'".
          // It's super specific, so I will not add any check on this...
          if (in_array($bundle, ['image', 'video'])) {
            // Create a new paragraph of the needed type.
            $newParagraph = Paragraph::create([
              'type' => $bundle,
              $bundle . '_field' => ['target_id' => $media->id()],
            ]);

            // Copy behaviors.
            $newParagraph->setAllBehaviorSettings($paragraph->getAllBehaviorSettings());
            $newParagraph->save();

            // @todo Maybe delete the previous paragraph?
            //    Waiting for validation of the whole migration before doing this.
            //    Just uncomment the following line when needed.
            // $paragraph->delete();

            return $newParagraph;
          }
        }
      }
    }

    return $paragraph;
  }

  /**
   * @param \Drupal\paragraphs\ParagraphInterface $layoutParagraph
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   * @param string $region
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function migrateBehaviorsToParent(ParagraphInterface $layoutParagraph, ParagraphInterface $paragraph, string $region) {
    $behaviors = $paragraph->getAllBehaviorSettings();

    $parentBehaviors = $layoutParagraph->getAllBehaviorSettings();

    if (!empty($behaviors['a12sfactory_paragraph_grid']['column'])) {
      if (!empty($behaviors['a12sfactory_paragraph_grid']['column']['class'])) {
        $parentBehaviors['layout_paragraphs']['config']['display_options']['grid_attribute_class']['region_classes_override'] = TRUE;
        $parentBehaviors['layout_paragraphs']['config']['display_options']['grid_attribute_class']['region_classes_regions'][$region] = $behaviors['a12sfactory_paragraph_grid']['column']['class'];
      }

      if (!empty($behaviors['a12sfactory_paragraph_grid']['column']['order'])) {
        $parentBehaviors['layout_paragraphs']['config']['display_options']['grid_region_order']['regions'][$region] = $behaviors['a12sfactory_paragraph_grid']['column']['order'];
      }

      if (!empty($behaviors['a12sfactory_paragraph_grid']['column']['align_self'])) {
        $parentBehaviors['layout_paragraphs']['config']['display_options']['grid_vertical_alignment']['regions_override'] = TRUE;
        $parentBehaviors['layout_paragraphs']['config']['display_options']['grid_vertical_alignment']['regions'][$region] = $behaviors['a12sfactory_paragraph_grid']['column']['align_self'];
      }
    }

    $layoutParagraph->setAllBehaviorSettings($parentBehaviors);
    $layoutParagraph->save();
  }

}
