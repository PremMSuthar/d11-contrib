<?php

namespace Drupal\Tests\drupal_settings_viewer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Drupal Settings Viewer functionality.
 *
 * @group drupal_settings_viewer
 */
class SettingsViewerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'drupal_settings_viewer',
    'toolbar',
    'user',
  ];

  /**
   * A user with permission to access the settings viewer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * A user without permission to access the settings viewer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $unprivilegedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create users.
    $this->privilegedUser = $this->drupalCreateUser([
      'access toolbar',
      'access drupal settings viewer',
    ]);

    $this->unprivilegedUser = $this->drupalCreateUser([
      'access toolbar',
    ]);
  }

  /**
   * Tests access to the settings viewer page.
   */
  public function testSettingsViewerAccess() {
    // Test that unprivileged user cannot access the page.
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/admin/development/settings-viewer');
    $this->assertSession()->statusCodeEquals(403);

    // Test that privileged user can access the page.
    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('/admin/development/settings-viewer');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Drupal Settings Viewer');
  }

  /**
   * Tests toolbar integration.
   */
  public function testToolbarIntegration() {
    // Test that unprivileged user doesn't see the toolbar item.
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('<front>');
    $this->assertSession()->linkNotExists('Settings');

    // Test that privileged user sees the toolbar item.
    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('<front>');
    $this->assertSession()->linkExists('Settings');
    
    // Test that clicking the toolbar item goes to the correct page.
    $this->clickLink('Settings');
    $this->assertSession()->addressEquals('/admin/development/settings-viewer');
  }

  /**
   * Tests the settings viewer page content.
   */
  public function testSettingsViewerContent() {
    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('/admin/development/settings-viewer');
    
    // Check that the page contains expected elements.
    $this->assertSession()->elementExists('css', '#drupal-settings-viewer-container');
    $this->assertSession()->pageTextContains('Loading drupalSettings...');
  }

}