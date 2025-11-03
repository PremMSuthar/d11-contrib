<?php

namespace Drupal\twig_debug_switcher\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to modify Twig debug settings.
 */
class TwigDebugEventSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a TwigDebugEventSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

  /**
   * Modifies Twig environment based on module settings.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    // Only process the main request
    if (!$event->isMainRequest()) {
      return;
    }

    try {
      $config = $this->configFactory->get('twig_debug_switcher.settings');
      $debug_enabled = (bool) $config->get('debug_enabled');

      if (\Drupal::hasService('twig')) {
        $twig = \Drupal::service('twig');
        
        // The Twig service in Drupal IS the environment
        // Use reflection to modify the debug properties
        $reflection = new \ReflectionClass($twig);
        
        // Set debug property
        if ($reflection->hasProperty('debug')) {
          $debugProperty = $reflection->getProperty('debug');
          $debugProperty->setAccessible(TRUE);
          $debugProperty->setValue($twig, $debug_enabled);
        }
        
        // Set auto_reload property
        if ($reflection->hasProperty('autoReload')) {
          $autoReloadProperty = $reflection->getProperty('autoReload');
          $autoReloadProperty->setAccessible(TRUE);
          $autoReloadProperty->setValue($twig, $debug_enabled);
        }
      }
    }
    catch (\Exception $e) {
      // Silently ignore errors to avoid breaking the site
    }
  }

}