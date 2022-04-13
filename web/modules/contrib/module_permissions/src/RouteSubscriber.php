<?php

namespace Drupal\module_permissions;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route Subscriber class.
 *
 * @package Drupal\module_permissions
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add custom access check to admin/people/permissions.
    $route = $collection->get('user.admin_permissions');
    if ($route) {
      $requirements = $route->getRequirements();
      unset($requirements['_permission']);
      /* @see UserAdminPermissionsAccessCheck::access() */
      $requirements['_module_permissions_user_admin_permissions_access_check'] = 'TRUE';
      $route->setRequirements($requirements);
    }

    // Add extra permissions to admin/modules.
    $route = $collection->get('system.modules_list');
    if ($route) {
      $route->setRequirement('_permission', 'administer modules+administer managed modules');
    }

    // Add extra permissions to admin/modules/list/confirm.
    $route = $collection->get('system.modules_list_confirm');
    if ($route) {
      $route->setRequirement('_permission', 'administer modules+administer managed modules');
    }

    // Add extra permissions to admin/modules/uninstall.
    $route = $collection->get('system.modules_uninstall');
    if ($route) {
      $route->setRequirement('_permission', 'administer modules+administer managed modules');
    }

    // Add extra permissions to admin/modules/uninstall/confirm.
    $route = $collection->get('system.modules_uninstall_confirm');
    if ($route) {
      $route->setRequirement('_permission', 'administer modules+administer managed modules');
    }
  }

}
