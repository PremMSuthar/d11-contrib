<?php

namespace Drupal\bootstrap_purge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\bootstrap_purge\Service\RuntimeDataCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for runtime data collection.
 */
class RuntimeDataController extends ControllerBase {

  /**
   * The runtime data collector service.
   */
  protected $runtimeDataCollector;

  /**
   * Constructs a RuntimeDataController object.
   */
  public function __construct(RuntimeDataCollector $runtime_data_collector) {
    $this->runtimeDataCollector = $runtime_data_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bootstrap_purge.runtime_collector')
    );
  }

  /**
   * Collects runtime usage data.
   */
  public function collect(Request $request) {
    $config = $this->config('bootstrap_purge.settings');
    
    if (!$config->get('enabled') || !$config->get('runtime_collection_enabled')) {
      return new JsonResponse(['status' => 'disabled'], 200);
    }

    $content = $request->getContent();
    if (empty($content)) {
      return new JsonResponse(['error' => 'No data provided'], 400);
    }

    $data = json_decode($content, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      return new JsonResponse(['error' => 'Invalid JSON'], 400);
    }

    if ($this->runtimeDataCollector->storeRuntimeData($data)) {
      return new JsonResponse(['status' => 'success'], 200);
    } else {
      return new JsonResponse(['error' => 'Failed to store data'], 500);
    }
  }

}