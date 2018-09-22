<?php

namespace Drupal\date_recur\Plugin\Field\FieldType;

use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList;

/**
 * Represents a configurable entity date_recur field.
 */
class DateRecurFieldItemList extends DateRangeFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);
    foreach ($this as $field_delta => $item) {
      /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */
      $item->getOccurrenceHandler()->onSave($update, $field_delta);
    }
    if ($update && isset($field_delta)) {
      $item->getOccurrenceHandler()->onSaveMaxDelta($field_delta);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */
    foreach ($this as $field_delta => $item) {
      $item->getOccurrenceHandler()->onDelete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    parent::deleteRevision();
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */
    foreach ($this as $field_delta => $item) {
      $item->getOccurrenceHandler()->onDeleteRevision();
    }
  }

}
