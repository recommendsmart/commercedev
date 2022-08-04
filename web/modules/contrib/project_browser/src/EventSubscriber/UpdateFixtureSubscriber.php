<?php

namespace Drupal\project_browser\EventSubscriber;

use Drupal\project_browser\Event\ProjectBrowserEvents;
use Drupal\project_browser\Event\UpdateFixtureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Update Fixture event subscriber.
 */
class UpdateFixtureSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ProjectBrowserEvents::UPDATE_FIXTURE => 'onFixtureUpdate',
    ];
  }

  /**
   * Update fixture only if plugin id is 'drupalorg_mockapi'.
   *
   * @param \Drupal\project_browser\Event\UpdateFixtureEvent $event
   *   The event.
   */
  public function onFixtureUpdate(UpdateFixtureEvent $event) {
    $current_source = $event->enabledSource->getCurrentSource();
    if ($current_source && $current_source->getPluginId() === 'drupalorg_mockapi') {
      $current_source->updateMostRecentChanges();
    }
  }

}
