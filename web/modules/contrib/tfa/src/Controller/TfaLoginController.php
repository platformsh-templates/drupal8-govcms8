<?php

namespace Drupal\tfa\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tfa\TfaLoginTrait;
use Drupal\user\UserInterface;

/**
 * Provides access control on the verification form.
 *
 * @package Drupal\tfa\Controller
 */
class TfaLoginController {
  use TfaLoginTrait;

  /**
   * Denies access unless user matches hash value.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route
   *   The route to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current logged in user, if any.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(RouteMatchInterface $route, AccountInterface $account) {
    $user = $route->getParameter('user');

    // Start with a positive access check which is cacheable for the current
    // route, which includes both route name and parameters.
    $access = AccessResult::allowed();
    $access->addCacheContexts(['route']);
    if (!$user instanceof UserInterface) {
      return $access->andIf(AccessResult::forbidden('Invalid user.'));
    }

    // Since we're about to check the login hash, which is based on properties
    // of the user, we now need to vary the cache based on the user object.
    $access->addCacheableDependency($user);
    // If the login hash doesn't match, forbid access.
    if ($this->getLoginHash($user) !== $route->getParameter('hash')) {
      return $access->andIf(AccessResult::forbidden('Invalid hash.'));
    }

    // If we've gotten here, we need to check that the current user is allowed
    // to use TFA features for this account. To make this decision, we need to
    // vary the cache based on the current user.
    $access->addCacheableDependency($account);
    if ($account->isAuthenticated()) {
      return $access->andIf($this->accessSelfOrAdmin($route, $account));
    }

    return $access;
  }

  /**
   * Checks that current user is selected user or is admin.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route
   *   The route to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function accessSelfOrAdmin(RouteMatchInterface $route, AccountInterface $account) {
    $target_user = $route->getParameter('user');

    // Start with a positive access result that can be cached based on the
    // current route, which includes both route name and parameters.
    $access = AccessResult::allowed();
    $access->addCacheContexts(['route']);

    if (!$target_user instanceof UserInterface) {
      return $access->andIf(AccessResult::forbidden('Invalid user.'));
    }

    // Before we perform any checks that are dependent on the current user, make
    // the result dependent on the current user. If we were just checking perms
    // here, we could rely on user.permissions, but in this case we are also
    // dependent on the ID of the user, which requires the higher level user
    // context.
    $access->addCacheableDependency($account);

    if (!$account->isAuthenticated()) {
      return $access->andIf(AccessResult::forbidden('User is not logged in.'));
    }

    $is_self = $account->id() === $target_user->id();
    // Any user including admin only has the access to their own tfa setup pages.
    // @see https://www.drupal.org/project/tfa/issues/3075304
    if (!$is_self) {
      $method = $route->getParameter('method');
      // All users are banned from visiting others' TFA recovery codes,
      // including admin users.
      if ($method === 'tfa_recovery_code') {
        return $access->andIf(AccessResult::forbidden('You can not access others\' TFA recovery codes.'));
      }
    }

    $is_admin = $account->hasPermission('administer users');
    $is_self_or_admin = AccessResult::allowedIf($is_self || $is_admin);

    return $access->andIf($is_self_or_admin);
  }

}
