<?php

namespace Drupal\site_analyzer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a Site Analyzer navigation block.
 *
 * @Block(
 *   id = "site_analyzer_menu",
 *   admin_label = @Translation("Site Analyzer Menu"),
 *   category = @Translation("Site Analyzer")
 * )
 */
class SiteAnalyzerMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_items = [
      'dashboard' => [
        'title' => $this->t('Dashboard'),
        'route' => 'site_analyzer.dashboard',
        'description' => $this->t('Overview and summary'),
        'icon' => '📊',
      ],
      'system' => [
        'title' => $this->t('System Analysis'),
        'route' => 'site_analyzer.system_analysis',
        'description' => $this->t('System requirements and configuration'),
        'icon' => '⚙️',
      ],
      'modules' => [
        'title' => $this->t('Module Analysis'),
        'route' => 'site_analyzer.module_analysis',
        'description' => $this->t('Module analysis and D11 readiness'),
        'icon' => '🧩',
      ],
      'themes' => [
        'title' => $this->t('Theme Analysis'),
        'route' => 'site_analyzer.theme_analysis',
        'description' => $this->t('Theme compatibility and overrides'),
        'icon' => '🎨',
      ],
      'content' => [
        'title' => $this->t('Content Analysis'),
        'route' => 'site_analyzer.content_analysis',
        'description' => $this->t('Content structure and fields'),
        'icon' => '📝',
      ],
      'database' => [
        'title' => $this->t('Database Analysis'),
        'route' => 'site_analyzer.database_analysis',
        'description' => $this->t('Database performance and structure'),
        'icon' => '🗄️',
      ],
      'security' => [
        'title' => $this->t('Security Analysis'),
        'route' => 'site_analyzer.security_analysis',
        'description' => $this->t('Security assessment and audit'),
        'icon' => '🔒',
      ],
      'performance' => [
        'title' => $this->t('Performance Analysis'),
        'route' => 'site_analyzer.performance_analysis',
        'description' => $this->t('Performance metrics and optimization'),
        'icon' => '⚡',
      ],
      'upgrade' => [
        'title' => $this->t('Upgrade Readiness'),
        'route' => 'site_analyzer.upgrade_report',
        'description' => $this->t('Drupal upgrade readiness report'),
        'icon' => '🚀',
      ],
    ];

    $build = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#attributes' => ['class' => ['site-analyzer-menu']],
      '#items' => [],
    ];

    foreach ($menu_items as $key => $item) {
      $link = Link::fromTextAndUrl(
        $item['icon'] . ' ' . $item['title'],
        Url::fromRoute($item['route'])
      );
      
      $build['#items'][] = [
        'data' => [
          'link' => $link,
          'description' => [
            '#markup' => '<div class="description">' . $item['description'] . '</div>',
          ],
        ],
        'class' => ['menu-item', 'menu-item--' . $key],
      ];
    }

    $build['#attached']['library'][] = 'site_analyzer/dashboard';

    return $build;
  }

}