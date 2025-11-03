<?php

namespace Drupal\bootstrap_purge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Symfony\Component\Process\Process;

/**
 * Service for analyzing assets to determine unused CSS/JS.
 */
class AssetAnalyzer {

  /**
   * The asset collector service.
   *
   * @var \Drupal\bootstrap_purge\Service\AssetCollector
   */
  protected $assetCollector;

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
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an AssetAnalyzer object.
   *
   * @param \Drupal\bootstrap_purge\Service\AssetCollector $asset_collector
   *   The asset collector service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    AssetCollector $asset_collector,
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->assetCollector = $asset_collector;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('bootstrap_purge');
  }

  /**
   * Analyzes assets to identify unused CSS and JS.
   *
   * @param array $options
   *   Analysis options including routes, sample size, etc.
   *
   * @return array
   *   Analysis results with suggested purges.
   */
  public function analyzeAssets(array $options = []) {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $analysis_mode = $options['mode'] ?? $config->get('analysis_mode');
    
    $results = [];

    switch ($analysis_mode) {
      case 'static':
        $results = $this->performStaticAnalysis($options);
        break;
        
      case 'runtime':
        $results = $this->performRuntimeAnalysis($options);
        break;
        
      case 'combined':
        $static_results = $this->performStaticAnalysis($options);
        $runtime_results = $this->performRuntimeAnalysis($options);
        $results = $this->combineAnalysisResults($static_results, $runtime_results);
        break;
    }

    return $results;
  }

  /**
   * Performs static analysis using PurgeCSS-like approach.
   *
   * @param array $options
   *   Analysis options.
   *
   * @return array
   *   Static analysis results.
   */
  protected function performStaticAnalysis(array $options) {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $routes = $options['routes'] ?? $config->get('snapshot_routes');
    
    // Generate HTML snapshots
    $html_snapshots = $this->generateHtmlSnapshots($routes);
    
    // Get all CSS assets
    $assets = $this->assetCollector->collectAssets();
    $css_assets = array_filter($assets, function($asset) {
      return $asset['type'] === 'css';
    });

    $results = [];
    
    foreach ($css_assets as $asset_key => $asset) {
      $analysis = $this->analyzeCssAsset($asset, $html_snapshots);
      if (!empty($analysis['unused_selectors'])) {
        $results[$asset_key] = $analysis;
      }
    }

    return $results;
  }

  /**
   * Generates HTML snapshots for specified routes.
   *
   * @param array $routes
   *   Array of route names or 'key' for key routes.
   *
   * @return array
   *   Array of HTML content for each route.
   */
  protected function generateHtmlSnapshots(array $routes) {
    $snapshots = [];
    
    if (in_array('key', $routes)) {
      $routes = $this->getKeyRoutes();
    }

    foreach ($routes as $route) {
      try {
        if ($route === '<front>') {
          $url = Url::fromRoute('<front>')->toString();
        } else {
          $url = Url::fromRoute($route)->toString();
        }
        
        // Use Drupal's internal request to get HTML
        $html = $this->fetchRouteHtml($url);
        if ($html) {
          $snapshots[$route] = $html;
        }
      } catch (\Exception $e) {
        $this->logger->warning('Failed to generate snapshot for route @route: @message', [
          '@route' => $route,
          '@message' => $e->getMessage(),
        ]);
      }
    }

    return $snapshots;
  }

  /**
   * Gets key routes for analysis.
   *
   * @return array
   *   Array of key route names.
   */
  protected function getKeyRoutes() {
    return [
      '<front>',
      'user.login',
      'user.register',
      'node.add',
      'system.admin',
    ];
  }

  /**
   * Fetches HTML content for a route.
   *
   * @param string $url
   *   The URL to fetch.
   *
   * @return string|null
   *   The HTML content or NULL on failure.
   */
  protected function fetchRouteHtml($url) {
    try {
      // Use Drupal's kernel to make an internal request
      $request = \Symfony\Component\HttpFoundation\Request::create($url);
      $kernel = \Drupal::service('http_kernel');
      $response = $kernel->handle($request);
      
      if ($response->getStatusCode() === 200) {
        return $response->getContent();
      }
    } catch (\Exception $e) {
      $this->logger->error('Failed to fetch HTML for URL @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
    }
    
    return NULL;
  }

  /**
   * Analyzes a CSS asset against HTML snapshots.
   *
   * @param array $asset
   *   The CSS asset information.
   * @param array $html_snapshots
   *   Array of HTML snapshots.
   *
   * @return array
   *   Analysis results for the asset.
   */
  protected function analyzeCssAsset(array $asset, array $html_snapshots) {
    $css_content = file_get_contents($asset['full_path']);
    $selectors = $this->extractCssSelectors($css_content);
    $whitelist = $this->getWhitelistPatterns();
    
    $unused_selectors = [];
    $used_selectors = [];
    
    foreach ($selectors as $selector) {
      // Skip whitelisted selectors
      if ($this->isWhitelisted($selector, $whitelist)) {
        continue;
      }
      
      $is_used = FALSE;
      foreach ($html_snapshots as $html) {
        if ($this->isSelectorUsed($selector, $html)) {
          $is_used = TRUE;
          break;
        }
      }
      
      if ($is_used) {
        $used_selectors[] = $selector;
      } else {
        $unused_selectors[] = $selector;
      }
    }

    $original_size = strlen($css_content);
    $estimated_purged_size = $this->estimatePurgedSize($css_content, $unused_selectors);
    
    return [
      'asset_key' => $asset['file_path'],
      'original_size' => $original_size,
      'estimated_purged_size' => $estimated_purged_size,
      'savings_bytes' => $original_size - $estimated_purged_size,
      'savings_percent' => round((($original_size - $estimated_purged_size) / $original_size) * 100, 2),
      'unused_selectors' => $unused_selectors,
      'used_selectors' => $used_selectors,
      'confidence_score' => $this->calculateConfidenceScore($used_selectors, $unused_selectors),
      'analysis_type' => 'static',
      'analyzed_at' => time(),
    ];
  }

  /**
   * Extracts CSS selectors from CSS content.
   *
   * @param string $css_content
   *   The CSS content.
   *
   * @return array
   *   Array of CSS selectors.
   */
  protected function extractCssSelectors($css_content) {
    $selectors = [];
    
    // Remove comments
    $css_content = preg_replace('/\/\*.*?\*\//s', '', $css_content);
    
    // Extract selectors using regex
    preg_match_all('/([^{}]+)\s*\{[^}]*\}/', $css_content, $matches);
    
    foreach ($matches[1] as $selector_group) {
      // Split multiple selectors
      $individual_selectors = explode(',', $selector_group);
      foreach ($individual_selectors as $selector) {
        $selector = trim($selector);
        if (!empty($selector)) {
          $selectors[] = $selector;
        }
      }
    }
    
    return array_unique($selectors);
  }

  /**
   * Gets whitelist patterns from configuration.
   *
   * @return array
   *   Array of whitelist patterns.
   */
  protected function getWhitelistPatterns() {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $whitelist_config = $this->configFactory->get('bootstrap_purge.whitelist');
    
    $patterns = [];
    $patterns = array_merge($patterns, $config->get('whitelist_patterns') ?: []);
    $patterns = array_merge($patterns, $config->get('bootstrap_whitelist') ?: []);
    $patterns = array_merge($patterns, $whitelist_config->get('global_patterns') ?: []);
    $patterns = array_merge($patterns, $whitelist_config->get('selector_patterns') ?: []);
    
    return $patterns;
  }

  /**
   * Checks if a selector is whitelisted.
   *
   * @param string $selector
   *   The CSS selector.
   * @param array $whitelist
   *   Array of whitelist patterns.
   *
   * @return bool
   *   TRUE if the selector is whitelisted.
   */
  protected function isWhitelisted($selector, array $whitelist) {
    foreach ($whitelist as $pattern) {
      // Handle regex patterns
      if (preg_match('/^\/.*\/$/', $pattern)) {
        if (preg_match($pattern, $selector)) {
          return TRUE;
        }
      }
      // Handle glob patterns
      elseif (fnmatch($pattern, $selector)) {
        return TRUE;
      }
    }
    
    return FALSE;
  }

  /**
   * Checks if a selector is used in HTML content.
   *
   * @param string $selector
   *   The CSS selector.
   * @param string $html
   *   The HTML content.
   *
   * @return bool
   *   TRUE if the selector is used.
   */
  protected function isSelectorUsed($selector, $html) {
    // Simple implementation - can be enhanced with proper CSS selector parsing
    $selector = trim($selector);
    
    // Handle class selectors
    if (strpos($selector, '.') === 0) {
      $class = substr($selector, 1);
      $class = preg_replace('/[^a-zA-Z0-9_-].*/', '', $class); // Get first class only
      return strpos($html, 'class="') !== FALSE && 
             preg_match('/class="[^"]*\b' . preg_quote($class, '/') . '\b[^"]*"/', $html);
    }
    
    // Handle ID selectors
    if (strpos($selector, '#') === 0) {
      $id = substr($selector, 1);
      $id = preg_replace('/[^a-zA-Z0-9_-].*/', '', $id); // Get first ID only
      return strpos($html, 'id="' . $id . '"') !== FALSE;
    }
    
    // Handle element selectors
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $selector)) {
      return strpos($html, '<' . $selector) !== FALSE;
    }
    
    // For complex selectors, be conservative and assume they're used
    return TRUE;
  }

  /**
   * Estimates the size of purged CSS.
   *
   * @param string $css_content
   *   The original CSS content.
   * @param array $unused_selectors
   *   Array of unused selectors.
   *
   * @return int
   *   Estimated size in bytes.
   */
  protected function estimatePurgedSize($css_content, array $unused_selectors) {
    // Simple estimation - remove rules with unused selectors
    $purged_content = $css_content;
    
    foreach ($unused_selectors as $selector) {
      // Remove CSS rules containing this selector
      $pattern = '/[^{}]*' . preg_quote($selector, '/') . '[^{}]*\{[^}]*\}/';
      $purged_content = preg_replace($pattern, '', $purged_content);
    }
    
    return strlen($purged_content);
  }

  /**
   * Calculates confidence score for the analysis.
   *
   * @param array $used_selectors
   *   Array of used selectors.
   * @param array $unused_selectors
   *   Array of unused selectors.
   *
   * @return int
   *   Confidence score (0-100).
   */
  protected function calculateConfidenceScore(array $used_selectors, array $unused_selectors) {
    $total_selectors = count($used_selectors) + count($unused_selectors);
    
    if ($total_selectors === 0) {
      return 0;
    }
    
    // Base confidence on ratio of analyzed selectors
    $base_score = (count($used_selectors) / $total_selectors) * 100;
    
    // Reduce confidence for high unused ratio (might indicate incomplete analysis)
    $unused_ratio = count($unused_selectors) / $total_selectors;
    if ($unused_ratio > 0.8) {
      $base_score *= 0.7; // Reduce confidence
    }
    
    return min(100, max(0, round($base_score)));
  }

  /**
   * Performs runtime analysis using collected data.
   *
   * @param array $options
   *   Analysis options.
   *
   * @return array
   *   Runtime analysis results.
   */
  protected function performRuntimeAnalysis(array $options) {
    // This would analyze runtime data collected from users
    // For now, return empty array as runtime collection needs to be implemented
    return [];
  }

  /**
   * Combines static and runtime analysis results.
   *
   * @param array $static_results
   *   Static analysis results.
   * @param array $runtime_results
   *   Runtime analysis results.
   *
   * @return array
   *   Combined analysis results.
   */
  protected function combineAnalysisResults(array $static_results, array $runtime_results) {
    // Combine and weight the results
    $combined = $static_results;
    
    foreach ($runtime_results as $asset_key => $runtime_data) {
      if (isset($combined[$asset_key])) {
        // Merge runtime data with static analysis
        $combined[$asset_key]['runtime_data'] = $runtime_data;
        // Adjust confidence score based on runtime data
        $combined[$asset_key]['confidence_score'] = min(100, 
          $combined[$asset_key]['confidence_score'] + 20);
      }
    }
    
    return $combined;
  }

}