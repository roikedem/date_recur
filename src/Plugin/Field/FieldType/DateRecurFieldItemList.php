<?php

namespace Drupal\date_recur\Plugin\Field\FieldType;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList;


/**
 * Represents a configurable entity date_recur field.
 */
class DateRecurFieldItemList extends DateRangeFieldItemList {
  public function postSave($update) {
    parent::postSave($update);

    $table_name = date_recur_get_table_name($this->getFieldDefinition());
    $entity_id = $this->getEntity()->id();
    $revision_id = $this->getEntity()->getRevisionId();
    $field_name = $this->getName();
    if ($update) {
      db_delete($table_name)
        ->condition('entity_id', $entity_id)
        ->execute();
    }

    $fields = ['entity_id', 'revision_id', 'field_delta', $field_name . '_value', $field_name . '_end_value', 'delta'];
    $default_values = [$entity_id, $revision_id];

    $q = db_insert($table_name)->fields($fields);

    $delta = 0;
    /** @var DateRecurItem $item*/
    foreach ($this as $field_delta => $item) {
      $dates = $item->getOccurrencesForStorage();
      foreach ($dates as $date) {
        $q->values(array_merge($default_values, [$field_delta], $date, [$delta]));
        $delta++;
      }
    }
    $q->execute();
  }

  public function delete() {
    parent::delete();
    $table_name = date_recur_get_table_name($this->getFieldDefinition());
    db_delete($table_name)->condition('entity_id', $this->getEntity()->id());
  }

  public function deleteRevision() {
    parent::deleteRevision();
    $table_name = date_recur_get_table_name($this->getFieldDefinition());
    db_delete($table_name)->condition('revision_id', $this->getEntity()->getRevisionId());
  }

}
