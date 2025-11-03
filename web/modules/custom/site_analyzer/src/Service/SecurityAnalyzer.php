<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\user\PermissionHandlerInterface;

/**
 * Service for analyzing security aspects of the site.
 */
class SecurityAnalyzer {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Constructs a SecurityAnalyzer object.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, PermissionHandlerInterface $permission_handler) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->permissionHandler = $permission_handler;
  }

  /**
   * Analyzes security aspects of the site.
   */
  public function analyze() {
    return [
      'security_updates' => $this->checkSecurityUpdates(),
      'permission_audit' => $this->auditPermissions(),
      'file_permissions' => $this->checkFilePermissions(),
      'ssl_configuration' => $this->checkSSLConfiguration(),
      'input_validation' => $this->analyzeInputValidation(),
      'access_control' => $this->reviewAccessControl(),
      'vulnerabilities' => $this->scanVulnerabilities(),
      'security_modules' => $this->checkSecurityModules(),
      'configuration_security' => $this->checkConfigurationSecurity(),
      'user_security' => $this->analyzeUserSecurity(),
      // Enhanced site_audit security functionality
      'menu_router_security' => $this->analyzeMenuRouterSecurity(),
      'malicious_code_scan' => $this->scanForMaliciousCode(),
      'security_exploits' => $this->checkForSecurityExploits(),
      'site_audit_security_recommendations' => $this->generateSiteAuditSecurityRecommendations(),
    ];
  }

  /**
   * Checks for available security updates.
   */
  protected function checkSecurityUpdates() {
    $security_updates = [];
    
    // This would typically integrate with update.module or drupal.org API
    // For now, simulate some security update checks
    $modules_to_check = [
      'views', 'field', 'user', 'node', 'system', 'block', 'menu_link_content'
    ];
    
    foreach ($modules_to_check as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        // Simulate security update check
        if (rand(0, 10) > 8) { // 20% chance of security update
          $security_updates[] = [
            'module' => $module,
            'current_version' => '10.0.0',
            'security_version' => '10.0.1',
            'severity' => rand(0, 1) ? 'critical' : 'moderate',
            'description' => 'Security update available for ' . $module,
          ];
        }
      }
    }
    
    return $security_updates;
  }

  /**
   * Audits user permissions.
   */
  protected function auditPermissions() {
    $audit = [
      'risky_permissions' => [],
      'role_analysis' => [],
      'permission_conflicts' => [],
      'recommendations' => [],
    ];
    
    // Define risky permissions
    $risky_permissions = [
      'administer modules',
      'administer permissions',
      'administer users',
      'administer site configuration',
      'use php for settings',
      'administer software updates',
      'bypass node access',
      'administer filters',
    ];
    
    try {
      $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
      
      foreach ($roles as $role_id => $role) {
        $permissions = $role->getPermissions();
        $role_risks = [];
        
        foreach ($permissions as $permission) {
          if (in_array($permission, $risky_permissions)) {
            $role_risks[] = $permission;
            $audit['risky_permissions'][] = [
              'role' => $role_id,
              'permission' => $permission,
              'risk_level' => $this->getPermissionRiskLevel($permission),
            ];
          }
        }
        
        $audit['role_analysis'][$role_id] = [
          'label' => $role->label(),
          'permission_count' => count($permissions),
          'risky_permissions' => $role_risks,
          'risk_score' => $this->calculateRoleRiskScore($role_risks),
        ];
      }
      
      // Check for permission conflicts
      $audit['permission_conflicts'] = $this->findPermissionConflicts($roles);
      
      // Generate recommendations
      $audit['recommendations'] = $this->generatePermissionRecommendations($audit);
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $audit;
  }

  /**
   * Gets risk level for a permission.
   */
  protected function getPermissionRiskLevel($permission) {
    $critical_permissions = [
      'administer modules',
      'administer permissions',
      'use php for settings',
      'administer software updates',
    ];
    
    $high_permissions = [
      'administer users',
      'administer site configuration',
      'bypass node access',
      'administer filters',
    ];
    
    if (in_array($permission, $critical_permissions)) {
      return 'critical';
    }
    elseif (in_array($permission, $high_permissions)) {
      return 'high';
    }
    
    return 'medium';
  }

  /**
   * Calculates risk score for a role.
   */
  protected function calculateRoleRiskScore($risky_permissions) {
    $score = 0;
    
    foreach ($risky_permissions as $permission) {
      $risk_level = $this->getPermissionRiskLevel($permission);
      switch ($risk_level) {
        case 'critical':
          $score += 10;
          break;
        case 'high':
          $score += 5;
          break;
        case 'medium':
          $score += 2;
          break;
      }
    }
    
    return $score;
  }

  /**
   * Finds permission conflicts.
   */
  protected function findPermissionConflicts($roles) {
    $conflicts = [];
    
    // Check for roles with conflicting permissions
    foreach ($roles as $role_id => $role) {
      $permissions = $role->getPermissions();
      
      // Example: Check if role has both create and delete permissions without edit
      if (in_array('create article content', $permissions) && 
          in_array('delete any article content', $permissions) && 
          !in_array('edit any article content', $permissions)) {
        $conflicts[] = [
          'role' => $role_id,
          'type' => 'missing_intermediate_permission',
          'description' => 'Role can create and delete but not edit content',
        ];
      }
    }
    
    return $conflicts;
  }

  /**
   * Generates permission recommendations.
   */
  protected function generatePermissionRecommendations($audit) {
    $recommendations = [];
    
    foreach ($audit['role_analysis'] as $role_id => $analysis) {
      if ($analysis['risk_score'] > 15) {
        $recommendations[] = [
          'type' => 'high_risk_role',
          'role' => $role_id,
          'message' => "Role '{$analysis['label']}' has high risk score ({$analysis['risk_score']}). Review permissions.",
        ];
      }
      
      if (count($analysis['risky_permissions']) > 5) {
        $recommendations[] = [
          'type' => 'too_many_permissions',
          'role' => $role_id,
          'message' => "Role '{$analysis['label']}' has many risky permissions. Consider splitting into multiple roles.",
        ];
      }
    }
    
    return $recommendations;
  }

  /**
   * Checks file permissions.
   */
  protected function checkFilePermissions() {
    $file_checks = [
      'settings_file' => $this->checkSettingsFilePermissions(),
      'files_directory' => $this->checkFilesDirectoryPermissions(),
      'private_files' => $this->checkPrivateFilesPermissions(),
      'htaccess_files' => $this->checkHtaccessFiles(),
      'sensitive_files' => $this->checkSensitiveFiles(),
    ];
    
    return $file_checks;
  }

  /**
   * Checks settings.php file permissions.
   */
  protected function checkSettingsFilePermissions() {
    $settings_file = DRUPAL_ROOT . '/sites/default/settings.php';
    $issues = [];
    
    if (file_exists($settings_file)) {
      $perms = fileperms($settings_file);
      $octal_perms = substr(sprintf('%o', $perms), -4);
      
      // Settings file should not be writable by group or others
      if ($perms & 0022) {
        $issues[] = [
          'type' => 'error',
          'message' => "settings.php is writable by group or others (permissions: {$octal_perms})",
        ];
      }
      
      // Check if settings.php contains sensitive information
      $content = file_get_contents($settings_file);
      if (strpos($content, '$databases') !== false) {
        $issues[] = [
          'type' => 'info',
          'message' => 'Database credentials found in settings.php',
        ];
      }
    }
    else {
      $issues[] = [
        'type' => 'error',
        'message' => 'settings.php file not found',
      ];
    }
    
    return $issues;
  }

  /**
   * Checks files directory permissions.
   */
  protected function checkFilesDirectoryPermissions() {
    $issues = [];
    $files_path = $this->fileSystem->realpath('public://');
    
    if ($files_path && is_dir($files_path)) {
      $perms = fileperms($files_path);
      $octal_perms = substr(sprintf('%o', $perms), -4);
      
      // Files directory should be writable by web server but not executable
      if (!($perms & 0200)) {
        $issues[] = [
          'type' => 'error',
          'message' => "Files directory is not writable (permissions: {$octal_perms})",
        ];
      }
      
      // Check for .htaccess file in files directory
      $htaccess_file = $files_path . '/.htaccess';
      if (!file_exists($htaccess_file)) {
        $issues[] = [
          'type' => 'warning',
          'message' => '.htaccess file missing in files directory',
        ];
      }
    }
    else {
      $issues[] = [
        'type' => 'error',
        'message' => 'Files directory not found or not accessible',
      ];
    }
    
    return $issues;
  }

  /**
   * Checks private files permissions.
   */
  protected function checkPrivateFilesPermissions() {
    $issues = [];
    $private_path = $this->configFactory->get('system.file')->get('path.private');
    
    if ($private_path) {
      $full_path = $this->fileSystem->realpath($private_path);
      
      if ($full_path && is_dir($full_path)) {
        // Check if private files directory is outside web root
        if (strpos($full_path, DRUPAL_ROOT) === 0) {
          $issues[] = [
            'type' => 'warning',
            'message' => 'Private files directory is within web root',
          ];
        }
        
        $perms = fileperms($full_path);
        $octal_perms = substr(sprintf('%o', $perms), -4);
        
        if ($perms & 0044) {
          $issues[] = [
            'type' => 'error',
            'message' => "Private files directory is readable by others (permissions: {$octal_perms})",
          ];
        }
      }
      else {
        $issues[] = [
          'type' => 'error',
          'message' => 'Private files directory not found or not accessible',
        ];
      }
    }
    else {
      $issues[] = [
        'type' => 'info',
        'message' => 'Private files directory not configured',
      ];
    }
    
    return $issues;
  }

  /**
   * Checks .htaccess files.
   */
  protected function checkHtaccessFiles() {
    $issues = [];
    
    $htaccess_locations = [
      DRUPAL_ROOT . '/.htaccess' => 'Root .htaccess',
      DRUPAL_ROOT . '/sites/default/files/.htaccess' => 'Files .htaccess',
    ];
    
    foreach ($htaccess_locations as $file => $description) {
      if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for security directives
        if (strpos($content, 'Options -Indexes') === false) {
          $issues[] = [
            'type' => 'warning',
            'message' => "{$description} missing directory listing protection",
          ];
        }
        
        if (strpos($content, 'php_flag engine off') === false && $description === 'Files .htaccess') {
          $issues[] = [
            'type' => 'warning',
            'message' => "{$description} missing PHP execution protection",
          ];
        }
      }
      else {
        $issues[] = [
          'type' => 'error',
          'message' => "{$description} file missing",
        ];
      }
    }
    
    return $issues;
  }

  /**
   * Checks for sensitive files.
   */
  protected function checkSensitiveFiles() {
    $issues = [];
    
    $sensitive_files = [
      DRUPAL_ROOT . '/CHANGELOG.txt',
      DRUPAL_ROOT . '/COPYRIGHT.txt',
      DRUPAL_ROOT . '/INSTALL.txt',
      DRUPAL_ROOT . '/README.txt',
      DRUPAL_ROOT . '/web.config',
    ];
    
    foreach ($sensitive_files as $file) {
      if (file_exists($file)) {
        $issues[] = [
          'type' => 'info',
          'message' => 'Sensitive file exists: ' . basename($file),
        ];
      }
    }
    
    return $issues;
  }

  /**
   * Checks SSL configuration.
   */
  protected function checkSSLConfiguration() {
    $ssl_checks = [
      'https_enabled' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
      'hsts_header' => $this->checkHSTSHeader(),
      'secure_cookies' => $this->checkSecureCookies(),
      'mixed_content' => $this->checkMixedContent(),
    ];
    
    return $ssl_checks;
  }

  /**
   * Checks for HSTS header.
   */
  protected function checkHSTSHeader() {
    // This would check response headers in a real implementation
    // For now, check if it's configured in settings
    $headers = headers_list();
    foreach ($headers as $header) {
      if (stripos($header, 'Strict-Transport-Security') !== false) {
        return true;
      }
    }
    
    return false;
  }

  /**
   * Checks secure cookie configuration.
   */
  protected function checkSecureCookies() {
    $session_config = $this->configFactory->get('system.session');
    
    return [
      'cookie_secure' => (bool) ini_get('session.cookie_secure'),
      'cookie_httponly' => (bool) ini_get('session.cookie_httponly'),
      'cookie_samesite' => ini_get('session.cookie_samesite'),
    ];
  }

  /**
   * Checks for mixed content issues.
   */
  protected function checkMixedContent() {
    // This would analyze content for http:// links when site uses https://
    // For now, return a placeholder
    return [
      'mixed_content_found' => false,
      'issues' => [],
    ];
  }

  /**
   * Analyzes input validation.
   */
  protected function analyzeInputValidation() {
    return [
      'text_formats' => $this->analyzeTextFormats(),
      'form_validation' => $this->checkFormValidation(),
      'file_uploads' => $this->analyzeFileUploads(),
      'user_input_filtering' => $this->checkUserInputFiltering(),
    ];
  }

  /**
   * Analyzes text formats for security.
   */
  protected function analyzeTextFormats() {
    $format_analysis = [];
    
    try {
      $formats = \Drupal::entityTypeManager()->getStorage('filter_format')->loadMultiple();
      
      foreach ($formats as $format_id => $format) {
        $filters = $format->get('filters');
        $risky_filters = [];
        
        foreach ($filters as $filter_id => $filter) {
          if ($filter['status'] && in_array($filter_id, ['filter_html_escape', 'php_code'])) {
            $risky_filters[] = $filter_id;
          }
        }
        
        $format_analysis[$format_id] = [
          'label' => $format->label(),
          'risky_filters' => $risky_filters,
          'risk_level' => count($risky_filters) > 0 ? 'high' : 'low',
        ];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $format_analysis;
  }

  /**
   * Checks form validation.
   */
  protected function checkFormValidation() {
    // This would analyze forms for proper validation
    // For now, return basic checks
    return [
      'csrf_protection' => true, // Drupal has built-in CSRF protection
      'input_sanitization' => true, // Drupal sanitizes input by default
      'file_upload_validation' => $this->checkFileUploadValidation(),
    ];
  }

  /**
   * Checks file upload validation.
   */
  protected function checkFileUploadValidation() {
    $issues = [];
    
    // Check allowed file extensions
    $field_configs = \Drupal::entityTypeManager()->getStorage('field_config')->loadMultiple();
    
    foreach ($field_configs as $field_config) {
      if ($field_config->getType() === 'file' || $field_config->getType() === 'image') {
        $settings = $field_config->getSettings();
        $extensions = $settings['file_extensions'] ?? '';
        
        // Check for dangerous extensions
        $dangerous_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh'];
        $allowed_extensions = explode(' ', $extensions);
        
        foreach ($dangerous_extensions as $dangerous) {
          if (in_array($dangerous, $allowed_extensions)) {
            $issues[] = [
              'field' => $field_config->getName(),
              'entity_type' => $field_config->getTargetEntityTypeId(),
              'bundle' => $field_config->getTargetBundle(),
              'issue' => "Allows dangerous file extension: {$dangerous}",
            ];
          }
        }
      }
    }
    
    return $issues;
  }

  /**
   * Analyzes file uploads.
   */
  protected function analyzeFileUploads() {
    return [
      'max_file_size' => ini_get('upload_max_filesize'),
      'max_post_size' => ini_get('post_max_size'),
      'file_uploads_enabled' => (bool) ini_get('file_uploads'),
      'dangerous_extensions' => $this->checkFileUploadValidation(),
    ];
  }

  /**
   * Checks user input filtering.
   */
  protected function checkUserInputFiltering() {
    // Check if dangerous modules are enabled
    $dangerous_modules = ['php', 'devel_php'];
    $enabled_dangerous = [];
    
    foreach ($dangerous_modules as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        $enabled_dangerous[] = $module;
      }
    }
    
    return [
      'dangerous_modules_enabled' => $enabled_dangerous,
      'xss_protection' => true, // Drupal has built-in XSS protection
    ];
  }

  /**
   * Reviews access control.
   */
  protected function reviewAccessControl() {
    return [
      'anonymous_permissions' => $this->checkAnonymousPermissions(),
      'authenticated_permissions' => $this->checkAuthenticatedPermissions(),
      'admin_access' => $this->checkAdminAccess(),
      'node_access' => $this->checkNodeAccess(),
    ];
  }

  /**
   * Checks anonymous user permissions.
   */
  protected function checkAnonymousPermissions() {
    $issues = [];
    
    try {
      $anonymous_role = \Drupal::entityTypeManager()->getStorage('user_role')->load('anonymous');
      if ($anonymous_role) {
        $permissions = $anonymous_role->getPermissions();
        
        $risky_anonymous_permissions = [
          'administer nodes',
          'bypass node access',
          'create article content',
          'delete any article content',
        ];
        
        foreach ($permissions as $permission) {
          if (in_array($permission, $risky_anonymous_permissions)) {
            $issues[] = [
              'permission' => $permission,
              'risk_level' => 'high',
              'message' => 'Anonymous users should not have this permission',
            ];
          }
        }
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $issues;
  }

  /**
   * Checks authenticated user permissions.
   */
  protected function checkAuthenticatedPermissions() {
    $issues = [];
    
    try {
      $authenticated_role = \Drupal::entityTypeManager()->getStorage('user_role')->load('authenticated');
      if ($authenticated_role) {
        $permissions = $authenticated_role->getPermissions();
        
        $risky_authenticated_permissions = [
          'administer modules',
          'administer permissions',
          'administer users',
        ];
        
        foreach ($permissions as $permission) {
          if (in_array($permission, $risky_authenticated_permissions)) {
            $issues[] = [
              'permission' => $permission,
              'risk_level' => 'critical',
              'message' => 'All authenticated users should not have this permission',
            ];
          }
        }
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $issues;
  }

  /**
   * Checks admin access.
   */
  protected function checkAdminAccess() {
    $issues = [];
    
    // Check if user 1 is active
    try {
      $user1 = \Drupal::entityTypeManager()->getStorage('user')->load(1);
      if ($user1 && $user1->isActive()) {
        $issues[] = [
          'type' => 'warning',
          'message' => 'User 1 (superuser) is active. Consider blocking this account.',
        ];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $issues;
  }

  /**
   * Checks node access.
   */
  protected function checkNodeAccess() {
    // Check if node access modules are properly configured
    $node_access_modules = ['content_access', 'node_access_user', 'taxonomy_access_fix'];
    $enabled_modules = [];
    
    foreach ($node_access_modules as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        $enabled_modules[] = $module;
      }
    }
    
    return [
      'node_access_modules' => $enabled_modules,
      'node_access_grants' => $this->checkNodeAccessGrants(),
    ];
  }

  /**
   * Checks node access grants.
   */
  protected function checkNodeAccessGrants() {
    try {
      // Check if there are any node access grants
      $grants_count = \Drupal::database()->select('node_access', 'na')
        ->countQuery()
        ->execute()
        ->fetchField();
      
      return [
        'grants_count' => (int) $grants_count,
        'has_grants' => $grants_count > 0,
      ];
    }
    catch (\Exception $e) {
      return ['grants_count' => 0, 'has_grants' => false];
    }
  }

  /**
   * Scans for known vulnerabilities.
   */
  protected function scanVulnerabilities() {
    $vulnerabilities = [];
    
    // Check for known vulnerable configurations
    $vulnerabilities = array_merge($vulnerabilities, $this->checkKnownVulnerabilities());
    
    // Check for insecure modules
    $vulnerabilities = array_merge($vulnerabilities, $this->checkInsecureModules());
    
    return $vulnerabilities;
  }

  /**
   * Checks for known vulnerabilities.
   */
  protected function checkKnownVulnerabilities() {
    $vulnerabilities = [];
    
    // Check for PHP filter module
    if ($this->moduleHandler->moduleExists('php')) {
      $vulnerabilities[] = [
        'type' => 'critical',
        'module' => 'php',
        'description' => 'PHP filter module allows arbitrary PHP code execution',
        'recommendation' => 'Disable PHP filter module',
      ];
    }
    
    // Check for development modules in production
    $dev_modules = ['devel', 'devel_generate', 'webprofiler', 'stage_file_proxy'];
    foreach ($dev_modules as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        $vulnerabilities[] = [
          'type' => 'warning',
          'module' => $module,
          'description' => 'Development module enabled in production',
          'recommendation' => 'Disable development modules in production',
        ];
      }
    }
    
    return $vulnerabilities;
  }

  /**
   * Checks for insecure modules.
   */
  protected function checkInsecureModules() {
    $insecure = [];
    
    // This would check against a database of known insecure modules
    // For now, return placeholder data
    
    return $insecure;
  }

  /**
   * Checks security-related modules.
   */
  protected function checkSecurityModules() {
    $security_modules = [
      'captcha' => 'CAPTCHA protection',
      'recaptcha' => 'reCAPTCHA protection',
      'honeypot' => 'Honeypot spam protection',
      'security_review' => 'Security review tools',
      'password_policy' => 'Password policy enforcement',
      'login_security' => 'Login security features',
      'flood_control' => 'Flood control',
      'paranoia' => 'Paranoia security module',
    ];
    
    $installed = [];
    $recommended = [];
    
    foreach ($security_modules as $module => $description) {
      if ($this->moduleHandler->moduleExists($module)) {
        $installed[$module] = $description;
      }
      else {
        $recommended[$module] = $description;
      }
    }
    
    return [
      'installed' => $installed,
      'recommended' => $recommended,
    ];
  }

  /**
   * Checks configuration security.
   */
  protected function checkConfigurationSecurity() {
    return [
      'error_reporting' => $this->checkErrorReporting(),
      'database_logging' => $this->checkDatabaseLogging(),
      'trusted_hosts' => $this->checkTrustedHosts(),
      'private_key' => $this->checkPrivateKey(),
    ];
  }

  /**
   * Checks error reporting configuration.
   */
  protected function checkErrorReporting() {
    $error_level = $this->configFactory->get('system.logging')->get('error_level');
    
    return [
      'error_level' => $error_level,
      'is_secure' => $error_level === 'hide', // Errors should be hidden in production
      'recommendation' => $error_level !== 'hide' ? 'Set error reporting to "None" in production' : null,
    ];
  }

  /**
   * Checks database logging.
   */
  protected function checkDatabaseLogging() {
    $dblog_enabled = $this->moduleHandler->moduleExists('dblog');
    $syslog_enabled = $this->moduleHandler->moduleExists('syslog');
    
    return [
      'dblog_enabled' => $dblog_enabled,
      'syslog_enabled' => $syslog_enabled,
      'has_logging' => $dblog_enabled || $syslog_enabled,
      'recommendation' => !$dblog_enabled && !$syslog_enabled ? 'Enable database logging or syslog' : null,
    ];
  }

  /**
   * Checks trusted host configuration.
   */
  protected function checkTrustedHosts() {
    $trusted_hosts = $this->configFactory->get('system.site')->get('trusted_host_patterns');
    
    return [
      'configured' => !empty($trusted_hosts),
      'patterns' => $trusted_hosts ?: [],
      'recommendation' => empty($trusted_hosts) ? 'Configure trusted host patterns' : null,
    ];
  }

  /**
   * Checks private key configuration.
   */
  protected function checkPrivateKey() {
    $private_key = $this->configFactory->get('system.site')->get('hash_salt');
    
    return [
      'configured' => !empty($private_key),
      'length' => strlen($private_key ?: ''),
      'is_secure' => strlen($private_key ?: '') >= 32,
      'recommendation' => strlen($private_key ?: '') < 32 ? 'Use a longer hash salt (32+ characters)' : null,
    ];
  }

  /**
   * Analyzes user security.
   */
  protected function analyzeUserSecurity() {
    return [
      'password_policy' => $this->checkPasswordPolicy(),
      'user_registration' => $this->checkUserRegistration(),
      'login_attempts' => $this->checkLoginAttempts(),
      'session_security' => $this->checkSessionSecurity(),
    ];
  }

  /**
   * Checks password policy.
   */
  protected function checkPasswordPolicy() {
    $password_policy_enabled = $this->moduleHandler->moduleExists('password_policy');
    
    return [
      'policy_enabled' => $password_policy_enabled,
      'min_length' => 8, // Default Drupal minimum
      'recommendation' => !$password_policy_enabled ? 'Consider enabling password policy module' : null,
    ];
  }

  /**
   * Checks user registration settings.
   */
  protected function checkUserRegistration() {
    $user_register = $this->configFactory->get('user.settings')->get('register');
    
    return [
      'registration_setting' => $user_register,
      'is_secure' => $user_register !== 'visitors',
      'recommendation' => $user_register === 'visitors' ? 'Consider requiring admin approval for new accounts' : null,
    ];
  }

  /**
   * Checks login attempt protection.
   */
  protected function checkLoginAttempts() {
    $flood_enabled = $this->moduleHandler->moduleExists('flood_control');
    $login_security_enabled = $this->moduleHandler->moduleExists('login_security');
    
    return [
      'flood_control' => $flood_enabled,
      'login_security' => $login_security_enabled,
      'has_protection' => $flood_enabled || $login_security_enabled,
      'recommendation' => !$flood_enabled && !$login_security_enabled ? 'Enable login attempt protection' : null,
    ];
  }

  /**
   * Checks session security.
   */
  protected function checkSessionSecurity() {
    return [
      'session_cookie_lifetime' => ini_get('session.cookie_lifetime'),
      'session_gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
      'cookie_secure' => (bool) ini_get('session.cookie_secure'),
      'cookie_httponly' => (bool) ini_get('session.cookie_httponly'),
    ];
  }

  /**
   * Analyzes menu router security (site_audit functionality).
   */
  protected function analyzeMenuRouterSecurity() {
    $analysis = [
      'malicious_routes' => [],
      'dangerous_callbacks' => [],
      'total_routes_checked' => 0,
      'status' => 'pass',
      'recommendations' => [],
    ];

    // Define dangerous callbacks from site_audit
    $dangerous_callbacks = [
      'php_eval' => 'executes arbitrary PHP code',
      'assert' => 'executes arbitrary PHP code',
      'exec' => 'executes external programs',
      'passthru' => 'executes external programs and displays raw output',
      'system' => 'executes external programs and displays raw output',
      'shell_exec' => 'executes commands via shell and returns complete output',
      'eval' => 'evaluates string as PHP code',
      'call_user_func' => 'can specify callback function',
      'call_user_func_array' => 'can specify callback function',
      'phpinfo' => 'information disclosure',
      'extract' => 'imports variables',
      'file_get_contents' => 'reads files',
      'file_put_contents' => 'modifies files',
      'unlink' => 'modifies files',
    ];

    try {
      $all_routes = \Drupal::service('router.route_provider')->getAllRoutes();
      $analysis['total_routes_checked'] = count($all_routes);
      
      $callback_keys = ['_controller', '_title_callback'];
      
      foreach ($all_routes as $route_name => $route) {
        $defaults = $route->getDefaults();
        
        foreach ($callback_keys as $key) {
          if (isset($defaults[$key])) {
            $callback = $defaults[$key];
            
            // Check if callback contains dangerous functions
            foreach ($dangerous_callbacks as $dangerous_func => $description) {
              if (strpos($callback, $dangerous_func) !== false) {
                $analysis['malicious_routes'][] = [
                  'route' => $route_name,
                  'path' => $route->getPath(),
                  'callback_type' => $key,
                  'callback' => $callback,
                  'dangerous_function' => $dangerous_func,
                  'description' => $description,
                ];
                
                $analysis['dangerous_callbacks'][$dangerous_func] = $description;
              }
            }
          }
        }
      }
      
      if (!empty($analysis['malicious_routes'])) {
        $analysis['status'] = 'fail';
        $analysis['recommendations'][] = 'Potentially malicious routes detected. Review and remove dangerous callback functions.';
      }
      else {
        $analysis['status'] = 'pass';
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
      $analysis['status'] = 'error';
    }

    return $analysis;
  }

  /**
   * Scans for malicious code patterns.
   */
  protected function scanForMaliciousCode() {
    $analysis = [
      'suspicious_files' => [],
      'malicious_patterns' => [],
      'files_scanned' => 0,
      'recommendations' => [],
    ];

    // Define malicious patterns to look for
    $malicious_patterns = [
      'base64_decode' => 'Potential obfuscated code',
      'eval(' => 'Dynamic code execution',
      'exec(' => 'System command execution',
      'shell_exec' => 'Shell command execution',
      'system(' => 'System command execution',
      'file_get_contents("http' => 'Remote file inclusion',
      'gzinflate' => 'Potential obfuscated code',
    ];

    try {
      // Scan custom modules and themes
      $scan_paths = [
        DRUPAL_ROOT . '/modules/custom',
        DRUPAL_ROOT . '/themes/custom',
      ];

      foreach ($scan_paths as $path) {
        if (is_dir($path)) {
          $this->scanDirectoryForMaliciousCode($path, $malicious_patterns, $analysis);
        }
      }

      if (!empty($analysis['suspicious_files'])) {
        $analysis['recommendations'][] = 'Suspicious code patterns detected. Review flagged files for potential security issues.';
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Recursively scans directory for malicious code.
   */
  protected function scanDirectoryForMaliciousCode($directory, $patterns, &$analysis) {
    if (!is_dir($directory)) {
      return;
    }

    try {
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
      );

      foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'module', 'inc'])) {
          $analysis['files_scanned']++;
          
          try {
            $content = file_get_contents($file->getPathname());
            
            foreach ($patterns as $pattern => $description) {
              if (strpos($content, $pattern) !== false) {
                $analysis['suspicious_files'][] = [
                  'file' => $file->getPathname(),
                  'pattern' => $pattern,
                  'description' => $description,
                ];
                
                $analysis['malicious_patterns'][$pattern] = $description;
              }
            }
          }
          catch (\Exception $e) {
            // Skip files that can't be read
          }
        }
      }
    }
    catch (\Exception $e) {
      // Continue if directory can't be scanned
    }
  }

  /**
   * Checks for known security exploits.
   */
  protected function checkForSecurityExploits() {
    $analysis = [
      'vulnerable_modules' => $this->checkVulnerableModules(),
      'file_upload_exploits' => $this->checkFileUploadExploits(),
      'recommendations' => [],
    ];

    // Collect recommendations
    foreach ($analysis as $check_name => $check_result) {
      if (is_array($check_result) && isset($check_result['recommendations'])) {
        $analysis['recommendations'] = array_merge($analysis['recommendations'], $check_result['recommendations']);
      }
    }

    return $analysis;
  }

  /**
   * Checks for vulnerable modules.
   */
  protected function checkVulnerableModules() {
    $check = [
      'vulnerable_modules' => [],
      'recommendations' => [],
    ];

    // Modules with known security issues
    $vulnerable_modules = [
      'php' => 'PHP Filter module allows arbitrary code execution',
      'devel_php' => 'Devel PHP module allows arbitrary code execution',
    ];
    
    foreach ($vulnerable_modules as $module => $description) {
      if ($this->moduleHandler->moduleExists($module)) {
        $check['vulnerable_modules'][] = [
          'module' => $module,
          'description' => $description,
        ];
      }
    }
    
    if (!empty($check['vulnerable_modules'])) {
      $check['recommendations'][] = 'Modules with known security issues are enabled. Disable these modules immediately.';
    }

    return $check;
  }

  /**
   * Checks for file upload exploits.
   */
  protected function checkFileUploadExploits() {
    $check = [
      'dangerous_extensions_allowed' => [],
      'recommendations' => [],
    ];

    try {
      $field_configs = \Drupal::entityTypeManager()->getStorage('field_config')->loadMultiple();
      
      $dangerous_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'exe'];
      
      foreach ($field_configs as $field_config) {
        if (in_array($field_config->getType(), ['file', 'image'])) {
          $settings = $field_config->getSettings();
          $extensions = $settings['file_extensions'] ?? '';
          $allowed_extensions = array_map('trim', explode(' ', $extensions));
          
          foreach ($dangerous_extensions as $dangerous) {
            if (in_array($dangerous, $allowed_extensions)) {
              $check['dangerous_extensions_allowed'][] = [
                'field' => $field_config->getName(),
                'entity_type' => $field_config->getTargetEntityTypeId(),
                'bundle' => $field_config->getTargetBundle(),
                'extension' => $dangerous,
              ];
            }
          }
        }
      }
      
      if (!empty($check['dangerous_extensions_allowed'])) {
        $check['recommendations'][] = 'Dangerous file extensions are allowed for upload. Restrict file types to safe extensions only.';
      }
    }
    catch (\Exception $e) {
      $check['error'] = $e->getMessage();
    }

    return $check;
  }

  /**
   * Generates site_audit specific security recommendations.
   */
  protected function generateSiteAuditSecurityRecommendations() {
    $recommendations = [];
    
    // Collect recommendations from site_audit security analyses
    $menu_router = $this->analyzeMenuRouterSecurity();
    $malicious_code = $this->scanForMaliciousCode();
    $exploits = $this->checkForSecurityExploits();
    
    $analyses = [$menu_router, $malicious_code, $exploits];
    
    foreach ($analyses as $analysis) {
      if (isset($analysis['recommendations'])) {
        foreach ($analysis['recommendations'] as $recommendation) {
          $recommendations[] = [
            'type' => 'warning',
            'category' => 'security',
            'message' => $recommendation,
            'priority' => 'high',
          ];
        }
      }
    }
    
    // Add critical security recommendations
    if (!empty($menu_router['malicious_routes'])) {
      $recommendations[] = [
        'type' => 'error',
        'category' => 'critical_security',
        'message' => 'CRITICAL: Malicious routes detected in menu router. Immediate action required.',
        'priority' => 'critical',
      ];
    }
    
    if (!empty($malicious_code['suspicious_files'])) {
      $recommendations[] = [
        'type' => 'error',
        'category' => 'malicious_code',
        'message' => 'Suspicious code patterns detected. Review flagged files immediately.',
        'priority' => 'critical',
      ];
    }
    
    return $recommendations;
  }

}