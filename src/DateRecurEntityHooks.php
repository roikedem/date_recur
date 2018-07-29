<?php

namespace Drupal\date_recur;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Reacts to Drupal entity hooks.
 */
class DateRecurEntityHooks {

  /**
   * Provides the Date recur occurrence handler plugin manager.
   *
   * @var \Drupal\date_recur\Plugin\DateRecurOccurrenceHandlerManagerInterface
   */
  protected $dateRecurOccurrenceManager;

  /**
   * Constructs a new DateRecurEntityHooks.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $dateRecurOccurrenceManager
   *   Provides the Date recur occurrence handler plugin manager.
   */
  public function __construct(PluginManagerInterface $dateRecurOccurrenceManager) {
    $this->dateRecurOccurrenceManager = $dateRecurOccurrenceManager;
  }

  /**
   * Reacts to an inserted field storage config entity.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldStorageConfig
   *   Field storage config.
   *
   * @see \date_recur_field_storage_config_insert()
   *
   * @throws \Exception
   *   Throws exceptions.
   */
  public function fieldStorageConfigInsert(FieldStorageConfigInterface $fieldStorageConfig) {
    if ($fieldStorageConfig->getType() != 'date_recur') {
      return;
    }
    $this->getInstanceFromDefinition($fieldStorageConfig)
      ->onFieldCreate($fieldStorageConfig);
  }

  /**
   * Reacts to an updated field storage config entity.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldStorageConfig
   *   Field storage config.
   *
   * @throws \Exception
   *   Throws exceptions.
   *
   * @see \date_recur_field_storage_config_update()
   */
  public function fieldStorageConfigUpdate(FieldStorageConfigInterface $fieldStorageConfig) {
    if ($fieldStorageConfig->getType() != 'date_recur') {
      return;
    }
    $this->getInstanceFromDefinition($fieldStorageConfig)
      ->onFieldUpdate($fieldStorageConfig);
  }

  /**
   * Reacts to a deleted field storage config entity.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldStorageConfig
   *   Field storage config.
   *
   * @throws \Exception
   *   Throws exceptions.
   *
   * @see \date_recur_field_storage_config_delete()
   */
  public function fieldStorageConfigDelete(FieldStorageConfigInterface $fieldStorageConfig) {
    if ($fieldStorageConfig->getType() != 'date_recur') {
      return;
    }
    $this->getInstanceFromDefinition($fieldStorageConfig)
      ->onFieldDelete($fieldStorageConfig);
  }

  /**
   * Get an occurrence handler for a field definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $fieldStorageConfig
   *   Field storage config.
   *
   * @return \Drupal\date_recur\Plugin\DateRecurOccurrenceHandlerInterface
   *   A date recur occurrence handler instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  protected function getInstanceFromDefinition(FieldStorageDefinitionInterface $fieldStorageConfig) {
    if ($fieldStorageConfig->getType() != 'date_recur') {
      throw new \InvalidArgumentException("Expected field of type date_recur.");
    }
    $pluginName = $fieldStorageConfig->getSetting('occurrence_handler_plugin');
    return $this->dateRecurOccurrenceManager->createInstance($pluginName);
  }

}
