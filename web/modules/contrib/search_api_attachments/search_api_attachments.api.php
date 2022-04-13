<?php

/**
 * @file
 * Hooks provided by the "Search API attachments" module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Determines whether an attachment should be indexed.
 *
 * @param object $file
 *   A file object.
 * @param \Drupal\search_api\Item\ItemInterface $item
 *   The item the file was referenced in.
 * @param string $field_name
 *   The name of the field the file was referenced in.
 *
 * @return bool|null
 *   Return FALSE if the attachment should not be indexed.
 */
function hook_search_api_attachments_indexable($file, \Drupal\search_api\Item\ItemInterface $item, $field_name) {
  // Don't index files on entities owned by our bulk upload bot accounts.
  if (in_array($item->getOriginalObject()->uid, my_module_blocked_uids())) {
    return FALSE;
  }
}

/**
 * Allow other modules to run after content extraction for a file.
 *
 * @param object $file
 *   A file object.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity where the file was referenced in.
 */
function hook_search_api_attachments_content_extracted($file, \Drupal\Core\Entity\EntityInterface $entity) {
  // Search for nodes using media item in specific fields.
  if ($entity->getEntityTypeId() === 'media') {
    $query = \Drupal::entityQuery('node')
      ->condition('field_pdf', $entity->id())
      ->condition('status', 1);

    // Remove access check to ensure all entities are returned.
    $query->accessCheck(FALSE);
    $results = $query->execute();
    if ($results) {
      // For each node, get all the applicable indexes
      // and mark items as need reindex.
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($results);
      foreach ($nodes as $node) {
        $indexes = ContentEntity::getIndexesForEntity($node);
        $item_ids = [];
        if (is_a($node, TranslatableInterface::class)) {
          $translations = $node->getTranslationLanguages();
          foreach ($translations as $translation_id => $translation) {
            $item_ids[] = $node->id() . ':' . $translation_id;
          }
        }
        $datasource_id = 'entity:node';
        foreach ($indexes as $index) {
          $index->trackItemsUpdated($datasource_id, $item_ids);
        }
      }
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
