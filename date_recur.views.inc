<?php

/**
 * @file
 * Provides views data for the date_recur module.
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\date_recur\DateRecurOccurrences;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function date_recur_field_views_data(FieldStorageConfigInterface $fieldDefinition) {
  $viewsDataCheckIfMoveColumnName = function ($fieldName, $columnName, $columnData) {
    $fieldsToMove = [
      $fieldName,
      $fieldName . '_value',
      $fieldName . '_end_value',
    ];
    return in_array($columnName, $fieldsToMove) || (strpos($columnName, $fieldName . '_value') === 0);
  };

  $return_data = [];

  $entityType = \Drupal::entityTypeManager()->getDefinition($fieldDefinition->getTargetEntityTypeId());
  $storage = _views_field_get_entity_type_storage($fieldDefinition);
  /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $tableMapping */
  $tableMapping = $storage->getTableMapping();

  $entityViewsTable = $entityType->getDataTable() ?: $entityType->getBaseTable();
  $fieldName = $fieldDefinition->getName();
  $fieldTable = $tableMapping->getDedicatedDataTableName($fieldDefinition);

  module_load_include('inc', 'datetime', 'datetime.views');
  $data = datetime_field_views_data($fieldDefinition);
  if (empty($data)) {
    return [];
  }

  // @todo: Revision support.
  $recur_table_name = DateRecurOccurrences::getOccurrenceCacheStorageTableName($fieldDefinition);
  if (\Drupal::database()->schema()->tableExists($recur_table_name)) {
    return [];
  }

  $field_table = $data[$fieldTable];
  $recur_table = $field_table;

  $join_key = array_keys($field_table['table']['join'])[0];
  $recur_table['table']['join'] = $field_table['table']['join'];
  $recur_table['table']['join'][$join_key]['table'] = $recur_table_name;
  $recur_table['table']['join'][$join_key]['extra'] = [];

  // Update table name references.
  $handler_keys = ['argument', 'filter', 'sort', 'field'];
  foreach ($recur_table as $column_name => &$column_data) {
    if ($column_name == 'table') {
      continue;
    }
    if (!$viewsDataCheckIfMoveColumnName($fieldName, $column_name, $column_data)) {
      unset($recur_table[$column_name]);
    }
    else {
      unset($field_table[$column_name]);
      foreach ($handler_keys as $key) {
        if (!empty($column_data[$key]['table'])) {
          $column_data[$key]['table'] = $recur_table_name;
          $column_data[$key]['additional fields'] = [
            $fieldName . '_value',
            $fieldName . '_end_value',
            'delta',
            'field_delta',
          ];
        }
      }
      if ($column_name == $fieldName . '_value') {
        $column_data['field']['click sortable'] = TRUE;
      }
    }
  }

  $custom_handler_name = $fieldName . '_simple_render';
  $recur_table[$custom_handler_name] = $recur_table[$fieldName];
  $recur_table[$custom_handler_name]['title'] .= ' (simple render)';
  $recur_table[$custom_handler_name]['field']['id'] = 'date_recur_field_simple_render';

  $return_data[$recur_table_name] = $recur_table;
  $return_data[$fieldTable] = $field_table;

  return $return_data;
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
