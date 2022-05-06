<?php
/**
 * @file
 * Synchronize translation for the given paragraph entities.
 */

namespace Drupal\a12sfactory\Paragraphs;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TranslationSynchronization implements EventSubscriberInterface {

  /**
   * Drupal Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Stores all paragraphs currently scheduled for translation sync.
   *
   * @var array
   */
  protected $scheduledParagraphs = [];

  /**
   * TranslationSynchronization constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Drupal Entity Field Manager.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Adds a paragraph to the list of paragraphs ready to be synced.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph to be synced.
   */
  public function deferSync(Paragraph $paragraph) {
    $this->scheduledParagraphs[] = $paragraph->id();
  }

  /**
   * Syncs all paragraphs scheduled for sync.
   *
   * Creates translations for the paragraph for all languages, the parent entity
   * is currently translated to.
   */
  public function sync(PostResponseEvent $event) {
    foreach ($this->scheduledParagraphs as $id) {
      $paragraph = Paragraph::load($id);
      /** @var ContentEntityInterface|null $parent */
      $parent = $paragraph->getParentEntity();

      // We are supposed to have always a parent, but is some specific case,
      // like migrations, the parent may not exist.
      if ($parent && $paragraph->isTranslatable()) {
        // We only want languages from the parent entity.
        $languages = $parent->getTranslationLanguages();

        foreach ($languages as $language) {
          // Entity doesn't have a translation for this language yet.
          if (!$paragraph->hasTranslation($language->getId())) {
            // Recreating the paragraph.
            $values = [];

            $field_definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $paragraph->bundle());

            foreach (array_keys($field_definitions) as $field_name) {
              if (isset($paragraph->{$field_name})) {
                $values[$field_name] = $paragraph->{$field_name};
              }
            }

            // Do not publish the paragraph, the translators will need to review
            // and translate it before.
            $values['status'] = FALSE;
            $paragraph->addTranslation($language->getId(), $values);
          }
        }

        $paragraph->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['sync'];
    return $events;
  }

}
