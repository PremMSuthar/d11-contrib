<?php

namespace Drupal\drupal_settings_viewer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the Drupal Settings Viewer.
 */
class SettingsViewerController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a SettingsViewerController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(RendererInterface $renderer, AccountInterface $current_user) {
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * Displays the Drupal settings viewer page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for the settings viewer page.
   */
  public function viewSettings(Request $request) {
    // Check access permission.
    if (!$this->currentUser->hasPermission('access drupal settings viewer')) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    $build = [
      '#theme' => 'drupal_settings_viewer',
      '#attached' => [
        'library' => [
          'drupal_settings_viewer/settings_viewer',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Returns the current drupalSettings as JSON.
   *
   * This endpoint provides a way to fetch the current page's drupalSettings
   * via AJAX for display in the settings viewer.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing the drupalSettings.
   */
  public function getSettingsJson(Request $request) {
    // Check access permission.
    if (!$this->currentUser->hasPermission('access drupal settings viewer')) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // Get the current page's drupalSettings.
    // Note: This will be populated by the JavaScript on the frontend.
    return new JsonResponse([
      'message' => 'Settings will be populated by JavaScript',
      'timestamp' => time(),
    ]);
  }

}