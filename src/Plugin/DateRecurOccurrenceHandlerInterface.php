<?php

namespace Drupal\date_recur\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 * Defines an interface for Date recur occurrence handler plugins.
 */
interface DateRecurOccurrenceHandlerInterface extends PluginInspectionInterface {

  public function onFieldCreate(FieldConfigInterface $field);

  public function onFieldUpdate(FieldConfigInterface $field);

  public function onFieldDelete(FieldConfigInterface $field);

  public function init(DateRecurItem $item);

  public function getOccurrencesForDisplay($start = NULL, $end = NULL, $num = NULL);
  public function humanReadable();


  public function viewsData(FieldStorageConfigInterface $field_storage, $data);

  /**
   * @param bool $update
   * @param int $field_delta
   */
  public function onSave($update, $field_delta);
  public function onSaveMaxDelta($field_delta);

  public function onDelete();

  public function onDeleteRevision();

}
