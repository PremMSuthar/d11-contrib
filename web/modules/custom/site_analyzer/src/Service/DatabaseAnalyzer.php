<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for analyzing database structure and performance.
 */
class DatabaseAnalyzer {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a DatabaseAnalyzer object.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Analyzes database structure and performance.
   */
  public function analyze() {
    return [
      'database_info' => $this->getDatabaseInfo(),
      'table_analysis' => $this->analyzeTableStructure(),
      'size_analysis' => $this->analyzeDatabaseSize(),
      'index_analysis' => $this->analyzeIndexes(),
      'performance_metrics' => $this->getPerformanceMetrics(),
      'custom_tables' => $this->findCustomTables(),
      'data_integrity' => $this->checkDataIntegrity(),
      'migration_requirements' => $this->assessMigrationRequirements(),
      // Enhanced site_audit functionality
      'collation_analysis' => $this->analyzeCollation(),
      'engine_analysis' => $this->analyzeEngines(),
      'fragmentation_analysis' => $this->analyzeFragmentation(),
      'row_count_analysis' => $this->analyzeRowCounts(),
      'site_audit_recommendations' => $this->generateSiteAuditRecommendations(),
    ];
  }

  /**
   * Gets basic database information.
   */
  protected function getDatabaseInfo() {
    $connection_info = $this->database->getConnectionOptions();
    
    return [
      'driver' => $connection_info['driver'],
      'host' => $connection_info['host'] ?? 'localhost',
      'port' => $connection_info['port'] ?? $this->getDefaultPort($connection_info['driver']),
      'database' => $connection_info['database'],
      'version' => $this->database->version(),
      'charset' => $connection_info['charset'] ?? 'utf8mb4',
      'collation' => $connection_info['collation'] ?? 'utf8mb4_general_ci',
      'connection_count' => $this->getConnectionCount(),
      'uptime' => $this->getDatabaseUptime(),
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
   * Gets current connection count.
   */
  protected function getConnectionCount() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $result = $this->database->query("SHOW STATUS LIKE 'Threads_connected'")->fetchAssoc();
        return (int) $result['Value'];
      }
      elseif ($driver === 'pgsql') {
        $result = $this->database->query("SELECT count(*) FROM pg_stat_activity")->fetchField();
        return (int) $result;
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return 0;
  }

  /**
   * Gets database uptime.
   */
  protected function getDatabaseUptime() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $result = $this->database->query("SHOW STATUS LIKE 'Uptime'")->fetchAssoc();
        return (int) $result['Value'];
      }
      elseif ($driver === 'pgsql') {
        $result = $this->database->query("SELECT EXTRACT(EPOCH FROM (now() - pg_postmaster_start_time()))")->fetchField();
        return (int) $result;
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return 0;
  }

  /**
   * Analyzes table structure.
   */
  protected function analyzeTableStructure() {
    $tables = [];
    $schema = $this->database->schema();
    $table_names = $schema->findTables('%');
    
    foreach ($table_names as $table_name) {
      $tables[$table_name] = [
        'name' => $table_name,
        'type' => $this->getTableType($table_name),
        'row_count' => $this->getTableRowCount($table_name),
        'size' => $this->getTableSize($table_name),
        'columns' => $this->getTableColumns($table_name),
        'indexes' => $this->getTableIndexes($table_name),
        'engine' => $this->getTableEngine($table_name),
        'collation' => $this->getTableCollation($table_name),
      ];
    }
    
    return $tables;
  }

  /**
   * Determines table type (core, contrib, custom).
   */
  protected function getTableType($table_name) {
    // Core tables
    $core_prefixes = [
      'batch', 'block', 'cache', 'comment', 'config', 'file', 'flood',
      'history', 'key_value', 'menu', 'node', 'queue', 'router', 'search',
      'semaphore', 'sequences', 'sessions', 'system', 'taxonomy', 'url_alias',
      'user', 'watchdog', 'locale', 'shortcut'
    ];
    
    foreach ($core_prefixes as $prefix) {
      if (strpos($table_name, $prefix) === 0) {
        return 'core';
      }
    }
    
    // Field tables
    if (preg_match('/^(node|user|taxonomy_term|comment|media|paragraph)__(field_|revision__)/', $table_name)) {
      return 'field';
    }
    
    // Check if it's a known contrib module table
    if ($this->isContribTable($table_name)) {
      return 'contrib';
    }
    
    return 'custom';
  }

  /**
   * Checks if table belongs to a contrib module.
   */
  protected function isContribTable($table_name) {
    // This would check against known contrib module table patterns
    $contrib_patterns = [
      'views_', 'field_collection_', 'webform_', 'commerce_', 'rules_',
      'pathauto_', 'redirect_', 'metatag_', 'xmlsitemap_'
    ];
    
    foreach ($contrib_patterns as $pattern) {
      if (strpos($table_name, $pattern) === 0) {
        return true;
      }
    }
    
    return false;
  }

  /**
   * Gets table row count.
   */
  protected function getTableRowCount($table_name) {
    try {
      $query = $this->database->select($table_name, 't')->countQuery();
      return (int) $query->execute()->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets table size in bytes.
   */
  protected function getTableSize($table_name) {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $query = "SELECT (data_length + index_length) AS size 
                  FROM information_schema.tables 
                  WHERE table_schema = DATABASE() AND table_name = :table";
        $result = $this->database->query($query, [':table' => $table_name])->fetchField();
        return (int) $result;
      }
      elseif ($driver === 'pgsql') {
        $query = "SELECT pg_total_relation_size(:table)";
        $result = $this->database->query($query, [':table' => $table_name])->fetchField();
        return (int) $result;
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return 0;
  }

  /**
   * Gets table columns information.
   */
  protected function getTableColumns($table_name) {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $query = "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
                  FROM information_schema.columns 
                  WHERE table_schema = DATABASE() AND table_name = :table
                  ORDER BY ordinal_position";
        $results = $this->database->query($query, [':table' => $table_name])->fetchAll();
        
        $columns = [];
        foreach ($results as $row) {
          $columns[] = [
            'name' => $row->column_name,
            'type' => $row->data_type,
            'nullable' => $row->is_nullable === 'YES',
            'default' => $row->column_default,
            'length' => $row->character_maximum_length,
          ];
        }
        
        return $columns;
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return [];
  }

  /**
   * Gets table indexes.
   */
  protected function getTableIndexes($table_name) {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $query = "SHOW INDEX FROM `{$table_name}`";
        $results = $this->database->query($query)->fetchAll();
        
        $indexes = [];
        foreach ($results as $row) {
          $index_name = $row->Key_name;
          if (!isset($indexes[$index_name])) {
            $indexes[$index_name] = [
              'name' => $index_name,
              'unique' => !$row->Non_unique,
              'columns' => [],
            ];
          }
          $indexes[$index_name]['columns'][] = $row->Column_name;
        }
        
        return array_values($indexes);
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return [];
  }

  /**
   * Gets table engine.
   */
  protected function getTableEngine($table_name) {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $query = "SELECT engine FROM information_schema.tables 
                  WHERE table_schema = DATABASE() AND table_name = :table";
        return $this->database->query($query, [':table' => $table_name])->fetchField();
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return null;
  }

  /**
   * Gets table collation.
   */
  protected function getTableCollation($table_name) {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $query = "SELECT table_collation FROM information_schema.tables 
                  WHERE table_schema = DATABASE() AND table_name = :table";
        return $this->database->query($query, [':table' => $table_name])->fetchField();
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return null;
  }

  /**
   * Analyzes database size breakdown.
   */
  protected function analyzeDatabaseSize() {
    $size_analysis = [
      'total_size' => 0,
      'data_size' => 0,
      'index_size' => 0,
      'largest_tables' => [],
      'size_by_type' => [
        'core' => 0,
        'contrib' => 0,
        'custom' => 0,
        'field' => 0,
      ],
    ];
    
    $tables = $this->analyzeTableStructure();
    
    foreach ($tables as $table_name => $table_info) {
      $size = $table_info['size'];
      $type = $table_info['type'];
      
      $size_analysis['total_size'] += $size;
      $size_analysis['size_by_type'][$type] += $size;
      
      $size_analysis['largest_tables'][] = [
        'name' => $table_name,
        'size' => $size,
        'rows' => $table_info['row_count'],
        'type' => $type,
      ];
    }
    
    // Sort largest tables by size
    usort($size_analysis['largest_tables'], function ($a, $b) {
      return $b['size'] - $a['size'];
    });
    
    // Keep only top 20
    $size_analysis['largest_tables'] = array_slice($size_analysis['largest_tables'], 0, 20);
    
    return $size_analysis;
  }

  /**
   * Analyzes database indexes.
   */
  protected function analyzeIndexes() {
    $index_analysis = [
      'total_indexes' => 0,
      'missing_indexes' => [],
      'unused_indexes' => [],
      'duplicate_indexes' => [],
      'recommendations' => [],
    ];
    
    $tables = $this->analyzeTableStructure();
    
    foreach ($tables as $table_name => $table_info) {
      $indexes = $table_info['indexes'];
      $index_analysis['total_indexes'] += count($indexes);
      
      // Check for missing primary keys
      $has_primary = false;
      foreach ($indexes as $index) {
        if ($index['name'] === 'PRIMARY') {
          $has_primary = true;
          break;
        }
      }
      
      if (!$has_primary && $table_info['row_count'] > 0) {
        $index_analysis['missing_indexes'][] = [
          'table' => $table_name,
          'type' => 'primary_key',
          'recommendation' => 'Add primary key to table',
        ];
      }
      
      // Check for tables with many rows but few indexes
      if ($table_info['row_count'] > 10000 && count($indexes) < 2) {
        $index_analysis['recommendations'][] = [
          'table' => $table_name,
          'type' => 'performance',
          'message' => 'Large table with few indexes may benefit from additional indexing',
        ];
      }
    }
    
    return $index_analysis;
  }

  /**
   * Gets performance metrics.
   */
  protected function getPerformanceMetrics() {
    $metrics = [
      'slow_queries' => $this->getSlowQueryCount(),
      'query_cache_hit_rate' => $this->getQueryCacheHitRate(),
      'table_locks' => $this->getTableLockStats(),
      'innodb_buffer_pool' => $this->getInnoDBBufferPoolStats(),
      'connection_stats' => $this->getConnectionStats(),
    ];
    
    return $metrics;
  }

  /**
   * Gets slow query count.
   */
  protected function getSlowQueryCount() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $result = $this->database->query("SHOW STATUS LIKE 'Slow_queries'")->fetchAssoc();
        return (int) $result['Value'];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return 0;
  }

  /**
   * Gets query cache hit rate.
   */
  protected function getQueryCacheHitRate() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $hits = $this->database->query("SHOW STATUS LIKE 'Qcache_hits'")->fetchAssoc();
        $inserts = $this->database->query("SHOW STATUS LIKE 'Qcache_inserts'")->fetchAssoc();
        
        $total = (int) $hits['Value'] + (int) $inserts['Value'];
        if ($total > 0) {
          return round(((int) $hits['Value'] / $total) * 100, 2);
        }
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return 0;
  }

  /**
   * Gets table lock statistics.
   */
  protected function getTableLockStats() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $waited = $this->database->query("SHOW STATUS LIKE 'Table_locks_waited'")->fetchAssoc();
        $immediate = $this->database->query("SHOW STATUS LIKE 'Table_locks_immediate'")->fetchAssoc();
        
        return [
          'waited' => (int) $waited['Value'],
          'immediate' => (int) $immediate['Value'],
        ];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return ['waited' => 0, 'immediate' => 0];
  }

  /**
   * Gets InnoDB buffer pool statistics.
   */
  protected function getInnoDBBufferPoolStats() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $pool_size = $this->database->query("SHOW STATUS LIKE 'Innodb_buffer_pool_pages_total'")->fetchAssoc();
        $pool_free = $this->database->query("SHOW STATUS LIKE 'Innodb_buffer_pool_pages_free'")->fetchAssoc();
        
        $total = (int) $pool_size['Value'];
        $free = (int) $pool_free['Value'];
        $used = $total - $free;
        
        return [
          'total_pages' => $total,
          'free_pages' => $free,
          'used_pages' => $used,
          'usage_percentage' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
        ];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return [];
  }

  /**
   * Gets connection statistics.
   */
  protected function getConnectionStats() {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $max_connections = $this->database->query("SHOW VARIABLES LIKE 'max_connections'")->fetchAssoc();
        $current_connections = $this->database->query("SHOW STATUS LIKE 'Threads_connected'")->fetchAssoc();
        
        return [
          'max_connections' => (int) $max_connections['Value'],
          'current_connections' => (int) $current_connections['Value'],
          'usage_percentage' => round(((int) $current_connections['Value'] / (int) $max_connections['Value']) * 100, 2),
        ];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return [];
  }

  /**
   * Finds custom tables not managed by Drupal.
   */
  protected function findCustomTables() {
    $custom_tables = [];
    $schema = $this->database->schema();
    $all_tables = $schema->findTables('%');
    
    foreach ($all_tables as $table_name) {
      $type = $this->getTableType($table_name);
      if ($type === 'custom') {
        $custom_tables[] = [
          'name' => $table_name,
          'row_count' => $this->getTableRowCount($table_name),
          'size' => $this->getTableSize($table_name),
          'columns' => count($this->getTableColumns($table_name)),
        ];
      }
    }
    
    return $custom_tables;
  }

  /**
   * Checks data integrity.
   */
  protected function checkDataIntegrity() {
    $integrity_checks = [
      'orphaned_files' => $this->findOrphanedFiles(),
      'missing_files' => $this->findMissingFiles(),
      'broken_references' => $this->findBrokenReferences(),
      'duplicate_entries' => $this->findDuplicateEntries(),
    ];
    
    return $integrity_checks;
  }

  /**
   * Finds orphaned files.
   */
  protected function findOrphanedFiles() {
    try {
      // Files in file_managed but not referenced in file_usage
      $query = "SELECT COUNT(*) FROM file_managed fm 
                LEFT JOIN file_usage fu ON fm.fid = fu.fid 
                WHERE fu.fid IS NULL";
      return (int) $this->database->query($query)->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Finds missing files.
   */
  protected function findMissingFiles() {
    try {
      // This would check if files exist on disk
      // For now, return a placeholder
      return 0;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Finds broken entity references.
   */
  protected function findBrokenReferences() {
    $broken_refs = 0;
    
    try {
      // Check node references
      $query = "SELECT COUNT(*) FROM node__field_tags nft 
                LEFT JOIN taxonomy_term_field_data ttfd ON nft.field_tags_target_id = ttfd.tid 
                WHERE ttfd.tid IS NULL AND nft.field_tags_target_id IS NOT NULL";
      $broken_refs += (int) $this->database->query($query)->fetchField();
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $broken_refs;
  }

  /**
   * Finds duplicate entries.
   */
  protected function findDuplicateEntries() {
    $duplicates = 0;
    
    try {
      // Check for duplicate URL aliases
      $query = "SELECT COUNT(*) - COUNT(DISTINCT alias) FROM path_alias";
      $duplicates += (int) $this->database->query($query)->fetchField();
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $duplicates;
  }

  /**
   * Assesses migration requirements.
   */
  protected function assessMigrationRequirements() {
    return [
      'database_size_mb' => round($this->analyzeDatabaseSize()['total_size'] / 1024 / 1024, 2),
      'estimated_migration_time' => $this->estimateMigrationTime(),
      'compatibility_issues' => $this->findMigrationCompatibilityIssues(),
      'backup_requirements' => $this->getBackupRequirements(),
    ];
  }

  /**
   * Estimates migration time based on database size.
   */
  protected function estimateMigrationTime() {
    $size_mb = round($this->analyzeDatabaseSize()['total_size'] / 1024 / 1024, 2);
    
    // Rough estimate: 1MB per minute for migration
    $estimated_minutes = $size_mb;
    
    if ($estimated_minutes < 60) {
      return round($estimated_minutes) . ' minutes';
    }
    else {
      return round($estimated_minutes / 60, 1) . ' hours';
    }
  }

  /**
   * Finds migration compatibility issues.
   */
  protected function findMigrationCompatibilityIssues() {
    $issues = [];
    
    // Check for deprecated table engines
    $tables = $this->analyzeTableStructure();
    foreach ($tables as $table_name => $table_info) {
      if ($table_info['engine'] === 'MyISAM') {
        $issues[] = [
          'type' => 'warning',
          'message' => "Table {$table_name} uses MyISAM engine, consider converting to InnoDB",
        ];
      }
    }
    
    return $issues;
  }

  /**
   * Gets backup requirements.
   */
  protected function getBackupRequirements() {
    $size_mb = round($this->analyzeDatabaseSize()['total_size'] / 1024 / 1024, 2);
    
    return [
      'database_size_mb' => $size_mb,
      'estimated_backup_time' => round($size_mb / 10) . ' minutes', // Rough estimate
      'recommended_method' => $size_mb > 1000 ? 'mysqldump with compression' : 'standard mysqldump',
    ];
  }

  /**
   * Analyzes database collation (site_audit functionality).
   */
  protected function analyzeCollation() {
    $analysis = [
      'database_collation' => null,
      'table_collations' => [],
      'inconsistent_collations' => [],
      'recommendations' => [],
    ];

    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        // Get database default collation
        $db_name = $this->database->getConnectionOptions()['database'];
        $query = "SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :db_name";
        $analysis['database_collation'] = $this->database->query($query, [':db_name' => $db_name])->fetchField();

        // Get table collations
        $query = "SELECT table_name, table_collation FROM information_schema.tables WHERE table_schema = :db_name";
        $results = $this->database->query($query, [':db_name' => $db_name])->fetchAll();
        
        foreach ($results as $row) {
          $analysis['table_collations'][$row->table_name] = $row->table_collation;
          
          // Check for inconsistent collations
          if ($row->table_collation !== $analysis['database_collation']) {
            $analysis['inconsistent_collations'][] = [
              'table' => $row->table_name,
              'collation' => $row->table_collation,
              'expected' => $analysis['database_collation'],
            ];
          }
        }

        // Generate recommendations
        if (!empty($analysis['inconsistent_collations'])) {
          $analysis['recommendations'][] = 'Some tables have different collations than the database default. Consider standardizing collations.';
        }

        if ($analysis['database_collation'] && strpos($analysis['database_collation'], 'utf8mb4') === false) {
          $analysis['recommendations'][] = 'Consider using utf8mb4 collation for full Unicode support.';
        }
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Analyzes database engines (site_audit functionality).
   */
  protected function analyzeEngines() {
    $analysis = [
      'engines_used' => [],
      'myisam_tables' => [],
      'innodb_tables' => [],
      'other_engines' => [],
      'recommendations' => [],
    ];

    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $db_name = $this->database->getConnectionOptions()['database'];
        $query = "SELECT table_name, engine FROM information_schema.tables WHERE table_schema = :db_name AND engine IS NOT NULL";
        $results = $this->database->query($query, [':db_name' => $db_name])->fetchAll();
        
        foreach ($results as $row) {
          $engine = $row->engine;
          
          if (!isset($analysis['engines_used'][$engine])) {
            $analysis['engines_used'][$engine] = 0;
          }
          $analysis['engines_used'][$engine]++;
          
          switch (strtolower($engine)) {
            case 'myisam':
              $analysis['myisam_tables'][] = $row->table_name;
              break;
            case 'innodb':
              $analysis['innodb_tables'][] = $row->table_name;
              break;
            default:
              $analysis['other_engines'][] = [
                'table' => $row->table_name,
                'engine' => $engine,
              ];
          }
        }

        // Generate recommendations
        if (!empty($analysis['myisam_tables'])) {
          $analysis['recommendations'][] = 'MyISAM tables detected. Consider converting to InnoDB for better performance and ACID compliance: ' . implode(', ', array_slice($analysis['myisam_tables'], 0, 5)) . (count($analysis['myisam_tables']) > 5 ? '...' : '');
        }

        if (empty($analysis['innodb_tables']) && !empty($analysis['engines_used'])) {
          $analysis['recommendations'][] = 'No InnoDB tables found. InnoDB is recommended for most use cases.';
        }
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Analyzes database fragmentation (site_audit functionality).
   */
  protected function analyzeFragmentation() {
    $analysis = [
      'fragmented_tables' => [],
      'total_fragmentation_mb' => 0,
      'recommendations' => [],
    ];

    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $db_name = $this->database->getConnectionOptions()['database'];
        $query = "SELECT table_name, data_free FROM information_schema.tables 
                  WHERE table_schema = :db_name AND data_free > 0 
                  ORDER BY data_free DESC";
        $results = $this->database->query($query, [':db_name' => $db_name])->fetchAll();
        
        foreach ($results as $row) {
          $fragmentation_mb = round($row->data_free / 1024 / 1024, 2);
          
          if ($fragmentation_mb > 1) { // Only report tables with > 1MB fragmentation
            $analysis['fragmented_tables'][] = [
              'table' => $row->table_name,
              'fragmentation_bytes' => $row->data_free,
              'fragmentation_mb' => $fragmentation_mb,
            ];
            
            $analysis['total_fragmentation_mb'] += $fragmentation_mb;
          }
        }

        // Generate recommendations
        if ($analysis['total_fragmentation_mb'] > 100) {
          $analysis['recommendations'][] = "Significant fragmentation detected ({$analysis['total_fragmentation_mb']} MB). Consider running OPTIMIZE TABLE on fragmented tables.";
        }
        elseif ($analysis['total_fragmentation_mb'] > 10) {
          $analysis['recommendations'][] = "Some fragmentation detected ({$analysis['total_fragmentation_mb']} MB). Monitor and consider optimization.";
        }
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Analyzes row counts (site_audit functionality).
   */
  protected function analyzeRowCounts() {
    $analysis = [
      'total_rows' => 0,
      'largest_tables' => [],
      'empty_tables' => [],
      'table_growth_estimates' => [],
      'recommendations' => [],
    ];

    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $db_name = $this->database->getConnectionOptions()['database'];
        $query = "SELECT table_name, table_rows, data_length, index_length 
                  FROM information_schema.tables 
                  WHERE table_schema = :db_name AND table_rows IS NOT NULL
                  ORDER BY table_rows DESC";
        $results = $this->database->query($query, [':db_name' => $db_name])->fetchAll();
        
        foreach ($results as $row) {
          $analysis['total_rows'] += $row->table_rows;
          
          if ($row->table_rows > 10000) {
            $analysis['largest_tables'][] = [
              'table' => $row->table_name,
              'rows' => $row->table_rows,
              'data_size_mb' => round($row->data_length / 1024 / 1024, 2),
              'index_size_mb' => round($row->index_length / 1024 / 1024, 2),
            ];
          }
          
          if ($row->table_rows == 0) {
            $analysis['empty_tables'][] = $row->table_name;
          }
        }

        // Limit largest tables to top 20
        $analysis['largest_tables'] = array_slice($analysis['largest_tables'], 0, 20);

        // Generate recommendations
        if (count($analysis['largest_tables']) > 10) {
          $analysis['recommendations'][] = 'Many large tables detected. Consider implementing data archiving strategies.';
        }

        if (count($analysis['empty_tables']) > 20) {
          $analysis['recommendations'][] = 'Many empty tables detected. Consider removing unused tables: ' . implode(', ', array_slice($analysis['empty_tables'], 0, 10)) . (count($analysis['empty_tables']) > 10 ? '...' : '');
        }

        if ($analysis['total_rows'] > 10000000) { // 10 million rows
          $analysis['recommendations'][] = 'Database contains over 10 million rows. Consider performance optimization strategies.';
        }
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Generates site_audit specific recommendations.
   */
  protected function generateSiteAuditRecommendations() {
    $recommendations = [];
    
    // Collect recommendations from site_audit analyses
    $collation = $this->analyzeCollation();
    $engines = $this->analyzeEngines();
    $fragmentation = $this->analyzeFragmentation();
    $row_counts = $this->analyzeRowCounts();
    
    $analyses = [$collation, $engines, $fragmentation, $row_counts];
    
    foreach ($analyses as $analysis) {
      if (isset($analysis['recommendations'])) {
        foreach ($analysis['recommendations'] as $recommendation) {
          $recommendations[] = [
            'type' => 'info',
            'category' => 'database',
            'message' => $recommendation,
            'priority' => 'medium',
          ];
        }
      }
    }
    
    // Add general database recommendations
    $size_analysis = $this->analyzeDatabaseSize();
    $total_size_mb = round($size_analysis['total_size'] / 1024 / 1024, 2);
    
    if ($total_size_mb > 1000) {
      $recommendations[] = [
        'type' => 'info',
        'category' => 'database_size',
        'message' => "Large database detected ({$total_size_mb} MB). Consider implementing data archiving and cleanup strategies.",
        'priority' => 'medium',
      ];
    }
    
    $performance = $this->getPerformanceMetrics();
    if ($performance['slow_queries'] > 100) {
      $recommendations[] = [
        'type' => 'warning',
        'category' => 'database_performance',
        'message' => "High number of slow queries ({$performance['slow_queries']}). Review and optimize database queries.",
        'priority' => 'high',
      ];
    }
    
    return $recommendations;
  }

}