<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;

/**
 * Service for analyzing site performance.
 */
class PerformanceAnalyzer {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a PerformanceAnalyzer object.
   */
  public function __construct(Connection $database, CacheBackendInterface $cache, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->cache = $cache;
    $this->configFactory = $config_factory;
  }

  /**
   * Analyzes site performance.
   */
  public function analyze() {
    return [
      'cache_analysis' => $this->analyzeCachePerformance(),
      'database_performance' => $this->analyzeDatabasePerformance(),
      'page_load_metrics' => $this->analyzePageLoadMetrics(),
      'asset_optimization' => $this->analyzeAssetOptimization(),
      'memory_usage' => $this->analyzeMemoryUsage(),
      'module_performance' => $this->analyzeModulePerformance(),
      'file_system_performance' => $this->analyzeFileSystemPerformance(),
      'third_party_integrations' => $this->analyzeThirdPartyIntegrations(),
      'recommendations' => $this->generatePerformanceRecommendations(),
      // Enhanced site_audit cache functionality
      'cache_detailed_analysis' => $this->analyzeCacheDetailed(),
      'cache_bins_analysis' => $this->analyzeCacheBinsDetailed(),
      'cache_page_expire' => $this->analyzeCachePageExpire(),
      'cache_preprocessing' => $this->analyzeCachePreprocessing(),
      'site_audit_cache_recommendations' => $this->generateSiteAuditCacheRecommendations(),
    ];
  }

  /**
   * Analyzes cache performance.
   */
  protected function analyzeCachePerformance() {
    $cache_analysis = [
      'cache_backends' => $this->getCacheBackends(),
      'cache_bins' => $this->getCacheBins(),
      'cache_hit_rates' => $this->getCacheHitRates(),
      'cache_sizes' => $this->getCacheSizes(),
      'page_cache' => $this->analyzePageCache(),
      'dynamic_cache' => $this->analyzeDynamicCache(),
    ];
    
    return $cache_analysis;
  }

  /**
   * Gets configured cache backends.
   */
  protected function getCacheBackends() {
    $backends = [];
    
    // Check cache configuration
    $cache_config = $this->configFactory->get('system.performance');
    
    $backends['page_cache'] = [
      'enabled' => (bool) $cache_config->get('cache.page.max_age'),
      'max_age' => $cache_config->get('cache.page.max_age'),
    ];
    
    $backends['css_aggregation'] = $cache_config->get('css.preprocess');
    $backends['js_aggregation'] = $cache_config->get('js.preprocess');
    
    // Check for external cache backends
    $backends['redis'] = class_exists('\Redis');
    $backends['memcache'] = class_exists('\Memcache') || class_exists('\Memcached');
    $backends['apcu'] = function_exists('apcu_enabled') && apcu_enabled();
    
    return $backends;
  }

  /**
   * Gets cache bins information.
   */
  protected function getCacheBins() {
    $bins = [];
    
    $cache_bins = [
      'bootstrap', 'config', 'data', 'default', 'discovery', 'dynamic_page_cache',
      'entity', 'menu', 'migrate', 'render', 'rest', 'static', 'toolbar'
    ];
    
    foreach ($cache_bins as $bin) {
      try {
        $cache_backend = \Drupal::cache($bin);
        $bins[$bin] = [
          'backend' => get_class($cache_backend),
          'items' => $this->getCacheBinItemCount($bin),
          'size' => $this->getCacheBinSize($bin),
        ];
      }
      catch (\Exception $e) {
        $bins[$bin] = [
          'backend' => 'Unknown',
          'items' => 0,
          'size' => 0,
        ];
      }
    }
    
    return $bins;
  }

  /**
   * Gets cache bin item count.
   */
  protected function getCacheBinItemCount($bin) {
    try {
      $count = $this->database->select('cache_' . $bin, 'c')
        ->countQuery()
        ->execute()
        ->fetchField();
      return (int) $count;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets cache bin size.
   */
  protected function getCacheBinSize($bin) {
    try {
      $query = $this->database->select('cache_' . $bin, 'c');
      $query->addExpression('SUM(LENGTH(data))', 'total_size');
      $result = $query->execute()->fetchField();
      return (int) $result;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets cache hit rates.
   */
  protected function getCacheHitRates() {
    // This would require cache statistics tracking
    // For now, return estimated values
    return [
      'page_cache' => $this->estimatePageCacheHitRate(),
      'render_cache' => $this->estimateRenderCacheHitRate(),
      'entity_cache' => $this->estimateEntityCacheHitRate(),
    ];
  }

  /**
   * Estimates page cache hit rate.
   */
  protected function estimatePageCacheHitRate() {
    $page_cache_enabled = $this->configFactory->get('system.performance')->get('cache.page.max_age');
    
    if (!$page_cache_enabled) {
      return 0;
    }
    
    // Estimate based on cache entries vs page views
    try {
      $cache_entries = $this->getCacheBinItemCount('page');
      // Rough estimate - in reality this would need proper tracking
      return min(85, $cache_entries * 0.1);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Estimates render cache hit rate.
   */
  protected function estimateRenderCacheHitRate() {
    try {
      $render_cache_entries = $this->getCacheBinItemCount('render');
      return min(90, $render_cache_entries * 0.05);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Estimates entity cache hit rate.
   */
  protected function estimateEntityCacheHitRate() {
    try {
      $entity_cache_entries = $this->getCacheBinItemCount('entity');
      return min(95, $entity_cache_entries * 0.02);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets cache sizes.
   */
  protected function getCacheSizes() {
    $sizes = [];
    $bins = $this->getCacheBins();
    
    foreach ($bins as $bin => $info) {
      $sizes[$bin] = $info['size'];
    }
    
    return $sizes;
  }

  /**
   * Analyzes page cache.
   */
  protected function analyzePageCache() {
    $page_cache_config = $this->configFactory->get('system.performance');
    
    return [
      'enabled' => (bool) $page_cache_config->get('cache.page.max_age'),
      'max_age' => $page_cache_config->get('cache.page.max_age'),
      'cache_entries' => $this->getCacheBinItemCount('page'),
      'hit_rate' => $this->estimatePageCacheHitRate(),
    ];
  }

  /**
   * Analyzes dynamic page cache.
   */
  protected function analyzeDynamicCache() {
    return [
      'enabled' => \Drupal::moduleHandler()->moduleExists('dynamic_page_cache'),
      'cache_entries' => $this->getCacheBinItemCount('dynamic_page_cache'),
      'hit_rate' => $this->estimateRenderCacheHitRate(),
    ];
  }

  /**
   * Analyzes database performance.
   */
  protected function analyzeDatabasePerformance() {
    return [
      'query_performance' => $this->analyzeQueryPerformance(),
      'slow_queries' => $this->getSlowQueries(),
      'database_size' => $this->getDatabaseSize(),
      'table_optimization' => $this->analyzeTableOptimization(),
      'index_usage' => $this->analyzeIndexUsage(),
    ];
  }

  /**
   * Analyzes query performance.
   */
  protected function analyzeQueryPerformance() {
    $performance = [
      'total_queries' => 0,
      'slow_queries' => 0,
      'average_query_time' => 0,
      'most_frequent_queries' => [],
    ];
    
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        // Get query statistics
        $queries = $this->database->query("SHOW STATUS LIKE 'Questions'")->fetchAssoc();
        $performance['total_queries'] = (int) $queries['Value'];
        
        $slow_queries = $this->database->query("SHOW STATUS LIKE 'Slow_queries'")->fetchAssoc();
        $performance['slow_queries'] = (int) $slow_queries['Value'];
        
        // Calculate slow query percentage
        if ($performance['total_queries'] > 0) {
          $performance['slow_query_percentage'] = round(($performance['slow_queries'] / $performance['total_queries']) * 100, 2);
        }
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $performance;
  }

  /**
   * Gets slow queries.
   */
  protected function getSlowQueries() {
    // This would analyze slow query log
    // For now, return placeholder data
    return [
      'count' => 0,
      'queries' => [],
    ];
  }

  /**
   * Gets database size.
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
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return 0;
  }

  /**
   * Analyzes table optimization.
   */
  protected function analyzeTableOptimization() {
    $optimization = [
      'fragmented_tables' => [],
      'large_tables' => [],
      'unused_indexes' => [],
    ];
    
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        // Find fragmented tables
        $query = "SELECT table_name, data_free 
                  FROM information_schema.tables 
                  WHERE table_schema = DATABASE() AND data_free > 1024 * 1024";
        $results = $this->database->query($query)->fetchAll();
        
        foreach ($results as $row) {
          $optimization['fragmented_tables'][] = [
            'table' => $row->table_name,
            'fragmentation_mb' => round($row->data_free / 1024 / 1024, 2),
          ];
        }
        
        // Find large tables
        $query = "SELECT table_name, ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
                  FROM information_schema.tables 
                  WHERE table_schema = DATABASE() 
                  ORDER BY (data_length + index_length) DESC 
                  LIMIT 10";
        $results = $this->database->query($query)->fetchAll();
        
        foreach ($results as $row) {
          $optimization['large_tables'][] = [
            'table' => $row->table_name,
            'size_mb' => (float) $row->size_mb,
          ];
        }
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $optimization;
  }

  /**
   * Analyzes index usage.
   */
  protected function analyzeIndexUsage() {
    // This would analyze index usage statistics
    // For now, return basic information
    return [
      'total_indexes' => $this->getTotalIndexCount(),
      'unused_indexes' => [],
      'missing_indexes' => [],
    ];
  }

  /**
   * Gets total index count.
   */
  protected function getTotalIndexCount() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $query = "SELECT COUNT(*) FROM information_schema.statistics 
                  WHERE table_schema = DATABASE()";
        return (int) $this->database->query($query)->fetchField();
      }
    }
    catch (\Exception $e) {
      return 0;
    }
    
    return 0;
  }

  /**
   * Analyzes page load metrics.
   */
  protected function analyzePageLoadMetrics() {
    // This would integrate with performance monitoring tools
    // For now, return estimated metrics
    return [
      'average_page_load_time' => $this->estimatePageLoadTime(),
      'time_to_first_byte' => $this->estimateTTFB(),
      'dom_content_loaded' => $this->estimateDOMContentLoaded(),
      'largest_contentful_paint' => $this->estimateLCP(),
      'cumulative_layout_shift' => $this->estimateCLS(),
      'first_input_delay' => $this->estimateFID(),
    ];
  }

  /**
   * Estimates page load time.
   */
  protected function estimatePageLoadTime() {
    // Base estimate on cache configuration and module count
    $modules_count = count(\Drupal::moduleHandler()->getModuleList());
    $cache_enabled = $this->configFactory->get('system.performance')->get('cache.page.max_age');
    
    $base_time = 2000; // 2 seconds base
    $module_penalty = $modules_count * 10; // 10ms per module
    $cache_bonus = $cache_enabled ? -500 : 0; // 500ms bonus for caching
    
    return max(500, $base_time + $module_penalty + $cache_bonus);
  }

  /**
   * Estimates Time to First Byte.
   */
  protected function estimateTTFB() {
    $cache_enabled = $this->configFactory->get('system.performance')->get('cache.page.max_age');
    return $cache_enabled ? 200 : 800; // Cached vs uncached
  }

  /**
   * Estimates DOM Content Loaded time.
   */
  protected function estimateDOMContentLoaded() {
    $js_aggregation = $this->configFactory->get('system.performance')->get('js.preprocess');
    return $js_aggregation ? 1200 : 1800;
  }

  /**
   * Estimates Largest Contentful Paint.
   */
  protected function estimateLCP() {
    $css_aggregation = $this->configFactory->get('system.performance')->get('css.preprocess');
    return $css_aggregation ? 2000 : 3000;
  }

  /**
   * Estimates Cumulative Layout Shift.
   */
  protected function estimateCLS() {
    // Lower is better for CLS
    return 0.1; // Estimated value
  }

  /**
   * Estimates First Input Delay.
   */
  protected function estimateFID() {
    $js_aggregation = $this->configFactory->get('system.performance')->get('js.preprocess');
    return $js_aggregation ? 50 : 150;
  }

  /**
   * Analyzes asset optimization.
   */
  protected function analyzeAssetOptimization() {
    $performance_config = $this->configFactory->get('system.performance');
    
    return [
      'css_aggregation' => $performance_config->get('css.preprocess'),
      'js_aggregation' => $performance_config->get('js.preprocess'),
      'css_files_count' => $this->getCSSFilesCount(),
      'js_files_count' => $this->getJSFilesCount(),
      'image_optimization' => $this->analyzeImageOptimization(),
      'font_optimization' => $this->analyzeFontOptimization(),
      'compression' => $this->analyzeCompression(),
    ];
  }

  /**
   * Gets CSS files count.
   */
  protected function getCSSFilesCount() {
    // This would scan for CSS files in themes and modules
    $count = 0;
    
    try {
      $themes_path = DRUPAL_ROOT . '/themes';
      $modules_path = DRUPAL_ROOT . '/modules';
      
      $count += $this->countFilesByExtension($themes_path, 'css');
      $count += $this->countFilesByExtension($modules_path, 'css');
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $count;
  }

  /**
   * Gets JS files count.
   */
  protected function getJSFilesCount() {
    $count = 0;
    
    try {
      $themes_path = DRUPAL_ROOT . '/themes';
      $modules_path = DRUPAL_ROOT . '/modules';
      
      $count += $this->countFilesByExtension($themes_path, 'js');
      $count += $this->countFilesByExtension($modules_path, 'js');
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $count;
  }

  /**
   * Counts files by extension in a directory.
   */
  protected function countFilesByExtension($directory, $extension) {
    $count = 0;
    
    if (!is_dir($directory)) {
      return $count;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === $extension) {
        $count++;
      }
    }
    
    return $count;
  }

  /**
   * Analyzes image optimization.
   */
  protected function analyzeImageOptimization() {
    return [
      'image_styles_enabled' => \Drupal::moduleHandler()->moduleExists('image'),
      'webp_support' => $this->checkWebPSupport(),
      'responsive_images' => \Drupal::moduleHandler()->moduleExists('responsive_image'),
      'lazy_loading' => $this->checkLazyLoading(),
    ];
  }

  /**
   * Checks WebP support.
   */
  protected function checkWebPSupport() {
    return function_exists('imagewebp') && function_exists('imagecreatefromwebp');
  }

  /**
   * Checks lazy loading implementation.
   */
  protected function checkLazyLoading() {
    // Check if lazy loading modules are enabled
    $lazy_modules = ['blazy', 'lazy', 'lazyloader'];
    
    foreach ($lazy_modules as $module) {
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        return true;
      }
    }
    
    return false;
  }

  /**
   * Analyzes font optimization.
   */
  protected function analyzeFontOptimization() {
    return [
      'web_fonts_used' => $this->checkWebFonts(),
      'font_display_swap' => $this->checkFontDisplaySwap(),
      'font_preloading' => $this->checkFontPreloading(),
    ];
  }

  /**
   * Checks for web fonts usage.
   */
  protected function checkWebFonts() {
    // This would scan CSS files for @font-face or Google Fonts
    return false; // Placeholder
  }

  /**
   * Checks font-display: swap usage.
   */
  protected function checkFontDisplaySwap() {
    // This would scan CSS for font-display: swap
    return false; // Placeholder
  }

  /**
   * Checks font preloading.
   */
  protected function checkFontPreloading() {
    // This would check for font preload links
    return false; // Placeholder
  }

  /**
   * Analyzes compression.
   */
  protected function analyzeCompression() {
    return [
      'gzip_enabled' => $this->checkGzipCompression(),
      'brotli_enabled' => $this->checkBrotliCompression(),
      'asset_compression' => $this->checkAssetCompression(),
    ];
  }

  /**
   * Checks Gzip compression.
   */
  protected function checkGzipCompression() {
    return function_exists('gzencode') && extension_loaded('zlib');
  }

  /**
   * Checks Brotli compression.
   */
  protected function checkBrotliCompression() {
    return function_exists('brotli_compress');
  }

  /**
   * Checks asset compression.
   */
  protected function checkAssetCompression() {
    $performance_config = $this->configFactory->get('system.performance');
    return $performance_config->get('css.preprocess') && $performance_config->get('js.preprocess');
  }

  /**
   * Analyzes memory usage.
   */
  protected function analyzeMemoryUsage() {
    return [
      'current_usage' => memory_get_usage(true),
      'peak_usage' => memory_get_peak_usage(true),
      'memory_limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
      'usage_percentage' => $this->calculateMemoryUsagePercentage(),
      'memory_intensive_modules' => $this->identifyMemoryIntensiveModules(),
    ];
  }

  /**
   * Parses memory limit string to bytes.
   */
  protected function parseMemoryLimit($limit) {
    $limit = trim($limit);
    $last = strtolower($limit[strlen($limit) - 1]);
    $limit = (int) $limit;
    
    switch ($last) {
      case 'g':
        $limit *= 1024;
      case 'm':
        $limit *= 1024;
      case 'k':
        $limit *= 1024;
    }
    
    return $limit;
  }

  /**
   * Calculates memory usage percentage.
   */
  protected function calculateMemoryUsagePercentage() {
    $current = memory_get_usage(true);
    $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
    
    if ($limit > 0) {
      return round(($current / $limit) * 100, 2);
    }
    
    return 0;
  }

  /**
   * Identifies memory-intensive modules.
   */
  protected function identifyMemoryIntensiveModules() {
    // Known memory-intensive modules
    $intensive_modules = [
      'views', 'panels', 'rules', 'commerce', 'webform', 'migrate'
    ];
    
    $enabled_intensive = [];
    foreach ($intensive_modules as $module) {
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        $enabled_intensive[] = $module;
      }
    }
    
    return $enabled_intensive;
  }

  /**
   * Analyzes module performance impact.
   */
  protected function analyzeModulePerformance() {
    return [
      'total_modules' => count(\Drupal::moduleHandler()->getModuleList()),
      'heavy_modules' => $this->identifyHeavyModules(),
      'module_load_time' => $this->estimateModuleLoadTime(),
      'hook_implementations' => $this->analyzeHookImplementations(),
    ];
  }

  /**
   * Identifies heavy modules.
   */
  protected function identifyHeavyModules() {
    $heavy_modules = [
      'views', 'panels', 'rules', 'commerce', 'webform', 'migrate',
      'field_collection', 'paragraphs', 'layout_builder'
    ];
    
    $enabled_heavy = [];
    foreach ($heavy_modules as $module) {
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        $enabled_heavy[] = $module;
      }
    }
    
    return $enabled_heavy;
  }

  /**
   * Estimates module load time.
   */
  protected function estimateModuleLoadTime() {
    $module_count = count(\Drupal::moduleHandler()->getModuleList());
    return $module_count * 2; // Rough estimate: 2ms per module
  }

  /**
   * Analyzes hook implementations.
   */
  protected function analyzeHookImplementations() {
    $hook_counts = [];
    $modules = \Drupal::moduleHandler()->getModuleList();
    
    foreach ($modules as $module => $extension) {
      $module_file = DRUPAL_ROOT . '/' . $extension->getPath() . '/' . $module . '.module';
      if (file_exists($module_file)) {
        $content = file_get_contents($module_file);
        $hook_count = preg_match_all('/function\s+' . $module . '_[a-zA-Z_]+\s*\(/', $content);
        $hook_counts[$module] = $hook_count;
      }
    }
    
    arsort($hook_counts);
    return array_slice($hook_counts, 0, 10, true); // Top 10 modules by hook count
  }

  /**
   * Analyzes file system performance.
   */
  protected function analyzeFileSystemPerformance() {
    return [
      'file_system_type' => $this->getFileSystemType(),
      'disk_io_performance' => $this->measureDiskIOPerformance(),
      'file_cache_performance' => $this->analyzeFileCachePerformance(),
      'temporary_files' => $this->analyzeTemporaryFiles(),
    ];
  }

  /**
   * Gets file system type.
   */
  protected function getFileSystemType() {
    // This would detect the file system type
    return 'Unknown'; // Placeholder
  }

  /**
   * Measures disk I/O performance.
   */
  protected function measureDiskIOPerformance() {
    $temp_file = sys_get_temp_dir() . '/drupal_io_test_' . uniqid();
    $data = str_repeat('x', 1024 * 1024); // 1MB of data
    
    $start_time = microtime(true);
    file_put_contents($temp_file, $data);
    $write_time = microtime(true) - $start_time;
    
    $start_time = microtime(true);
    $read_data = file_get_contents($temp_file);
    $read_time = microtime(true) - $start_time;
    
    unlink($temp_file);
    
    return [
      'write_speed_mbps' => round(1 / $write_time, 2),
      'read_speed_mbps' => round(1 / $read_time, 2),
    ];
  }

  /**
   * Analyzes file cache performance.
   */
  protected function analyzeFileCachePerformance() {
    return [
      'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status(),
      'realpath_cache_size' => ini_get('realpath_cache_size'),
      'realpath_cache_ttl' => ini_get('realpath_cache_ttl'),
    ];
  }

  /**
   * Analyzes temporary files.
   */
  protected function analyzeTemporaryFiles() {
    $temp_path = \Drupal::service('file_system')->realpath('temporary://');
    
    if (!$temp_path || !is_dir($temp_path)) {
      return ['error' => 'Temporary directory not accessible'];
    }
    
    $file_count = 0;
    $total_size = 0;
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($temp_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $file_count++;
        $total_size += $file->getSize();
      }
    }
    
    return [
      'file_count' => $file_count,
      'total_size' => $total_size,
      'total_size_mb' => round($total_size / 1024 / 1024, 2),
    ];
  }

  /**
   * Analyzes third-party integrations.
   */
  protected function analyzeThirdPartyIntegrations() {
    return [
      'external_apis' => $this->identifyExternalAPIs(),
      'cdn_usage' => $this->checkCDNUsage(),
      'social_media_widgets' => $this->checkSocialMediaWidgets(),
      'analytics_tracking' => $this->checkAnalyticsTracking(),
    ];
  }

  /**
   * Identifies external API integrations.
   */
  protected function identifyExternalAPIs() {
    $api_modules = [
      'mailchimp', 'salesforce', 'google_analytics', 'facebook_pixel',
      'twitter', 'instagram', 'youtube', 'vimeo'
    ];
    
    $enabled_apis = [];
    foreach ($api_modules as $module) {
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        $enabled_apis[] = $module;
      }
    }
    
    return $enabled_apis;
  }

  /**
   * Checks CDN usage.
   */
  protected function checkCDNUsage() {
    $cdn_modules = ['cdn', 'cloudflare', 'amazons3'];
    
    foreach ($cdn_modules as $module) {
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        return true;
      }
    }
    
    return false;
  }

  /**
   * Checks social media widgets.
   */
  protected function checkSocialMediaWidgets() {
    $social_modules = ['addtoany', 'social_media_links', 'sharethis'];
    
    $enabled_social = [];
    foreach ($social_modules as $module) {
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        $enabled_social[] = $module;
      }
    }
    
    return $enabled_social;
  }

  /**
   * Checks analytics tracking.
   */
  protected function checkAnalyticsTracking() {
    $analytics_modules = ['google_analytics', 'googletagmanager', 'matomo'];
    
    $enabled_analytics = [];
    foreach ($analytics_modules as $module) {
      if (\Drupal::moduleHandler()->moduleExists($module)) {
        $enabled_analytics[] = $module;
      }
    }
    
    return $enabled_analytics;
  }

  /**
   * Generates performance recommendations.
   */
  protected function generatePerformanceRecommendations() {
    $recommendations = [];
    
    // Cache recommendations
    $cache_analysis = $this->analyzeCachePerformance();
    if (!$cache_analysis['page_cache']['enabled']) {
      $recommendations[] = [
        'category' => 'caching',
        'priority' => 'high',
        'message' => 'Enable page caching to improve performance',
      ];
    }
    
    // Asset optimization recommendations
    $asset_analysis = $this->analyzeAssetOptimization();
    if (!$asset_analysis['css_aggregation']) {
      $recommendations[] = [
        'category' => 'assets',
        'priority' => 'medium',
        'message' => 'Enable CSS aggregation to reduce HTTP requests',
      ];
    }
    
    if (!$asset_analysis['js_aggregation']) {
      $recommendations[] = [
        'category' => 'assets',
        'priority' => 'medium',
        'message' => 'Enable JavaScript aggregation to reduce HTTP requests',
      ];
    }
    
    // Memory recommendations
    $memory_analysis = $this->analyzeMemoryUsage();
    if ($memory_analysis['usage_percentage'] > 80) {
      $recommendations[] = [
        'category' => 'memory',
        'priority' => 'high',
        'message' => 'Memory usage is high (' . $memory_analysis['usage_percentage'] . '%). Consider increasing memory limit or optimizing code.',
      ];
    }
    
    // Module recommendations
    $module_analysis = $this->analyzeModulePerformance();
    if ($module_analysis['total_modules'] > 50) {
      $recommendations[] = [
        'category' => 'modules',
        'priority' => 'medium',
        'message' => 'Large number of modules (' . $module_analysis['total_modules'] . ') may impact performance. Review and disable unused modules.',
      ];
    }
    
    return $recommendations;
  }

  /**
   * Analyzes cache configuration in detail (site_audit functionality).
   */
  protected function analyzeCacheDetailed() {
    $analysis = [
      'page_cache_status' => $this->getPageCacheStatus(),
      'dynamic_page_cache_status' => $this->getDynamicPageCacheStatus(),
      'render_cache_status' => $this->getRenderCacheStatus(),
      'cache_bins_status' => $this->getCacheBinsStatus(),
      'recommendations' => [],
    ];

    // Generate recommendations based on cache status
    if (!$analysis['page_cache_status']['enabled']) {
      $analysis['recommendations'][] = 'Page cache is disabled. Enable page caching for better performance.';
    }

    if (!$analysis['dynamic_page_cache_status']['enabled']) {
      $analysis['recommendations'][] = 'Dynamic page cache is disabled. Enable dynamic page cache for authenticated users.';
    }

    return $analysis;
  }

  /**
   * Gets page cache status.
   */
  protected function getPageCacheStatus() {
    $config = $this->configFactory->get('system.performance');
    $max_age = $config->get('cache.page.max_age');
    
    return [
      'enabled' => $max_age > 0,
      'max_age' => $max_age,
      'max_age_minutes' => round($max_age / 60, 1),
      'status' => $this->getCachePageStatus($max_age),
    ];
  }

  /**
   * Gets cache page status based on max_age.
   */
  protected function getCachePageStatus($max_age) {
    if ($max_age == 0) {
      return 'fail';
    }
    elseif ($max_age >= 900) { // 15 minutes
      return 'pass';
    }
    else {
      return 'warn';
    }
  }

  /**
   * Gets dynamic page cache status.
   */
  protected function getDynamicPageCacheStatus() {
    $enabled = \Drupal::moduleHandler()->moduleExists('dynamic_page_cache');
    
    return [
      'enabled' => $enabled,
      'module_exists' => $enabled,
      'status' => $enabled ? 'pass' : 'warn',
    ];
  }

  /**
   * Gets render cache status.
   */
  protected function getRenderCacheStatus() {
    // Render cache is always enabled in Drupal 8+
    return [
      'enabled' => true,
      'status' => 'pass',
    ];
  }

  /**
   * Gets cache bins status.
   */
  protected function getCacheBinsStatus() {
    $bins_status = [];
    
    $cache_bins = [
      'bootstrap', 'config', 'data', 'default', 'discovery',
      'dynamic_page_cache', 'entity', 'menu', 'render', 'static'
    ];
    
    foreach ($cache_bins as $bin) {
      try {
        $cache_backend = \Drupal::cache($bin);
        $bins_status[$bin] = [
          'backend' => get_class($cache_backend),
          'available' => true,
        ];
      }
      catch (\Exception $e) {
        $bins_status[$bin] = [
          'backend' => 'Unknown',
          'available' => false,
          'error' => $e->getMessage(),
        ];
      }
    }
    
    return $bins_status;
  }

  /**
   * Analyzes cache bins in detail (site_audit functionality).
   */
  protected function analyzeCacheBinsDetailed() {
    $analysis = [
      'bins_all' => $this->getAllCacheBins(),
      'bins_default' => $this->getDefaultCacheBins(),
      'bins_used' => $this->getUsedCacheBins(),
      'recommendations' => [],
    ];

    // Check for missing cache bins
    $expected_bins = ['bootstrap', 'config', 'data', 'default', 'discovery', 'entity', 'menu', 'render'];
    $missing_bins = array_diff($expected_bins, array_keys($analysis['bins_all']));
    
    if (!empty($missing_bins)) {
      $analysis['recommendations'][] = 'Some expected cache bins are missing: ' . implode(', ', $missing_bins);
    }

    return $analysis;
  }

  /**
   * Gets all cache bins.
   */
  protected function getAllCacheBins() {
    $bins = [];
    
    // Get cache bin services
    $container = \Drupal::getContainer();
    $service_ids = $container->getServiceIds();
    
    foreach ($service_ids as $service_id) {
      if (strpos($service_id, 'cache.') === 0 && $service_id !== 'cache.factory') {
        $bin_name = substr($service_id, 6); // Remove 'cache.' prefix
        try {
          $cache_service = $container->get($service_id);
          $bins[$bin_name] = [
            'service_id' => $service_id,
            'backend' => get_class($cache_service),
          ];
        }
        catch (\Exception $e) {
          $bins[$bin_name] = [
            'service_id' => $service_id,
            'backend' => 'Error',
            'error' => $e->getMessage(),
          ];
        }
      }
    }
    
    return $bins;
  }

  /**
   * Gets default cache bins.
   */
  protected function getDefaultCacheBins() {
    $default_bins = [
      'bootstrap' => 'Bootstrap cache',
      'config' => 'Configuration cache',
      'data' => 'Data cache',
      'default' => 'Default cache',
      'discovery' => 'Discovery cache',
      'entity' => 'Entity cache',
      'menu' => 'Menu cache',
      'render' => 'Render cache',
      'static' => 'Static cache',
    ];
    
    $bins = [];
    foreach ($default_bins as $bin => $description) {
      try {
        $cache_backend = \Drupal::cache($bin);
        $bins[$bin] = [
          'description' => $description,
          'backend' => get_class($cache_backend),
          'available' => true,
        ];
      }
      catch (\Exception $e) {
        $bins[$bin] = [
          'description' => $description,
          'backend' => 'Error',
          'available' => false,
        ];
      }
    }
    
    return $bins;
  }

  /**
   * Gets used cache bins with statistics.
   */
  protected function getUsedCacheBins() {
    $bins = [];
    
    $cache_bins = ['bootstrap', 'config', 'data', 'default', 'discovery', 'entity', 'menu', 'render'];
    
    foreach ($cache_bins as $bin) {
      try {
        $item_count = $this->getCacheBinItemCount($bin);
        $size = $this->getCacheBinSize($bin);
        
        $bins[$bin] = [
          'item_count' => $item_count,
          'size_bytes' => $size,
          'size_mb' => round($size / 1024 / 1024, 2),
          'used' => $item_count > 0,
        ];
      }
      catch (\Exception $e) {
        $bins[$bin] = [
          'item_count' => 0,
          'size_bytes' => 0,
          'size_mb' => 0,
          'used' => false,
          'error' => $e->getMessage(),
        ];
      }
    }
    
    return $bins;
  }

  /**
   * Analyzes cache page expiration (site_audit functionality).
   */
  protected function analyzeCachePageExpire() {
    $config = $this->configFactory->get('system.performance');
    $max_age = $config->get('cache.page.max_age');
    
    $analysis = [
      'max_age' => $max_age,
      'max_age_minutes' => round($max_age / 60, 1),
      'status' => $this->getCachePageStatus($max_age),
      'message' => $this->getCachePageMessage($max_age),
      'recommendation' => $this->getCachePageRecommendation($max_age),
    ];
    
    return $analysis;
  }

  /**
   * Gets cache page message.
   */
  protected function getCachePageMessage($max_age) {
    if ($max_age == 0) {
      return 'Page caching is disabled.';
    }
    else {
      return 'Page cache expiration is set to ' . round($max_age / 60, 1) . ' minutes.';
    }
  }

  /**
   * Gets cache page recommendation.
   */
  protected function getCachePageRecommendation($max_age) {
    if ($max_age == 0) {
      return 'Enable page caching by setting a cache expiration time of at least 15 minutes.';
    }
    elseif ($max_age < 900) {
      return 'Consider increasing page cache expiration to at least 15 minutes for better performance.';
    }
    else {
      return 'Page cache expiration is appropriately configured.';
    }
  }

  /**
   * Analyzes cache preprocessing (site_audit functionality).
   */
  protected function analyzeCachePreprocessing() {
    $config = $this->configFactory->get('system.performance');
    
    $analysis = [
      'css_preprocessing' => $this->analyzeCSSPreprocessing($config),
      'js_preprocessing' => $this->analyzeJSPreprocessing($config),
      'overall_status' => 'pass',
      'recommendations' => [],
    ];
    
    // Determine overall status
    if (!$analysis['css_preprocessing']['enabled'] || !$analysis['js_preprocessing']['enabled']) {
      $analysis['overall_status'] = 'warn';
    }
    
    // Collect recommendations
    if (isset($analysis['css_preprocessing']['recommendation'])) {
      $analysis['recommendations'][] = $analysis['css_preprocessing']['recommendation'];
    }
    if (isset($analysis['js_preprocessing']['recommendation'])) {
      $analysis['recommendations'][] = $analysis['js_preprocessing']['recommendation'];
    }
    
    return $analysis;
  }

  /**
   * Analyzes CSS preprocessing.
   */
  protected function analyzeCSSPreprocessing($config) {
    $enabled = $config->get('css.preprocess');
    
    return [
      'enabled' => $enabled,
      'status' => $enabled ? 'pass' : 'warn',
      'message' => $enabled ? 'CSS preprocessing is enabled.' : 'CSS preprocessing is disabled.',
      'recommendation' => $enabled ? null : 'Enable CSS preprocessing to reduce HTTP requests and improve performance.',
    ];
  }

  /**
   * Analyzes JS preprocessing.
   */
  protected function analyzeJSPreprocessing($config) {
    $enabled = $config->get('js.preprocess');
    
    return [
      'enabled' => $enabled,
      'status' => $enabled ? 'pass' : 'warn',
      'message' => $enabled ? 'JavaScript preprocessing is enabled.' : 'JavaScript preprocessing is disabled.',
      'recommendation' => $enabled ? null : 'Enable JavaScript preprocessing to reduce HTTP requests and improve performance.',
    ];
  }

  /**
   * Generates site_audit specific cache recommendations.
   */
  protected function generateSiteAuditCacheRecommendations() {
    $recommendations = [];
    
    // Collect recommendations from site_audit cache analyses
    $cache_detailed = $this->analyzeCacheDetailed();
    $cache_bins = $this->analyzeCacheBinsDetailed();
    $cache_expire = $this->analyzeCachePageExpire();
    $cache_preprocessing = $this->analyzeCachePreprocessing();
    
    $analyses = [$cache_detailed, $cache_bins, $cache_preprocessing];
    
    foreach ($analyses as $analysis) {
      if (isset($analysis['recommendations'])) {
        foreach ($analysis['recommendations'] as $recommendation) {
          $recommendations[] = [
            'type' => 'info',
            'category' => 'cache',
            'message' => $recommendation,
            'priority' => 'medium',
          ];
        }
      }
    }
    
    // Add cache expiration recommendation
    if (isset($cache_expire['recommendation'])) {
      $recommendations[] = [
        'type' => $cache_expire['status'] === 'fail' ? 'error' : 'warning',
        'category' => 'cache_expiration',
        'message' => $cache_expire['recommendation'],
        'priority' => $cache_expire['status'] === 'fail' ? 'high' : 'medium',
      ];
    }
    
    return $recommendations;
  }

}