<?php

/**
 * @file
 * Provides views data for the date_recur module.
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
    // Remove fields not present in date_recur tables and change the join to
    // the date_recur cache table.
    // @todo: Are there situations where the join can have more than one table?
    if (!empty($table_data['table']['join']) && count($table_data['table']['join'])  === 1) {
      foreach ($table_data['table']['join'] as $join_key => &$join) {
        if (empty($join['extra'])) {
          continue;
        }
        $join['table'] = $table_name;
        foreach ($join['extra'] as $extra_key => $extra) {
          if (in_array($extra['field'], ['deleted', 'langcode'])) {
            unset($join['extra'][$extra_key]);
          }
        }
      }
    }

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
