<?php

namespace Drupal\a12sfactory\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PhpErrorsSubscriber
 *
 * @package Drupal\a12sfactory\EventSubscriber
 */
class PhpErrorsSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The bare HTML page renderer.
   *
   * @var \Drupal\Core\Render\BareHtmlPageRendererInterface
   */
  protected $bareHtmlPageRenderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache backend for plugin discovery.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDiscovery;

  /**
   * Constructs a new MaintenanceModeSubscriber.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation.
   * @param \Drupal\Core\Render\BareHtmlPageRendererInterface $bare_html_page_renderer
   *   The bare HTML page renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_discovery
   *   The cache backend for plugin discovery.
   */
  public function __construct(TranslationInterface $translation, BareHtmlPageRendererInterface $bare_html_page_renderer, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_discovery) {
    $this->bareHtmlPageRenderer = $bare_html_page_renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheDiscovery = $cache_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Take care of errors that have not be handled by other subscribers, but
    // just before the last subscriber (256).
    $events[KernelEvents::EXCEPTION][] = ['onException', -255];
    return $events;
  }

  /**
   * Take care of errors that are not handled by anyone.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    // There is an very old issue with paragraph entity type, where the
    // "translation" handler is missing from the entity type definition.
    // In such case, a simple rebuild of the entity cache fixes the issue.
    // It seems that the "content_translation_entity_type_alter()" hook
    // is missing for some unclear reasons.
    // @see https://www.drupal.org/project/paragraphs/issues/3031598
    if ($event->getException() instanceof InvalidPluginDefinitionException) {
      /** @var InvalidPluginDefinitionException $exception */
      $exception = $event->getException();

      if ($exception->getPluginId() === 'paragraph') {
        try {
          $entity_type = $this->entityTypeManager->getDefinition('paragraph');

          if (!$entity_type->hasHandlerClass('translation')) {
            // Rebuild the entity_type cache discovery, so the definition of the
            // paragraph entity type will be rebuilt on next call.
            $this->cacheDiscovery->delete('entity_type');

            // Reload current page. Note that we loose the anchor part if any.
            $request = $event->getRequest();
            $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());

            // Do not try to redirect non-HTML request, as it may lead to bigger
            // errors than the one we try to fix.
            if ($format == 'html') {
              $url = $request->getSchemeAndHttpHost() . $request->getRequestUri();

              if (!empty($request->getQueryString())) {
                $url .= '?' . $request->getQueryString();
              }

              $response = new RedirectResponse($url, 302, ['Cache-Control' => 'no-cache']);
              $event->setResponse($response);
              return;
            }
          }
        }
        catch (PluginNotFoundException $e) {
          // Pass through
        }
      }
    }

    $request = $event->getRequest();
    $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());

    if ($format === 'html') {
      drupal_maintenance_theme();
      $response = $this->bareHtmlPageRenderer->renderBarePage(
        ['#markup' => $this->t('The website encountered an unexpected error. Please try again later.')],
        $this->t('Unexpected error'),
        'maintenance_page'
      );
      $event->setResponse($response);
    }
  }

}
