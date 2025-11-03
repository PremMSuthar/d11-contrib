<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Service for analyzing watchdog logs and error reporting.
 */
class WatchdogAnalyzer {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a WatchdogAnalyzer object.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Analyzes watchdog logs and error reporting.
   */
  public function analyze() {
    return [
      'watchdog_status' => $this->getWatchdogStatus(),
      'error_404_analysis' => $this->analyze404Errors(),
      'php_error_analysis' => $this->analyzePHPErrors(),
      'watchdog_statistics' => $this->getWatchdogStatistics(),
      'log_age_analysis' => $this->analyzeLogAge(),
      'error_trends' => $this->analyzeErrorTrends(),
      'top_error_sources' => $this->getTopErrorSources(),
      'recommendations' => $this->generateWatchdogRecommendations(),
    ];
  }

  /**
   * Gets watchdog status.
   */
  protected function getWatchdogStatus() {
    $dblog_enabled = $this->moduleHandler->moduleExists('dblog');
    $syslog_enabled = $this->moduleHandler->moduleExists('syslog');
    
    return [
      'dblog_enabled' => $dblog_enabled,
      'syslog_enabled' => $syslog_enabled,
      'logging_enabled' => $dblog_enabled || $syslog_enabled,
      'table_exists' => $this->database->schema()->tableExists('watchdog'),
    ];
  }

  /**
   * Analyzes 404 errors.
   */
  protected function analyze404Errors() {
    if (!$this->database->schema()->tableExists('watchdog')) {
      return [
        'enabled' => false,
        'message' => 'Watchdog table not available',
      ];
    }

    try {
      // Get total watchdog entries
      $total_entries = $this->database->select('watchdog', 'w')
        ->countQuery()
        ->execute()
        ->fetchField();

      // Get 404 error count
      $count_404 = $this->database->select('watchdog', 'w')
        ->condition('type', 'page not found')
        ->countQuery()
        ->execute()
        ->fetchField();

      // Calculate percentage
      $percent_404 = $total_entries > 0 ? round(($count_404 / $total_entries) * 100, 2) : 0;

      // Get top 404 URLs
      $top_404_urls = $this->getTop404URLs();

      // Get recent 404 errors
      $recent_404s = $this->getRecent404Errors();

      return [
        'enabled' => true,
        'total_entries' => (int) $total_entries,
        'count_404' => (int) $count_404,
        'percent_404' => $percent_404,
        'top_404_urls' => $top_404_urls,
        'recent_404s' => $recent_404s,
        'status' => $this->get404Status($percent_404),
        'recommendation' => $this->get404Recommendation($percent_404),
      ];
    }
    catch (\Exception $e) {
      return [
        'enabled' => false,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Gets top 404 URLs.
   */
  protected function getTop404URLs($limit = 10) {
    try {
      $query = $this->database->select('watchdog', 'w')
        ->fields('w', ['message'])
        ->condition('type', 'page not found')
        ->orderBy('timestamp', 'DESC')
        ->range(0, 100); // Get recent 100 to analyze

      $results = $query->execute()->fetchAll();
      $url_counts = [];

      foreach ($results as $row) {
        // Extract URL from message
        if (preg_match('/page "([^"]+)"/', $row->message, $matches)) {
          $url = $matches[1];
          if (!isset($url_counts[$url])) {
            $url_counts[$url] = 0;
          }
          $url_counts[$url]++;
        }
      }

      arsort($url_counts);
      return array_slice($url_counts, 0, $limit, true);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets recent 404 errors.
   */
  protected function getRecent404Errors($limit = 20) {
    try {
      $query = $this->database->select('watchdog', 'w')
        ->fields('w', ['wid', 'message', 'timestamp', 'hostname'])
        ->condition('type', 'page not found')
        ->orderBy('timestamp', 'DESC')
        ->range(0, $limit);

      $results = $query->execute()->fetchAll();
      $recent_errors = [];

      foreach ($results as $row) {
        $recent_errors[] = [
          'wid' => $row->wid,
          'message' => $row->message,
          'timestamp' => $row->timestamp,
          'hostname' => $row->hostname,
          'formatted_time' => date('Y-m-d H:i:s', $row->timestamp),
        ];
      }

      return $recent_errors;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets 404 status based on percentage.
   */
  protected function get404Status($percent_404) {
    if ($percent_404 == 0) {
      return 'pass';
    }
    elseif ($percent_404 >= 10) {
      return 'warn';
    }
    else {
      return 'info';
    }
  }

  /**
   * Gets 404 recommendation.
   */
  protected function get404Recommendation($percent_404) {
    if ($percent_404 >= 10) {
      return 'High number of 404 errors detected. Review the full report and fix broken links or add URL redirects.';
    }
    elseif ($percent_404 > 0) {
      return 'Some 404 errors detected. Monitor and fix broken links as needed.';
    }
    else {
      return 'No 404 errors found.';
    }
  }

  /**
   * Analyzes PHP errors.
   */
  protected function analyzePHPErrors() {
    if (!$this->database->schema()->tableExists('watchdog')) {
      return [
        'enabled' => false,
        'message' => 'Watchdog table not available',
      ];
    }

    try {
      // Get PHP error count
      $php_errors = $this->database->select('watchdog', 'w')
        ->condition('type', 'php')
        ->countQuery()
        ->execute()
        ->fetchField();

      // Get recent PHP errors
      $recent_php_errors = $this->getRecentPHPErrors();

      // Get PHP error types
      $error_types = $this->getPHPErrorTypes();

      // Get total entries for percentage calculation
      $total_entries = $this->database->select('watchdog', 'w')
        ->countQuery()
        ->execute()
        ->fetchField();

      $percent_php = $total_entries > 0 ? round(($php_errors / $total_entries) * 100, 2) : 0;

      return [
        'enabled' => true,
        'count_php_errors' => (int) $php_errors,
        'percent_php' => $percent_php,
        'recent_errors' => $recent_php_errors,
        'error_types' => $error_types,
        'status' => $this->getPHPErrorStatus($php_errors),
        'recommendation' => $this->getPHPErrorRecommendation($php_errors),
      ];
    }
    catch (\Exception $e) {
      return [
        'enabled' => false,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Gets recent PHP errors.
   */
  protected function getRecentPHPErrors($limit = 20) {
    try {
      $query = $this->database->select('watchdog', 'w')
        ->fields('w', ['wid', 'message', 'timestamp', 'severity', 'location'])
        ->condition('type', 'php')
        ->orderBy('timestamp', 'DESC')
        ->range(0, $limit);

      $results = $query->execute()->fetchAll();
      $recent_errors = [];

      foreach ($results as $row) {
        $recent_errors[] = [
          'wid' => $row->wid,
          'message' => $row->message,
          'timestamp' => $row->timestamp,
          'severity' => $row->severity,
          'location' => $row->location,
          'formatted_time' => date('Y-m-d H:i:s', $row->timestamp),
        ];
      }

      return $recent_errors;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets PHP error types breakdown.
   */
  protected function getPHPErrorTypes() {
    try {
      $query = $this->database->select('watchdog', 'w')
        ->fields('w', ['severity'])
        ->condition('type', 'php');
      $query->addExpression('COUNT(*)', 'count');
      $query->groupBy('severity');

      $results = $query->execute()->fetchAll();
      $error_types = [];

      foreach ($results as $row) {
        $severity_name = $this->getSeverityName($row->severity);
        $error_types[$severity_name] = (int) $row->count;
      }

      return $error_types;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets severity name from severity level.
   */
  protected function getSeverityName($severity) {
    $severity_levels = [
      0 => 'Emergency',
      1 => 'Alert',
      2 => 'Critical',
      3 => 'Error',
      4 => 'Warning',
      5 => 'Notice',
      6 => 'Info',
      7 => 'Debug',
    ];

    return $severity_levels[$severity] ?? 'Unknown';
  }

  /**
   * Gets PHP error status.
   */
  protected function getPHPErrorStatus($php_errors) {
    if ($php_errors == 0) {
      return 'pass';
    }
    elseif ($php_errors > 100) {
      return 'fail';
    }
    else {
      return 'warn';
    }
  }

  /**
   * Gets PHP error recommendation.
   */
  protected function getPHPErrorRecommendation($php_errors) {
    if ($php_errors > 100) {
      return 'High number of PHP errors detected. Review and fix PHP errors immediately.';
    }
    elseif ($php_errors > 0) {
      return 'Some PHP errors detected. Review and fix PHP errors.';
    }
    else {
      return 'No PHP errors found.';
    }
  }

  /**
   * Gets watchdog statistics.
   */
  protected function getWatchdogStatistics() {
    if (!$this->database->schema()->tableExists('watchdog')) {
      return [
        'enabled' => false,
        'message' => 'Watchdog table not available',
      ];
    }

    try {
      // Total entries
      $total_entries = $this->database->select('watchdog', 'w')
        ->countQuery()
        ->execute()
        ->fetchField();

      // Entries by type
      $query = $this->database->select('watchdog', 'w')
        ->fields('w', ['type']);
      $query->addExpression('COUNT(*)', 'count');
      $query->groupBy('type');
      $query->orderBy('count', 'DESC');

      $results = $query->execute()->fetchAll();
      $entries_by_type = [];

      foreach ($results as $row) {
        $entries_by_type[$row->type] = (int) $row->count;
      }

      // Entries by severity
      $query = $this->database->select('watchdog', 'w')
        ->fields('w', ['severity']);
      $query->addExpression('COUNT(*)', 'count');
      $query->groupBy('severity');

      $results = $query->execute()->fetchAll();
      $entries_by_severity = [];

      foreach ($results as $row) {
        $severity_name = $this->getSeverityName($row->severity);
        $entries_by_severity[$severity_name] = (int) $row->count;
      }

      return [
        'enabled' => true,
        'total_entries' => (int) $total_entries,
        'entries_by_type' => $entries_by_type,
        'entries_by_severity' => $entries_by_severity,
      ];
    }
    catch (\Exception $e) {
      return [
        'enabled' => false,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Analyzes log age.
   */
  protected function analyzeLogAge() {
    if (!$this->database->schema()->tableExists('watchdog')) {
      return [
        'enabled' => false,
        'message' => 'Watchdog table not available',
      ];
    }

    try {
      // Get oldest and newest entries
      $oldest = $this->database->select('watchdog', 'w')
        ->fields('w', ['timestamp'])
        ->orderBy('timestamp', 'ASC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      $newest = $this->database->select('watchdog', 'w')
        ->fields('w', ['timestamp'])
        ->orderBy('timestamp', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      if ($oldest && $newest) {
        $age_days = round(($newest - $oldest) / 86400, 1);
        $oldest_formatted = date('Y-m-d H:i:s', $oldest);
        $newest_formatted = date('Y-m-d H:i:s', $newest);

        return [
          'enabled' => true,
          'oldest_entry' => $oldest,
          'newest_entry' => $newest,
          'oldest_formatted' => $oldest_formatted,
          'newest_formatted' => $newest_formatted,
          'age_days' => $age_days,
          'status' => $age_days > 30 ? 'info' : 'pass',
        ];
      }
      else {
        return [
          'enabled' => true,
          'message' => 'No log entries found',
        ];
      }
    }
    catch (\Exception $e) {
      return [
        'enabled' => false,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Analyzes error trends.
   */
  protected function analyzeErrorTrends() {
    if (!$this->database->schema()->tableExists('watchdog')) {
      return [
        'enabled' => false,
        'message' => 'Watchdog table not available',
      ];
    }

    try {
      $now = time();
      $periods = [
        'last_hour' => $now - 3600,
        'last_day' => $now - 86400,
        'last_week' => $now - 604800,
        'last_month' => $now - 2592000,
      ];

      $trends = [];

      foreach ($periods as $period => $timestamp) {
        $count = $this->database->select('watchdog', 'w')
          ->condition('timestamp', $timestamp, '>')
          ->condition('severity', 3, '<=') // Error level and above
          ->countQuery()
          ->execute()
          ->fetchField();

        $trends[$period] = (int) $count;
      }

      return [
        'enabled' => true,
        'trends' => $trends,
      ];
    }
    catch (\Exception $e) {
      return [
        'enabled' => false,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Gets top error sources.
   */
  protected function getTopErrorSources($limit = 10) {
    if (!$this->database->schema()->tableExists('watchdog')) {
      return [
        'enabled' => false,
        'message' => 'Watchdog table not available',
      ];
    }

    try {
      $query = $this->database->select('watchdog', 'w')
        ->fields('w', ['location']);
      $query->addExpression('COUNT(*)', 'count');
      $query->condition('severity', 3, '<='); // Error level and above
      $query->groupBy('location');
      $query->orderBy('count', 'DESC');
      $query->range(0, $limit);

      $results = $query->execute()->fetchAll();
      $sources = [];

      foreach ($results as $row) {
        $sources[$row->location] = (int) $row->count;
      }

      return [
        'enabled' => true,
        'sources' => $sources,
      ];
    }
    catch (\Exception $e) {
      return [
        'enabled' => false,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Generates watchdog recommendations.
   */
  protected function generateWatchdogRecommendations() {
    $recommendations = [];
    $status = $this->getWatchdogStatus();

    if (!$status['logging_enabled']) {
      $recommendations[] = [
        'type' => 'error',
        'category' => 'logging',
        'message' => 'No logging module enabled. Enable Database Logging (dblog) or Syslog module.',
        'priority' => 'high',
      ];
    }

    $error_404 = $this->analyze404Errors();
    if ($error_404['enabled'] && $error_404['percent_404'] >= 10) {
      $recommendations[] = [
        'type' => 'warning',
        'category' => '404_errors',
        'message' => "High number of 404 errors ({$error_404['percent_404']}%). Review and fix broken links.",
        'priority' => 'medium',
      ];
    }

    $php_errors = $this->analyzePHPErrors();
    if ($php_errors['enabled'] && $php_errors['count_php_errors'] > 100) {
      $recommendations[] = [
        'type' => 'error',
        'category' => 'php_errors',
        'message' => "High number of PHP errors ({$php_errors['count_php_errors']}). Review and fix PHP errors immediately.",
        'priority' => 'high',
      ];
    }

    $log_age = $this->analyzeLogAge();
    if ($log_age['enabled'] && isset($log_age['age_days']) && $log_age['age_days'] > 90) {
      $recommendations[] = [
        'type' => 'info',
        'category' => 'log_maintenance',
        'message' => "Log entries span {$log_age['age_days']} days. Consider implementing log rotation.",
        'priority' => 'low',
      ];
    }

    return $recommendations;
  }

}