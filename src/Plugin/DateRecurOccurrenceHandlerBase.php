<?php

namespace Drupal\date_recur\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Zend\Stdlib\Exception\InvalidArgumentException;

/**
 * Base class for Date recur occurrence handler plugins.
 */
abstract class DateRecurOccurrenceHandlerBase extends PluginBase implements DateRecurOccurrenceHandlerInterface {

    /**
     * Drupal\Core\Database\Driver\mysql\Connection definition.
     *
     * @var \Drupal\Core\Database\Driver\mysql\Connection
     */
  protected $database;

  /**
   * @param FieldStorageDefinitionInterface|FieldDefinitionInterface $field
   * @return string
   */
  protected function getOccurrenceTableName($field) {
    if (! ($field instanceof FieldStorageDefinitionInterface || $field instanceof FieldDefinitionInterface)) {
      throw new InvalidArgumentException();
    }
    $entity_type = $field->getTargetEntityTypeId();
    $field_name = $field->getName();
    $table_name = 'date_recur__' . $entity_type . '__' . $field_name;
    return $table_name;
  }


}
