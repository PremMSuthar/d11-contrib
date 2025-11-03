<?php

namespace Drupal\bootstrap_purge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\bootstrap_purge\Service\AssetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Bootstrap Purge admin pages.
 */
class BootstrapPurgeController extends ControllerBase {

  /**
   * The asset manager service.
   *
   * @var \Drupal\bootstrap_purge\Service\AssetManager
   */
  protected $assetManager;

  /**
   * Constructs a BootstrapPurgeController object.
   *
   * @param \Drupal\bootstrap_purge\Service\AssetManager $asset_manager
   *   The asset manager service.
   */
  public function __construct(AssetManager $asset_manager) {
    $this->assetManager = $asset_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bootstrap_purge.asset_manager')
    );
  }

  /**
   * Dashboard page.
   *
   * @return array
   *   Render array for the dashboard.
   */
  public function dashboard() {
    $stats = $this->assetManager->getDashboardStats();
    $pending = $this->assetManager->getPendingCandidates();
    $approved = $this->assetManager->getApprovedPurges();
    
    $recent_purges = array_slice($approved, -5, 5, TRUE);
    
    $build = [
      '#theme' => 'bootstrap_purge_dashboard',
      '#stats' => $stats,
      '#recent_purges' => $recent_purges,
      '#attached' => [
        'library' => ['bootstrap_purge/admin_ui'],
      ],
    ];
    
    // Add action buttons
    $build['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['bootstrap-purge-actions']],
    ];
    
    $build['actions']['run_analysis'] = [
      '#type' => 'link',
      '#title' => $this->t('Run Analysis'),
      '#url' => Url::fromRoute('bootstrap_purge.run_analysis'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];
    
    $build['actions']['view_assets'] = [
      '#type' => 'link',
      '#title' => $this->t('Manage Assets'),
      '#url' => Url::fromRoute('bootstrap_purge.assets'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];
    
    // Add pending candidates summary
    if (!empty($pending)) {
      $build['pending_summary'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['bootstrap-purge-pending']],
      ];
      
      $build['pending_summary']['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Pending Purge Candidates'),
      ];
      
      $build['pending_summary']['count'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->formatPlural(
          count($pending),
          'There is 1 asset waiting for review.',
          'There are @count assets waiting for review.'
        ),
      ];
      
      $build['pending_summary']['link'] = [
        '#type' => 'link',
        '#title' => $this->t('Review Candidates'),
        '#url' => Url::fromRoute('bootstrap_purge.assets'),
        '#attributes' => [
          'class' => ['button', 'button--small'],
        ],
      ];
    }
    
    return $build;
  }

  /**
   * Assets list page.
   *
   * @return array
   *   Render array for the assets list.
   */
  public function assetsList() {
    $pending = $this->assetManager->getPendingCandidates();
    $approved = $this->assetManager->getApprovedPurges();
    $rejected = $this->assetManager->getRejectedPurges();
    
    $build = [];
    
    // Pending candidates table
    if (!empty($pending)) {
      $build['pending'] = [
        '#type' => 'details',
        '#title' => $this->t('Pending Candidates (@count)', ['@count' => count($pending)]),
        '#open' => TRUE,
      ];
      
      $build['pending']['table'] = $this->buildAssetsTable($pending, 'pending');
    }
    
    // Approved purges table
    if (!empty($approved)) {
      $build['approved'] = [
        '#type' => 'details',
        '#title' => $this->t('Approved Purges (@count)', ['@count' => count($approved)]),
        '#open' => FALSE,
      ];
      
      $build['approved']['table'] = $this->buildAssetsTable($approved, 'approved');
    }
    
    // Rejected purges table
    if (!empty($rejected)) {
      $build['rejected'] = [
        '#type' => 'details',
        '#title' => $this->t('Rejected Purges (@count)', ['@count' => count($rejected)]),
        '#open' => FALSE,
      ];
      
      $build['rejected']['table'] = $this->buildAssetsTable($rejected, 'rejected');
    }
    
    if (empty($pending) && empty($approved) && empty($rejected)) {
      $build['empty'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No assets have been analyzed yet. <a href="@url">Run analysis</a> to get started.', [
          '@url' => Url::fromRoute('bootstrap_purge.run_analysis')->toString(),
        ]),
      ];
    }
    
    $build['#attached']['library'][] = 'bootstrap_purge/admin_ui';
    
    return $build;
  }

  /**
   * Builds an assets table.
   *
   * @param array $assets
   *   Array of assets.
   * @param string $status
   *   The status (pending, approved, rejected).
   *
   * @return array
   *   Table render array.
   */
  protected function buildAssetsTable(array $assets, $status) {
    $header = [
      $this->t('Asset'),
      $this->t('Type'),
      $this->t('Original Size'),
      $this->t('Purged Size'),
      $this->t('Savings'),
      $this->t('Confidence'),
      $this->t('Actions'),
    ];
    
    $rows = [];
    foreach ($assets as $asset_key => $asset_data) {
      $asset_info = $this->assetManager->getAssetInfo($asset_key);
      
      $original_size = $asset_data['original_size'] ?? 0;
      $purged_size = $asset_data['estimated_purged_size'] ?? $asset_data['size'] ?? 0;
      $savings = $original_size - $purged_size;
      $savings_percent = $original_size > 0 ? round(($savings / $original_size) * 100, 1) : 0;
      
      $actions = [];
      
      // Diff link
      $actions[] = [
        '#type' => 'link',
        '#title' => $this->t('Diff'),
        '#url' => Url::fromRoute('bootstrap_purge.asset_diff', ['asset_id' => base64_encode($asset_key)]),
        '#attributes' => ['class' => ['button', 'button--small']],
      ];
      
      if ($status === 'pending') {
        // Approve link
        $actions[] = [
          '#type' => 'link',
          '#title' => $this->t('Approve'),
          '#url' => Url::fromRoute('bootstrap_purge.asset_approve', ['asset_id' => base64_encode($asset_key)]),
          '#attributes' => [
            'class' => ['button', 'button--small', 'button--primary'],
            'data-confirm' => $this->t('Are you sure you want to approve this purge?'),
          ],
        ];
        
        // Reject link
        $actions[] = [
          '#type' => 'link',
          '#title' => $this->t('Reject'),
          '#url' => Url::fromRoute('bootstrap_purge.asset_reject', ['asset_id' => base64_encode($asset_key)]),
          '#attributes' => [
            'class' => ['button', 'button--small', 'button--danger'],
            'data-confirm' => $this->t('Are you sure you want to reject this purge?'),
          ],
        ];
      }
      
      if ($status === 'approved') {
        // Preview link
        $actions[] = [
          '#type' => 'link',
          '#title' => $this->t('Preview'),
          '#url' => Url::fromRoute('bootstrap_purge.preview', ['asset_id' => base64_encode($asset_key)]),
          '#attributes' => ['class' => ['button', 'button--small']],
        ];
        
        // Revert link
        $actions[] = [
          '#type' => 'link',
          '#title' => $this->t('Revert'),
          '#url' => Url::fromRoute('bootstrap_purge.asset_revert', ['asset_id' => base64_encode($asset_key)]),
          '#attributes' => [
            'class' => ['button', 'button--small', 'button--danger'],
            'data-confirm' => $this->t('Are you sure you want to revert this purge?'),
          ],
        ];
      }
      
      $rows[] = [
        $asset_info ? $asset_info['file_path'] : $asset_key,
        $asset_info ? $asset_info['type'] : 'unknown',
        format_size($original_size),
        format_size($purged_size),
        format_size($savings) . ' (' . $savings_percent . '%)',
        ($asset_data['confidence_score'] ?? 0) . '%',
        ['data' => $actions],
      ];
    }
    
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No assets found.'),
    ];
  }

  /**
   * Asset diff page.
   *
   * @param string $asset_id
   *   Base64 encoded asset key.
   *
   * @return array
   *   Render array for the diff page.
   */
  public function assetDiff($asset_id) {
    $asset_key = base64_decode($asset_id);
    $asset_info = $this->assetManager->getAssetInfo($asset_key);
    $analysis = $this->assetManager->getAssetAnalysis($asset_key);
    
    if (!$asset_info || !$analysis) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    
    $original_content = file_get_contents($asset_info['full_path']);
    
    // Generate purged content for preview
    $purged_content = $original_content;
    if ($asset_info['type'] === 'css' && !empty($analysis['unused_selectors'])) {
      // Simple removal for preview
      foreach ($analysis['unused_selectors'] as $selector) {
        $pattern = '/[^{}]*' . preg_quote($selector, '/') . '[^{}]*\{[^}]*\}/s';
        $purged_content = preg_replace($pattern, '', $purged_content);
      }
    }
    
    $build = [
      '#theme' => 'bootstrap_purge_asset_diff',
      '#original_content' => $original_content,
      '#purged_content' => $purged_content,
      '#removed_selectors' => $analysis['unused_selectors'] ?? [],
      '#file_path' => $asset_info['file_path'],
      '#attached' => [
        'library' => ['bootstrap_purge/admin_ui'],
      ],
    ];
    
    return $build;
  }

  /**
   * Approves an asset purge.
   *
   * @param string $asset_id
   *   Base64 encoded asset key.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function approveAsset($asset_id) {
    $asset_key = base64_decode($asset_id);
    
    if ($this->assetManager->approvePurge($asset_key)) {
      $this->messenger()->addStatus($this->t('Asset purge approved successfully.'));
    } else {
      $this->messenger()->addError($this->t('Failed to approve asset purge.'));
    }
    
    return new RedirectResponse(Url::fromRoute('bootstrap_purge.assets')->toString());
  }

  /**
   * Rejects an asset purge.
   *
   * @param string $asset_id
   *   Base64 encoded asset key.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function rejectAsset($asset_id) {
    $asset_key = base64_decode($asset_id);
    
    if ($this->assetManager->rejectPurge($asset_key)) {
      $this->messenger()->addStatus($this->t('Asset purge rejected.'));
    } else {
      $this->messenger()->addError($this->t('Failed to reject asset purge.'));
    }
    
    return new RedirectResponse(Url::fromRoute('bootstrap_purge.assets')->toString());
  }

  /**
   * Previews a purged asset.
   *
   * @param string $asset_id
   *   Base64 encoded asset key.
   *
   * @return array
   *   Render array for the preview.
   */
  public function previewAsset($asset_id) {
    $asset_key = base64_decode($asset_id);
    $approved = $this->assetManager->getApprovedPurges();
    
    if (!isset($approved[$asset_key])) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    
    $purged_info = $approved[$asset_key];
    $purged_content = '';
    
    if (isset($purged_info['path'])) {
      $purged_file_path = \Drupal::service('file_system')->realpath($purged_info['path']);
      if (file_exists($purged_file_path)) {
        $purged_content = file_get_contents($purged_file_path);
      }
    }
    
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['bootstrap-purge-preview']],
    ];
    
    $build['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['purge-info']],
      '#value' => $this->t('Purged file size: @size', [
        '@size' => format_size($purged_info['size'] ?? 0),
      ]),
    ];
    
    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#attributes' => ['class' => ['purged-content']],
      '#value' => htmlspecialchars($purged_content),
    ];
    
    return $build;
  }

}