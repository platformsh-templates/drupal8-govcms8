<?php

namespace Drupal\ds\Plugin\DsField\Block;

use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the title of a block.
 *
 * @DsField(
 *   id = "block_title",
 *   title = @Translation("Title"),
 *   entity_type = "block_content",
 *   provider = "block_content"
 * )
 */
class BlockTitle extends Title {

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    return 'info';
  }

}
