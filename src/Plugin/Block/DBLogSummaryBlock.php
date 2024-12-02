<?php

namespace Drupal\storm_cms_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Block(
  id: "db_log_summary_block",
  admin_label: new TranslatableMarkup("DB Log Summary"),
  category: new TranslatableMarkup("Dashboard")
)]

/**
 * The DB Logger calss for log summary.
 */
class DBLogSummaryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a DbLogStatusBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Check if the dblog module is enabled.
    $enabled = $this->moduleHandler->moduleExists('dblog');
    $error = $enabled ? TRUE : FALSE;

    // Early exit if the DB Log module is not enabled.
    if (!$error) {
      return [
        '#theme' => 'db_log_summary',
        '#criticals' => 0,
        '#warnings' => 0,
        '#error_display' => $error,
      ];
    }

    // Fetch counts for different severities.
    $critical_count = $this->getLogCount(RfcLogLevel::CRITICAL);
    $warning_count = $this->getLogCount(RfcLogLevel::WARNING);

    return [
      '#theme' => 'db_log_summary',
      '#criticals' => $critical_count,
      '#warnings' => $warning_count,
      '#error_display' => $error,
    ];
  }

  /**
   * Helper function to get the count of log entries by severity.
   *
   * @param int $severity
   *   The log severity.
   *
   * @return string
   *   The count of the severity.
   */
  private function getLogCount($severity) {
    $connection = Database::getConnection();
    $query = $connection->select('watchdog', 'w')
      ->condition('severity', $severity);
    $count = $query->countQuery()->execute()->fetchField();
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Disable caching for this block.
    return 0;
  }

}
