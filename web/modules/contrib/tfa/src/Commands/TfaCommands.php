<?php

namespace Drupal\tfa\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * A Drush command file.
 */
class TfaCommands extends DrushCommands implements SanitizePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function sanitize($result, CommandData $commandData) {
    // DBTNG does not support expressions in delete queries.
    $sql = "DELETE FROM users_data WHERE LEFT(name, 4) = 'tfa_'";
    \Drupal::service('database')->query($sql);
    $this->logger()->success('Removed recovery codes and other user-specific TFA data.');
  }

  /**
   * {@inheritdoc}
   */
  public function messages(&$messages, InputInterface $input) {
    return $messages[] = dt('Remove recovery codes and other user-specific TFA data.');
  }

}
