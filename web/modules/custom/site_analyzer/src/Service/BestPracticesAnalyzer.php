<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Service for analyzing Drupal best practices.
 */
class BestPracticesAnalyzer {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a BestPracticesAnalyzer object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * Analyzes Drupal best practices.
   */
  public function analyze() {
    return [
      'settings_file' => $this->analyzeSettingsFile(),
      'folder_structure' => $this->analyzeFolderStructure(),
      'multisite_setup' => $this->analyzeMultisiteSetup(),
      'services_configuration' => $this->analyzeServicesConfiguration(),
      'sites_configuration' => $this->analyzeSitesConfiguration(),
      'fast_404' => $this->analyzeFast404(),
      'development_modules' => $this->analyzeDevelopmentModules(),
      'file_permissions' => $this->analyzeFilePermissions(),
      'recommendations' => $this->generateBestPracticesRecommendations(),
    ];
  }

  /**
   * Analyzes settings.php file.
   */
  protected function analyzeSettingsFile() {
    $settings_file = DRUPAL_ROOT . '/sites/default/settings.php';
    $analysis = [
      'exists' => false,
      'is_writable' => false,
      'is_symlink' => false,
      'size' => 0,
      'contains_database_config' => false,
      'contains_hash_salt' => false,
      'contains_trusted_hosts' => false,
      'status' => 'fail',
      'message' => '',
      'recommendations' => [],
    ];

    if (file_exists($settings_file)) {
      $analysis['exists'] = true;
      $analysis['is_writable'] = is_writable($settings_file);
      $analysis['is_symlink'] = is_link($settings_file);
      $analysis['size'] = filesize($settings_file);

      if ($analysis['is_symlink']) {
        $analysis['status'] = 'warn';
        $analysis['message'] = 'settings.php is a symbolic link.';
        $analysis['recommendations'][] = 'Don\'t rely on symbolic links for core configuration files; copy settings.php where it should be and remove the symbolic link.';
      }
      else {
        $analysis['status'] = 'pass';
        $analysis['message'] = 'settings.php exists and is not a symbolic link.';
      }

      // Check file contents
      $content = file_get_contents($settings_file);
      $analysis['contains_database_config'] = strpos($content, '$databases') !== false;
      $analysis['contains_hash_salt'] = strpos($content, 'hash_salt') !== false;
      $analysis['contains_trusted_hosts'] = strpos($content, 'trusted_host_patterns') !== false;

      // Security recommendations
      if ($analysis['is_writable']) {
        $analysis['recommendations'][] = 'settings.php should not be writable by the web server for security reasons.';
      }

      if (!$analysis['contains_hash_salt']) {
        $analysis['recommendations'][] = 'Add a hash_salt configuration to settings.php for better security.';
      }

      if (!$analysis['contains_trusted_hosts']) {
        $analysis['recommendations'][] = 'Configure trusted_host_patterns in settings.php to prevent HTTP Host header attacks.';
      }
    }
    else {
      $analysis['status'] = 'fail';
      $analysis['message'] = 'settings.php file not found.';
      $analysis['recommendations'][] = 'Even if environment settings are injected, create a stub settings.php file for compatibility.';
    }

    return $analysis;
  }

  /**
   * Analyzes folder structure.
   */
  protected function analyzeFolderStructure() {
    $analysis = [
      'drupal_root_structure' => [],
      'sites_structure' => [],
      'custom_directories' => [],
      'recommendations' => [],
    ];

    // Check standard Drupal directories
    $standard_dirs = [
      'core' => 'Drupal core files',
      'modules' => 'Contributed and custom modules',
      'themes' => 'Contributed and custom themes',
      'sites' => 'Site-specific files and configurations',
      'vendor' => 'Composer dependencies',
    ];

    foreach ($standard_dirs as $dir => $description) {
      $path = DRUPAL_ROOT . '/' . $dir;
      $analysis['drupal_root_structure'][$dir] = [
        'exists' => is_dir($path),
        'description' => $description,
        'writable' => is_dir($path) ? is_writable($path) : false,
      ];
    }

    // Check sites directory structure
    $sites_dir = DRUPAL_ROOT . '/sites';
    if (is_dir($sites_dir)) {
      $sites = scandir($sites_dir);
      foreach ($sites as $site) {
        if ($site !== '.' && $site !== '..' && is_dir($sites_dir . '/' . $site)) {
          $analysis['sites_structure'][$site] = [
            'has_settings' => file_exists($sites_dir . '/' . $site . '/settings.php'),
            'has_files_dir' => is_dir($sites_dir . '/' . $site . '/files'),
            'files_writable' => is_dir($sites_dir . '/' . $site . '/files') ? is_writable($sites_dir . '/' . $site . '/files') : false,
          ];
        }
      }
    }

    // Check for custom directories that shouldn't be in root
    $root_files = scandir(DRUPAL_ROOT);
    $allowed_root_items = [
      '.', '..', '.htaccess', '.gitignore', 'autoload.php', 'composer.json', 'composer.lock',
      'core', 'index.php', 'modules', 'profiles', 'robots.txt', 'sites', 'themes', 'vendor',
      'web.config', 'README.txt', 'INSTALL.txt', 'CHANGELOG.txt', 'COPYRIGHT.txt', 'LICENSE.txt',
    ];

    foreach ($root_files as $item) {
      if (!in_array($item, $allowed_root_items)) {
        $analysis['custom_directories'][] = $item;
      }
    }

    // Generate recommendations
    if (!$analysis['drupal_root_structure']['modules']['exists']) {
      $analysis['recommendations'][] = 'Create a modules directory for contributed and custom modules.';
    }

    if (!$analysis['drupal_root_structure']['themes']['exists']) {
      $analysis['recommendations'][] = 'Create a themes directory for contributed and custom themes.';
    }

    if (!empty($analysis['custom_directories'])) {
      $analysis['recommendations'][] = 'Consider moving custom files/directories out of Drupal root: ' . implode(', ', $analysis['custom_directories']);
    }

    return $analysis;
  }

  /**
   * Analyzes multisite setup.
   */
  protected function analyzeMultisiteSetup() {
    $analysis = [
      'is_multisite' => false,
      'sites_count' => 0,
      'sites_list' => [],
      'shared_resources' => [],
      'recommendations' => [],
    ];

    $sites_dir = DRUPAL_ROOT . '/sites';
    if (is_dir($sites_dir)) {
      $sites = scandir($sites_dir);
      $site_directories = [];

      foreach ($sites as $site) {
        if ($site !== '.' && $site !== '..' && $site !== 'default' && is_dir($sites_dir . '/' . $site)) {
          $site_directories[] = $site;
        }
      }

      $analysis['sites_count'] = count($site_directories);
      $analysis['sites_list'] = $site_directories;
      $analysis['is_multisite'] = $analysis['sites_count'] > 0;

      if ($analysis['is_multisite']) {
        // Check for shared resources
        $shared_modules = is_dir(DRUPAL_ROOT . '/modules');
        $shared_themes = is_dir(DRUPAL_ROOT . '/themes');
        $shared_libraries = is_dir(DRUPAL_ROOT . '/libraries');

        $analysis['shared_resources'] = [
          'modules' => $shared_modules,
          'themes' => $shared_themes,
          'libraries' => $shared_libraries,
        ];

        // Multisite recommendations
        $analysis['recommendations'][] = 'Multisite setup detected. Ensure proper isolation between sites.';
        $analysis['recommendations'][] = 'Use shared modules and themes directories for better resource management.';
        $analysis['recommendations'][] = 'Consider using separate databases for each site for better security.';
      }
    }

    return $analysis;
  }

  /**
   * Analyzes services configuration.
   */
  protected function analyzeServicesConfiguration() {
    $analysis = [
      'services_yml_exists' => false,
      'development_services_exists' => false,
      'custom_services' => [],
      'recommendations' => [],
    ];

    // Check for services.yml
    $services_file = DRUPAL_ROOT . '/sites/default/services.yml';
    $analysis['services_yml_exists'] = file_exists($services_file);

    // Check for development services
    $dev_services_file = DRUPAL_ROOT . '/sites/development.services.yml';
    $analysis['development_services_exists'] = file_exists($dev_services_file);

    if ($analysis['services_yml_exists']) {
      // Parse services.yml for custom configurations
      try {
        $services_content = file_get_contents($services_file);
        
        // Check for common configurations
        $has_twig_debug = strpos($services_content, 'debug: true') !== false;
        $has_twig_cache = strpos($services_content, 'cache: false') !== false;
        $has_renderer_debug = strpos($services_content, 'debug: true') !== false;

        if ($has_twig_debug || $has_twig_cache || $has_renderer_debug) {
          $analysis['recommendations'][] = 'Development settings detected in services.yml. Ensure these are disabled in production.';
        }
      }
      catch (\Exception $e) {
        // Continue
      }
    }
    else {
      $analysis['recommendations'][] = 'Consider creating a services.yml file for custom service configurations.';
    }

    return $analysis;
  }

  /**
   * Analyzes sites configuration.
   */
  protected function analyzeSitesConfiguration() {
    $analysis = [
      'sites_php_exists' => false,
      'default_site_configured' => false,
      'custom_sites' => [],
      'recommendations' => [],
    ];

    // Check for sites.php
    $sites_file = DRUPAL_ROOT . '/sites/sites.php';
    $analysis['sites_php_exists'] = file_exists($sites_file);

    // Check default site configuration
    $default_settings = DRUPAL_ROOT . '/sites/default/settings.php';
    $analysis['default_site_configured'] = file_exists($default_settings);

    if ($analysis['sites_php_exists']) {
      try {
        $sites_content = file_get_contents($sites_file);
        // Parse for custom site configurations
        if (preg_match_all('/\$sites\[\'([^\']+)\'\]/', $sites_content, $matches)) {
          $analysis['custom_sites'] = $matches[1];
        }
      }
      catch (\Exception $e) {
        // Continue
      }
    }

    // Generate recommendations
    if (!$analysis['default_site_configured']) {
      $analysis['recommendations'][] = 'Configure the default site with proper settings.php file.';
    }

    if (!empty($analysis['custom_sites'])) {
      $analysis['recommendations'][] = 'Multiple sites configured. Ensure proper domain mapping and security isolation.';
    }

    return $analysis;
  }

  /**
   * Analyzes Fast 404 configuration.
   */
  protected function analyzeFast404() {
    $analysis = [
      'enabled' => false,
      'configured_extensions' => [],
      'configured_paths' => [],
      'recommendations' => [],
    ];

    // Check settings.php for Fast 404 configuration
    $settings_file = DRUPAL_ROOT . '/sites/default/settings.php';
    if (file_exists($settings_file)) {
      $content = file_get_contents($settings_file);
      
      $analysis['enabled'] = strpos($content, 'fast_404') !== false;
      
      if ($analysis['enabled']) {
        // Extract configured extensions
        if (preg_match('/fast_404_exts.*?[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
          $analysis['configured_extensions'] = explode('|', $matches[1]);
        }
        
        // Extract configured paths
        if (preg_match('/fast_404_paths.*?[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
          $analysis['configured_paths'] = explode('|', $matches[1]);
        }
      }
      else {
        $analysis['recommendations'][] = 'Consider enabling Fast 404 for better performance on missing files.';
      }
    }

    return $analysis;
  }

  /**
   * Analyzes development modules.
   */
  protected function analyzeDevelopmentModules() {
    $analysis = [
      'development_modules_enabled' => [],
      'testing_modules_enabled' => [],
      'debug_modules_enabled' => [],
      'recommendations' => [],
    ];

    $dev_modules = [
      'devel' => 'Development helper module',
      'devel_generate' => 'Content generation for development',
      'webprofiler' => 'Performance profiling',
      'stage_file_proxy' => 'File proxy for development',
      'field_ui' => 'Field UI (should be disabled in production)',
      'views_ui' => 'Views UI (should be disabled in production)',
    ];

    $testing_modules = [
      'simpletest' => 'Legacy testing framework',
      'phpunit' => 'PHPUnit testing',
    ];

    $debug_modules = [
      'dblog' => 'Database logging',
      'syslog' => 'System logging',
    ];

    foreach ($dev_modules as $module => $description) {
      if ($this->moduleHandler->moduleExists($module)) {
        $analysis['development_modules_enabled'][$module] = $description;
      }
    }

    foreach ($testing_modules as $module => $description) {
      if ($this->moduleHandler->moduleExists($module)) {
        $analysis['testing_modules_enabled'][$module] = $description;
      }
    }

    foreach ($debug_modules as $module => $description) {
      if ($this->moduleHandler->moduleExists($module)) {
        $analysis['debug_modules_enabled'][$module] = $description;
      }
    }

    // Generate recommendations
    if (!empty($analysis['development_modules_enabled'])) {
      $analysis['recommendations'][] = 'Development modules are enabled. Disable these in production: ' . implode(', ', array_keys($analysis['development_modules_enabled']));
    }

    if (empty($analysis['debug_modules_enabled'])) {
      $analysis['recommendations'][] = 'No logging modules enabled. Enable dblog or syslog for error tracking.';
    }

    return $analysis;
  }

  /**
   * Analyzes file permissions.
   */
  protected function analyzeFilePermissions() {
    $analysis = [
      'settings_permissions' => [],
      'files_permissions' => [],
      'directory_permissions' => [],
      'recommendations' => [],
    ];

    // Check settings.php permissions
    $settings_file = DRUPAL_ROOT . '/sites/default/settings.php';
    if (file_exists($settings_file)) {
      $perms = fileperms($settings_file);
      $octal_perms = substr(sprintf('%o', $perms), -4);
      
      $analysis['settings_permissions'] = [
        'file' => $settings_file,
        'permissions' => $octal_perms,
        'is_writable' => is_writable($settings_file),
        'is_secure' => !($perms & 0022), // Not writable by group or others
      ];

      if (!$analysis['settings_permissions']['is_secure']) {
        $analysis['recommendations'][] = "settings.php has insecure permissions ({$octal_perms}). Should be 644 or 444.";
      }
    }

    // Check files directory permissions
    $files_path = $this->fileSystem->realpath('public://');
    if ($files_path && is_dir($files_path)) {
      $perms = fileperms($files_path);
      $octal_perms = substr(sprintf('%o', $perms), -4);
      
      $analysis['files_permissions'] = [
        'directory' => $files_path,
        'permissions' => $octal_perms,
        'is_writable' => is_writable($files_path),
        'has_htaccess' => file_exists($files_path . '/.htaccess'),
      ];

      if (!$analysis['files_permissions']['is_writable']) {
        $analysis['recommendations'][] = "Files directory is not writable ({$octal_perms}). Should be 755 or 775.";
      }

      if (!$analysis['files_permissions']['has_htaccess']) {
        $analysis['recommendations'][] = 'Files directory missing .htaccess file for security.';
      }
    }

    // Check other important directories
    $important_dirs = [
      DRUPAL_ROOT . '/sites/default' => 'Sites default directory',
      DRUPAL_ROOT . '/modules' => 'Modules directory',
      DRUPAL_ROOT . '/themes' => 'Themes directory',
    ];

    foreach ($important_dirs as $dir => $description) {
      if (is_dir($dir)) {
        $perms = fileperms($dir);
        $octal_perms = substr(sprintf('%o', $perms), -4);
        
        $analysis['directory_permissions'][$dir] = [
          'description' => $description,
          'permissions' => $octal_perms,
          'is_writable' => is_writable($dir),
        ];
      }
    }

    return $analysis;
  }

  /**
   * Generates best practices recommendations.
   */
  protected function generateBestPracticesRecommendations() {
    $recommendations = [];

    // Collect recommendations from all analyses
    $analyses = [
      $this->analyzeSettingsFile(),
      $this->analyzeFolderStructure(),
      $this->analyzeMultisiteSetup(),
      $this->analyzeServicesConfiguration(),
      $this->analyzeSitesConfiguration(),
      $this->analyzeFast404(),
      $this->analyzeDevelopmentModules(),
      $this->analyzeFilePermissions(),
    ];

    foreach ($analyses as $analysis) {
      if (isset($analysis['recommendations'])) {
        foreach ($analysis['recommendations'] as $recommendation) {
          $recommendations[] = [
            'type' => 'info',
            'category' => 'best_practices',
            'message' => $recommendation,
            'priority' => 'medium',
          ];
        }
      }
    }

    // Add general best practices recommendations
    $general_recommendations = [
      'Keep Drupal core and contributed modules updated',
      'Use version control for your codebase',
      'Implement proper backup strategies',
      'Use environment-specific configuration',
      'Enable caching for better performance',
      'Regularly review and clean up unused modules',
      'Implement proper error handling and logging',
      'Use HTTPS for all production sites',
      'Regularly audit user permissions and roles',
      'Keep development and production environments separate',
    ];

    foreach ($general_recommendations as $recommendation) {
      $recommendations[] = [
        'type' => 'info',
        'category' => 'general_best_practices',
        'message' => $recommendation,
        'priority' => 'low',
      ];
    }

    return $recommendations;
  }

}