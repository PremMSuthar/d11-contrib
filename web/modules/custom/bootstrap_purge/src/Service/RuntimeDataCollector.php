<?php

namespace Drupal\bootstrap_purge\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Service for collecting runtime usage data.
 */
class RuntimeDataCollector {

  /**
   * The database connection.
   */
  protected $database;

  /**
   * The config factory.
   */
  protected $configFactory;

  /**
   * The logger channel.
   */
  protected $logger;

  /**
   * Constructs a RuntimeDataCollector object.
   */
  public function __construct(
    Connection $database,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('bootstrap_purge');
  }

  /**
   * Stores runtime usage data.
   */
  public function storeRuntimeData(array $data) {
    try {
      if (empty($data['route']) || empty($data['selectors'])) {
        return FALSE;
      }

      $record = [
        'route' => $data['route'],
        'selectors' => json_encode($data['selectors']),
        'events' => json_encode($data['events'] ?? []),
        'duration' => $data['duration'] ?? 0,
        'url' => $data['url'] ?? '',
        'viewport_width' => $data['viewport']['width'] ?? 0,
        'viewport_height' => $data['viewport']['height'] ?? 0,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip_address' => $this->getAnonymizedIp(),
        'timestamp' => $data['timestamp'] ?? time(),
        'created' => time(),
      ];

      $this->database->insert('bootstrap_purge_runtime_data')
        ->fields($record)
        ->execute();

      return TRUE;
    } catch (\Exception $e) {
      $this->logger->error('Failed to store runtime data: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets runtime usage data for analysis.
   */
  public function getRuntimeData(array $options = []) {
    $query = $this->database->select('bootstrap_purge_runtime_data', 'rd');
    $query->fields('rd');

    if (!empty($options['route'])) {
      $query->condition('route', $options['route']);
    }

    if (!empty($options['since'])) {
      $query->condition('timestamp', $options['since'], '>=');
    }

    if (!empty($options['limit'])) {
      $query->range(0, $options['limit']);
    }

    $query->orderBy('timestamp', 'DESC');
    $results = $query->execute()->fetchAll();

    $processed_data = [];
    foreach ($results as $row) {
      $processed_data[] = [
        'id' => $row->id,
        'route' => $row->route,
        'selectors' => json_decode($row->selectors, TRUE),
        'events' => json_decode($row->events, TRUE),
        'duration' => $row->duration,
        'url' => $row->url,
        'viewport' => [
          'width' => $row->viewport_width,
          'height' => $row->viewport_height,
        ],
        'timestamp' => $row->timestamp,
        'created' => $row->created,
      ];
    }

    return $processed_data;
  }

  /**
   * Gets anonymized IP address for privacy.
   */
  protected function getAnonymizedIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (empty($ip)) {
      return '';
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $parts = explode('.', $ip);
      $parts[3] = '0';
      return implode('.', $parts);
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      $parts = explode(':', $ip);
      for ($i = max(0, count($parts) - 4); $i < count($parts); $i++) {
        $parts[$i] = '0';
      }
      return implode(':', $parts);
    }

    return '';
  }

}