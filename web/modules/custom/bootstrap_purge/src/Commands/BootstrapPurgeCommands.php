<?php

namespace Drupal\bootstrap_purge\Commands;

use Drupal\bootstrap_purge\Service\AssetManager;
use Drupal\bootstrap_purge\Service\AssetCollector;
use Drupal\bootstrap_purge\Service\AssetAnalyzer;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Drush commands for Bootstrap Purge.
 */
class BootstrapPurgeCommands extends DrushCommands {

  /**
   * The asset manager service.
   *
   * @var \Drupal\bootstrap_purge\Service\AssetManager
   */
  protected $assetManager;

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
   * Constructs a BootstrapPurgeCommands object.
   *
   * @param \Drupal\bootstrap_purge\Service\AssetManager $asset_manager
   *   The asset manager service.
   * @param \Drupal\bootstrap_purge\Service\AssetCollector $asset_collector
   *   The asset collector service.
   * @param \Drupal\bootstrap_purge\Service\AssetAnalyzer $asset_analyzer
   *   The asset analyzer service.
   */
  public function __construct(
    AssetManager $asset_manager,
    AssetCollector $asset_collector,
    AssetAnalyzer $asset_analyzer
  ) {
    $this->assetManager = $asset_manager;
    $this->assetCollector = $asset_collector;
    $this->assetAnalyzer = $asset_analyzer;
  }

  /**
   * Analyzes assets for unused CSS/JS.
   *
   * @param array $options
   *   Command options.
   *
   * @option routes
   *   Comma-separated list of routes to analyze, or 'all' for all routes, 'key' for key routes.
   * @option mode
   *   Analysis mode: static, runtime, or combined.
   * @option sample
   *   Sample percentage for runtime analysis.
   * @option bootstrap-only
   *   Analyze only Bootstrap-related assets.
   *
   * @command bootstrap-purge:analyze
   * @aliases bp:analyze
   * @usage bootstrap-purge:analyze --routes=all
   *   Analyze all routes for unused assets.
   * @usage bootstrap-purge:analyze --routes=key --bootstrap-only
   *   Analyze key routes for Bootstrap assets only.
   */
  public function analyze(array $options = [
    'routes' => 'key',
    'mode' => 'static',
    'sample' => 10,
    'bootstrap-only' => FALSE,
  ]) {
    $this->output()->writeln('<info>Starting Bootstrap Purge analysis...</info>');

    // Prepare analysis options
    $analysis_options = [
      'mode' => $options['mode'],
    ];

    // Handle routes option
    if ($options['routes'] === 'all') {
      // This would need to be implemented to get all available routes
      $analysis_options['routes'] = ['key'];
      $this->output()->writeln('<comment>Using key routes (all routes analysis not yet implemented).</comment>');
    } elseif ($options['routes'] === 'key') {
      $analysis_options['routes'] = ['key'];
    } else {
      $analysis_options['routes'] = explode(',', $options['routes']);
    }

    // Run analysis
    $results = $this->assetManager->runAnalysis($analysis_options);

    if (empty($results)) {
      $this->output()->writeln('<comment>No purge candidates found.</comment>');
      return;
    }

    // Filter for Bootstrap assets if requested
    if ($options['bootstrap-only']) {
      $bootstrap_assets = $this->assetCollector->getBootstrapAssets();
      $bootstrap_keys = array_keys($bootstrap_assets);
      $results = array_intersect_key($results, array_flip($bootstrap_keys));
    }

    // Display results
    $this->displayAnalysisResults($results);

    $this->output()->writeln(sprintf(
      '<info>Analysis complete. Found %d purge candidates.</info>',
      count($results)
    ));
  }

  /**
   * Applies approved purges.
   *
   * @param string $asset
   *   Specific asset to apply, or 'all' for all approved assets.
   * @param array $options
   *   Command options.
   *
   * @option force
   *   Force application without confirmation.
   * @option confidence-threshold
   *   Minimum confidence score to apply.
   *
   * @command bootstrap-purge:apply
   * @aliases bp:apply
   * @usage bootstrap-purge:apply all
   *   Apply all approved purges.
   * @usage bootstrap-purge:apply bootstrap.css --force
   *   Apply specific asset purge without confirmation.
   */
  public function apply($asset = 'all', array $options = [
    'force' => FALSE,
    'confidence-threshold' => 80,
  ]) {
    $pending = $this->assetManager->getPendingCandidates();

    if (empty($pending)) {
      $this->output()->writeln('<comment>No pending purge candidates found.</comment>');
      return;
    }

    $to_apply = [];

    if ($asset === 'all') {
      // Filter by confidence threshold
      foreach ($pending as $asset_key => $candidate) {
        if (($candidate['confidence_score'] ?? 0) >= $options['confidence-threshold']) {
          $to_apply[$asset_key] = $candidate;
        }
      }
    } else {
      // Find specific asset
      foreach ($pending as $asset_key => $candidate) {
        if (strpos($asset_key, $asset) !== FALSE) {
          $to_apply[$asset_key] = $candidate;
        }
      }
    }

    if (empty($to_apply)) {
      $this->output()->writeln('<comment>No assets match the criteria for application.</comment>');
      return;
    }

    // Show what will be applied
    $this->output()->writeln('<info>Assets to be purged:</info>');
    foreach ($to_apply as $asset_key => $candidate) {
      $savings = $candidate['savings_bytes'] ?? 0;
      $confidence = $candidate['confidence_score'] ?? 0;
      $this->output()->writeln(sprintf(
        '  - %s (saves %s, confidence: %d%%)',
        $asset_key,
        format_size($savings),
        $confidence
      ));
    }

    // Confirm unless forced
    if (!$options['force']) {
      if (!$this->io()->confirm('Apply these purges?', FALSE)) {
        throw new UserAbortException();
      }
    }

    // Apply purges
    $applied = 0;
    $failed = 0;

    foreach ($to_apply as $asset_key => $candidate) {
      $this->output()->writeln(sprintf('<info>Applying purge for %s...</info>', $asset_key));

      if ($this->assetManager->approvePurge($asset_key)) {
        $applied++;
        $this->output()->writeln('<info>  ✓ Applied successfully</info>');
      } else {
        $failed++;
        $this->output()->writeln('<error>  ✗ Failed to apply</error>');
      }
    }

    $this->output()->writeln(sprintf(
      '<info>Purge application complete. Applied: %d, Failed: %d</info>',
      $applied,
      $failed
    ));
  }

  /**
   * Lists assets and their purge status.
   *
   * @param array $options
   *   Command options.
   *
   * @option status
   *   Filter by status: pending, approved, rejected, or all.
   * @option bootstrap-only
   *   Show only Bootstrap-related assets.
   * @option format
   *   Output format: table, json, or yaml.
   *
   * @command bootstrap-purge:list
   * @aliases bp:list
   * @usage bootstrap-purge:list --status=pending
   *   List pending purge candidates.
   * @usage bootstrap-purge:list --bootstrap-only --format=json
   *   List Bootstrap assets in JSON format.
   */
  public function listAssets(array $options = [
    'status' => 'all',
    'bootstrap-only' => FALSE,
    'format' => 'table',
  ]) {
    $pending = $this->assetManager->getPendingCandidates();
    $approved = $this->assetManager->getApprovedPurges();
    $rejected = $this->assetManager->getRejectedPurges();

    $assets = [];

    // Collect assets based on status filter
    switch ($options['status']) {
      case 'pending':
        $assets = array_map(function($item) { return $item + ['status' => 'pending']; }, $pending);
        break;
      case 'approved':
        $assets = array_map(function($item) { return $item + ['status' => 'approved']; }, $approved);
        break;
      case 'rejected':
        $assets = array_map(function($item) { return $item + ['status' => 'rejected']; }, $rejected);
        break;
      default:
        $assets = array_merge(
          array_map(function($item) { return $item + ['status' => 'pending']; }, $pending),
          array_map(function($item) { return $item + ['status' => 'approved']; }, $approved),
          array_map(function($item) { return $item + ['status' => 'rejected']; }, $rejected)
        );
    }

    // Filter for Bootstrap assets if requested
    if ($options['bootstrap-only']) {
      $bootstrap_assets = $this->assetCollector->getBootstrapAssets();
      $bootstrap_keys = array_keys($bootstrap_assets);
      $assets = array_intersect_key($assets, array_flip($bootstrap_keys));
    }

    if (empty($assets)) {
      $this->output()->writeln('<comment>No assets found matching the criteria.</comment>');
      return;
    }

    // Output in requested format
    switch ($options['format']) {
      case 'json':
        $this->output()->writeln(json_encode($assets, JSON_PRETTY_PRINT));
        break;
      case 'yaml':
        $this->output()->writeln(\Drupal\Component\Serialization\Yaml::encode($assets));
        break;
      default:
        $this->displayAssetsTable($assets);
    }
  }

  /**
   * Reverts a purged asset to its original version.
   *
   * @param string $asset
   *   Asset to revert, or 'all' for all approved assets.
   * @param array $options
   *   Command options.
   *
   * @option force
   *   Force revert without confirmation.
   *
   * @command bootstrap-purge:revert
   * @aliases bp:revert
   * @usage bootstrap-purge:revert bootstrap.css
   *   Revert specific asset.
   * @usage bootstrap-purge:revert all --force
   *   Revert all assets without confirmation.
   */
  public function revert($asset, array $options = ['force' => FALSE]) {
    $approved = $this->assetManager->getApprovedPurges();

    if (empty($approved)) {
      $this->output()->writeln('<comment>No approved purges found to revert.</comment>');
      return;
    }

    $to_revert = [];

    if ($asset === 'all') {
      $to_revert = array_keys($approved);
    } else {
      foreach ($approved as $asset_key => $purge_info) {
        if (strpos($asset_key, $asset) !== FALSE) {
          $to_revert[] = $asset_key;
        }
      }
    }

    if (empty($to_revert)) {
      $this->output()->writeln('<comment>No assets match the criteria for reversion.</comment>');
      return;
    }

    // Show what will be reverted
    $this->output()->writeln('<info>Assets to be reverted:</info>');
    foreach ($to_revert as $asset_key) {
      $this->output()->writeln('  - ' . $asset_key);
    }

    // Confirm unless forced
    if (!$options['force']) {
      if (!$this->io()->confirm('Revert these assets?', FALSE)) {
        throw new UserAbortException();
      }
    }

    // Revert assets
    $reverted = 0;
    $failed = 0;

    foreach ($to_revert as $asset_key) {
      $this->output()->writeln(sprintf('<info>Reverting %s...</info>', $asset_key));

      if ($this->assetManager->revertAsset($asset_key)) {
        $reverted++;
        $this->output()->writeln('<info>  ✓ Reverted successfully</info>');
      } else {
        $failed++;
        $this->output()->writeln('<error>  ✗ Failed to revert</error>');
      }
    }

    $this->output()->writeln(sprintf(
      '<info>Revert complete. Reverted: %d, Failed: %d</info>',
      $reverted,
      $failed
    ));
  }

  /**
   * Shows statistics about purged assets.
   *
   * @command bootstrap-purge:stats
   * @aliases bp:stats
   * @usage bootstrap-purge:stats
   *   Show purge statistics.
   */
  public function stats() {
    $stats = $this->assetManager->getDashboardStats();

    $this->output()->writeln('<info>Bootstrap Purge Statistics</info>');
    $this->output()->writeln('================================');
    $this->output()->writeln(sprintf('Total Assets: %d', $stats['total_assets']));
    $this->output()->writeln(sprintf('Bootstrap Assets: %d', $stats['bootstrap_assets']));
    $this->output()->writeln(sprintf('Purged Assets: %d', $stats['total_purged']));
    $this->output()->writeln(sprintf('Bytes Saved: %s', format_size($stats['bytes_saved'])));
    $this->output()->writeln(sprintf('Percent Saved: %.2f%%', $stats['percent_saved']));

    if ($stats['last_analysis']) {
      $this->output()->writeln(sprintf('Last Analysis: %s', date('Y-m-d H:i:s', $stats['last_analysis'])));
    }

    if ($stats['last_purge']) {
      $this->output()->writeln(sprintf('Last Purge: %s', date('Y-m-d H:i:s', $stats['last_purge'])));
    }
  }

  /**
   * Clears all analysis data.
   *
   * @param array $options
   *   Command options.
   *
   * @option force
   *   Force clear without confirmation.
   *
   * @command bootstrap-purge:clear
   * @aliases bp:clear
   * @usage bootstrap-purge:clear --force
   *   Clear all analysis data without confirmation.
   */
  public function clear(array $options = ['force' => FALSE]) {
    if (!$options['force']) {
      if (!$this->io()->confirm('This will clear all analysis data and revert all purges. Continue?', FALSE)) {
        throw new UserAbortException();
      }
    }

    $this->assetManager->clearAnalysisData();
    $this->output()->writeln('<info>All analysis data cleared.</info>');
  }

  /**
   * Displays analysis results in a table format.
   *
   * @param array $results
   *   Analysis results.
   */
  protected function displayAnalysisResults(array $results) {
    $rows = [];
    foreach ($results as $asset_key => $analysis) {
      $rows[] = [
        $asset_key,
        format_size($analysis['original_size'] ?? 0),
        format_size($analysis['estimated_purged_size'] ?? 0),
        format_size($analysis['savings_bytes'] ?? 0),
        sprintf('%.1f%%', $analysis['savings_percent'] ?? 0),
        sprintf('%d%%', $analysis['confidence_score'] ?? 0),
      ];
    }

    $this->io()->table([
      'Asset',
      'Original Size',
      'Purged Size',
      'Savings',
      'Savings %',
      'Confidence',
    ], $rows);
  }

  /**
   * Displays assets in a table format.
   *
   * @param array $assets
   *   Assets data.
   */
  protected function displayAssetsTable(array $assets) {
    $rows = [];
    foreach ($assets as $asset_key => $asset_data) {
      $rows[] = [
        $asset_key,
        $asset_data['status'] ?? 'unknown',
        format_size($asset_data['original_size'] ?? 0),
        format_size($asset_data['estimated_purged_size'] ?? $asset_data['size'] ?? 0),
        sprintf('%d%%', $asset_data['confidence_score'] ?? 0),
      ];
    }

    $this->io()->table([
      'Asset',
      'Status',
      'Original Size',
      'Purged Size',
      'Confidence',
    ], $rows);
  }

}