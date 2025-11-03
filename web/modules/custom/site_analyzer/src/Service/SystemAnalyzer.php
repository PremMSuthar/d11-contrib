<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Service for analyzing comprehensive system information.
 */
class SystemAnalyzer {

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
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a SystemAnalyzer object.
   */
  public function __construct(
    Connection $database, 
    ModuleHandlerInterface $module_handler, 
    ThemeHandlerInterface $theme_handler,
    ConfigFactoryInterface $config_factory,
    StateInterface $state,
    FileSystemInterface $file_system
  ) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->fileSystem = $file_system;
  }

  /**
   * Analyzes comprehensive system information.
   */
  public function analyze() {
    return [
      'drupal_version' => $this->getDrupalInfo(),
      'php_version' => $this->getPhpInfo(),
      'database_info' => $this->getDatabaseInfo(),
      'server_info' => $this->getWebServerInfo(),
      'memory_info' => $this->getMemoryInfo(),
      'disk_space' => $this->getDiskSpaceInfo(),
      'operating_system' => $this->getOperatingSystemInfo(),
      'environment_info' => $this->getEnvironmentInfo(),
      'file_system_info' => $this->getFileSystemInfo(),
      'cache_info' => $this->getCacheInfo(),
      'cron_info' => $this->getCronInfo(),
      'configuration_info' => $this->getConfigurationInfo(),
      'extensions_info' => $this->getExtensionsInfo(),
      'requirements' => $this->checkRequirements(),
      'recommendations' => $this->getSystemRecommendations(),
    ];
  }

  /**
   * Gets comprehensive Drupal information.
   */
  protected function getDrupalInfo() {
    $install_profile = \Drupal::installProfile();
    $site_config = $this->configFactory->get('system.site');
    
    return [
      'version' => \Drupal::VERSION,
      'core_version' => \Drupal::VERSION,
      'install_profile' => $install_profile,
      'site_name' => $site_config->get('name'),
      'site_mail' => $site_config->get('mail'),
      'site_uuid' => $site_config->get('uuid'),
      'default_langcode' => $site_config->get('default_langcode'),
      'installation_time' => $this->state->get('system.cron_last'),
      'maintenance_mode' => $this->state->get('system.maintenance_mode'),
      'trusted_host_patterns' => $this->getTrustedHostPatterns(),
      'base_url' => $GLOBALS['base_url'] ?? 'Not set',
      'hash_salt' => !empty($GLOBALS['settings']['hash_salt']) ? 'Set' : 'Not set',
    ];
  }

  /**
   * Gets Drupal release date.
   */
  protected function getDrupalReleaseDate() {
    // This would typically come from drupal.org API or release notes
    // For now, return a placeholder
    return date('Y-m-d', filemtime(DRUPAL_ROOT . '/core/lib/Drupal.php'));
  }

  /**
   * Gets comprehensive PHP information.
   */
  protected function getPhpInfo() {
    return [
      'version' => PHP_VERSION,
      'major_version' => PHP_MAJOR_VERSION,
      'minor_version' => PHP_MINOR_VERSION,
      'release_version' => PHP_RELEASE_VERSION,
      'sapi' => php_sapi_name(),
      'extensions' => get_loaded_extensions(),
      'memory_limit' => ini_get('memory_limit'),
      'max_execution_time' => ini_get('max_execution_time'),
      'max_input_time' => ini_get('max_input_time'),
      'post_max_size' => ini_get('post_max_size'),
      'upload_max_filesize' => ini_get('upload_max_filesize'),
      'max_file_uploads' => ini_get('max_file_uploads'),
      'session_save_handler' => ini_get('session.save_handler'),
      'session_save_path' => ini_get('session.save_path'),
      'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status(),
      'xdebug_enabled' => extension_loaded('xdebug'),
      'error_reporting' => error_reporting(),
      'display_errors' => ini_get('display_errors'),
      'log_errors' => ini_get('log_errors'),
      'error_log' => ini_get('error_log'),
      'date_timezone' => ini_get('date.timezone'),
      'mbstring_enabled' => extension_loaded('mbstring'),
      'gd_enabled' => extension_loaded('gd'),
      'curl_enabled' => extension_loaded('curl'),
      'openssl_enabled' => extension_loaded('openssl'),
      'pdo_enabled' => extension_loaded('pdo'),
      'json_enabled' => extension_loaded('json'),
      'xml_enabled' => extension_loaded('xml'),
    ];
  }

  /**
   * Gets relevant PHP extensions.
   */
  protected function getPhpExtensions() {
    $required_extensions = [
      'curl', 'dom', 'gd', 'hash', 'json', 'mbstring', 'openssl', 
      'pcre', 'pdo', 'session', 'simplexml', 'spl', 'tokenizer', 'xml'
    ];
    
    $extensions = [];
    foreach ($required_extensions as $extension) {
      $extensions[$extension] = extension_loaded($extension);
    }
    
    return $extensions;
  }

  /**
   * Gets important PHP ini settings.
   */
  protected function getPhpIniSettings() {
    $settings = [
      'display_errors',
      'log_errors',
      'error_reporting',
      'session.cookie_httponly',
      'session.cookie_secure',
      'allow_url_fopen',
      'allow_url_include',
    ];
    
    $ini_settings = [];
    foreach ($settings as $setting) {
      $ini_settings[$setting] = ini_get($setting);
    }
    
    return $ini_settings;
  }

  /**
   * Gets database information.
   */
  protected function getDatabaseInfo() {
    $connection_info = $this->database->getConnectionOptions();
    
    return [
      'driver' => $connection_info['driver'],
      'host' => $connection_info['host'] ?? 'localhost',
      'port' => $connection_info['port'] ?? $this->getDefaultPort($connection_info['driver']),
      'database' => $connection_info['database'],
      'version' => $this->getDatabaseVersion(),
      'charset' => $connection_info['charset'] ?? 'utf8mb4',
      'collation' => $connection_info['collation'] ?? 'utf8mb4_general_ci',
      'size' => $this->getDatabaseSize(),
      'table_count' => $this->getTableCount(),
    ];
  }

  /**
   * Gets default port for database driver.
   */
  protected function getDefaultPort($driver) {
    $default_ports = [
      'mysql' => 3306,
      'pgsql' => 5432,
      'sqlite' => null,
    ];
    
    return $default_ports[$driver] ?? null;
  }

  /**
   * Gets database version.
   */
  protected function getDatabaseVersion() {
    try {
      return $this->database->version();
    }
    catch (\Exception $e) {
      return 'Unknown';
    }
  }

  /**
   * Gets database size in MB.
   */
  protected function getDatabaseSize() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                  FROM information_schema.tables 
                  WHERE table_schema = DATABASE()";
        $result = $this->database->query($query)->fetchField();
        return (float) $result;
      }
      elseif ($driver === 'pgsql') {
        $query = "SELECT pg_size_pretty(pg_database_size(current_database()))";
        $result = $this->database->query($query)->fetchField();
        // Parse the result to get MB value
        if (preg_match('/(\d+(?:\.\d+)?)\s*MB/', $result, $matches)) {
          return (float) $matches[1];
        }
      }
    }
    catch (\Exception $e) {
      // Log error but don't fail
    }
    
    return 0;
  }

  /**
   * Gets table count.
   */
  protected function getTableCount() {
    try {
      $schema = $this->database->schema();
      $tables = $schema->findTables('%');
      return count($tables);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets web server information.
   */
  protected function getWebServerInfo() {
    $server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    
    return [
      'software' => $server_software,
      'version' => $this->parseServerVersion($server_software),
      'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
      'server_name' => $_SERVER['SERVER_NAME'] ?? '',
      'https' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ];
  }

  /**
   * Parses server version from server software string.
   */
  protected function parseServerVersion($server_software) {
    if (preg_match('/Apache\/([0-9.]+)/', $server_software, $matches)) {
      return $matches[1];
    }
    elseif (preg_match('/nginx\/([0-9.]+)/', $server_software, $matches)) {
      return $matches[1];
    }
    
    return 'Unknown';
  }

  /**
   * Gets operating system information.
   */
  protected function getOperatingSystemInfo() {
    return [
      'name' => php_uname('s'),
      'version' => php_uname('r'),
      'architecture' => php_uname('m'),
      'hostname' => php_uname('n'),
      'full' => php_uname(),
    ];
  }

  /**
   * Gets memory usage information.
   */
  protected function getMemoryInfo() {
    $memory_limit = $this->parseMemoryValue(ini_get('memory_limit'));
    $memory_usage = memory_get_usage(true);
    $memory_peak = memory_get_peak_usage(true);
    
    return [
      'limit' => $memory_limit,
      'limit_formatted' => $this->formatBytes($memory_limit),
      'current_usage' => $memory_usage,
      'current_usage_formatted' => $this->formatBytes($memory_usage),
      'peak_usage' => $memory_peak,
      'peak_usage_formatted' => $this->formatBytes($memory_peak),
      'usage_percentage' => $memory_limit > 0 ? round(($memory_usage / $memory_limit) * 100, 2) : 0,
      'available' => $memory_limit - $memory_usage,
      'available_formatted' => $this->formatBytes($memory_limit - $memory_usage),
    ];
  }

  /**
   * Parses memory value from ini setting.
   */
  protected function parseMemoryValue($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value) - 1]);
    $value = (int) $value;
    
    switch ($last) {
      case 'g':
        $value *= 1024;
      case 'm':
        $value *= 1024;
      case 'k':
        $value *= 1024;
    }
    
    return $value;
  }

  /**
   * Formats bytes to human readable format.
   */
  protected function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
      $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
  }

  /**
   * Gets disk space information.
   */
  protected function getDiskSpaceInfo() {
    $root_path = DRUPAL_ROOT;
    
    return [
      'total' => disk_total_space($root_path),
      'total_formatted' => $this->formatBytes(disk_total_space($root_path)),
      'free' => disk_free_space($root_path),
      'free_formatted' => $this->formatBytes(disk_free_space($root_path)),
      'used' => disk_total_space($root_path) - disk_free_space($root_path),
      'used_formatted' => $this->formatBytes(disk_total_space($root_path) - disk_free_space($root_path)),
      'usage_percentage' => round(((disk_total_space($root_path) - disk_free_space($root_path)) / disk_total_space($root_path)) * 100, 2),
    ];
  }

  /**
   * Gets environment information.
   */
  protected function getEnvironmentInfo() {
    return [
      'drupal_root' => DRUPAL_ROOT,
      'site_path' => \Drupal::service('kernel')->getSitePath(),
      'private_files_path' => $this->configFactory->get('system.file')->get('path.private'),
      'public_files_path' => \Drupal::service('file_system')->realpath('public://'),
      'temporary_files_path' => \Drupal::service('file_system')->realpath('temporary://'),
      'config_sync_directory' => $this->configFactory->get('system.file')->get('path.temporary'),
      'trusted_host_patterns' => $this->configFactory->get('system.site')->get('trusted_host_patterns'),
      'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
      'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
      'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
      'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
      'https' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
      'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'Unknown',
      'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
      'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
      'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
      'environment_type' => $this->detectEnvironmentType(),
    ];
  }

  /**
   * Checks system requirements.
   */
  protected function checkRequirements() {
    $requirements = [];
    
    // PHP version check
    if (version_compare(PHP_VERSION, '8.1', '<')) {
      $requirements['php_version'] = [
        'status' => 'warning',
        'message' => 'PHP version ' . PHP_VERSION . ' is below recommended 8.1+',
      ];
    }
    else {
      $requirements['php_version'] = [
        'status' => 'ok',
        'message' => 'PHP version ' . PHP_VERSION . ' is supported',
      ];
    }
    
    // Memory limit check
    $memory_limit = $this->parseMemoryValue(ini_get('memory_limit'));
    if ($memory_limit < 256 * 1024 * 1024) { // 256MB
      $requirements['memory_limit'] = [
        'status' => 'warning',
        'message' => 'Memory limit ' . ini_get('memory_limit') . ' is below recommended 256M',
      ];
    }
    else {
      $requirements['memory_limit'] = [
        'status' => 'ok',
        'message' => 'Memory limit ' . ini_get('memory_limit') . ' is adequate',
      ];
    }
    
    // Required PHP extensions
    $required_extensions = ['curl', 'dom', 'gd', 'hash', 'json', 'mbstring', 'openssl', 'pcre', 'pdo', 'xml'];
    foreach ($required_extensions as $extension) {
      if (!extension_loaded($extension)) {
        $requirements['php_extension_' . $extension] = [
          'status' => 'error',
          'message' => 'Required PHP extension ' . $extension . ' is not loaded',
        ];
      }
    }
    
    return $requirements;
  }



  /**
   * Gets file system information.
   */
  protected function getFileSystemInfo() {
    $public_path = $this->fileSystem->realpath('public://');
    $private_path = $this->fileSystem->realpath('private://');
    $temp_path = $this->fileSystem->realpath('temporary://');
    
    return [
      'public_files_path' => $public_path,
      'private_files_path' => $private_path,
      'temporary_files_path' => $temp_path,
      'public_writable' => is_writable($public_path),
      'private_writable' => $private_path ? is_writable($private_path) : false,
      'temp_writable' => is_writable($temp_path),
      'file_permissions' => $this->getFilePermissions(),
      'directory_permissions' => $this->getDirectoryPermissions(),
    ];
  }

  /**
   * Gets cache information.
   */
  protected function getCacheInfo() {
    $cache_config = $this->configFactory->get('system.performance');
    
    return [
      'page_cache_enabled' => $cache_config->get('cache.page.use_internal'),
      'dynamic_page_cache_enabled' => $this->moduleHandler->moduleExists('dynamic_page_cache'),
      'big_pipe_enabled' => $this->moduleHandler->moduleExists('big_pipe'),
      'css_aggregation' => $cache_config->get('css.preprocess'),
      'js_aggregation' => $cache_config->get('js.preprocess'),
      'cache_max_age' => $cache_config->get('cache.page.max_age'),
      'cache_backends' => $this->getCacheBackends(),
    ];
  }

  /**
   * Gets cron information.
   */
  protected function getCronInfo() {
    $cron_last = $this->state->get('system.cron_last');
    $cron_config = $this->configFactory->get('automated_cron.settings');
    
    return [
      'last_run' => $cron_last ? date('Y-m-d H:i:s', $cron_last) : 'Never',
      'last_run_timestamp' => $cron_last,
      'automated_cron_enabled' => $this->moduleHandler->moduleExists('automated_cron'),
      'cron_interval' => $cron_config->get('interval'),
      'time_since_last_run' => $cron_last ? time() - $cron_last : null,
      'cron_overdue' => $cron_last ? (time() - $cron_last) > 86400 : true,
    ];
  }

  /**
   * Gets configuration information.
   */
  protected function getConfigurationInfo() {
    return [
      'config_sync_directory' => $this->getConfigSyncDirectory(),
      'active_config_storage' => get_class(\Drupal::service('config.storage')),
      'staging_config_storage' => get_class(\Drupal::service('config.storage.sync')),
      'config_split_enabled' => $this->moduleHandler->moduleExists('config_split'),
      'config_ignore_enabled' => $this->moduleHandler->moduleExists('config_ignore'),
      'features_enabled' => $this->moduleHandler->moduleExists('features'),
    ];
  }

  /**
   * Gets extensions information.
   */
  protected function getExtensionsInfo() {
    return [
      'total_modules' => count($this->moduleHandler->getModuleList()),
      'enabled_modules' => count($this->moduleHandler->getModuleList()),
      'contrib_modules' => $this->getContribModulesCount(),
      'custom_modules' => $this->getCustomModulesCount(),
      'total_themes' => count($this->themeHandler->listInfo()),
      'enabled_themes' => count($this->themeHandler->listInfo()),
      'default_theme' => $this->themeHandler->getDefault(),
      'admin_theme' => $this->configFactory->get('system.theme')->get('admin'),
    ];
  }

  /**
   * Gets system recommendations.
   */
  protected function getSystemRecommendations() {
    $recommendations = [];
    
    // PHP version check
    if (version_compare(PHP_VERSION, '8.1', '<')) {
      $recommendations[] = [
        'type' => 'warning',
        'title' => 'PHP Version',
        'message' => 'Consider upgrading to PHP 8.1 or higher for better performance and security.',
        'priority' => 'high',
      ];
    }
    
    // Memory limit check
    $memory_limit = ini_get('memory_limit');
    if ($memory_limit !== '-1' && $this->convertToBytes($memory_limit) < 256 * 1024 * 1024) {
      $recommendations[] = [
        'type' => 'warning',
        'title' => 'Memory Limit',
        'message' => 'Consider increasing PHP memory limit to at least 256MB.',
        'priority' => 'medium',
      ];
    }
    
    // OPcache check
    if (!function_exists('opcache_get_status') || !opcache_get_status()) {
      $recommendations[] = [
        'type' => 'info',
        'title' => 'OPcache',
        'message' => 'Enable OPcache for better PHP performance.',
        'priority' => 'medium',
      ];
    }
    
    // Cron check
    $cron_last = $this->state->get('system.cron_last');
    if (!$cron_last || (time() - $cron_last) > 86400) {
      $recommendations[] = [
        'type' => 'error',
        'title' => 'Cron',
        'message' => 'Cron has not run in the last 24 hours. Set up automated cron.',
        'priority' => 'high',
      ];
    }
    
    return $recommendations;
  }

  /**
   * Helper methods.
   */
  protected function getTrustedHostPatterns() {
    return $GLOBALS['settings']['trusted_host_patterns'] ?? [];
  }

  protected function detectEnvironmentType() {
    if (defined('PANTHEON_ENVIRONMENT')) {
      return 'Pantheon';
    }
    if (isset($_ENV['PLATFORM_BRANCH'])) {
      return 'Platform.sh';
    }
    if (isset($_ENV['ACQUIA_HOSTING'])) {
      return 'Acquia';
    }
    if (file_exists('/var/www/.ddev')) {
      return 'DDEV';
    }
    return 'Unknown';
  }

  protected function getFilePermissions() {
    $public_path = $this->fileSystem->realpath('public://');
    return $public_path ? substr(sprintf('%o', fileperms($public_path)), -4) : 'Unknown';
  }

  protected function getDirectoryPermissions() {
    return [
      'sites/default' => $this->getPathPermissions(DRUPAL_ROOT . '/sites/default'),
      'sites/default/files' => $this->getPathPermissions($this->fileSystem->realpath('public://')),
    ];
  }

  protected function getPathPermissions($path) {
    return file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : 'Not found';
  }

  protected function getCacheBackends() {
    $backends = [];
    $cache_bins = ['default', 'bootstrap', 'config', 'data', 'discovery', 'dynamic_page_cache', 'entity', 'menu', 'migrate', 'render', 'rest', 'static', 'toolbar'];
    
    foreach ($cache_bins as $bin) {
      try {
        $cache = \Drupal::cache($bin);
        $backends[$bin] = get_class($cache);
      }
      catch (\Exception $e) {
        $backends[$bin] = 'Not available';
      }
    }
    
    return $backends;
  }

  protected function getConfigSyncDirectory() {
    return $GLOBALS['config_directories']['sync'] ?? 'Not configured';
  }

  protected function getContribModulesCount() {
    $contrib_count = 0;
    foreach ($this->moduleHandler->getModuleList() as $name => $module) {
      if (strpos($module->getPath(), 'modules/contrib') !== false) {
        $contrib_count++;
      }
    }
    return $contrib_count;
  }

  protected function getCustomModulesCount() {
    $custom_count = 0;
    foreach ($this->moduleHandler->getModuleList() as $name => $module) {
      if (strpos($module->getPath(), 'modules/custom') !== false) {
        $custom_count++;
      }
    }
    return $custom_count;
  }

  protected function convertToBytes($value) {
    $unit = strtolower(substr($value, -1));
    $value = (int) $value;
    switch ($unit) {
      case 'g':
        $value *= 1024;
      case 'm':
        $value *= 1024;
      case 'k':
        $value *= 1024;
    }
    return $value;
  }

}