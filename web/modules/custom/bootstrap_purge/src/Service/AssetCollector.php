<?php

namespace Drupal\bootstrap_purge\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Service for collecting assets from themes and modules.
 */
class AssetCollector {

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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an AssetCollector object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ThemeHandlerInterface $theme_handler,
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('bootstrap_purge');
  }

  /**
   * Collects all CSS and JS assets from enabled themes and modules.
   *
   * @return array
   *   Array of assets keyed by extension/library/file.
   */
  public function collectAssets() {
    $assets = [];

    // Collect from modules
    foreach ($this->moduleHandler->getModuleList() as $module_name => $module) {
      $module_assets = $this->collectModuleAssets($module_name);
      $assets = array_merge($assets, $module_assets);
    }

    // Collect from themes
    foreach ($this->themeHandler->listInfo() as $theme_name => $theme) {
      if ($theme->status) {
        $theme_assets = $this->collectThemeAssets($theme_name);
        $assets = array_merge($assets, $theme_assets);
      }
    }

    return $assets;
  }

  /**
   * Collects assets from a specific module.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return array
   *   Array of assets from the module.
   */
  protected function collectModuleAssets($module_name) {
    $assets = [];
    $module_path = $this->moduleHandler->getModule($module_name)->getPath();
    $libraries_file = DRUPAL_ROOT . '/' . $module_path . '/' . $module_name . '.libraries.yml';

    if (file_exists($libraries_file)) {
      $libraries = \Drupal\Component\Serialization\Yaml::decode(file_get_contents($libraries_file));
      if ($libraries) {
        foreach ($libraries as $library_name => $library_info) {
          $assets = array_merge($assets, $this->processLibraryAssets($module_name, $library_name, $library_info, $module_path));
        }
      }
    }

    return $assets;
  }

  /**
   * Collects assets from a specific theme.
   *
   * @param string $theme_name
   *   The theme name.
   *
   * @return array
   *   Array of assets from the theme.
   */
  protected function collectThemeAssets($theme_name) {
    $assets = [];
    $theme_path = $this->themeHandler->getTheme($theme_name)->getPath();
    $libraries_file = DRUPAL_ROOT . '/' . $theme_path . '/' . $theme_name . '.libraries.yml';

    if (file_exists($libraries_file)) {
      $libraries = \Drupal\Component\Serialization\Yaml::decode(file_get_contents($libraries_file));
      if ($libraries) {
        foreach ($libraries as $library_name => $library_info) {
          $assets = array_merge($assets, $this->processLibraryAssets($theme_name, $library_name, $library_info, $theme_path));
        }
      }
    }

    return $assets;
  }

  /**
   * Processes library assets and extracts CSS/JS files.
   *
   * @param string $extension_name
   *   The extension name (module or theme).
   * @param string $library_name
   *   The library name.
   * @param array $library_info
   *   The library information.
   * @param string $extension_path
   *   The extension path.
   *
   * @return array
   *   Array of processed assets.
   */
  protected function processLibraryAssets($extension_name, $library_name, array $library_info, $extension_path) {
    $assets = [];

    // Process CSS files
    if (isset($library_info['css'])) {
      foreach ($library_info['css'] as $category => $files) {
        foreach ($files as $file_path => $file_info) {
          if (is_string($file_info)) {
            $file_info = [];
          }
          
          $full_path = DRUPAL_ROOT . '/' . $extension_path . '/' . $file_path;
          if (file_exists($full_path)) {
            $asset_key = $extension_name . '/' . $library_name . '/' . $file_path;
            $assets[$asset_key] = [
              'type' => 'css',
              'extension' => $extension_name,
              'library' => $library_name,
              'file_path' => $file_path,
              'full_path' => $full_path,
              'category' => $category,
              'info' => $file_info,
              'size' => filesize($full_path),
              'modified' => filemtime($full_path),
            ];
          }
        }
      }
    }

    // Process JS files
    if (isset($library_info['js'])) {
      foreach ($library_info['js'] as $file_path => $file_info) {
        if (is_string($file_info)) {
          $file_info = [];
        }
        
        $full_path = DRUPAL_ROOT . '/' . $extension_path . '/' . $file_path;
        if (file_exists($full_path)) {
          $asset_key = $extension_name . '/' . $library_name . '/' . $file_path;
          $assets[$asset_key] = [
            'type' => 'js',
            'extension' => $extension_name,
            'library' => $library_name,
            'file_path' => $file_path,
            'full_path' => $full_path,
            'info' => $file_info,
            'size' => filesize($full_path),
            'modified' => filemtime($full_path),
          ];
        }
      }
    }

    return $assets;
  }

  /**
   * Gets assets that match Bootstrap patterns.
   *
   * @return array
   *   Array of Bootstrap-related assets.
   */
  public function getBootstrapAssets() {
    $all_assets = $this->collectAssets();
    $bootstrap_assets = [];

    foreach ($all_assets as $asset_key => $asset) {
      // Check if asset is Bootstrap-related
      if ($this->isBootstrapAsset($asset)) {
        $bootstrap_assets[$asset_key] = $asset;
      }
    }

    return $bootstrap_assets;
  }

  /**
   * Determines if an asset is Bootstrap-related.
   *
   * @param array $asset
   *   The asset information.
   *
   * @return bool
   *   TRUE if the asset is Bootstrap-related.
   */
  protected function isBootstrapAsset(array $asset) {
    $bootstrap_patterns = [
      'bootstrap',
      'bs-',
      'btn-',
      'modal',
      'dropdown',
      'nav-',
      'navbar',
      'carousel',
      'alert',
      'badge',
      'card',
      'form-',
      'input-',
      'table',
      'col-',
      'd-',
    ];

    $file_content = '';
    if (file_exists($asset['full_path'])) {
      $file_content = file_get_contents($asset['full_path']);
    }

    foreach ($bootstrap_patterns as $pattern) {
      if (strpos($asset['file_path'], $pattern) !== FALSE ||
          strpos($file_content, $pattern) !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Gets the total size of all collected assets.
   *
   * @return int
   *   Total size in bytes.
   */
  public function getTotalAssetSize() {
    $assets = $this->collectAssets();
    $total_size = 0;

    foreach ($assets as $asset) {
      $total_size += $asset['size'];
    }

    return $total_size;
  }

}