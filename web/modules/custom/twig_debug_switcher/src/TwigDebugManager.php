<?php

namespace Drupal\twig_debug_switcher;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Service for managing Twig debug settings.
 */
class TwigDebugManager {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a TwigDebugManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The Drupal kernel.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DrupalKernelInterface $kernel, MessengerInterface $messenger, CacheBackendInterface $cache) {
    $this->configFactory = $config_factory;
    $this->kernel = $kernel;
    $this->messenger = $messenger;
    $this->cache = $cache;
  }

  /**
   * Checks if Twig debug is currently enabled.
   *
   * @return bool
   *   TRUE if Twig debug is enabled, FALSE otherwise.
   */
  public function isDebugEnabled() {
    $twig_config = $this->configFactory->get('twig_debug_switcher.settings');
    return (bool) $twig_config->get('debug_enabled');
  }

  /**
   * Enables Twig debug mode.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function enableDebug() {
    try {
      // Update our module's configuration
      $config = $this->configFactory->getEditable('twig_debug_switcher.settings');
      $config->set('debug_enabled', TRUE)->save();

      // Update development settings
      $this->updateDevelopmentSettings(TRUE);

      // Clear relevant caches
      $this->clearCaches();

      $this->messenger->addStatus($this->t('Twig debug mode has been enabled. Cache has been cleared.'));
      return TRUE;
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('Failed to enable Twig debug mode: @error', ['@error' => $e->getMessage()]));
      return FALSE;
    }
  }

  /**
   * Disables Twig debug mode.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function disableDebug() {
    try {
      // Update our module's configuration
      $config = $this->configFactory->getEditable('twig_debug_switcher.settings');
      $config->set('debug_enabled', FALSE)->save();

      // Update development settings
      $this->updateDevelopmentSettings(FALSE);

      // Clear relevant caches
      $this->clearCaches();

      $this->messenger->addStatus($this->t('Twig debug mode has been disabled. Cache has been cleared.'));
      return TRUE;
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('Failed to disable Twig debug mode: @error', ['@error' => $e->getMessage()]));
      return FALSE;
    }
  }

  /**
   * Updates the development settings configuration.
   *
   * @param bool $debug_enabled
   *   Whether to enable or disable debug mode.
   */
  protected function updateDevelopmentSettings($debug_enabled) {
    // Write to a temporary state that our event subscriber can read
    \Drupal::state()->set('twig_debug_switcher.debug_enabled', $debug_enabled);
    
    // Try to update the Twig environment directly
    $this->updateTwigEnvironmentDirect($debug_enabled);
  }

  /**
   * Updates the Twig environment directly.
   *
   * @param bool $debug_enabled
   *   Whether to enable or disable debug mode.
   */
  protected function updateTwigEnvironmentDirect($debug_enabled) {
    try {
      if (\Drupal::hasService('twig')) {
        $twig = \Drupal::service('twig');
        
        // The Twig service in Drupal IS the environment
        // Use reflection to modify the debug property
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
        
        // Clear the template cache if disabling debug
        if (!$debug_enabled && method_exists($twig, 'clearTemplateCache')) {
          $twig->clearTemplateCache();
        }
      }
    }
    catch (\Exception $e) {
      // Log the error but don't break the functionality
      \Drupal::logger('twig_debug_switcher')->error('Failed to update Twig environment: @error', ['@error' => $e->getMessage()]);
    }
  }

  /**
   * Clears relevant caches to apply Twig changes.
   */
  protected function clearCaches() {
    // Clear Twig cache
    if (\Drupal::hasService('twig')) {
      try {
        $twig = \Drupal::service('twig');
        if (method_exists($twig, 'invalidate')) {
          $twig->invalidate();
        }
      }
      catch (\Exception $e) {
        // Ignore errors
      }
    }
    
    // Clear render cache
    \Drupal::cache('render')->deleteAll();
    
    // Clear discovery cache
    \Drupal::cache('discovery')->deleteAll();
    
    // Clear container cache to rebuild with new parameters
    \Drupal::service('kernel')->invalidateContainer();
  }

  /**
   * Gets the current Twig debug status from the actual Twig environment.
   *
   * @return bool
   *   TRUE if Twig debug is currently active, FALSE otherwise.
   */
  public function getCurrentTwigDebugStatus() {
    try {
      // Check the actual Twig environment first
      if (\Drupal::hasService('twig')) {
        $twig = \Drupal::service('twig');
        
        if (method_exists($twig, 'isDebug')) {
          return $twig->isDebug();
        }
      }
      
      // Fallback to our module's configuration
      $module_config = $this->configFactory->get('twig_debug_switcher.settings');
      return (bool) $module_config->get('debug_enabled');
    }
    catch (\Exception $e) {
      // Final fallback to checking our module config
      $config = $this->configFactory->get('twig_debug_switcher.settings');
      return (bool) $config->get('debug_enabled');
    }
  }

}