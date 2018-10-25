<?php

namespace Drupal\date_recur\Event;

/**
 * Reacts to changes on entity types.
 */
final class DateRecurEvents {

  /**
   * Dispatched after an entity containing a date recur field is saved.
   */
  const FIELD_VALUE_SAVE = 'date_recur_field_value_save';

  /**
   * Dispatched when an entity containing date recur fields is almost deleted.
   */
  const FIELD_ENTITY_DELETE = 'date_recur_field_entity_delete';

  /**
   * Dispatched when an entity revision is deleted.
   */
  const FIELD_REVISION_DELETE = 'date_recur_field_entity_revision_delete';

}
