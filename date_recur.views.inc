<?php

/**
 * @file
 * Provides views data for the date_recur module.
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function date_recur_field_views_data(FieldStorageConfigInterface $field_storage) {
  $plugin = date_recur_create_occurrence_handler($field_storage);
  return $plugin->viewsData($field_storage);
}

/**
 * Implements hook_views_data().
 */
function date_recur_views_data() {
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager */
  $entityManager = \Drupal::service('entity_type.manager');
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
  $entityFieldManager = \Drupal::service('entity_field.manager');

  // Date recur fields keyed by entity type id.
  $fieldsAll = [];

  // Get all date recur fields as base and attached fields.
  foreach ($entityManager->getDefinitions() as $entityType) {
    // \Drupal\views\EntityViewsData class only allows entities with
    // \Drupal\Core\Entity\Sql\SqlEntityStorageInterface.
    // Only fieldable entities have base fields.
    if (
      $entityManager->getStorage($entityType->id()) instanceof SqlEntityStorageInterface &&
      $entityType->hasHandlerClass('views_data') &&
      $entityType->entityClassImplements(FieldableEntityInterface::class)) {
      $fieldsAll[$entityType->id()] = array_filter(
        $entityFieldManager->getFieldStorageDefinitions($entityType->id()),
        function (FieldStorageDefinitionInterface $baseField) {
          return 'date_recur' === $baseField->getType();
        }
      );
    }
  }

  // Remove entity types with no date recur fields.
  $fieldsAll = array_filter($fieldsAll);

  // @todo test single vs multi cardinality.

  $data = [];
  foreach ($fieldsAll as $entityTypeId => $fields) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */

    $entityType = $entityManager->getDefinition($entityTypeId);
    $entityViewsTable = $entityType->getDataTable() ?: $entityType->getBaseTable();

    foreach ($fields as $field) {
      $fieldName = $field->getName();
      $entityIdField = $entityType->getKey('id');
      $data[$entityViewsTable][$fieldName . '_occurrences']['filter'] = [
        'id' => 'date_recur_occurrences_filter',
        'title' => \t('Occurrences filter for @field_name', [
          '@field_name' => $field->getLabel(),
        ]),
        // Instruct the filter to join the occurrence.entity_id field on
        // base.entityId:
        'field base entity_id' => $entityIdField,
        'date recur field name' => $fieldName,
        'entity_type' => $entityType->id(),
      ];
    }
  }

  return $data;
}
