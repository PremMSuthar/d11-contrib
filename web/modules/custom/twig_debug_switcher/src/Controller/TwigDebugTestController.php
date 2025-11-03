<?php

namespace Drupal\twig_debug_switcher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\twig_debug_switcher\TwigDebugManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for testing Twig debug functionality.
 */
class TwigDebugTestController extends ControllerBase {

  /**
   * The Twig debug manager service.
   *
   * @var \Drupal\twig_debug_switcher\TwigDebugManager
   */
  protected $twigDebugManager;

  /**
   * Constructs a TwigDebugTestController object.
   *
   * @param \Drupal\twig_debug_switcher\TwigDebugManager $twig_debug_manager
   *   The Twig debug manager service.
   */
  public function __construct(TwigDebugManager $twig_debug_manager) {
    $this->twigDebugManager = $twig_debug_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig_debug_switcher.twig_debug_manager')
    );
  }

  /**
   * Test page to show Twig debug status.
   *
   * @return array
   *   A render array.
   */
  public function testPage() {
    $current_status = $this->twigDebugManager->getCurrentTwigDebugStatus();
    $module_config = $this->config('twig_debug_switcher.settings');
    $module_debug = $module_config->get('debug_enabled');

    // Get Twig environment details
    $twig_details = [];
    if (\Drupal::hasService('twig')) {
      try {
        $twig = \Drupal::service('twig');
        
        $twig_details = [
          'debug' => method_exists($twig, 'isDebug') ? $twig->isDebug() : 'Unknown',
          'auto_reload' => method_exists($twig, 'isAutoReload') ? $twig->isAutoReload() : 'Unknown',
          'cache' => $twig->getCache() ? 'Enabled' : 'Disabled',
        ];
      }
      catch (\Exception $e) {
        $twig_details['error'] = $e->getMessage();
      }
    }

    return [
      '#theme' => 'twig_debug_test_page',
      '#current_status' => $current_status,
      '#module_debug' => $module_debug,
      '#twig_details' => $twig_details,
      '#cache' => [
        'max-age' => 0, // Don't cache this page
      ],
    ];
  }

}