<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Service for generating comprehensive site analysis reports.
 */
class ReportGenerator {

  /**
   * The system analyzer.
   *
   * @var \Drupal\site_analyzer\Service\SystemAnalyzer
   */
  protected $systemAnalyzer;

  /**
   * The module analyzer.
   *
   * @var \Drupal\site_analyzer\Service\ModuleAnalyzer
   */
  protected $moduleAnalyzer;

  /**
   * The theme analyzer.
   *
   * @var \Drupal\site_analyzer\Service\ThemeAnalyzer
   */
  protected $themeAnalyzer;

  /**
   * The content analyzer.
   *
   * @var \Drupal\site_analyzer\Service\ContentAnalyzer
   */
  protected $contentAnalyzer;

  /**
   * The database analyzer.
   *
   * @var \Drupal\site_analyzer\Service\DatabaseAnalyzer
   */
  protected $databaseAnalyzer;

  /**
   * The code analyzer.
   *
   * @var \Drupal\site_analyzer\Service\CodeAnalyzer
   */
  protected $codeAnalyzer;

  /**
   * The performance analyzer.
   *
   * @var \Drupal\site_analyzer\Service\PerformanceAnalyzer
   */
  protected $performanceAnalyzer;

  /**
   * The security analyzer.
   *
   * @var \Drupal\site_analyzer\Service\SecurityAnalyzer
   */
  protected $securityAnalyzer;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ReportGenerator object.
   */
  public function __construct(
    SystemAnalyzer $system_analyzer,
    ModuleAnalyzer $module_analyzer,
    ThemeAnalyzer $theme_analyzer,
    ContentAnalyzer $content_analyzer,
    DatabaseAnalyzer $database_analyzer,
    CodeAnalyzer $code_analyzer,
    PerformanceAnalyzer $performance_analyzer,
    SecurityAnalyzer $security_analyzer,
    RendererInterface $renderer,
    ConfigFactoryInterface $config_factory
  ) {
    $this->systemAnalyzer = $system_analyzer;
    $this->moduleAnalyzer = $module_analyzer;
    $this->themeAnalyzer = $theme_analyzer;
    $this->contentAnalyzer = $content_analyzer;
    $this->databaseAnalyzer = $database_analyzer;
    $this->codeAnalyzer = $code_analyzer;
    $this->performanceAnalyzer = $performance_analyzer;
    $this->securityAnalyzer = $security_analyzer;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
  }

  /**
   * Generates a full comprehensive report.
   */
  public function generateFullReport() {
    return [
      'metadata' => $this->generateReportMetadata(),
      'system' => $this->generateSystemReport(),
      'modules' => $this->generateModuleReport(),
      'themes' => $this->generateThemeReport(),
      'content' => $this->generateContentReport(),
      'database' => $this->generateDatabaseReport(),
      'code' => $this->generateCodeReport(),
      'performance' => $this->generatePerformanceReport(),
      'security' => $this->generateSecurityReport(),
      'upgrade' => $this->generateUpgradeReport(),
      'summary' => $this->generateSummary(),
    ];
  }

  /**
   * Generates report metadata.
   */
  protected function generateReportMetadata() {
    return [
      'generated_at' => date('Y-m-d H:i:s'),
      'generated_by' => \Drupal::currentUser()->getAccountName(),
      'site_name' => $this->configFactory->get('system.site')->get('name'),
      'site_url' => \Drupal::request()->getSchemeAndHttpHost(),
      'drupal_version' => \Drupal::VERSION,
      'analyzer_version' => '1.0.0',
    ];
  }

  /**
   * Generates dashboard summary data.
   */
  public function generateDashboardSummary() {
    $system_data = $this->systemAnalyzer->analyze();
    $module_data = $this->moduleAnalyzer->analyze();
    $theme_data = $this->themeAnalyzer->analyze();
    $content_data = $this->contentAnalyzer->analyze();
    $security_data = $this->securityAnalyzer->analyze();
    $performance_data = $this->performanceAnalyzer->analyze();

    return [
      'system' => [
        'drupal_version' => $system_data['drupal_version']['version'],
        'php_version' => $system_data['php_version']['version'],
        'database_type' => $system_data['database_info']['driver'],
        'memory_usage' => $system_data['memory_info']['usage_percentage'],
        'disk_usage' => $system_data['disk_space']['usage_percentage'],
        'status' => $this->getSystemHealthStatus($system_data),
      ],
      'modules' => [
        'total_count' => $module_data['total_count'],
        'enabled_count' => $module_data['enabled_count'],
        'security_updates' => count($module_data['security_updates']),
        'deprecated_count' => count($module_data['deprecated_modules']),
        'custom_count' => count($module_data['custom_modules']),
        'status' => $this->getModuleHealthStatus($module_data),
      ],
      'themes' => [
        'total_count' => $theme_data['total_count'],
        'enabled_count' => $theme_data['enabled_count'],
        'default_theme' => $theme_data['default_theme'],
        'admin_theme' => $theme_data['admin_theme'],
        'custom_count' => count($theme_data['custom_themes']),
        'status' => $this->getThemeHealthStatus($theme_data),
      ],
      'content' => [
        'content_types' => count($content_data['content_types']),
        'total_nodes' => $content_data['content_volume']['node']['count'] ?? 0,
        'total_users' => $content_data['content_volume']['user']['count'] ?? 0,
        'total_files' => $content_data['media_usage']['total_files'],
        'deprecated_fields' => count($content_data['deprecated_fields']),
        'status' => $this->getContentHealthStatus($content_data),
      ],
      'security' => [
        'security_updates' => count($security_data['security_updates']),
        'vulnerabilities' => count($security_data['vulnerabilities']),
        'risky_permissions' => count($security_data['permission_audit']['risky_permissions']),
        'file_permission_issues' => $this->countFilePermissionIssues($security_data['file_permissions']),
        'status' => $this->getSecurityHealthStatus($security_data),
      ],
      'performance' => [
        'page_cache_enabled' => $performance_data['cache_analysis']['page_cache']['enabled'],
        'css_aggregation' => $performance_data['asset_optimization']['css_aggregation'],
        'js_aggregation' => $performance_data['asset_optimization']['js_aggregation'],
        'memory_usage' => $performance_data['memory_usage']['usage_percentage'],
        'cache_hit_rate' => $performance_data['cache_analysis']['cache_hit_rates']['page_cache'],
        'status' => $this->getPerformanceHealthStatus($performance_data),
      ],
      'upgrade' => $this->generateUpgradeSummary(),
    ];
  }

  /**
   * Generates system report.
   */
  public function generateSystemReport() {
    return $this->systemAnalyzer->analyze();
  }

  /**
   * Generates module report.
   */
  public function generateModuleReport() {
    return $this->moduleAnalyzer->analyze();
  }

  /**
   * Generates theme report.
   */
  public function generateThemeReport() {
    return $this->themeAnalyzer->analyze();
  }

  /**
   * Generates content report.
   */
  public function generateContentReport() {
    return $this->contentAnalyzer->analyze();
  }

  /**
   * Generates database report.
   */
  public function generateDatabaseReport() {
    return $this->databaseAnalyzer->analyze();
  }

  /**
   * Generates code report.
   */
  public function generateCodeReport() {
    return $this->codeAnalyzer->analyze();
  }

  /**
   * Generates performance report.
   */
  public function generatePerformanceReport() {
    return $this->performanceAnalyzer->analyze();
  }

  /**
   * Generates security report.
   */
  public function generateSecurityReport() {
    return $this->securityAnalyzer->analyze();
  }

  /**
   * Generates upgrade readiness report.
   */
  public function generateUpgradeReport() {
    $report = [
      'timestamp' => time(),
      'drupal_version' => \Drupal::VERSION,
      'target_version' => '11.0.0',
    ];

    // Get all analysis data
    $system_data = $this->systemAnalyzer->analyze();
    $module_data = $this->moduleAnalyzer->analyze();
    $theme_data = $this->themeAnalyzer->analyze();
    $content_data = $this->contentAnalyzer->analyze();
    $database_data = $this->databaseAnalyzer->analyze();
    $code_data = $this->codeAnalyzer->analyze();
    $performance_data = $this->performanceAnalyzer->analyze();
    $security_data = $this->securityAnalyzer->analyze();

    // Compile upgrade readiness
    $report['compatibility_matrix'] = $this->generateCompatibilityMatrix($module_data, $theme_data);
    $report['breaking_changes'] = $this->identifyBreakingChanges($code_data);
    $report['migration_requirements'] = $this->assessMigrationRequirements($system_data, $database_data);
    $report['effort_estimation'] = $this->estimateUpgradeEffort($module_data, $theme_data, $code_data);
    $report['risk_assessment'] = $this->assessUpgradeRisks($module_data, $theme_data, $code_data);
    $report['recommendations'] = $this->generateUpgradeRecommendations($system_data, $database_data);

    return $report;
  }
  
  /**
   * Gets the module analyzer service.
   */
  public function getModuleAnalyzer() {
    return $this->moduleAnalyzer;
  }
  
  /**
   * Gets the theme analyzer service.
   */
  public function getThemeAnalyzer() {
    return $this->themeAnalyzer;
  }
  
  /**
   * Gets the system analyzer service.
   */
  public function getSystemAnalyzer() {
    return $this->systemAnalyzer;
  }
  
  /**
   * Gets the content analyzer service.
   */
  public function getContentAnalyzer() {
    return $this->contentAnalyzer;
  }
  
  /**
   * Gets the database analyzer service.
   */
  public function getDatabaseAnalyzer() {
    return $this->databaseAnalyzer;
  }
  
  /**
   * Gets the code analyzer service.
   */
  public function getCodeAnalyzer() {
    return $this->codeAnalyzer;
  }
  
  /**
   * Gets the performance analyzer service.
   */
  public function getPerformanceAnalyzer() {
    return $this->performanceAnalyzer;
  }
  
  /**
   * Gets the security analyzer service.
   */
  public function getSecurityAnalyzer() {
    return $this->securityAnalyzer;
  }

  /**
   * Generates compatibility matrix.
   */
  protected function generateCompatibilityMatrix($module_data, $theme_data) {
    $matrix = [
      'modules' => [
        'compatible' => [],
        'incompatible' => [],
        'unknown' => [],
      ],
      'themes' => [
        'compatible' => [],
        'incompatible' => [],
        'unknown' => [],
      ],
    ];

    // Analyze module compatibility
    foreach ($module_data['contrib_modules'] as $name => $module) {
      if (!empty($module['compatibility_issues'])) {
        $matrix['modules']['incompatible'][$name] = $module;
      }
      elseif ($module['type'] === 'contrib' && !empty($module['latest_version'])) {
        $matrix['modules']['compatible'][$name] = $module;
      }
      else {
        $matrix['modules']['unknown'][$name] = $module;
      }
    }

    // Analyze theme compatibility
    foreach ($theme_data['contrib_themes'] as $name => $theme) {
      if (!empty($theme['compatibility_issues'])) {
        $matrix['themes']['incompatible'][$name] = $theme;
      }
      else {
        $matrix['themes']['compatible'][$name] = $theme;
      }
    }

    return $matrix;
  }

  /**
   * Identifies breaking changes.
   */
  protected function identifyBreakingChanges($code_data) {
    $breaking_changes = [];

    // Ensure we have the expected data structure
    if (!isset($code_data['deprecated_functions']) || !is_array($code_data['deprecated_functions'])) {
      $code_data['deprecated_functions'] = [];
    }
    if (!isset($code_data['api_changes']) || !is_array($code_data['api_changes'])) {
      $code_data['api_changes'] = [];
    }

    // Deprecated functions are breaking changes
    foreach ($code_data['deprecated_functions'] as $usage) {
      if (is_array($usage) && isset($usage['function'])) {
        $breaking_changes[] = [
          'type' => 'deprecated_function',
          'location' => $usage['file'] ?? 'unknown',
          'function' => $usage['function'],
          'file' => $usage['file'] ?? 'unknown',
          'line' => $usage['line'] ?? 0,
          'replacement' => $usage['replacement'] ?? 'No replacement specified',
          'severity' => $usage['severity'] ?? 'high',
        ];
      }
    }

    // API changes
    foreach ($code_data['api_changes'] as $change) {
      if (is_array($change) && isset($change['impact_level']) && $change['impact_level'] === 'high') {
        $breaking_changes[] = [
          'type' => 'api_change',
          'location' => $change['location'] ?? 'unknown',
          'impact' => $change['impact_level'],
          'changes_count' => $change['deprecated_functions'] ?? 0,
          'severity' => 'high',
        ];
      }
    }

    return $breaking_changes;
  }

  /**
   * Assesses migration requirements.
   */
  protected function assessMigrationRequirements($system_data, $database_data) {
    $requirements = [
      'system_requirements' => [],
      'database_requirements' => [],
      'estimated_downtime' => 'medium',
      'backup_size_estimate' => 0,
      'migration_complexity' => 'medium',
    ];

    // System requirements
    if (version_compare($system_data['php_version']['version'], '8.1', '<')) {
      $requirements['system_requirements'][] = [
        'type' => 'php_upgrade',
        'current' => $system_data['php_version']['version'],
        'required' => '8.1+',
        'priority' => 'high',
      ];
    }

    // Database requirements
    if ($database_data['database_info']['driver'] === 'mysql') {
      $version = $database_data['database_info']['version'];
      if (version_compare($version, '5.7.8', '<')) {
        $requirements['database_requirements'][] = [
          'type' => 'mysql_upgrade',
          'current' => $version,
          'required' => '5.7.8+',
          'priority' => 'high',
        ];
      }
    }

    // Estimate backup size
    if (isset($database_data['migration_requirements']['database_size_mb'])) {
      $requirements['backup_size_estimate'] = $database_data['migration_requirements']['database_size_mb'];
    }

    // Determine complexity
    $complexity_factors = count($requirements['system_requirements']) + count($requirements['database_requirements']);
    if ($complexity_factors > 2) {
      $requirements['migration_complexity'] = 'high';
      $requirements['estimated_downtime'] = 'high';
    } elseif ($complexity_factors > 0) {
      $requirements['migration_complexity'] = 'medium';
      $requirements['estimated_downtime'] = 'medium';
    } else {
      $requirements['migration_complexity'] = 'low';
      $requirements['estimated_downtime'] = 'low';
    }

    return $requirements;
  }

  /**
   * Generates upgrade recommendations.
   */
  protected function generateUpgradeRecommendations($system_data, $database_data) {
    return $this->generateMigrationRecommendations($system_data, $database_data);
  }

  /**
   * Generates migration recommendations.
   */
  protected function generateMigrationRecommendations($system_data, $database_data) {
    $recommendations = [];

    // System recommendations
    if (version_compare($system_data['php_version']['version'], '8.1', '<')) {
      $recommendations[] = [
        'category' => 'system',
        'priority' => 'high',
        'title' => 'Upgrade PHP Version',
        'description' => 'Current PHP version ' . $system_data['php_version']['version'] . ' should be upgraded to 8.1+ for optimal Drupal 10+ compatibility.',
        'effort' => 'medium',
      ];
    }

    // Database recommendations
    if ($database_data['database_info']['driver'] === 'mysql') {
      $version = $database_data['database_info']['version'];
      if (version_compare($version, '5.7.8', '<')) {
        $recommendations[] = [
          'category' => 'database',
          'priority' => 'high',
          'title' => 'Upgrade MySQL Version',
          'description' => 'MySQL version ' . $version . ' should be upgraded to 5.7.8+ or MySQL 8.0+ for better performance and compatibility.',
          'effort' => 'high',
        ];
      }
    }

    // Memory recommendations
    if ($system_data['memory_info']['usage_percentage'] > 80) {
      $recommendations[] = [
        'category' => 'system',
        'priority' => 'medium',
        'title' => 'Increase Memory Limit',
        'description' => 'Current memory usage is high (' . $system_data['memory_info']['usage_percentage'] . '%). Consider increasing PHP memory limit.',
        'effort' => 'low',
      ];
    }

    // Database size recommendations
    if ($database_data['migration_requirements']['database_size_mb'] > 1000) {
      $recommendations[] = [
        'category' => 'database',
        'priority' => 'medium',
        'title' => 'Plan for Large Database Migration',
        'description' => 'Database size is ' . $database_data['migration_requirements']['database_size_mb'] . 'MB. Plan for extended migration time and consider optimization.',
        'effort' => 'high',
      ];
    }

    return $recommendations;
  }

  /**
   * Estimates upgrade effort.
   */
  protected function estimateUpgradeEffort($module_data, $theme_data, $code_data) {
    $effort = [
      'total_hours' => 0,
      'breakdown' => [],
      'complexity' => 'low',
    ];

    // Base upgrade effort
    $effort['breakdown']['base_upgrade'] = 8; // 8 hours base
    $effort['total_hours'] += 8;

    // Module-related effort
    $incompatible_modules = count($module_data['compatibility_issues']);
    $deprecated_modules = count($module_data['deprecated_modules']);
    $custom_modules = count($module_data['custom_modules']);

    $module_effort = ($incompatible_modules * 4) + ($deprecated_modules * 2) + ($custom_modules * 6);
    $effort['breakdown']['modules'] = $module_effort;
    $effort['total_hours'] += $module_effort;

    // Theme-related effort
    $custom_themes = count($theme_data['custom_themes']);
    $theme_compatibility_issues = count($theme_data['compatibility_issues']);

    $theme_effort = ($custom_themes * 8) + ($theme_compatibility_issues * 4);
    $effort['breakdown']['themes'] = $theme_effort;
    $effort['total_hours'] += $theme_effort;

    // Code-related effort
    $deprecated_functions = 0;
    if (isset($code_data['deprecated_functions']) && is_array($code_data['deprecated_functions'])) {
      $deprecated_functions = count($code_data['deprecated_functions']);
    }

    $code_effort = $deprecated_functions * 0.5; // 30 minutes per deprecated function
    $effort['breakdown']['code_fixes'] = $code_effort;
    $effort['total_hours'] += $code_effort;

    // Testing effort (20% of total)
    $testing_effort = $effort['total_hours'] * 0.2;
    $effort['breakdown']['testing'] = $testing_effort;
    $effort['total_hours'] += $testing_effort;

    // Determine complexity
    if ($effort['total_hours'] > 100) {
      $effort['complexity'] = 'high';
    }
    elseif ($effort['total_hours'] > 40) {
      $effort['complexity'] = 'medium';
    }

    return $effort;
  }

  /**
   * Assesses upgrade risks.
   */
  protected function assessUpgradeRisks($module_data, $theme_data, $code_data) {
    $risks = [];

    // High-risk factors
    if (count($module_data['custom_modules']) > 5) {
      $risks[] = [
        'level' => 'high',
        'category' => 'modules',
        'description' => 'Large number of custom modules (' . count($module_data['custom_modules']) . ') increases upgrade complexity.',
        'mitigation' => 'Thoroughly test all custom modules and update deprecated code.',
      ];
    }

    $total_deprecated = 0;
    if (isset($code_data['deprecated_functions']) && is_array($code_data['deprecated_functions'])) {
      $total_deprecated = count($code_data['deprecated_functions']);
    }

    if ($total_deprecated > 0) {
      if ($total_deprecated > 20) {
        $risks[] = [
          'level' => 'high',
          'category' => 'code',
          'description' => 'High number of deprecated function usages (' . $total_deprecated . ') detected.',
          'mitigation' => 'Update all deprecated function calls before upgrading.',
        ];
      }
    }

    // Medium-risk factors
    if (count($module_data['contrib_modules']) > 50) {
      $risks[] = [
        'level' => 'medium',
        'category' => 'modules',
        'description' => 'Large number of contributed modules may have compatibility issues.',
        'mitigation' => 'Review each module for compatibility and available updates.',
      ];
    }

    if (count($theme_data['custom_themes']) > 2) {
      $risks[] = [
        'level' => 'medium',
        'category' => 'themes',
        'description' => 'Multiple custom themes require individual compatibility testing.',
        'mitigation' => 'Test each theme thoroughly and update template overrides.',
      ];
    }

    // Calculate overall risk level
    $high_risks = array_filter($risks, function($risk) { return $risk['level'] === 'high'; });
    $medium_risks = array_filter($risks, function($risk) { return $risk['level'] === 'medium'; });

    if (count($high_risks) > 2) {
      $overall_risk = 'high';
    }
    elseif (count($high_risks) > 0 || count($medium_risks) > 3) {
      $overall_risk = 'medium';
    }
    else {
      $overall_risk = 'low';
    }

    return [
      'overall_risk' => $overall_risk,
      'risk_factors' => $risks,
      'high_risk_count' => count($high_risks),
      'medium_risk_count' => count($medium_risks),
    ];
  }

  /**
   * Suggests upgrade timeline.
   */
  protected function suggestUpgradeTimeline() {
    return [
      'phases' => [
        [
          'phase' => 'Preparation',
          'duration' => '1-2 weeks',
          'tasks' => [
            'Backup current site',
            'Set up development environment',
            'Inventory all customizations',
            'Review module compatibility',
          ],
        ],
        [
          'phase' => 'Code Updates',
          'duration' => '2-4 weeks',
          'tasks' => [
            'Update deprecated function calls',
            'Fix custom module compatibility',
            'Update custom themes',
            'Test all functionality',
          ],
        ],
        [
          'phase' => 'Migration',
          'duration' => '1 week',
          'tasks' => [
            'Perform Drupal core upgrade',
            'Update contributed modules',
            'Run database updates',
            'Clear caches and test',
          ],
        ],
        [
          'phase' => 'Testing & Launch',
          'duration' => '1-2 weeks',
          'tasks' => [
            'Comprehensive testing',
            'Performance optimization',
            'Security review',
            'Go live and monitor',
          ],
        ],
      ],
      'total_duration' => '5-9 weeks',
      'critical_path' => 'Custom code updates and testing',
    ];
  }

  /**
   * Generates preparation checklist.
   */
  protected function generatePreparationChecklist() {
    return [
      'pre_upgrade' => [
        'Create full site backup',
        'Document current configuration',
        'Test backup restoration',
        'Set up development environment',
        'Inventory all customizations',
        'Check module update status',
        'Review security updates',
        'Plan downtime window',
      ],
      'during_upgrade' => [
        'Put site in maintenance mode',
        'Update Drupal core',
        'Update contributed modules',
        'Run database updates',
        'Clear all caches',
        'Test critical functionality',
        'Check error logs',
        'Verify file permissions',
      ],
      'post_upgrade' => [
        'Comprehensive functionality testing',
        'Performance testing',
        'Security review',
        'Update documentation',
        'Train content editors',
        'Monitor error logs',
        'Plan regular maintenance',
        'Schedule security updates',
      ],
    ];
  }

  /**
   * Generates upgrade summary.
   */
  protected function generateUpgradeSummary() {
    $module_data = $this->moduleAnalyzer->analyze();
    $theme_data = $this->themeAnalyzer->analyze();
    $code_data = $this->codeAnalyzer->analyze();

    $compatibility_issues = 0;
    if (isset($module_data['compatibility_issues'])) {
      $compatibility_issues += is_array($module_data['compatibility_issues']) ? count($module_data['compatibility_issues']) : 0;
    }
    if (isset($theme_data['compatibility_issues'])) {
      $compatibility_issues += is_array($theme_data['compatibility_issues']) ? count($theme_data['compatibility_issues']) : 0;
    }
    
    $deprecated_usage = 0;
    if (isset($code_data['deprecated_functions']) && is_array($code_data['deprecated_functions'])) {
      $deprecated_usage = count($code_data['deprecated_functions']);
    }

    $readiness_score = $this->calculateUpgradeReadinessScore($module_data, $theme_data, $code_data);

    return [
      'readiness_score' => $readiness_score,
      'readiness_level' => $this->getReadinessLevel($readiness_score),
      'compatibility_issues' => $compatibility_issues,
      'deprecated_usage' => $deprecated_usage,
      'custom_code_count' => count($code_data['custom_modules']) + count($code_data['custom_themes']),
      'estimated_effort_hours' => $this->estimateUpgradeEffort($module_data, $theme_data, $code_data)['total_hours'],
      'risk_level' => $this->assessUpgradeRisks($module_data, $theme_data, $code_data)['overall_risk'],
    ];
  }

  /**
   * Calculates upgrade readiness score.
   */
  protected function calculateUpgradeReadinessScore($module_data, $theme_data, $code_data) {
    $score = 100;

    // Deduct points for issues
    if (isset($module_data['compatibility_issues']) && is_array($module_data['compatibility_issues'])) {
      $score -= count($module_data['compatibility_issues']) * 5;
    }
    if (isset($module_data['deprecated_modules']) && is_array($module_data['deprecated_modules'])) {
      $score -= count($module_data['deprecated_modules']) * 3;
    }
    if (isset($theme_data['compatibility_issues']) && is_array($theme_data['compatibility_issues'])) {
      $score -= count($theme_data['compatibility_issues']) * 5;
    }

    if (isset($code_data['deprecated_functions']) && is_array($code_data['deprecated_functions'])) {
      $score -= count($code_data['deprecated_functions']) * 2;
    }

    if (isset($code_data['custom_modules']) && is_array($code_data['custom_modules'])) {
      $score -= count($code_data['custom_modules']) * 2;
    }
    if (isset($code_data['custom_themes']) && is_array($code_data['custom_themes'])) {
      $score -= count($code_data['custom_themes']) * 3;
    }

    return max(0, $score);
  }

  /**
   * Gets readiness level from score.
   */
  protected function getReadinessLevel($score) {
    if ($score >= 80) {
      return 'ready';
    }
    elseif ($score >= 60) {
      return 'mostly_ready';
    }
    elseif ($score >= 40) {
      return 'needs_work';
    }
    else {
      return 'not_ready';
    }
  }

  /**
   * Generates overall summary.
   */
  protected function generateSummary() {
    $dashboard_data = $this->generateDashboardSummary();

    return [
      'overall_health' => $this->calculateOverallHealth($dashboard_data),
      'critical_issues' => $this->identifyCriticalIssues($dashboard_data),
      'recommendations' => $this->generateTopRecommendations($dashboard_data),
      'next_steps' => $this->suggestNextSteps($dashboard_data),
    ];
  }

  /**
   * Calculates overall site health.
   */
  protected function calculateOverallHealth($dashboard_data) {
    $health_scores = [
      $this->getHealthScore($dashboard_data['system']['status']),
      $this->getHealthScore($dashboard_data['modules']['status']),
      $this->getHealthScore($dashboard_data['themes']['status']),
      $this->getHealthScore($dashboard_data['content']['status']),
      $this->getHealthScore($dashboard_data['security']['status']),
      $this->getHealthScore($dashboard_data['performance']['status']),
    ];

    $average_score = array_sum($health_scores) / count($health_scores);

    if ($average_score >= 80) {
      return 'excellent';
    }
    elseif ($average_score >= 60) {
      return 'good';
    }
    elseif ($average_score >= 40) {
      return 'fair';
    }
    else {
      return 'poor';
    }
  }

  /**
   * Gets numeric health score from status.
   */
  protected function getHealthScore($status) {
    switch ($status) {
      case 'excellent':
        return 100;
      case 'good':
        return 80;
      case 'fair':
        return 60;
      case 'poor':
        return 40;
      default:
        return 50;
    }
  }

  /**
   * Identifies critical issues.
   */
  protected function identifyCriticalIssues($dashboard_data) {
    $critical_issues = [];

    if ($dashboard_data['security']['security_updates'] > 0) {
      $critical_issues[] = [
        'category' => 'security',
        'message' => $dashboard_data['security']['security_updates'] . ' security updates available',
        'priority' => 'critical',
      ];
    }

    if ($dashboard_data['security']['vulnerabilities'] > 0) {
      $critical_issues[] = [
        'category' => 'security',
        'message' => $dashboard_data['security']['vulnerabilities'] . ' security vulnerabilities detected',
        'priority' => 'critical',
      ];
    }

    if ($dashboard_data['system']['memory_usage'] > 90) {
      $critical_issues[] = [
        'category' => 'system',
        'message' => 'Memory usage is critically high (' . $dashboard_data['system']['memory_usage'] . '%)',
        'priority' => 'high',
      ];
    }

    if ($dashboard_data['system']['disk_usage'] > 90) {
      $critical_issues[] = [
        'category' => 'system',
        'message' => 'Disk usage is critically high (' . $dashboard_data['system']['disk_usage'] . '%)',
        'priority' => 'high',
      ];
    }

    return $critical_issues;
  }

  /**
   * Generates top recommendations.
   */
  protected function generateTopRecommendations($dashboard_data) {
    $recommendations = [];

    // Security recommendations
    if ($dashboard_data['security']['security_updates'] > 0) {
      $recommendations[] = [
        'category' => 'security',
        'priority' => 'critical',
        'message' => 'Apply ' . $dashboard_data['security']['security_updates'] . ' security updates immediately',
      ];
    }

    // Performance recommendations
    if (!$dashboard_data['performance']['page_cache_enabled']) {
      $recommendations[] = [
        'category' => 'performance',
        'priority' => 'high',
        'message' => 'Enable page caching to improve site performance',
      ];
    }

    if (!$dashboard_data['performance']['css_aggregation'] || !$dashboard_data['performance']['js_aggregation']) {
      $recommendations[] = [
        'category' => 'performance',
        'priority' => 'medium',
        'message' => 'Enable CSS and JavaScript aggregation to reduce HTTP requests',
      ];
    }

    // Module recommendations
    if ($dashboard_data['modules']['deprecated_count'] > 0) {
      $recommendations[] = [
        'category' => 'modules',
        'priority' => 'medium',
        'message' => 'Review and replace ' . $dashboard_data['modules']['deprecated_count'] . ' deprecated modules',
      ];
    }

    return array_slice($recommendations, 0, 5); // Top 5 recommendations
  }

  /**
   * Suggests next steps.
   */
  protected function suggestNextSteps($dashboard_data) {
    $next_steps = [];

    // Immediate actions
    if ($dashboard_data['security']['security_updates'] > 0) {
      $next_steps[] = 'Apply security updates immediately';
    }

    if ($dashboard_data['system']['memory_usage'] > 80) {
      $next_steps[] = 'Investigate high memory usage and optimize';
    }

    // Short-term actions
    if (!$dashboard_data['performance']['page_cache_enabled']) {
      $next_steps[] = 'Configure and enable page caching';
    }

    if ($dashboard_data['modules']['deprecated_count'] > 0) {
      $next_steps[] = 'Plan migration away from deprecated modules';
    }

    // Long-term actions
    if ($dashboard_data['upgrade']['readiness_score'] < 80) {
      $next_steps[] = 'Prepare for Drupal upgrade by addressing compatibility issues';
    }

    $next_steps[] = 'Schedule regular site maintenance and monitoring';

    return $next_steps;
  }

  /**
   * Converts report to CSV format.
   */
  public function convertToCsv($report) {
    $csv_data = "Category,Metric,Value\n";

    // System information
    $csv_data .= "System,Drupal Version," . $report['system']['drupal_version']['version'] . "\n";
    $csv_data .= "System,PHP Version," . $report['system']['php_version']['version'] . "\n";
    $csv_data .= "System,Database," . $report['system']['database_info']['driver'] . " " . $report['system']['database_info']['version'] . "\n";
    $csv_data .= "System,Memory Usage," . $report['system']['memory_info']['usage_percentage'] . "%\n";

    // Module information
    $csv_data .= "Modules,Total Count," . $report['modules']['total_count'] . "\n";
    $csv_data .= "Modules,Enabled Count," . $report['modules']['enabled_count'] . "\n";
    $csv_data .= "Modules,Security Updates," . count($report['modules']['security_updates']) . "\n";
    $csv_data .= "Modules,Deprecated," . count($report['modules']['deprecated_modules']) . "\n";

    // Content information
    $csv_data .= "Content,Content Types," . count($report['content']['content_types']) . "\n";
    $csv_data .= "Content,Total Files," . $report['content']['media_usage']['total_files'] . "\n";

    // Security information
    $csv_data .= "Security,Security Updates," . count($report['security']['security_updates']) . "\n";
    $csv_data .= "Security,Vulnerabilities," . count($report['security']['vulnerabilities']) . "\n";

    return $csv_data;
  }

  /**
   * Helper methods for health status calculation.
   */
  protected function getSystemHealthStatus($system_data) {
    $issues = 0;

    if (version_compare($system_data['php_version']['version'], '8.1', '<')) {
      $issues++;
    }

    if ($system_data['memory_info']['usage_percentage'] > 80) {
      $issues++;
    }

    if ($system_data['disk_space']['usage_percentage'] > 90) {
      $issues++;
    }

    return $this->getHealthStatusFromIssues($issues, 3);
  }

  protected function getModuleHealthStatus($module_data) {
    $issues = 0;

    if (count($module_data['security_updates']) > 0) {
      $issues += 2; // Security updates are critical
    }

    if (count($module_data['deprecated_modules']) > 0) {
      $issues++;
    }

    if (count($module_data['compatibility_issues']) > 0) {
      $issues++;
    }

    return $this->getHealthStatusFromIssues($issues, 4);
  }

  protected function getThemeHealthStatus($theme_data) {
    $issues = count($theme_data['compatibility_issues']);
    return $this->getHealthStatusFromIssues($issues, 2);
  }

  protected function getContentHealthStatus($content_data) {
    $issues = count($content_data['deprecated_fields']);
    return $this->getHealthStatusFromIssues($issues, 2);
  }

  protected function getSecurityHealthStatus($security_data) {
    $issues = 0;

    if (count($security_data['security_updates']) > 0) {
      $issues += 3; // Critical
    }

    if (count($security_data['vulnerabilities']) > 0) {
      $issues += 2;
    }

    if (count($security_data['permission_audit']['risky_permissions']) > 5) {
      $issues++;
    }

    return $this->getHealthStatusFromIssues($issues, 6);
  }

  protected function getPerformanceHealthStatus($performance_data) {
    $issues = 0;

    if (!$performance_data['cache_analysis']['page_cache']['enabled']) {
      $issues++;
    }

    if (!$performance_data['asset_optimization']['css_aggregation']) {
      $issues++;
    }

    if (!$performance_data['asset_optimization']['js_aggregation']) {
      $issues++;
    }

    if ($performance_data['memory_usage']['usage_percentage'] > 80) {
      $issues++;
    }

    return $this->getHealthStatusFromIssues($issues, 4);
  }

  protected function getHealthStatusFromIssues($issues, $max_issues) {
    $percentage = 100 - (($issues / $max_issues) * 100);

    if ($percentage >= 80) {
      return 'excellent';
    }
    elseif ($percentage >= 60) {
      return 'good';
    }
    elseif ($percentage >= 40) {
      return 'fair';
    }
    else {
      return 'poor';
    }
  }

  protected function countFilePermissionIssues($file_permissions) {
    $count = 0;
    foreach ($file_permissions as $category => $issues) {
      if (is_array($issues)) {
        $count += count($issues);
      }
    }
    return $count;
  }

}