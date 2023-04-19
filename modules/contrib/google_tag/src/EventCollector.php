<?php

declare(strict_types=1);

namespace Drupal\google_tag;

use Drupal\google_tag\Plugin\GoogleTag\Event\GoogleTagEventInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Event collector service.
 */
final class EventCollector implements EventCollectorInterface {

  /**
   * Events list.
   *
   * @phpstan-var array<int, \Drupal\google_tag\Plugin\GoogleTag\Event\GoogleTagEventInterface>
   */
  private array $events = [];

  /**
   * Collector constructor.
   *
   * @param \Drupal\google_tag\GoogleTagEventManager $googleTagEventManager
   *   Event plugin manager.
   * @param \Drupal\google_tag\TagContainerResolver $tagResolver
   *   Tag resolver service.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Session service.
   */
  public function __construct(
    private GoogleTagEventManager $googleTagEventManager,
    private TagContainerResolver $tagResolver,
    private SessionInterface $session,
  ) {
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function addEvent(string $name, array $contexts = []): void {
    $event = $this->createEventInstance($name, $contexts);
    if ($event === NULL) {
      return;
    }

    $this->events[] = $event;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function addDelayedEvent(string $name, array $contexts = []): void {
    $event = $this->createEventInstance($name, $contexts);
    if ($event === NULL) {
      return;
    }

    $delayed_events = $this->session->get('google_tag_events', []);
    $delayed_events[] = $event;
    $this->session->set('google_tag_events', $delayed_events);
  }

  /**
   * {@inheritDoc}
   */
  public function getEvents(): array {
    $events = $this->events;
    $this->events = [];
    $delayed_events = $this->session->get('google_tag_events', []);
    $this->session->set('google_tag_events', []);
    return $delayed_events + $events;
  }

  /**
   * Instantiates an event plugin.
   *
   * @param string $name
   *   Event name.
   * @param array $contexts
   *   Event plugin context.
   *
   * @return \Drupal\google_tag\Plugin\GoogleTag\Event\GoogleTagEventInterface|null
   *   Event plugin if configured, otherwise null.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function createEventInstance(string $name, array $contexts = []): ?GoogleTagEventInterface {
    $config = $this->tagResolver->resolve();
    if ($config === NULL || !$config->hasEvent($name)) {
      return NULL;
    }

    $event = $this->googleTagEventManager->createInstance(
      $name,
      $config->getEventConfiguration($name),
    );
    assert($event instanceof GoogleTagEventInterface);
    foreach ($contexts as $id => $context) {
      $event->setContextValue($id, $context);
    }
    return $event;
  }

}
