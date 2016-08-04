<?php

/**
 * @file
 * Provides views data for the datetime module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function date_recur_field_views_data(FieldStorageConfigInterface $field_storage) {
  module_load_include('inc', 'datetime', 'datetime.views');
  $data = datetime_field_views_data($field_storage);
  if (empty($data)) {
    return [];
  }
  $field_name = $field_storage->getName();
  list($table_alias, $revision_table_alias) = array_keys($data);
  // @todo: Revision support.
  unset($data[$revision_table_alias]);
  $table_name = date_recur_get_table_name($field_storage);

  foreach ($data as $table_alias => &$table_data) {
    // Remove fields not present in date_recur tables.
    if (!empty($table_data['table']['join'])) {
      foreach ($table_data['table']['join'] as $join_key => $join) {
        if (empty($join['extra'])) {
          continue;
        }
        foreach ($join['extra'] as $extra_key => $extra) {
          if (in_array($extra['field'], ['deleted', 'langcode'])) {
            unset($table_data['table']['join'][$join_key]['extra'][$extra_key]);
          }
        }
      }
    }

    // Remove unneeded fields for now.
    unset($table_data[$field_name . '_rrule']);
    unset($table_data[$field_name . '_recur_end_value']);

    // Update table name references.
    $handler_keys = ['argument', 'filter', 'sort', 'field'];
    foreach ($table_data as $column_name => &$column_data) {
      if (strpos($column_name, $field_name) === 0) {
        foreach ($handler_keys as $key) {
          if (!empty($column_data[$key]['table'])) {
            $column_data[$key]['table'] = $table_name;
          }
        }
      }
    }
  }
  return [$table_name => $data[$table_alias]];
}
