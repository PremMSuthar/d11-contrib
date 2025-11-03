<?php

namespace Drupal\bootstrap_purge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for managing whitelist patterns.
 */
class WhitelistManager {

  /**
   * The config factory.
   */
  protected $configFactory;

  /**
   * Constructs a WhitelistManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Gets all whitelist patterns.
   */
  public function getAllPatterns() {
    $config = $this->configFactory->get('bootstrap_purge.settings');
    $whitelist_config = $this->configFactory->get('bootstrap_purge.whitelist');
    
    $patterns = [];
    $patterns = array_merge($patterns, $config->get('whitelist_patterns') ?: []);
    $patterns = array_merge($patterns, $config->get('bootstrap_whitelist') ?: []);
    $patterns = array_merge($patterns, $whitelist_config->get('global_patterns') ?: []);
    $patterns = array_merge($patterns, $whitelist_config->get('selector_patterns') ?: []);
    
    return array_unique($patterns);
  }

  /**
   * Checks if a selector matches any whitelist pattern.
   */
  public function isWhitelisted($selector, $theme = NULL) {
    $patterns = $this->getAllPatterns();
    
    if ($theme) {
      $whitelist_config = $this->configFactory->get('bootstrap_purge.whitelist');
      $theme_patterns = $whitelist_config->get('theme_patterns.' . $theme) ?: [];
      $patterns = array_merge($patterns, $theme_patterns);
    }
    
    foreach ($patterns as $pattern) {
      if ($this->matchesPattern($selector, $pattern)) {
        return TRUE;
      }
    }
    
    return FALSE;
  }

  /**
   * Checks if a selector matches a specific pattern.
   */
  protected function matchesPattern($selector, $pattern) {
    if (preg_match('/^\/.*\/$/', $pattern)) {
      return preg_match($pattern, $selector);
    }
    
    return fnmatch($pattern, $selector);
  }

}