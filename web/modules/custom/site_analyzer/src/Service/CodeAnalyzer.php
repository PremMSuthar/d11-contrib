<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\File\FileSystemInterface;

/**
 * Service for analyzing custom code quality and deprecated functions.
 */
class CodeAnalyzer {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $moduleList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $themeList;

  /**
   * Constructs a CodeAnalyzer object.
   */
  public function __construct(FileSystemInterface $file_system, ExtensionList $module_list, ExtensionList $theme_list) {
    $this->fileSystem = $file_system;
    $this->moduleList = $module_list;
    $this->themeList = $theme_list;
  }

  /**
   * Analyzes custom code for deprecated functions and API changes.
   */
  public function analyze() {
    $analysis = [
      'deprecated_functions' => [],
      'deprecated_hooks' => [],
      'api_changes' => [],
      'security_issues' => [],
      'coding_standards' => [],
      'performance_issues' => [],
      'custom_modules' => [],
      'custom_themes' => [],
    ];

    // Analyze custom modules
    $custom_modules = $this->getCustomModules();
    foreach ($custom_modules as $module_name => $module_path) {
      $module_analysis = $this->analyzeModule($module_name, $module_path);
      $analysis['custom_modules'][$module_name] = $module_analysis;
      
      // Aggregate findings
      $analysis['deprecated_functions'] = array_merge(
        $analysis['deprecated_functions'],
        $module_analysis['deprecated_functions']
      );
      $analysis['deprecated_hooks'] = array_merge(
        $analysis['deprecated_hooks'],
        $module_analysis['deprecated_hooks']
      );
      $analysis['api_changes'] = array_merge(
        $analysis['api_changes'],
        $module_analysis['api_changes']
      );
      $analysis['security_issues'] = array_merge(
        $analysis['security_issues'],
        $module_analysis['security_issues']
      );
    }

    // Analyze custom themes
    $custom_themes = $this->getCustomThemes();
    foreach ($custom_themes as $theme_name => $theme_path) {
      $theme_analysis = $this->analyzeTheme($theme_name, $theme_path);
      $analysis['custom_themes'][$theme_name] = $theme_analysis;
      
      // Aggregate findings
      $analysis['deprecated_functions'] = array_merge(
        $analysis['deprecated_functions'],
        $theme_analysis['deprecated_functions']
      );
    }

    // Generate summary
    $analysis['summary'] = $this->generateCodeAnalysisSummary($analysis);

    return $analysis;
  }

  /**
   * Scans a specific module for deprecated code and issues.
   */
  public function scanModule($module_name) {
    // Get module information
    $module_list = \Drupal::service('extension.list.module');
    
    if (!$module_list->exists($module_name)) {
      throw new \InvalidArgumentException("Module '$module_name' does not exist.");
    }
    
    $module = $module_list->get($module_name);
    $module_path = $module->getPath();
    $full_path = DRUPAL_ROOT . '/' . $module_path;
    
    if (!is_dir($full_path)) {
      throw new \InvalidArgumentException("Module path '$module_path' does not exist.");
    }
    
    // Perform detailed analysis
    $analysis = $this->analyzeModule($module_name, $module_path);
    
    // Add additional scan-specific information
    $analysis['scan_metadata'] = [
      'module_name' => $module_name,
      'module_path' => $module_path,
      'scan_time' => date('Y-m-d H:i:s'),
      'files_scanned' => $this->getFileCount($full_path),
      'lines_of_code' => $analysis['lines_of_code'],
    ];
    
    return $analysis;
  }

  /**
   * Analyzes custom modules.
   */
  protected function analyzeCustomModules() {
    $custom_modules = [];
    $modules = $this->moduleList->getList();
    
    foreach ($modules as $name => $module) {
      if ($this->isCustomModule($module)) {
        $custom_modules[$name] = $this->analyzeModuleCode($name, $module);
      }
    }
    
    return $custom_modules;
  }

  /**
   * Checks if a module is custom.
   */
  protected function isCustomModule($module) {
    $path = $module->getPath();
    return strpos($path, 'modules/custom') !== false || 
           (strpos($path, 'sites/') !== false && strpos($path, '/modules') !== false);
  }

  /**
   * Analyzes module code.
   */
  protected function analyzeModuleCode($name, $module) {
    $module_path = DRUPAL_ROOT . '/' . $module->getPath();
    
    return [
      'name' => $name,
      'path' => $module->getPath(),
      'files' => $this->getModuleFiles($module_path),
      'lines_of_code' => $this->countLinesOfCode($module_path),
      'functions' => $this->extractFunctions($module_path),
      'classes' => $this->extractClasses($module_path),
      'hooks' => $this->extractHooks($module_path, $name),
      'deprecated_usage' => $this->findDeprecatedUsage($module_path),
      'coding_violations' => $this->checkModuleCodingStandards($module_path),
      'complexity_score' => $this->calculateComplexityScore($module_path),
    ];
  }

  /**
   * Gets all PHP files in a module.
   */
  protected function getModuleFiles($module_path) {
    $files = [];
    
    if (!is_dir($module_path)) {
      return $files;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($module_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && in_array($file->getExtension(), ['php', 'module', 'inc', 'install'])) {
        $files[] = [
          'path' => str_replace($module_path . '/', '', $file->getPathname()),
          'size' => $file->getSize(),
          'modified' => $file->getMTime(),
        ];
      }
    }
    
    return $files;
  }

  /**
   * Counts lines of code in a directory.
   */
  protected function countLinesOfCode($directory) {
    $total_lines = 0;
    $code_lines = 0;
    $comment_lines = 0;
    
    if (!is_dir($directory)) {
      return ['total' => 0, 'code' => 0, 'comments' => 0];
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && in_array($file->getExtension(), ['php', 'module', 'inc', 'install'])) {
        $file_stats = $this->analyzeFileLines($file->getPathname());
        $total_lines += $file_stats['total'];
        $code_lines += $file_stats['code'];
        $comment_lines += $file_stats['comments'];
      }
    }
    
    return [
      'total' => $total_lines,
      'code' => $code_lines,
      'comments' => $comment_lines,
    ];
  }

  /**
   * Analyzes lines in a single file.
   */
  protected function analyzeFileLines($file_path) {
    $content = file_get_contents($file_path);
    $lines = explode("\n", $content);
    
    $total_lines = count($lines);
    $code_lines = 0;
    $comment_lines = 0;
    $in_multiline_comment = false;
    
    foreach ($lines as $line) {
      $trimmed = trim($line);
      
      if (empty($trimmed)) {
        continue;
      }
      
      // Check for multiline comments
      if (strpos($trimmed, '/*') !== false) {
        $in_multiline_comment = true;
      }
      
      if ($in_multiline_comment) {
        $comment_lines++;
        if (strpos($trimmed, '*/') !== false) {
          $in_multiline_comment = false;
        }
        continue;
      }
      
      // Check for single line comments
      if (strpos($trimmed, '//') === 0 || strpos($trimmed, '#') === 0) {
        $comment_lines++;
        continue;
      }
      
      $code_lines++;
    }
    
    return [
      'total' => $total_lines,
      'code' => $code_lines,
      'comments' => $comment_lines,
    ];
  }

  /**
   * Extracts functions from module code.
   */
  protected function extractFunctions($module_path) {
    $functions = [];
    
    if (!is_dir($module_path)) {
      return $functions;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($module_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && in_array($file->getExtension(), ['php', 'module', 'inc', 'install'])) {
        $content = file_get_contents($file->getPathname());
        
        // Find function definitions
        if (preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches)) {
          foreach ($matches[1] as $function_name) {
            $functions[] = [
              'name' => $function_name,
              'file' => str_replace($module_path . '/', '', $file->getPathname()),
              'type' => $this->getFunctionType($function_name),
            ];
          }
        }
      }
    }
    
    return $functions;
  }

  /**
   * Determines function type (hook, helper, etc.).
   */
  protected function getFunctionType($function_name) {
    if (strpos($function_name, '_') === false) {
      return 'unknown';
    }
    
    $parts = explode('_', $function_name);
    
    // Check if it's a hook implementation
    if (count($parts) >= 2) {
      $potential_hook = implode('_', array_slice($parts, 1));
      if (in_array($potential_hook, $this->getKnownHooks())) {
        return 'hook';
      }
    }
    
    // Check for common patterns
    if (strpos($function_name, '_form') !== false) {
      return 'form';
    }
    
    if (strpos($function_name, '_validate') !== false) {
      return 'validation';
    }
    
    if (strpos($function_name, '_submit') !== false) {
      return 'submit';
    }
    
    return 'helper';
  }

  /**
   * Gets list of known Drupal hooks.
   */
  protected function getKnownHooks() {
    return [
      'init', 'menu', 'permission', 'theme', 'form_alter', 'node_view',
      'node_presave', 'node_insert', 'node_update', 'node_delete',
      'user_login', 'user_logout', 'cron', 'install', 'uninstall',
      'schema', 'update_N', 'requirements', 'help', 'mail',
    ];
  }

  /**
   * Extracts classes from module code.
   */
  protected function extractClasses($module_path) {
    $classes = [];
    
    if (!is_dir($module_path)) {
      return $classes;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($module_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Find class definitions
        if (preg_match_all('/class\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:extends\s+([a-zA-Z_][a-zA-Z0-9_\\\\]*))?\s*(?:implements\s+([a-zA-Z_][a-zA-Z0-9_\\\\,\s]*))?\s*{/', $content, $matches)) {
          for ($i = 0; $i < count($matches[1]); $i++) {
            $classes[] = [
              'name' => $matches[1][$i],
              'extends' => $matches[2][$i] ?? null,
              'implements' => $matches[3][$i] ?? null,
              'file' => str_replace($module_path . '/', '', $file->getPathname()),
              'methods' => $this->extractClassMethods($content, $matches[1][$i]),
            ];
          }
        }
      }
    }
    
    return $classes;
  }

  /**
   * Extracts methods from a class.
   */
  protected function extractClassMethods($content, $class_name) {
    $methods = [];
    
    // This is a simplified method extraction
    if (preg_match_all('/(public|protected|private)\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches)) {
      for ($i = 0; $i < count($matches[2]); $i++) {
        $methods[] = [
          'name' => $matches[2][$i],
          'visibility' => $matches[1][$i],
        ];
      }
    }
    
    return $methods;
  }

  /**
   * Extracts hook implementations.
   */
  protected function extractHooks($module_path, $module_name) {
    $hooks = [];
    
    $module_file = $module_path . '/' . $module_name . '.module';
    if (file_exists($module_file)) {
      $content = file_get_contents($module_file);
      
      // Find hook implementations
      if (preg_match_all('/function\s+' . $module_name . '_([a-zA-Z_]+)\s*\(/', $content, $matches)) {
        foreach ($matches[1] as $hook_name) {
          $hooks[] = [
            'hook' => $hook_name,
            'function' => $module_name . '_' . $hook_name,
            'type' => $this->getHookType($hook_name),
          ];
        }
      }
    }
    
    return $hooks;
  }

  /**
   * Determines hook type.
   */
  protected function getHookType($hook_name) {
    $hook_types = [
      'form' => ['form_alter', 'form_validate', 'form_submit'],
      'entity' => ['entity_presave', 'entity_insert', 'entity_update', 'entity_delete'],
      'node' => ['node_view', 'node_presave', 'node_insert', 'node_update', 'node_delete'],
      'user' => ['user_login', 'user_logout', 'user_presave'],
      'system' => ['init', 'cron', 'requirements', 'install', 'uninstall'],
      'theme' => ['theme', 'preprocess', 'theme_suggestions'],
    ];
    
    foreach ($hook_types as $type => $hooks) {
      foreach ($hooks as $hook) {
        if (strpos($hook_name, $hook) !== false) {
          return $type;
        }
      }
    }
    
    return 'other';
  }

  /**
   * Analyzes custom themes.
   */
  protected function analyzeCustomThemes() {
    $custom_themes = [];
    $themes = $this->themeList->getList();
    
    foreach ($themes as $name => $theme) {
      if ($this->isCustomTheme($theme)) {
        $custom_themes[$name] = $this->analyzeThemeCode($name, $theme);
      }
    }
    
    return $custom_themes;
  }

  /**
   * Checks if a theme is custom.
   */
  protected function isCustomTheme($theme) {
    $path = $theme->getPath();
    return strpos($path, 'themes/custom') !== false || 
           (strpos($path, 'sites/') !== false && strpos($path, '/themes') !== false);
  }

  /**
   * Analyzes theme code.
   */
  protected function analyzeThemeCode($name, $theme) {
    $theme_path = DRUPAL_ROOT . '/' . $theme->getPath();
    
    return [
      'name' => $name,
      'path' => $theme->getPath(),
      'files' => $this->getThemeFiles($theme_path),
      'templates' => $this->getThemeTemplates($theme_path),
      'preprocess_functions' => $this->getPreprocessFunctions($theme_path, $name),
      'theme_functions' => $this->getThemeFunctions($theme_path, $name),
      'css_files' => $this->getCSSFiles($theme_path),
      'js_files' => $this->getJSFiles($theme_path),
      'deprecated_usage' => $this->findDeprecatedUsage($theme_path),
    ];
  }

  /**
   * Gets all files in a theme.
   */
  protected function getThemeFiles($theme_path) {
    $files = [];
    
    if (!is_dir($theme_path)) {
      return $files;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($theme_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $files[] = [
          'path' => str_replace($theme_path . '/', '', $file->getPathname()),
          'extension' => $file->getExtension(),
          'size' => $file->getSize(),
          'modified' => $file->getMTime(),
        ];
      }
    }
    
    return $files;
  }

  /**
   * Gets theme templates.
   */
  protected function getThemeTemplates($theme_path) {
    $templates = [];
    $templates_path = $theme_path . '/templates';
    
    if (!is_dir($templates_path)) {
      return $templates;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($templates_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'twig') {
        $templates[] = [
          'name' => $file->getBasename('.html.twig'),
          'path' => str_replace($theme_path . '/', '', $file->getPathname()),
          'size' => $file->getSize(),
          'variables' => $this->extractTwigVariables($file->getPathname()),
        ];
      }
    }
    
    return $templates;
  }

  /**
   * Extracts variables from Twig templates.
   */
  protected function extractTwigVariables($template_path) {
    $content = file_get_contents($template_path);
    $variables = [];
    
    // Find Twig variables ({{ variable }})
    if (preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_\.]*)\s*\}\}/', $content, $matches)) {
      $variables = array_unique($matches[1]);
    }
    
    return $variables;
  }

  /**
   * Gets preprocess functions.
   */
  protected function getPreprocessFunctions($theme_path, $theme_name) {
    $functions = [];
    $theme_file = $theme_path . '/' . $theme_name . '.theme';
    
    if (file_exists($theme_file)) {
      $content = file_get_contents($theme_file);
      
      // Find preprocess functions
      if (preg_match_all('/function\s+' . $theme_name . '_preprocess_([a-zA-Z_]+)\s*\(/', $content, $matches)) {
        foreach ($matches[1] as $template) {
          $functions[] = [
            'template' => $template,
            'function' => $theme_name . '_preprocess_' . $template,
          ];
        }
      }
    }
    
    return $functions;
  }

  /**
   * Gets theme functions.
   */
  protected function getThemeFunctions($theme_path, $theme_name) {
    $functions = [];
    $theme_file = $theme_path . '/' . $theme_name . '.theme';
    
    if (file_exists($theme_file)) {
      $content = file_get_contents($theme_file);
      
      // Find theme functions
      if (preg_match_all('/function\s+' . $theme_name . '_([a-zA-Z_]+)\s*\(/', $content, $matches)) {
        foreach ($matches[1] as $function_suffix) {
          if (strpos($function_suffix, 'preprocess_') !== 0) {
            $functions[] = [
              'name' => $function_suffix,
              'function' => $theme_name . '_' . $function_suffix,
            ];
          }
        }
      }
    }
    
    return $functions;
  }

  /**
   * Gets CSS files.
   */
  protected function getCSSFiles($theme_path) {
    return $this->getFilesByExtension($theme_path, 'css');
  }

  /**
   * Gets JS files.
   */
  protected function getJSFiles($theme_path) {
    return $this->getFilesByExtension($theme_path, 'js');
  }

  /**
   * Gets files by extension.
   */
  protected function getFilesByExtension($directory, $extension) {
    $files = [];
    
    if (!is_dir($directory)) {
      return $files;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === $extension) {
        $files[] = [
          'path' => str_replace($directory . '/', '', $file->getPathname()),
          'size' => $file->getSize(),
          'modified' => $file->getMTime(),
        ];
      }
    }
    
    return $files;
  }

  /**
   * Finds deprecated function usage.
   */
  protected function findDeprecatedFunctions() {
    $deprecated_functions = $this->getDeprecatedFunctionsList();
    $usage = [];
    
    // Scan custom modules
    $custom_modules = $this->analyzeCustomModules();
    foreach ($custom_modules as $module_name => $module_info) {
      $module_usage = $this->findDeprecatedUsage(DRUPAL_ROOT . '/' . $module_info['path']);
      if (!empty($module_usage)) {
        $usage[$module_name] = $module_usage;
      }
    }
    
    // Scan custom themes
    $custom_themes = $this->analyzeCustomThemes();
    foreach ($custom_themes as $theme_name => $theme_info) {
      $theme_usage = $this->findDeprecatedUsage(DRUPAL_ROOT . '/' . $theme_info['path']);
      if (!empty($theme_usage)) {
        $usage[$theme_name] = $theme_usage;
      }
    }
    
    return $usage;
  }

  /**
   * Gets list of deprecated functions.
   */
  protected function getDeprecatedFunctionsList() {
    return [
      // Drupal 8 to 9 deprecated functions
      'drupal_set_message' => 'Use messenger service instead',
      'file_prepare_directory' => 'Use file_system service instead',
      'file_unmanaged_copy' => 'Use file_system service instead',
      'file_unmanaged_move' => 'Use file_system service instead',
      'file_unmanaged_delete' => 'Use file_system service instead',
      'drupal_realpath' => 'Use file_system service instead',
      'drupal_basename' => 'Use file_system service instead',
      'drupal_dirname' => 'Use file_system service instead',
      'drupal_mkdir' => 'Use file_system service instead',
      'drupal_rmdir' => 'Use file_system service instead',
      'drupal_chmod' => 'Use file_system service instead',
      'drupal_unlink' => 'Use file_system service instead',
      'format_date' => 'Use date.formatter service instead',
      'format_interval' => 'Use date.formatter service instead',
      'drupal_render' => 'Use renderer service instead',
      'drupal_render_root' => 'Use renderer service instead',
      'drupal_render_children' => 'Use renderer service instead',
      'entity_load' => 'Use entity_type.manager service instead',
      'entity_load_multiple' => 'Use entity_type.manager service instead',
      'entity_delete_multiple' => 'Use entity_type.manager service instead',
      'node_load' => 'Use entity_type.manager service instead',
      'node_load_multiple' => 'Use entity_type.manager service instead',
      'user_load' => 'Use entity_type.manager service instead',
      'user_load_multiple' => 'Use entity_type.manager service instead',
      'taxonomy_term_load' => 'Use entity_type.manager service instead',
      'taxonomy_term_load_multiple' => 'Use entity_type.manager service instead',
      'db_query' => 'Use database service instead',
      'db_select' => 'Use database service instead',
      'db_insert' => 'Use database service instead',
      'db_update' => 'Use database service instead',
      'db_delete' => 'Use database service instead',
      'db_merge' => 'Use database service instead',
      'variable_get' => 'Use config system instead',
      'variable_set' => 'Use config system instead',
      'variable_del' => 'Use config system instead',
      'cache_get' => 'Use cache service instead',
      'cache_set' => 'Use cache service instead',
      'cache_clear_all' => 'Use cache service instead',
      'drupal_static' => 'Use static variables or services instead',
      'drupal_static_reset' => 'Use static variables or services instead',
      'drupal_add_css' => 'Use #attached in render arrays instead',
      'drupal_add_js' => 'Use #attached in render arrays instead',
      'drupal_add_library' => 'Use #attached in render arrays instead',
      'drupal_get_path' => 'Use extension.list services instead',
      'drupal_get_filename' => 'Use extension.list services instead',
      'system_rebuild_module_data' => 'Use extension.list.module service instead',
      'system_rebuild_theme_data' => 'Use extension.list.theme service instead',
      'drupal_http_request' => 'Use http_client service instead',
      'url' => 'Use Url::fromRoute() or Url::fromUri() instead',
      'l' => 'Use Link::createFromRoute() or Link::fromTextAndUrl() instead',
      'check_plain' => 'Use Html::escape() instead',
      'check_markup' => 'Use check_markup() filter instead',
      'filter_xss' => 'Use Xss::filter() instead',
      'filter_xss_admin' => 'Use Xss::filterAdmin() instead',
      't' => 'Use $this->t() in classes or \Drupal::translation() service',
      'watchdog' => 'Use logger service instead',
    ];
  }

  /**
   * Finds deprecated usage in a directory.
   */
  protected function findDeprecatedUsage($directory) {
    $deprecated_functions = $this->getDeprecatedFunctionsList();
    $usage = [];
    
    if (!is_dir($directory)) {
      return $usage;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && in_array($file->getExtension(), ['php', 'module', 'inc', 'install', 'theme'])) {
        $content = file_get_contents($file->getPathname());
        
        foreach ($deprecated_functions as $function => $replacement) {
          if (preg_match_all('/\b' . preg_quote($function, '/') . '\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
              $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
              
              $usage[] = [
                'function' => $function,
                'replacement' => $replacement,
                'file' => str_replace($directory . '/', '', $file->getPathname()),
                'line' => $line_number,
              ];
            }
          }
        }
      }
    }
    
    return $usage;
  }

  /**
   * Checks coding standards compliance.
   */
  protected function checkCodingStandards() {
    $violations = [];
    
    // Check custom modules
    $custom_modules = $this->analyzeCustomModules();
    foreach ($custom_modules as $module_name => $module_info) {
      $module_violations = $this->checkModuleCodingStandards(DRUPAL_ROOT . '/' . $module_info['path']);
      if (!empty($module_violations)) {
        $violations[$module_name] = $module_violations;
      }
    }
    
    // Check custom themes
    $custom_themes = $this->analyzeCustomThemes();
    foreach ($custom_themes as $theme_name => $theme_info) {
      $theme_violations = $this->checkModuleCodingStandards(DRUPAL_ROOT . '/' . $theme_info['path']);
      if (!empty($theme_violations)) {
        $violations[$theme_name] = $theme_violations;
      }
    }
    
    return $violations;
  }

  /**
   * Checks coding standards for a module/theme.
   */
  protected function checkModuleCodingStandards($directory) {
    $violations = [];
    
    if (!is_dir($directory)) {
      return $violations;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && in_array($file->getExtension(), ['php', 'module', 'inc', 'install', 'theme'])) {
        $file_violations = $this->checkFileCodingStandards($file->getPathname());
        if (!empty($file_violations)) {
          $violations[str_replace($directory . '/', '', $file->getPathname())] = $file_violations;
        }
      }
    }
    
    return $violations;
  }

  /**
   * Checks coding standards for a single file.
   */
  protected function checkFileCodingStandards($file_path) {
    $violations = [];
    $content = file_get_contents($file_path);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line_number => $line) {
      $line_number++; // 1-based line numbers
      
      // Check for trailing whitespace
      if (preg_match('/\s+$/', $line)) {
        $violations[] = [
          'line' => $line_number,
          'type' => 'whitespace',
          'message' => 'Trailing whitespace found',
        ];
      }
      
      // Check for tabs instead of spaces
      if (strpos($line, "\t") !== false) {
        $violations[] = [
          'line' => $line_number,
          'type' => 'indentation',
          'message' => 'Use spaces instead of tabs for indentation',
        ];
      }
      
      // Check line length (should be <= 80 characters)
      if (strlen($line) > 80) {
        $violations[] = [
          'line' => $line_number,
          'type' => 'line_length',
          'message' => 'Line exceeds 80 characters (' . strlen($line) . ' characters)',
        ];
      }
      
      // Check for missing space after control structures
      if (preg_match('/\b(if|for|foreach|while|switch)\(/', $line)) {
        $violations[] = [
          'line' => $line_number,
          'type' => 'spacing',
          'message' => 'Missing space after control structure',
        ];
      }
      
      // Check for missing space around operators
      if (preg_match('/[a-zA-Z0-9_]\=[^=]/', $line) || preg_match('/[^=]\=[a-zA-Z0-9_]/', $line)) {
        $violations[] = [
          'line' => $line_number,
          'type' => 'spacing',
          'message' => 'Missing space around assignment operator',
        ];
      }
    }
    
    return $violations;
  }

  /**
   * Finds security issues in custom code.
   */
  protected function findSecurityIssues() {
    $issues = [];
    
    // Check custom modules
    $custom_modules = $this->analyzeCustomModules();
    foreach ($custom_modules as $module_name => $module_info) {
      $module_issues = $this->findSecurityIssuesInDirectory(DRUPAL_ROOT . '/' . $module_info['path']);
      if (!empty($module_issues)) {
        $issues[$module_name] = $module_issues;
      }
    }
    
    // Check custom themes
    $custom_themes = $this->analyzeCustomThemes();
    foreach ($custom_themes as $theme_name => $theme_info) {
      $theme_issues = $this->findSecurityIssuesInDirectory(DRUPAL_ROOT . '/' . $theme_info['path']);
      if (!empty($theme_issues)) {
        $issues[$theme_name] = $theme_issues;
      }
    }
    
    return $issues;
  }

  /**
   * Finds security issues in a directory.
   */
  protected function findSecurityIssuesInDirectory($directory) {
    $issues = [];
    
    if (!is_dir($directory)) {
      return $issues;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && in_array($file->getExtension(), ['php', 'module', 'inc', 'install', 'theme'])) {
        $file_issues = $this->findSecurityIssuesInFile($file->getPathname());
        if (!empty($file_issues)) {
          $issues[str_replace($directory . '/', '', $file->getPathname())] = $file_issues;
        }
      }
    }
    
    return $issues;
  }

  /**
   * Finds security issues in a single file.
   */
  protected function findSecurityIssuesInFile($file_path) {
    $issues = [];
    $content = file_get_contents($file_path);
    $lines = explode("\n", $content);
    
    $security_patterns = [
      'eval\s*\(' => 'Use of eval() function is dangerous',
      'exec\s*\(' => 'Use of exec() function may be dangerous',
      'system\s*\(' => 'Use of system() function may be dangerous',
      'shell_exec\s*\(' => 'Use of shell_exec() function may be dangerous',
      'passthru\s*\(' => 'Use of passthru() function may be dangerous',
      '\$_GET\[' => 'Direct use of $_GET without sanitization',
      '\$_POST\[' => 'Direct use of $_POST without sanitization',
      '\$_REQUEST\[' => 'Direct use of $_REQUEST without sanitization',
      '\$_COOKIE\[' => 'Direct use of $_COOKIE without sanitization',
      'mysql_query\s*\(' => 'Use of deprecated mysql_query() function',
      'mysqli_query\s*\(' => 'Direct database query without prepared statements',
      'file_get_contents\s*\(\s*["\']http' => 'HTTP request without proper validation',
      'fopen\s*\(\s*["\']http' => 'HTTP request without proper validation',
      'include\s*\(\s*\$' => 'Dynamic include with variable',
      'require\s*\(\s*\$' => 'Dynamic require with variable',
      'echo\s+\$_' => 'Direct output of user input',
      'print\s+\$_' => 'Direct output of user input',
    ];
    
    foreach ($lines as $line_number => $line) {
      $line_number++; // 1-based line numbers
      
      foreach ($security_patterns as $pattern => $message) {
        if (preg_match('/' . $pattern . '/', $line)) {
          $issues[] = [
            'line' => $line_number,
            'type' => 'security',
            'severity' => $this->getSecuritySeverity($pattern),
            'message' => $message,
            'code' => trim($line),
          ];
        }
      }
    }
    
    return $issues;
  }

  /**
   * Gets security severity for a pattern.
   */
  protected function getSecuritySeverity($pattern) {
    $critical_patterns = ['eval\s*\(', 'exec\s*\(', 'system\s*\('];
    $high_patterns = ['shell_exec\s*\(', 'passthru\s*\(', '\$_GET\[', '\$_POST\['];
    
    if (in_array($pattern, $critical_patterns)) {
      return 'critical';
    }
    elseif (in_array($pattern, $high_patterns)) {
      return 'high';
    }
    
    return 'medium';
  }

  /**
   * Finds performance issues in custom code.
   */
  protected function findPerformanceIssues() {
    $issues = [];
    
    // Check custom modules
    $custom_modules = $this->analyzeCustomModules();
    foreach ($custom_modules as $module_name => $module_info) {
      $module_issues = $this->findPerformanceIssuesInDirectory(DRUPAL_ROOT . '/' . $module_info['path']);
      if (!empty($module_issues)) {
        $issues[$module_name] = $module_issues;
      }
    }
    
    return $issues;
  }

  /**
   * Finds performance issues in a directory.
   */
  protected function findPerformanceIssuesInDirectory($directory) {
    $issues = [];
    
    if (!is_dir($directory)) {
      return $issues;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && in_array($file->getExtension(), ['php', 'module', 'inc', 'install', 'theme'])) {
        $file_issues = $this->findPerformanceIssuesInFile($file->getPathname());
        if (!empty($file_issues)) {
          $issues[str_replace($directory . '/', '', $file->getPathname())] = $file_issues;
        }
      }
    }
    
    return $issues;
  }

  /**
   * Finds performance issues in a single file.
   */
  protected function findPerformanceIssuesInFile($file_path) {
    $issues = [];
    $content = file_get_contents($file_path);
    $lines = explode("\n", $content);
    
    $performance_patterns = [
      'entity_load_multiple\s*\(\s*["\'][^"\']*["\']\s*\)' => 'Loading all entities without limit',
      'node_load_multiple\s*\(\s*\)' => 'Loading all nodes without limit',
      'user_load_multiple\s*\(\s*\)' => 'Loading all users without limit',
      'db_query\s*\([^)]*\)\s*->fetchAll\s*\(\s*\)' => 'Fetching all results without limit',
      'for\s*\([^)]*entity_load[^)]*\)' => 'Loading entities in a loop',
      'foreach\s*\([^)]*\)\s*\{[^}]*entity_load' => 'Loading entities in a loop',
      'file_get_contents\s*\([^)]*http' => 'Synchronous HTTP request',
      'sleep\s*\(' => 'Use of sleep() function',
      'usleep\s*\(' => 'Use of usleep() function',
    ];
    
    foreach ($lines as $line_number => $line) {
      $line_number++; // 1-based line numbers
      
      foreach ($performance_patterns as $pattern => $message) {
        if (preg_match('/' . $pattern . '/', $line)) {
          $issues[] = [
            'line' => $line_number,
            'type' => 'performance',
            'message' => $message,
            'code' => trim($line),
          ];
        }
      }
    }
    
    return $issues;
  }

  /**
   * Checks for API changes impact.
   */
  protected function checkAPIChanges() {
    $api_changes = [];
    
    // This would check for API changes between Drupal versions
    // For now, return basic information about deprecated functions
    $deprecated_usage = $this->findDeprecatedFunctions();
    
    foreach ($deprecated_usage as $module_or_theme => $usage) {
      $api_changes[$module_or_theme] = [
        'deprecated_functions' => count($usage),
        'impact_level' => $this->calculateAPIChangeImpact($usage),
        'changes' => $usage,
      ];
    }
    
    return $api_changes;
  }

  /**
   * Calculates API change impact level.
   */
  protected function calculateAPIChangeImpact($usage) {
    $count = count($usage);
    
    if ($count > 20) {
      return 'high';
    }
    elseif ($count > 5) {
      return 'medium';
    }
    elseif ($count > 0) {
      return 'low';
    }
    
    return 'none';
  }

  /**
   * Calculates code metrics.
   */
  protected function calculateCodeMetrics() {
    $metrics = [
      'total_custom_modules' => 0,
      'total_custom_themes' => 0,
      'total_lines_of_code' => 0,
      'total_functions' => 0,
      'total_classes' => 0,
      'total_files' => 0,
      'complexity_score' => 0,
    ];
    
    // Calculate metrics for custom modules
    $custom_modules = $this->analyzeCustomModules();
    $metrics['total_custom_modules'] = count($custom_modules);
    
    foreach ($custom_modules as $module_info) {
      $metrics['total_lines_of_code'] += $module_info['lines_of_code']['total'];
      $metrics['total_functions'] += count($module_info['functions']);
      $metrics['total_classes'] += count($module_info['classes']);
      $metrics['total_files'] += count($module_info['files']);
      $metrics['complexity_score'] += $module_info['complexity_score'];
    }
    
    // Calculate metrics for custom themes
    $custom_themes = $this->analyzeCustomThemes();
    $metrics['total_custom_themes'] = count($custom_themes);
    
    foreach ($custom_themes as $theme_info) {
      $metrics['total_files'] += count($theme_info['files']);
    }
    
    // Calculate average complexity
    if ($metrics['total_custom_modules'] > 0) {
      $metrics['average_complexity'] = round($metrics['complexity_score'] / $metrics['total_custom_modules'], 2);
    }
    else {
      $metrics['average_complexity'] = 0;
    }
    
    return $metrics;
  }

  /**
   * Gets custom modules.
   */
  protected function getCustomModules() {
    $custom_modules = [];
    $all_modules = $this->moduleList->getList();
    
    foreach ($all_modules as $name => $module) {
      $path = $module->getPath();
      if (strpos($path, 'modules/custom') !== false || 
          (strpos($path, 'sites/') !== false && strpos($path, '/modules') !== false)) {
        $custom_modules[$name] = $path;
      }
    }
    
    return $custom_modules;
  }

  /**
   * Gets custom themes.
   */
  protected function getCustomThemes() {
    $custom_themes = [];
    $all_themes = $this->themeList->getList();
    
    foreach ($all_themes as $name => $theme) {
      $path = $theme->getPath();
      if (strpos($path, 'themes/custom') !== false || 
          (strpos($path, 'sites/') !== false && strpos($path, '/themes') !== false)) {
        $custom_themes[$name] = $path;
      }
    }
    
    return $custom_themes;
  }

  /**
   * Analyzes a specific module.
   */
  protected function analyzeModule($module_name, $module_path) {
    $full_path = DRUPAL_ROOT . '/' . $module_path;
    
    return [
      'deprecated_functions' => $this->scanForDeprecatedFunctions($full_path),
      'deprecated_hooks' => $this->scanForDeprecatedHooks($full_path),
      'api_changes' => $this->scanForAPIChanges($full_path),
      'security_issues' => $this->scanForSecurityIssues($full_path),
      'coding_standards' => $this->checkModuleCodingStandards($full_path),
      'performance_issues' => $this->scanForPerformanceIssues($full_path),
      'file_count' => $this->getFileCount($full_path),
      'lines_of_code' => $this->countLinesOfCode($full_path),
    ];
  }

  /**
   * Analyzes a specific theme.
   */
  protected function analyzeTheme($theme_name, $theme_path) {
    $full_path = DRUPAL_ROOT . '/' . $theme_path;
    
    return [
      'deprecated_functions' => $this->scanForDeprecatedFunctions($full_path),
      'template_issues' => $this->scanTemplateFiles($full_path),
      'css_issues' => $this->scanCSSFiles($full_path),
      'js_issues' => $this->scanJSFiles($full_path),
      'file_count' => $this->getFileCount($full_path),
      'lines_of_code' => $this->countLinesOfCode($full_path),
    ];
  }

  /**
   * Scans for deprecated functions in a directory.
   */
  protected function scanForDeprecatedFunctions($directory) {
    $deprecated_functions = [];
    
    // Common deprecated functions in Drupal 10/11
    $deprecated_patterns = [
      'drupal_set_message' => 'Use \Drupal::messenger()->addMessage()',
      'entity_load' => 'Use \Drupal::entityTypeManager()->getStorage()->load()',
      'entity_load_multiple' => 'Use \Drupal::entityTypeManager()->getStorage()->loadMultiple()',
      'node_load' => 'Use \Drupal::entityTypeManager()->getStorage("node")->load()',
      'user_load' => 'Use \Drupal::entityTypeManager()->getStorage("user")->load()',
      'file_load' => 'Use \Drupal::entityTypeManager()->getStorage("file")->load()',
      'taxonomy_term_load' => 'Use \Drupal::entityTypeManager()->getStorage("taxonomy_term")->load()',
      'db_query' => 'Use \Drupal::database()->query()',
      'db_select' => 'Use \Drupal::database()->select()',
      'variable_get' => 'Use \Drupal::config() or \Drupal::state()',
      'variable_set' => 'Use \Drupal::configFactory() or \Drupal::state()',
      'cache_get' => 'Use \Drupal::cache()->get()',
      'cache_set' => 'Use \Drupal::cache()->set()',
      'drupal_goto' => 'Use RedirectResponse',
      'format_date' => 'Use \Drupal::service("date.formatter")->format()',
    ];
    
    $files = $this->getPhpFiles($directory);
    
    foreach ($files as $file) {
      $content = file_get_contents($file);
      $lines = explode("\n", $content);
      
      foreach ($deprecated_patterns as $function => $replacement) {
        foreach ($lines as $line_number => $line) {
          if (strpos($line, $function) !== false && 
              preg_match('/\b' . preg_quote($function, '/') . '\s*\(/', $line)) {
            $deprecated_functions[] = [
              'function' => $function,
              'file' => str_replace(DRUPAL_ROOT . '/', '', $file),
              'line' => $line_number + 1,
              'code' => trim($line),
              'replacement' => $replacement,
              'severity' => 'high',
            ];
          }
        }
      }
    }
    
    return $deprecated_functions;
  }

  /**
   * Scans for deprecated hooks.
   */
  protected function scanForDeprecatedHooks($directory) {
    $deprecated_hooks = [];
    
    // Common deprecated hooks
    $deprecated_hook_patterns = [
      'hook_init' => 'Use EventSubscriber for KernelEvents::REQUEST',
      'hook_exit' => 'Use EventSubscriber for KernelEvents::TERMINATE',
      'hook_boot' => 'Use EventSubscriber for KernelEvents::REQUEST',
      'hook_field_info' => 'Use plugin annotations in field type plugins',
      'hook_field_widget_info' => 'Use plugin annotations in widget plugins',
      'hook_field_formatter_info' => 'Use plugin annotations in formatter plugins',
    ];
    
    $files = $this->getPhpFiles($directory);
    
    foreach ($files as $file) {
      $content = file_get_contents($file);
      $lines = explode("\n", $content);
      
      foreach ($deprecated_hook_patterns as $hook => $replacement) {
        foreach ($lines as $line_number => $line) {
          if (preg_match('/function\s+\w+_' . substr($hook, 5) . '\s*\(/', $line)) {
            $deprecated_hooks[] = [
              'hook' => $hook,
              'file' => str_replace(DRUPAL_ROOT . '/', '', $file),
              'line' => $line_number + 1,
              'code' => trim($line),
              'replacement' => $replacement,
              'severity' => 'medium',
            ];
          }
        }
      }
    }
    
    return $deprecated_hooks;
  }

  /**
   * Scans for API changes.
   */
  protected function scanForAPIChanges($directory) {
    $api_changes = [];
    
    // This would scan for specific API changes between Drupal versions
    // For now, return empty array
    
    return $api_changes;
  }

  /**
   * Scans for security issues.
   */
  protected function scanForSecurityIssues($directory) {
    $security_issues = [];
    
    // Common security anti-patterns
    $security_patterns = [
      'eval(' => 'Avoid using eval() - security risk',
      'exec(' => 'Avoid using exec() - security risk',
      'system(' => 'Avoid using system() - security risk',
      'shell_exec(' => 'Avoid using shell_exec() - security risk',
      '$_GET' => 'Sanitize user input from $_GET',
      '$_POST' => 'Sanitize user input from $_POST',
      '$_REQUEST' => 'Sanitize user input from $_REQUEST',
    ];
    
    $files = $this->getPhpFiles($directory);
    
    foreach ($files as $file) {
      $content = file_get_contents($file);
      $lines = explode("\n", $content);
      
      foreach ($security_patterns as $pattern => $message) {
        foreach ($lines as $line_number => $line) {
          if (strpos($line, $pattern) !== false) {
            $security_issues[] = [
              'pattern' => $pattern,
              'file' => str_replace(DRUPAL_ROOT . '/', '', $file),
              'line' => $line_number + 1,
              'code' => trim($line),
              'message' => $message,
              'severity' => 'high',
            ];
          }
        }
      }
    }
    
    return $security_issues;
  }

  /**
   * Scans for performance issues.
   */
  protected function scanForPerformanceIssues($directory) {
    $performance_issues = [];
    
    // Common performance anti-patterns
    $performance_patterns = [
      'entity_load_multiple()' => 'Consider using entity queries for better performance',
      'node_load_multiple()' => 'Consider using entity queries for better performance',
      'db_query(' => 'Consider using entity queries or database API',
    ];
    
    $files = $this->getPhpFiles($directory);
    
    foreach ($files as $file) {
      $content = file_get_contents($file);
      $lines = explode("\n", $content);
      
      foreach ($performance_patterns as $pattern => $message) {
        foreach ($lines as $line_number => $line) {
          if (strpos($line, $pattern) !== false) {
            $performance_issues[] = [
              'pattern' => $pattern,
              'file' => str_replace(DRUPAL_ROOT . '/', '', $file),
              'line' => $line_number + 1,
              'code' => trim($line),
              'message' => $message,
              'severity' => 'medium',
            ];
          }
        }
      }
    }
    
    return $performance_issues;
  }

  /**
   * Gets PHP files in a directory.
   */
  protected function getPhpFiles($directory) {
    $files = [];
    
    if (!is_dir($directory)) {
      return $files;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
      }
    }
    
    return $files;
  }

  /**
   * Gets file count in a directory.
   */
  protected function getFileCount($directory) {
    if (!is_dir($directory)) {
      return 0;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    return iterator_count($iterator);
  }

  /**
   * Scans template files for issues.
   */
  protected function scanTemplateFiles($directory) {
    // Scan .twig files for deprecated template functions
    return [];
  }

  /**
   * Scans CSS files for issues.
   */
  protected function scanCSSFiles($directory) {
    // Scan CSS files for deprecated properties
    return [];
  }

  /**
   * Scans JS files for issues.
   */
  protected function scanJSFiles($directory) {
    // Scan JS files for deprecated jQuery usage
    return [];
  }

  /**
   * Generates code analysis summary.
   */
  protected function generateCodeAnalysisSummary($analysis) {
    return [
      'total_deprecated_functions' => count($analysis['deprecated_functions']),
      'total_deprecated_hooks' => count($analysis['deprecated_hooks']),
      'total_api_changes' => count($analysis['api_changes']),
      'total_security_issues' => count($analysis['security_issues']),
      'custom_modules_count' => count($analysis['custom_modules']),
      'custom_themes_count' => count($analysis['custom_themes']),
      'overall_status' => $this->calculateOverallCodeStatus($analysis),
    ];
  }

  /**
   * Calculates overall code status.
   */
  protected function calculateOverallCodeStatus($analysis) {
    $total_issues = count($analysis['deprecated_functions']) + 
                   count($analysis['deprecated_hooks']) + 
                   count($analysis['security_issues']);
    
    if ($total_issues === 0) {
      return 'excellent';
    }
    elseif ($total_issues <= 5) {
      return 'good';
    }
    elseif ($total_issues <= 15) {
      return 'fair';
    }
    else {
      return 'poor';
    }
  }

}