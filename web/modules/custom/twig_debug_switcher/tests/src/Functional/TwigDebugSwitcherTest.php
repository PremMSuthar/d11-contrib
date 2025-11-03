<?php

namespace Drupal\Tests\twig_debug_switcher\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Twig Debug Switcher functionality.
 *
 * @group twig_debug_switcher
 */
class TwigDebugSwitcherTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['twig_debug_switcher'];

  /**
   * A user with permission to administer twig debug switcher.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer twig debug switcher',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the Twig Debug Switcher settings form.
   */
  public function testTwigDebugSwitcherForm() {
    // Visit the settings page.
    $this->drupalGet('admin/config/development/twig-debug-switcher');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Twig Debug Switcher');
    $this->assertSession()->pageTextContains('Warning: This module should only be used in development environments');

    // Test enabling debug mode.
    $this->submitForm(['debug_enabled' => TRUE], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Test disabling debug mode.
    $this->submitForm(['debug_enabled' => FALSE], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

  /**
   * Tests access control for the settings form.
   */
  public function testAccessControl() {
    // Log out the admin user.
    $this->drupalLogout();

    // Create a user without the required permission.
    $user = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($user);

    // Try to access the settings page.
    $this->drupalGet('admin/config/development/twig-debug-switcher');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the Twig Debug Manager service.
   */
  public function testTwigDebugManager() {
    $manager = \Drupal::service('twig_debug_switcher.twig_debug_manager');

    // Test enabling debug.
    $result = $manager->enableDebug();
    $this->assertTrue($result);
    $this->assertTrue($manager->isDebugEnabled());

    // Test disabling debug.
    $result = $manager->disableDebug();
    $this->assertTrue($result);
    $this->assertFalse($manager->isDebugEnabled());
  }

}