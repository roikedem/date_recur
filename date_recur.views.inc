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
  dsm('load data');
  module_load_include('inc', 'datetime', 'datetime.views');
  $data = datetime_field_views_data($field_storage);
  $plugin = date_recur_create_occurrence_handler($field_storage);
  $data = $plugin->viewsData($field_storage, $data);
  return $data;
}
