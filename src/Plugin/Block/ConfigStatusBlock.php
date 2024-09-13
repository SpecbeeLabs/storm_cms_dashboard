<?php

namespace Drupal\storm_cms_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration status block.
 *
 * @Block(
 *   id = "config_status_block",
 *   admin_label = @Translation("Configuration Status"),
 *   category = @Translation("Custom Blocks")
 * )
 */
class ConfigStatusBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The sync configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The import storage transformer service.
   *
   * @var \Drupal\Core\Config\ImportStorageTransformer
   */
  protected $importTransformer;

  /**
   * Constructs a ConfigStatusBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   *   The sync storage service.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active storage service.
   * @param \Drupal\Core\Config\ImportStorageTransformer $import_transformer
   *   The import transformer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StorageInterface $sync_storage, StorageInterface $active_storage, ImportStorageTransformer $import_transformer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->syncStorage = $sync_storage;
    $this->activeStorage = $active_storage;
    $this->importTransformer = $import_transformer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('config.import_transformer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $message = '';
    // Transform the sync storage to compare it with the active storage.
    $syncStorage = $this->importTransformer->transform($this->syncStorage);
    $storageComparer = new StorageComparer($syncStorage, $this->activeStorage);
    $storageComparer->createChangelist();
    $source_list = $syncStorage->listAll();

    // If there are no pending configuration changes.
    if (empty($source_list) || !$storageComparer->hasChanges()) {
      $message = $this->t('All configurations are up-to-date.');
      return [
        '#theme' => 'storm_cms_config_status',
        '#status_message' => $message,
        '#changes' => [],
      ];
    }

    $changes = [];
    foreach ($storageComparer->getAllCollectionNames() as $collection) {
      foreach ($storageComparer->getChangelist(NULL, $collection) as $config_change_type => $config_names) {
        if (empty($config_names)) {
          continue;
        }
        $changes[$config_change_type] = count($config_names);
      }
    }

    return [
      '#theme' => 'storm_cms_config_status',
      '#status_message' => $this->t('There are pending configuration changes.'),
      '#changes' => $changes,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Disable caching for this block.
    return 0;
  }

}
