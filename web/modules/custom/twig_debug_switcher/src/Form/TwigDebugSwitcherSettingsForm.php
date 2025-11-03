<?php

namespace Drupal\twig_debug_switcher\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\twig_debug_switcher\TwigDebugManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Twig Debug Switcher settings for this site.
 */
class TwigDebugSwitcherSettingsForm extends ConfigFormBase {

  /**
   * The Twig debug manager service.
   *
   * @var \Drupal\twig_debug_switcher\TwigDebugManager
   */
  protected $twigDebugManager;

  /**
   * Constructs a TwigDebugSwitcherSettingsForm object.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twig_debug_switcher_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['twig_debug_switcher.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twig_debug_switcher.settings');
    $current_status = $this->twigDebugManager->getCurrentTwigDebugStatus();

    // Attach CSS library
    $form['#attached']['library'][] = 'twig_debug_switcher/admin';
    $form['#attributes']['class'][] = 'twig-debug-switcher-form';

    // Warning message for production environments
    $form['warning'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--warning">' . 
        $this->t('<strong>Warning:</strong> This module should only be used in development environments. Do not use in production!') . 
        '</div>',
    ];

    // Current status display
    $status_text = $current_status ? $this->t('Enabled') : $this->t('Disabled');
    $status_class = $current_status ? 'color-success' : 'color-error';
    
    $form['current_status'] = [
      '#type' => 'markup',
      '#markup' => '<p><strong>' . $this->t('Current Twig Debug Status: ') . '</strong>' .
        '<span class="' . $status_class . '">' . $status_text . '</span></p>',
    ];

    // Debug toggle
    $form['debug_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Twig Debug Mode'),
      '#description' => $this->t('When enabled, Twig templates will include debug information and auto-reload when changed. This helps with theme development but should never be enabled in production.'),
      '#default_value' => $config->get('debug_enabled'),
    ];

    // Information about what this does
    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('What does this do?'),
      '#open' => FALSE,
    ];

    $form['info']['description'] = [
      '#type' => 'markup',
      '#markup' => '<ul>' .
        '<li>' . $this->t('Enables Twig debug mode, which adds HTML comments to templates showing which template files are being used.') . '</li>' .
        '<li>' . $this->t('Enables auto-reload, so template changes are immediately visible without clearing cache.') . '</li>' .
        '<li>' . $this->t('Helps developers identify which template files to modify when theming.') . '</li>' .
        '<li>' . $this->t('Shows template suggestions and the template hierarchy.') . '</li>' .
        '</ul>',
    ];

    // Quick action buttons
    $form['quick_actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quick-actions']],
    ];

    $form['quick_actions']['enable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enable Debug'),
      '#submit' => ['::enableDebugSubmit'],
      '#button_type' => 'primary',
      '#access' => !$current_status,
      '#attributes' => ['class' => ['button--action-enable']],
    ];

    $form['quick_actions']['disable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Disable Debug'),
      '#submit' => ['::disableDebugSubmit'],
      '#button_type' => 'primary', 
      '#access' => $current_status,
      '#attributes' => ['class' => ['button--action-disable']],
    ];

    // Add a refresh status button
    $form['quick_actions']['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh Status'),
      '#submit' => ['::refreshStatusSubmit'],
      '#attributes' => ['class' => ['button--refresh']],
    ];

    // Add a test page link
    $form['quick_actions']['test_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Test Debug Status'),
      '#url' => \Drupal\Core\Url::fromRoute('twig_debug_switcher.test'),
      '#attributes' => ['class' => ['button', 'button--test'], 'target' => '_blank'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $debug_enabled = $form_state->getValue('debug_enabled');
    
    if ($debug_enabled) {
      $this->twigDebugManager->enableDebug();
    }
    else {
      $this->twigDebugManager->disableDebug();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for the enable debug button.
   */
  public function enableDebugSubmit(array &$form, FormStateInterface $form_state) {
    $this->twigDebugManager->enableDebug();
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for the disable debug button.
   */
  public function disableDebugSubmit(array &$form, FormStateInterface $form_state) {
    $this->twigDebugManager->disableDebug();
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for the refresh status button.
   */
  public function refreshStatusSubmit(array &$form, FormStateInterface $form_state) {
    $current_status = $this->twigDebugManager->getCurrentTwigDebugStatus();
    $status_text = $current_status ? $this->t('enabled') : $this->t('disabled');
    $this->messenger()->addStatus($this->t('Current Twig debug status: @status', ['@status' => $status_text]));
    $form_state->setRebuild(TRUE);
  }

}