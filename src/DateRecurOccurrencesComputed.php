<?php

namespace Drupal\date_recur;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 *
 */
class DateRecurOccurrencesComputed extends ItemList {

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
    if ($item instanceof DateRecurItem) {
      $values = $item->getOccurrenceHandler()->getOccurrencesForComputedProperty();
      $this->setValue($values);
    }
    return parent::getValue();
  }


  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->getValue();
    return parent::isEmpty();
  }

}
