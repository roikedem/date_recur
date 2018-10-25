<?php

namespace Drupal\date_recur\Plugin\Field\FieldType;

use Drupal\date_recur\Event\DateRecurEvents;
use Drupal\date_recur\Event\DateRecurValueEvent;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList;

/**
 * Recurring date field item list.
 */
class DateRecurFieldItemList extends DateRangeFieldItemList {

  /**
   * An event dispatcher, primarily for unit testing purposes.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|null
   */
  protected $eventDispatcher = NULL;

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);
    $event = new DateRecurValueEvent($this, !$update);
    $this->getDispatcher()->dispatch(DateRecurEvents::FIELD_VALUE_SAVE, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    $event = new DateRecurValueEvent($this, FALSE);
    $this->getDispatcher()->dispatch(DateRecurEvents::FIELD_ENTITY_DELETE, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    parent::deleteRevision();
    $event = new DateRecurValueEvent($this, FALSE);
    $this->getDispatcher()->dispatch(DateRecurEvents::FIELD_REVISION_DELETE, $event);
  }

  /**
   * Get the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher.
   */
  protected function getDispatcher() {
    if (isset($this->eventDispatcher)) {
      return $this->eventDispatcher;
    }
    return \Drupal::service('event_dispatcher');
  }

  /**
   * Set the event dispatcher.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface|null $eventDispatcher
   *   The event dispatcher.
   */
  public function setEventDispatcher($eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

}
