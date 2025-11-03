<?php

namespace Drupal\twig_debug_switcher\TwigExtension;

use Drupal\Core\Config\ConfigFactoryInterface;
use Twig\Extension\AbstractExtension;

/**
 * Twig extension for debug functionality.
 */
class DebugExtension extends AbstractExtension {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DebugExtension object.
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
  public function getName() {
    return 'twig_debug_switcher_debug';
  }

  /**
   * Checks if debug mode is enabled.
   *
   * @return bool
   *   TRUE if debug is enabled.
   */
  public function isDebugEnabled() {
    $config = $this->configFactory->get('twig_debug_switcher.settings');
    return (bool) $config->get('debug_enabled');
  }

}