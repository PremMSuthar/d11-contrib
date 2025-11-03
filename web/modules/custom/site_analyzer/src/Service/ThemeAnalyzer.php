<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Service for analyzing themes.
 */
class ThemeAnalyzer {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $themeList;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ThemeAnalyzer object.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ExtensionList $theme_list, ConfigFactoryInterface $config_factory) {
    $this->themeHandler = $theme_handler;
    $this->themeList = $theme_list;
    $this->configFactory = $config_factory;
  }

  /**
   * Analyzes installed themes.
   */
  public function analyze() {
    $themes = $this->themeList->getList();
    $default_theme = $this->configFactory->get('system.theme')->get('default');
    $admin_theme = $this->configFactory->get('system.theme')->get('admin');
    
    $analysis = [
      'total_count' => count($themes),
      'enabled_count' => count($this->themeHandler->listInfo()),
      'default_theme' => $default_theme,
      'admin_theme' => $admin_theme,
      'core_themes' => [],
      'contrib_themes' => [],
      'custom_themes' => [],
      'template_overrides' => [],
      'css_js_assets' => [],
      'responsive_frameworks' => [],
      'compatibility_issues' => [],
      'theme_dependencies' => [],
    ];

    foreach ($themes as $name => $theme) {
      $theme_info = $this->analyzeTheme($name, $theme);
      
      // Categorize by type
      if ($theme_info['type'] === 'core') {
        $analysis['core_themes'][$name] = $theme_info;
      }
      elseif ($theme_info['type'] === 'contrib') {
        $analysis['contrib_themes'][$name] = $theme_info;
      }
      else {
        $analysis['custom_themes'][$name] = $theme_info;
      }
      
      // Collect template overrides
      if (!empty($theme_info['template_overrides'])) {
        $analysis['template_overrides'][$name] = $theme_info['template_overrides'];
      }
      
      // Collect CSS/JS assets
      if (!empty($theme_info['assets'])) {
        $analysis['css_js_assets'][$name] = $theme_info['assets'];
      }
      
      // Check for responsive frameworks
      if (!empty($theme_info['responsive_framework'])) {
        $analysis['responsive_frameworks'][$name] = $theme_info['responsive_framework'];
      }
      
      // Check for compatibility issues
      if (!empty($theme_info['compatibility_issues'])) {
        $analysis['compatibility_issues'][$name] = $theme_info['compatibility_issues'];
      }
      
      // Store dependencies
      if (!empty($theme_info['dependencies'])) {
        $analysis['theme_dependencies'][$name] = $theme_info['dependencies'];
      }
    }

    return $analysis;
  }

  /**
   * Analyzes a single theme.
   */
  protected function analyzeTheme($name, $theme) {
    $info = [
      'name' => $name,
      'display_name' => $theme->info['name'] ?? $name,
      'description' => $theme->info['description'] ?? '',
      'package' => $theme->info['package'] ?? 'Other',
      'version' => $theme->info['version'] ?? 'dev',
      'core_version_requirement' => $theme->info['core_version_requirement'] ?? '',
      'type' => $this->getThemeType($theme),
      'status' => $this->themeHandler->themeExists($name) ? 'enabled' : 'disabled',
      'path' => $theme->getPath(),
      'base_theme' => $theme->info['base theme'] ?? null,
      'dependencies' => $theme->info['dependencies'] ?? [],
      'regions' => $theme->info['regions'] ?? [],
      'libraries' => $theme->info['libraries'] ?? [],
      'template_overrides' => $this->getTemplateOverrides($theme),
      'assets' => $this->getThemeAssets($theme),
      'responsive_framework' => $this->getResponsiveFramework($theme),
      'compatibility_issues' => [],
      'file_count' => $this->getThemeFileCount($theme->getPath()),
      'size' => $this->getThemeSize($theme->getPath()),
      'last_modified' => $this->getLastModified($theme->getPath()),
      'breakpoints' => $this->getBreakpoints($name),
      'component_libraries' => $this->getComponentLibraries($theme),
    ];

    // Check compatibility
    $info['compatibility_issues'] = $this->checkThemeCompatibility($theme);

    return $info;
  }

  /**
   * Determines theme type (core, contrib, custom).
   */
  protected function getThemeType($theme) {
    $path = $theme->getPath();
    
    if (strpos($path, 'core/themes') === 0) {
      return 'core';
    }
    elseif (strpos($path, 'themes/contrib') !== false) {
      return 'contrib';
    }
    elseif (strpos($path, 'themes/custom') !== false) {
      return 'custom';
    }
    elseif (strpos($path, 'sites/') !== false && strpos($path, '/themes') !== false) {
      return 'custom';
    }
    
    return 'contrib';
  }

  /**
   * Gets template overrides for a theme.
   */
  protected function getTemplateOverrides($theme) {
    $overrides = [];
    $theme_path = DRUPAL_ROOT . '/' . $theme->getPath();
    
    if (!is_dir($theme_path . '/templates')) {
      return $overrides;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($theme_path . '/templates', \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'twig') {
        $template_name = $file->getBasename('.html.twig');
        $relative_path = str_replace($theme_path . '/templates/', '', $file->getPathname());
        
        $overrides[] = [
          'template' => $template_name,
          'path' => $relative_path,
          'size' => $file->getSize(),
          'modified' => $file->getMTime(),
        ];
      }
    }
    
    return $overrides;
  }

  /**
   * Gets CSS and JS assets for a theme.
   */
  protected function getThemeAssets($theme) {
    $assets = [
      'css' => [],
      'js' => [],
      'libraries' => [],
    ];
    
    $theme_path = DRUPAL_ROOT . '/' . $theme->getPath();
    
    // Find CSS files
    $css_files = $this->findFilesByExtension($theme_path, 'css');
    foreach ($css_files as $file) {
      $assets['css'][] = [
        'path' => str_replace($theme_path . '/', '', $file),
        'size' => filesize($file),
        'modified' => filemtime($file),
      ];
    }
    
    // Find JS files
    $js_files = $this->findFilesByExtension($theme_path, 'js');
    foreach ($js_files as $file) {
      $assets['js'][] = [
        'path' => str_replace($theme_path . '/', '', $file),
        'size' => filesize($file),
        'modified' => filemtime($file),
      ];
    }
    
    // Check for libraries file
    $libraries_file = $theme_path . '/' . $theme->getName() . '.libraries.yml';
    if (file_exists($libraries_file)) {
      $assets['libraries'] = $this->parseLibrariesFile($libraries_file);
    }
    
    return $assets;
  }

  /**
   * Finds files by extension in a directory.
   */
  protected function findFilesByExtension($directory, $extension) {
    $files = [];
    
    if (!is_dir($directory)) {
      return $files;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === $extension) {
        $files[] = $file->getPathname();
      }
    }
    
    return $files;
  }

  /**
   * Parses theme libraries file.
   */
  protected function parseLibrariesFile($file) {
    try {
      $content = file_get_contents($file);
      $libraries = \Drupal::service('serialization.yaml')->decode($content);
      return array_keys($libraries ?: []);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets responsive framework used by theme.
   */
  protected function getResponsiveFramework($theme) {
    $framework = null;
    $theme_path = DRUPAL_ROOT . '/' . $theme->getPath();
    
    // Check for common frameworks
    $frameworks = [
      'bootstrap' => ['bootstrap', 'bs-', 'btn-', 'col-'],
      'foundation' => ['foundation', 'grid-', 'column'],
      'bulma' => ['bulma', 'is-', 'has-'],
      'tailwind' => ['tailwind', 'tw-', 'text-'],
      'materialize' => ['materialize', 'material'],
    ];
    
    // Check CSS files for framework indicators
    $css_files = $this->findFilesByExtension($theme_path, 'css');
    foreach ($css_files as $file) {
      $content = file_get_contents($file);
      
      foreach ($frameworks as $name => $indicators) {
        foreach ($indicators as $indicator) {
          if (strpos($content, $indicator) !== false) {
            $framework = $name;
            break 2;
          }
        }
      }
    }
    
    // Check for framework-specific files
    if (!$framework) {
      $framework_files = [
        'bootstrap' => ['bootstrap.css', 'bootstrap.min.css'],
        'foundation' => ['foundation.css', 'foundation.min.css'],
        'bulma' => ['bulma.css', 'bulma.min.css'],
      ];
      
      foreach ($framework_files as $name => $files) {
        foreach ($files as $file) {
          if (file_exists($theme_path . '/' . $file)) {
            $framework = $name;
            break 2;
          }
        }
      }
    }
    
    return $framework;
  }

  /**
   * Checks theme compatibility.
   */
  protected function checkThemeCompatibility($theme) {
    $issues = [];
    $core_version = \Drupal::VERSION;
    
    // Check core version requirement
    $core_requirement = $theme->info['core_version_requirement'] ?? '';
    if ($core_requirement) {
      if (strpos($core_requirement, '^9') !== false && version_compare($core_version, '10.0', '>=')) {
        $issues[] = [
          'type' => 'warning',
          'message' => 'Theme may not be fully compatible with Drupal ' . $core_version,
        ];
      }
    }
    
    // Check for deprecated base themes
    $base_theme = $theme->info['base theme'] ?? null;
    if ($base_theme && in_array($base_theme, ['seven', 'bartik', 'garland'])) {
      $issues[] = [
        'type' => 'error',
        'message' => 'Uses deprecated base theme: ' . $base_theme,
      ];
    }
    
    // Check for deprecated template files
    $deprecated_templates = [
      'html.html.twig',
      'maintenance-page.html.twig',
    ];
    
    $template_overrides = $this->getTemplateOverrides($theme);
    foreach ($template_overrides as $override) {
      if (in_array($override['template'] . '.html.twig', $deprecated_templates)) {
        $issues[] = [
          'type' => 'warning',
          'message' => 'Uses deprecated template: ' . $override['template'],
        ];
      }
    }
    
    return $issues;
  }

  /**
   * Gets file count for a theme.
   */
  protected function getThemeFileCount($path) {
    $full_path = DRUPAL_ROOT . '/' . $path;
    if (!is_dir($full_path)) {
      return 0;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($full_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    return iterator_count($iterator);
  }

  /**
   * Gets theme size in bytes.
   */
  protected function getThemeSize($path) {
    $full_path = DRUPAL_ROOT . '/' . $path;
    if (!is_dir($full_path)) {
      return 0;
    }
    
    $size = 0;
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($full_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $size += $file->getSize();
      }
    }
    
    return $size;
  }

  /**
   * Gets last modified time for a theme.
   */
  protected function getLastModified($path) {
    $full_path = DRUPAL_ROOT . '/' . $path;
    $latest = 0;
    
    if (!is_dir($full_path)) {
      return $latest;
    }
    
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($full_path, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $mtime = $file->getMTime();
        if ($mtime > $latest) {
          $latest = $mtime;
        }
      }
    }
    
    return $latest;
  }

  /**
   * Gets breakpoints for a theme.
   */
  protected function getBreakpoints($theme_name) {
    $breakpoints_file = DRUPAL_ROOT . '/' . $this->themeList->getPath($theme_name) . '/' . $theme_name . '.breakpoints.yml';
    
    if (!file_exists($breakpoints_file)) {
      return [];
    }
    
    try {
      $content = file_get_contents($breakpoints_file);
      return \Drupal::service('serialization.yaml')->decode($content) ?: [];
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets component libraries for a theme.
   */
  protected function getComponentLibraries($theme) {
    $components = [];
    $theme_path = DRUPAL_ROOT . '/' . $theme->getPath();
    
    // Check for Single Directory Components
    $components_dir = $theme_path . '/components';
    if (is_dir($components_dir)) {
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($components_dir, \RecursiveDirectoryIterator::SKIP_DOTS)
      );
      
      foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'yml') {
          $component_name = $file->getBasename('.component.yml');
          if (strpos($file->getFilename(), '.component.yml') !== false) {
            $components[] = $component_name;
          }
        }
      }
    }
    
    return $components;
  }

}