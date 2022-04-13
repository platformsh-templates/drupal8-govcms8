<?php

namespace Drupal\module_permissions;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * UserAdminPermissions AccessCheck class.
 *
 * @package Drupal\module_permissions
 */
class UserAdminPermissionsAccessCheck implements AccessInterface {

  /**
   * Determines access for the route user.admin_permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function access(AccountInterface $account) {
    if ($account->hasPermission('administer permissions')) {
      $has_access = TRUE;
    }
    else {
      $has_access = $account->hasPermission('administer managed modules')
        && $account->hasPermission('administer managed modules permissions');
    }
    return AccessResult::allowedIf($has_access)
      ->cachePerUser()
      ->cachePerPermissions();
  }

}
