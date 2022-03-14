<?php

namespace Drupal\metatag\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class EntityViewSubscriber.
 *
 * @package Drupal\metatag\EventSubscriber
 */
class EntityViewSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onViewUnsetSpecifiedTags', 10];
    return $events;
  }

  /**
   * Remove specified tags from head.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The event to process.
   */
  public function onViewUnsetSpecifiedTags(GetResponseForControllerResultEvent $event) {
    $build = $event->getControllerResult();
    _metatag_unset_specified_tags($build);
    $event->setControllerResult($build);
  }

}