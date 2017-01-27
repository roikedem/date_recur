<?php

namespace Drupal\date_recur;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\Core\TypedData\TypedData;

/**
 *
 */
class DateRecurOccurrencesComputed extends ItemList {

  protected $occurrences = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($langcode = NULL) {
    /** @var DateRecurItem $item */
    $item = $this->getParent();
    $handler = $item->getOccurrenceHandler();
    $occurrences = $handler->getOccurrencesForDisplay();
    $values = [];
    foreach ($occurrences as $delta => $occurrence) {
      $values[] = [
        'value' => $occurrence['value'],
        'end_value' => $occurrence['end_value'],
      ];
    }
    $this->setValue($values);
    return parent::getValue();
  }

}
