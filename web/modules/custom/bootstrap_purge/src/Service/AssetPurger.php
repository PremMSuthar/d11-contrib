<?php

namespace Drupal\bootstrap_purge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\Process\Process;

/**
 * Service for purging unused CSS and JS from assets.
 */
class AssetPurger {

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
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an AssetPurger object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    FileRepositoryInterface $file_repository,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->fileRepository = $file_repository;
    $this->logger = $logger_factory->get('bootstrap_purge');
  }

  /**
   * Purges CSS file based on analysis results.
   *
   * @param array $asset
   *   The asset information.
   * @param array $analysis
   *   The analysis results.
   *
   * @return array|null
   *   Purged file information or NULL on failure.
   */
  public function purgeCssAsset(array $asset, array $analysis) {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $purgecss_path = $config->get('purgecss_path');
    
    if (!empty($purgecss_path) && $this->isPurgeCssAvailable($purgecss_path)) {
      return $this->purgeCssWithPurgeCSS($asset, $analysis);
    } else {
      return $this->purgeCssWithPhp($asset, $analysis);
    }
  }

  /**
   * Purges CSS using PurgeCSS tool.
   *
   * @param array $asset
   *   The asset information.
   * @param array $analysis
   *   The analysis results.
   *
   * @return array|null
   *   Purged file information or NULL on failure.
   */
  protected function purgeCssWithPurgeCSS(array $asset, array $analysis) {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $purgecss_path = $config->get('purgecss_path');
    $storage_path = $config->get('storage_path');
    
    // Prepare storage directory
    $this->fileSystem->prepareDirectory($storage_path, FileSystemInterface::CREATE_DIRECTORY);
    
    // Create temporary config file for PurgeCSS
    $temp_config = $this->createPurgeCssConfig($asset, $analysis);
    $temp_config_path = $this->fileSystem->getTempDirectory() . '/purgecss_config_' . uniqid() . '.json';
    file_put_contents($temp_config_path, json_encode($temp_config));
    
    // Generate output filename
    $hash = substr(md5($asset['full_path'] . serialize($analysis)), 0, 8);
    $output_filename = pathinfo($asset['file_path'], PATHINFO_FILENAME) . '.purged.' . $hash . '.css';
    $output_path = $storage_path . '/' . $output_filename;
    $full_output_path = $this->fileSystem->realpath($output_path);
    
    try {
      // Run PurgeCSS
      $process = new Process([
        $purgecss_path,
        '--config', $temp_config_path,
        '--output', dirname($full_output_path)
      ]);
      
      $process->run();
      
      if ($process->isSuccessful()) {
        // Clean up temp config
        unlink($temp_config_path);
        
        if (file_exists($full_output_path)) {
          // Create managed file
          $file = $this->fileRepository->writeData(
            file_get_contents($full_output_path),
            $output_path,
            FileRepositoryInterface::EXISTS_REPLACE
          );
          
          return [
            'path' => $output_path,
            'file_id' => $file->id(),
            'size' => filesize($full_output_path),
            'hash' => $hash,
            'method' => 'purgecss',
            'created' => time(),
          ];
        }
      } else {
        $this->logger->error('PurgeCSS failed: @error', [
          '@error' => $process->getErrorOutput(),
        ]);
      }
    } catch (\Exception $e) {
      $this->logger->error('PurgeCSS execution failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    } finally {
      // Clean up temp files
      if (file_exists($temp_config_path)) {
        unlink($temp_config_path);
      }
    }
    
    return NULL;
  }

  /**
   * Purges CSS using PHP implementation.
   *
   * @param array $asset
   *   The asset information.
   * @param array $analysis
   *   The analysis results.
   *
   * @return array|null
   *   Purged file information or NULL on failure.
   */
  protected function purgeCssWithPhp(array $asset, array $analysis) {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $storage_path = $config->get('storage_path');
    
    // Prepare storage directory
    $this->fileSystem->prepareDirectory($storage_path, FileSystemInterface::CREATE_DIRECTORY);
    
    $css_content = file_get_contents($asset['full_path']);
    $purged_content = $this->removeCssRules($css_content, $analysis['unused_selectors']);
    
    // Generate output filename
    $hash = substr(md5($asset['full_path'] . serialize($analysis)), 0, 8);
    $output_filename = pathinfo($asset['file_path'], PATHINFO_FILENAME) . '.purged.' . $hash . '.css';
    $output_path = $storage_path . '/' . $output_filename;
    
    try {
      // Create managed file
      $file = $this->fileRepository->writeData(
        $purged_content,
        $output_path,
        FileRepositoryInterface::EXISTS_REPLACE
      );
      
      return [
        'path' => $output_path,
        'file_id' => $file->id(),
        'size' => strlen($purged_content),
        'hash' => $hash,
        'method' => 'php',
        'created' => time(),
      ];
    } catch (\Exception $e) {
      $this->logger->error('Failed to create purged CSS file: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    
    return NULL;
  }

  /**
   * Removes CSS rules containing unused selectors.
   *
   * @param string $css_content
   *   The original CSS content.
   * @param array $unused_selectors
   *   Array of unused selectors.
   *
   * @return string
   *   The purged CSS content.
   */
  protected function removeCssRules($css_content, array $unused_selectors) {
    // Remove comments first
    $css_content = preg_replace('/\/\*.*?\*\//s', '', $css_content);
    
    foreach ($unused_selectors as $selector) {
      // Escape special regex characters in selector
      $escaped_selector = preg_quote($selector, '/');
      
      // Remove CSS rules containing this selector
      // This is a simplified approach - a full CSS parser would be more accurate
      $pattern = '/[^{}]*' . $escaped_selector . '[^{}]*\{[^}]*\}/s';
      $css_content = preg_replace($pattern, '', $css_content);
    }
    
    // Clean up extra whitespace
    $css_content = preg_replace('/\s+/', ' ', $css_content);
    $css_content = preg_replace('/\s*{\s*/', '{', $css_content);
    $css_content = preg_replace('/\s*}\s*/', '}', $css_content);
    
    return trim($css_content);
  }

  /**
   * Purges JavaScript file based on analysis results.
   *
   * @param array $asset
   *   The asset information.
   * @param array $analysis
   *   The analysis results.
   *
   * @return array|null
   *   Purged file information or NULL on failure.
   */
  public function purgeJsAsset(array $asset, array $analysis) {
    // JavaScript purging is more complex and would typically require
    // integration with bundlers like webpack, rollup, or esbuild
    // For now, we'll implement a basic approach
    
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $storage_path = $config->get('storage_path');
    
    // Prepare storage directory
    $this->fileSystem->prepareDirectory($storage_path, FileSystemInterface::CREATE_DIRECTORY);
    
    $js_content = file_get_contents($asset['full_path']);
    
    // Basic JS minification (remove comments and extra whitespace)
    $purged_content = $this->minifyJavaScript($js_content);
    
    // Generate output filename
    $hash = substr(md5($asset['full_path'] . serialize($analysis)), 0, 8);
    $output_filename = pathinfo($asset['file_path'], PATHINFO_FILENAME) . '.purged.' . $hash . '.js';
    $output_path = $storage_path . '/' . $output_filename;
    
    try {
      // Create managed file
      $file = $this->fileRepository->writeData(
        $purged_content,
        $output_path,
        FileRepositoryInterface::EXISTS_REPLACE
      );
      
      return [
        'path' => $output_path,
        'file_id' => $file->id(),
        'size' => strlen($purged_content),
        'hash' => $hash,
        'method' => 'php_minify',
        'created' => time(),
      ];
    } catch (\Exception $e) {
      $this->logger->error('Failed to create purged JS file: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    
    return NULL;
  }

  /**
   * Basic JavaScript minification.
   *
   * @param string $js_content
   *   The JavaScript content.
   *
   * @return string
   *   The minified JavaScript content.
   */
  protected function minifyJavaScript($js_content) {
    // Remove single-line comments
    $js_content = preg_replace('/\/\/.*$/m', '', $js_content);
    
    // Remove multi-line comments
    $js_content = preg_replace('/\/\*.*?\*\//s', '', $js_content);
    
    // Remove extra whitespace
    $js_content = preg_replace('/\s+/', ' ', $js_content);
    
    // Remove whitespace around operators and punctuation
    $js_content = preg_replace('/\s*([{}();,:])\s*/', '$1', $js_content);
    
    return trim($js_content);
  }

  /**
   * Creates PurgeCSS configuration.
   *
   * @param array $asset
   *   The asset information.
   * @param array $analysis
   *   The analysis results.
   *
   * @return array
   *   PurgeCSS configuration array.
   */
  protected function createPurgeCssConfig(array $asset, array $analysis) {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $whitelist = $this->getWhitelistForPurgeCSS();
    
    return [
      'content' => $this->getContentPaths(),
      'css' => [$asset['full_path']],
      'whitelist' => $whitelist,
      'whitelistPatterns' => $this->getWhitelistPatterns(),
      'extractors' => [
        [
          'extractor' => 'purgeCSS.defaultExtractor',
          'extensions' => ['html', 'php', 'twig'],
        ],
      ],
    ];
  }

  /**
   * Gets content paths for PurgeCSS.
   *
   * @return array
   *   Array of content paths.
   */
  protected function getContentPaths() {
    // This would return paths to template files and generated HTML
    // For now, return basic paths
    return [
      DRUPAL_ROOT . '/core/themes/**/*.html.twig',
      DRUPAL_ROOT . '/themes/**/*.html.twig',
      DRUPAL_ROOT . '/modules/**/*.html.twig',
    ];
  }

  /**
   * Gets whitelist for PurgeCSS.
   *
   * @return array
   *   Array of whitelisted selectors.
   */
  protected function getWhitelistForPurgeCSS() {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $whitelist_config = $this->configFactory->get('bootstrap_purge.whitelist');
    
    $whitelist = [];
    $whitelist = array_merge($whitelist, $config->get('whitelist_patterns') ?: []);
    $whitelist = array_merge($whitelist, $whitelist_config->get('global_patterns') ?: []);
    
    return $whitelist;
  }

  /**
   * Gets whitelist patterns for PurgeCSS.
   *
   * @return array
   *   Array of whitelist patterns.
   */
  protected function getWhitelistPatterns() {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    return $config->get('bootstrap_whitelist') ?: [];
  }

  /**
   * Checks if PurgeCSS is available.
   *
   * @param string $purgecss_path
   *   Path to PurgeCSS executable.
   *
   * @return bool
   *   TRUE if PurgeCSS is available.
   */
  protected function isPurgeCssAvailable($purgecss_path) {
    if (empty($purgecss_path)) {
      return FALSE;
    }
    
    try {
      $process = new Process([$purgecss_path, '--version']);
      $process->run();
      return $process->isSuccessful();
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Reverts a purged asset to its original version.
   *
   * @param string $asset_key
   *   The asset key.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function revertAsset($asset_key) {
    // Implementation would remove the purged file and update mappings
    // This is a placeholder for the revert functionality
    $this->logger->info('Reverting asset: @asset', ['@asset' => $asset_key]);
    return TRUE;
  }

}