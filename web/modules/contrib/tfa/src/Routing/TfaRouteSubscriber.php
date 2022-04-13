<?php

namespace Drupal\tfa\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * Class TfaRouteSubscriber.
 *
 * @package Drupal\tfa\Routing
 */
class TfaRouteSubscriber extends RouteSubscriberBase {

  /**
   * Overrides user.login route with our custom login form.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   Route to be altered.
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change path of user login to our overridden TFA login form.
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_form', '\Drupal\tfa\Form\TfaLoginForm');
    }
  }

}
