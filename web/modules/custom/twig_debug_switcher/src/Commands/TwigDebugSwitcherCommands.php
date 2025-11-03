<?php

namespace Drupal\twig_debug_switcher\Commands;

use Drupal\twig_debug_switcher\TwigDebugManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for Twig Debug Switcher.
 */
final class TwigDebugSwitcherCommands extends DrushCommands {

  /**
   * The Twig debug manager service.
   *
   * @var \Drupal\twig_debug_switcher\TwigDebugManager
   */
  protected $twigDebugManager;

  /**
   * Constructs a TwigDebugSwitcherCommands object.
   *
   * @param \Drupal\twig_debug_switcher\TwigDebugManager $twig_debug_manager
   *   The Twig debug manager service.
   */
  public function __construct(TwigDebugManager $twig_debug_manager) {
    parent::__construct();
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
   * Enable Twig debug mode.
   */
  #[CLI\Command(name: 'twig-debug:enable', aliases: ['tde'])]
  #[CLI\Help(description: 'Enable Twig debug mode for development.')]
  #[CLI\Usage(name: 'drush twig-debug:enable', description: 'Enable Twig debug mode')]
  public function enableDebug() {
    if ($this->twigDebugManager->enableDebug()) {
      $this->output()->writeln('<info>Twig debug mode has been enabled.</info>');
    }
    else {
      $this->output()->writeln('<error>Failed to enable Twig debug mode.</error>');
    }
  }

  /**
   * Disable Twig debug mode.
   */
  #[CLI\Command(name: 'twig-debug:disable', aliases: ['tdd'])]
  #[CLI\Help(description: 'Disable Twig debug mode.')]
  #[CLI\Usage(name: 'drush twig-debug:disable', description: 'Disable Twig debug mode')]
  public function disableDebug() {
    if ($this->twigDebugManager->disableDebug()) {
      $this->output()->writeln('<info>Twig debug mode has been disabled.</info>');
    }
    else {
      $this->output()->writeln('<error>Failed to disable Twig debug mode.</error>');
    }
  }

  /**
   * Show current Twig debug status.
   */
  #[CLI\Command(name: 'twig-debug:status', aliases: ['tds'])]
  #[CLI\Help(description: 'Show current Twig debug status.')]
  #[CLI\Usage(name: 'drush twig-debug:status', description: 'Show current Twig debug status')]
  public function debugStatus() {
    $status = $this->twigDebugManager->getCurrentTwigDebugStatus();
    $status_text = $status ? 'enabled' : 'disabled';
    $this->output()->writeln("Twig debug mode is currently <info>{$status_text}</info>.");
  }

}