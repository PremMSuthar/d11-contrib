<?php

namespace Drupal\bootstrap_purge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Service for managing asset purging operations.
 */
class AssetManager {

  /**
   * The asset collector service.
   *
   * @var \Drupal\bootstrap_purge\Service\AssetCollector
   */
  protected $assetCollector;

  /**
   * The asset analyzer service.
   *
   * @var \Drupal\bootstrap_purge\Service\AssetAnalyzer
   */
  protected $assetAnalyzer;

  /**
   * The asset purger service.
   *
   * @var \Drupal\bootstrap_purge\Service\AssetPurger
   */
  protected $assetPurger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs an AssetManager object.
   *
   * @param \Drupal\bootstrap_purge\Service\AssetCollector $asset_collector
   *   The asset collector service.
   * @param \Drupal\bootstrap_purge\Service\AssetAnalyzer $asset_analyzer
   *   The asset analyzer service.
   * @param \Drupal\bootstrap_purge\Service\AssetPurger $asset_purger
   *   The asset purger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    AssetCollector $asset_collector,
    AssetAnalyzer $asset_analyzer,
    AssetPurger $asset_purger,
    ConfigFactoryInterface $config_factory,
    StateInterface $state
  ) {
    $this->assetCollector = $asset_collector;
    $this->assetAnalyzer = $asset_analyzer;
    $this->assetPurger = $asset_purger;
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * Gets dashboard statistics.
   *
   * @return array
   *   Dashboard statistics.
   */
  public function getDashboardStats() {
    $total_assets = count($this->assetCollector->collectAssets());
    $bootstrap_assets = count($this->assetCollector->getBootstrapAssets());
    $purged_mappings = $this->getPurgedAssetMappings();
    $total_purged = count($purged_mappings);
    
    $total_original_size = 0;
    $total_purged_size = 0;
    
    foreach ($purged_mappings as $mapping) {
      $total_original_size += $mapping['original_size'] ?? 0;
      $total_purged_size += $mapping['size'] ?? 0;
    }
    
    $bytes_saved = $total_original_size - $total_purged_size;
    $percent_saved = $total_original_size > 0 ? 
      round(($bytes_saved / $total_original_size) * 100, 2) : 0;
    
    return [
      'total_assets' => $total_assets,
      'bootstrap_assets' => $bootstrap_assets,
      'total_purged' => $total_purged,
      'bytes_saved' => $bytes_saved,
      'percent_saved' => $percent_saved,
      'last_analysis' => $this->state->get('bootstrap_purge.last_analysis', 0),
      'last_purge' => $this->state->get('bootstrap_purge.last_purge', 0),
    ];
  }

  /**
   * Gets pending purge candidates.
   *
   * @return array
   *   Array of pending purge candidates.
   */
  public function getPendingCandidates() {
    return $this->state->get('bootstrap_purge.pending_candidates', []);
  }

  /**
   * Gets approved purges.
   *
   * @return array
   *   Array of approved purges.
   */
  public function getApprovedPurges() {
    return $this->state->get('bootstrap_purge.approved_purges', []);
  }

  /**
   * Gets rejected purges.
   *
   * @return array
   *   Array of rejected purges.
   */
  public function getRejectedPurges() {
    return $this->state->get('bootstrap_purge.rejected_purges', []);
  }

  /**
   * Approves a purge candidate.
   *
   * @param string $asset_key
   *   The asset key.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function approvePurge($asset_key) {
    $pending = $this->getPendingCandidates();
    
    if (!isset($pending[$asset_key])) {
      return FALSE;
    }
    
    $candidate = $pending[$asset_key];
    
    // Perform the actual purge
    $asset = $this->assetCollector->collectAssets()[$asset_key] ?? NULL;
    if (!$asset) {
      return FALSE;
    }
    
    if ($asset['type'] === 'css') {
      $purged_info = $this->assetPurger->purgeCssAsset($asset, $candidate);
    } else {
      $purged_info = $this->assetPurger->purgeJsAsset($asset, $candidate);
    }
    
    if ($purged_info) {
      // Move to approved
      $approved = $this->getApprovedPurges();
      $approved[$asset_key] = array_merge($candidate, $purged_info);
      $this->state->set('bootstrap_purge.approved_purges', $approved);
      
      // Remove from pending
      unset($pending[$asset_key]);
      $this->state->set('bootstrap_purge.pending_candidates', $pending);
      
      // Update mappings
      $this->updatePurgedAssetMappings($asset_key, $purged_info);
      
      $this->state->set('bootstrap_purge.last_purge', time());
      
      return TRUE;
    }
    
    return FALSE;
  }

  /**
   * Rejects a purge candidate.
   *
   * @param string $asset_key
   *   The asset key.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function rejectPurge($asset_key) {
    $pending = $this->getPendingCandidates();
    
    if (!isset($pending[$asset_key])) {
      return FALSE;
    }
    
    $candidate = $pending[$asset_key];
    
    // Move to rejected
    $rejected = $this->getRejectedPurges();
    $rejected[$asset_key] = $candidate;
    $this->state->set('bootstrap_purge.rejected_purges', $rejected);
    
    // Remove from pending
    unset($pending[$asset_key]);
    $this->state->set('bootstrap_purge.pending_candidates', $pending);
    
    return TRUE;
  }

  /**
   * Runs analysis and generates purge candidates.
   *
   * @param array $options
   *   Analysis options.
   *
   * @return array
   *   Analysis results.
   */
  public function runAnalysis(array $options = []) {
    $results = $this->assetAnalyzer->analyzeAssets($options);
    
    // Store as pending candidates
    $pending = $this->getPendingCandidates();
    foreach ($results as $asset_key => $analysis) {
      $pending[$asset_key] = $analysis;
    }
    $this->state->set('bootstrap_purge.pending_candidates', $pending);
    $this->state->set('bootstrap_purge.last_analysis', time());
    
    // Auto-approve high-confidence candidates if enabled
    $config = $this->configFactory->get('bootstrap_purge.settings');
    if ($config->get('auto_apply')) {
      $threshold = $config->get('confidence_threshold') ?: 80;
      foreach ($results as $asset_key => $analysis) {
        if ($analysis['confidence_score'] >= $threshold) {
          $this->approvePurge($asset_key);
        }
      }
    }
    
    return $results;
  }

  /**
   * Gets purged asset mappings for library_info_alter.
   *
   * @return array
   *   Array of asset mappings.
   */
  public function getPurgedAssetMappings() {
    return $this->state->get('bootstrap_purge.asset_mappings', []);
  }

  /**
   * Updates purged asset mappings.
   *
   * @param string $asset_key
   *   The asset key.
   * @param array $purged_info
   *   The purged file information.
   */
  protected function updatePurgedAssetMappings($asset_key, array $purged_info) {
    $mappings = $this->getPurgedAssetMappings();
    $mappings[$asset_key] = $purged_info;
    $this->state->set('bootstrap_purge.asset_mappings', $mappings);
  }

  /**
   * Reverts a purged asset.
   *
   * @param string $asset_key
   *   The asset key.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function revertAsset($asset_key) {
    $approved = $this->getApprovedPurges();
    
    if (!isset($approved[$asset_key])) {
      return FALSE;
    }
    
    // Remove from approved
    unset($approved[$asset_key]);
    $this->state->set('bootstrap_purge.approved_purges', $approved);
    
    // Remove from mappings
    $mappings = $this->getPurgedAssetMappings();
    unset($mappings[$asset_key]);
    $this->state->set('bootstrap_purge.asset_mappings', $mappings);
    
    // Call purger to clean up files
    $this->assetPurger->revertAsset($asset_key);
    
    return TRUE;
  }

  /**
   * Gets asset information by key.
   *
   * @param string $asset_key
   *   The asset key.
   *
   * @return array|null
   *   Asset information or NULL if not found.
   */
  public function getAssetInfo($asset_key) {
    $assets = $this->assetCollector->collectAssets();
    return $assets[$asset_key] ?? NULL;
  }

  /**
   * Gets analysis information for an asset.
   *
   * @param string $asset_key
   *   The asset key.
   *
   * @return array|null
   *   Analysis information or NULL if not found.
   */
  public function getAssetAnalysis($asset_key) {
    $pending = $this->getPendingCandidates();
    $approved = $this->getApprovedPurges();
    $rejected = $this->getRejectedPurges();
    
    return $pending[$asset_key] ?? $approved[$asset_key] ?? $rejected[$asset_key] ?? NULL;
  }

  /**
   * Clears all analysis data.
   */
  public function clearAnalysisData() {
    $this->state->delete('bootstrap_purge.pending_candidates');
    $this->state->delete('bootstrap_purge.approved_purges');
    $this->state->delete('bootstrap_purge.rejected_purges');
    $this->state->delete('bootstrap_purge.asset_mappings');
    $this->state->delete('bootstrap_purge.last_analysis');
    $this->state->delete('bootstrap_purge.last_purge');
  }

}