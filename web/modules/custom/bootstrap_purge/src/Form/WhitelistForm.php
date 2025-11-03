<?php

namespace Drupal\bootstrap_purge\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bootstrap_purge\Service\WhitelistManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Bootstrap Purge whitelist management.
 */
class WhitelistForm extends ConfigFormBase {

  /**
   * The whitelist manager service.
   *
   * @var \Drupal\bootstrap_purge\Service\WhitelistManager
   */
  protected $whitelistManager;

  /**
   * Constructs a WhitelistForm object.
   *
   * @param \Drupal\bootstrap_purge\Service\WhitelistManager $whitelist_manager
   *   The whitelist manager service.
   */
  public function __construct(WhitelistManager $whitelist_manager) {
    $this->whitelistManager = $whitelist_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bootstrap_purge.whitelist_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bootstrap_purge.whitelist'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bootstrap_purge_whitelist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_purge.whitelist');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Manage whitelist patterns to prevent specific CSS selectors and JavaScript functions from being purged. Patterns support glob syntax (*) and regular expressions (enclosed in forward slashes).') . '</p>',
    ];

    $form['examples'] = [
      '#type' => 'details',
      '#title' => $this->t('Pattern Examples'),
      '#open' => FALSE,
    ];

    $form['examples']['content'] = [
      '#type' => 'markup',
      '#markup' => '
        <ul>
          <li><strong>Glob patterns:</strong>
            <ul>
              <li><code>.btn-*</code> - Matches all button classes</li>
              <li><code>*-active</code> - Matches classes ending with "-active"</li>
              <li><code>.d-*-block</code> - Matches responsive display classes</li>
            </ul>
          </li>
          <li><strong>Regular expressions:</strong>
            <ul>
              <li><code>/^\.d-.*$/</code> - Matches all display utility classes</li>
              <li><code>/^\.col-.*-\d+$/</code> - Matches grid column classes</li>
              <li><code>/^\.text-(primary|secondary|success)$/</code> - Matches specific text color classes</li>
            </ul>
          </li>
        </ul>
      ',
    ];

    $form['global_patterns'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Patterns'),
      '#open' => TRUE,
    ];

    $form['global_patterns']['global_patterns_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Global Whitelist Patterns'),
      '#description' => $this->t('CSS selector patterns that should never be purged (one per line).'),
      '#default_value' => implode("\n", $config->get('global_patterns') ?: []),
      '#rows' => 10,
    ];

    $form['selector_patterns'] = [
      '#type' => 'details',
      '#title' => $this->t('CSS Selector Patterns'),
      '#open' => TRUE,
    ];

    $form['selector_patterns']['selector_patterns_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CSS Selector Patterns'),
      '#description' => $this->t('Specific CSS selector patterns to preserve.'),
      '#default_value' => implode("\n", $config->get('selector_patterns') ?: []),
      '#rows' => 8,
    ];

    $form['js_patterns'] = [
      '#type' => 'details',
      '#title' => $this->t('JavaScript Function Patterns'),
      '#open' => FALSE,
    ];

    $form['js_patterns']['js_function_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JavaScript Function Patterns'),
      '#description' => $this->t('JavaScript function names or patterns to preserve during JS purging.'),
      '#default_value' => implode("\n", $config->get('js_function_patterns') ?: []),
      '#rows' => 6,
    ];

    $form['theme_patterns'] = [
      '#type' => 'details',
      '#title' => $this->t('Theme-Specific Patterns'),
      '#open' => FALSE,
    ];

    // Get available themes
    $theme_handler = \Drupal::service('theme_handler');
    $themes = $theme_handler->listInfo();

    foreach ($themes as $theme_name => $theme) {
      if ($theme->status) {
        $form['theme_patterns'][$theme_name] = [
          '#type' => 'textarea',
          '#title' => $this->t('Patterns for @theme', ['@theme' => $theme->info['name']]),
          '#description' => $this->t('Whitelist patterns specific to the @theme theme.', ['@theme' => $theme->info['name']]),
          '#default_value' => implode("\n", $config->get('theme_patterns.' . $theme_name) ?: []),
          '#rows' => 4,
        ];
      }
    }

    $form['test_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Test Patterns'),
      '#open' => FALSE,
    ];

    $form['test_section']['test_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test Selector'),
      '#description' => $this->t('Enter a CSS selector to test against current whitelist patterns.'),
      '#placeholder' => '.btn-primary',
    ];

    $form['test_section']['test_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test Selector'),
      '#submit' => ['::testSelector'],
      '#ajax' => [
        'callback' => '::testSelectorCallback',
        'wrapper' => 'test-result',
      ],
      '#limit_validation_errors' => [['test_selector']],
    ];

    $form['test_section']['test_result'] = [
      '#type' => 'markup',
      '#markup' => '',
      '#prefix' => '<div id="test-result">',
      '#suffix' => '</div>',
    ];

    $form['actions']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Bootstrap Defaults'),
      '#submit' => ['::importBootstrapDefaults'],
      '#button_type' => 'secondary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_purge.whitelist');

    // Process global patterns
    $global_patterns = array_filter(array_map('trim', explode("\n", $form_state->getValue('global_patterns_list'))));
    $config->set('global_patterns', $global_patterns);

    // Process selector patterns
    $selector_patterns = array_filter(array_map('trim', explode("\n", $form_state->getValue('selector_patterns_list'))));
    $config->set('selector_patterns', $selector_patterns);

    // Process JS function patterns
    $js_patterns = array_filter(array_map('trim', explode("\n", $form_state->getValue('js_function_patterns'))));
    $config->set('js_function_patterns', $js_patterns);

    // Process theme-specific patterns
    $theme_handler = \Drupal::service('theme_handler');
    $themes = $theme_handler->listInfo();
    $theme_patterns = [];

    foreach ($themes as $theme_name => $theme) {
      if ($theme->status && $form_state->hasValue($theme_name)) {
        $patterns = array_filter(array_map('trim', explode("\n", $form_state->getValue($theme_name))));
        if (!empty($patterns)) {
          $theme_patterns[$theme_name] = $patterns;
        }
      }
    }

    $config->set('theme_patterns', $theme_patterns);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Tests a selector against whitelist patterns.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function testSelector(array &$form, FormStateInterface $form_state) {
    // This is handled by the AJAX callback
  }

  /**
   * AJAX callback for testing selectors.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element to update.
   */
  public function testSelectorCallback(array &$form, FormStateInterface $form_state) {
    $selector = $form_state->getValue('test_selector');
    
    if (empty($selector)) {
      $form['test_section']['test_result']['#markup'] = '<div class="messages messages--warning">Please enter a selector to test.</div>';
      return $form['test_section']['test_result'];
    }

    $is_whitelisted = $this->whitelistManager->isWhitelisted($selector);
    
    if ($is_whitelisted) {
      $form['test_section']['test_result']['#markup'] = '<div class="messages messages--status">✓ Selector "' . htmlspecialchars($selector) . '" is whitelisted and will NOT be purged.</div>';
    } else {
      $form['test_section']['test_result']['#markup'] = '<div class="messages messages--error">✗ Selector "' . htmlspecialchars($selector) . '" is NOT whitelisted and may be purged.</div>';
    }

    return $form['test_section']['test_result'];
  }

  /**
   * Imports Bootstrap default whitelist patterns.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function importBootstrapDefaults(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_purge.whitelist');
    
    $bootstrap_defaults = [
      // Utility classes
      '/^\.d-.*/',
      '/^\.col-.*/',
      '/^\.row.*/',
      '/^\.container.*/',
      '/^\.btn.*/',
      '/^\.text-.*/',
      '/^\.bg-.*/',
      '/^\.border-.*/',
      '/^\.p-.*/',
      '/^\.m-.*/',
      '/^\.w-.*/',
      '/^\.h-.*/',
      '/^\.flex-.*/',
      '/^\.justify-.*/',
      '/^\.align-.*/',
      '/^\.position-.*/',
      '/^\.top-.*/',
      '/^\.bottom-.*/',
      '/^\.start-.*/',
      '/^\.end-.*/',
      
      // Component classes
      '/^\.modal.*/',
      '/^\.dropdown.*/',
      '/^\.nav.*/',
      '/^\.navbar.*/',
      '/^\.carousel.*/',
      '/^\.alert.*/',
      '/^\.badge.*/',
      '/^\.card.*/',
      '/^\.form-.*/',
      '/^\.input-.*/',
      '/^\.table.*/',
      
      // State classes
      '/^\.active$/',
      '/^\.disabled$/',
      '/^\.show$/',
      '/^\.hide$/',
      '/^\.fade$/',
      '/^\.collapse.*/',
      '/^\.collapsing$/',
      
      // Responsive classes
      '/^\.d-.*-block$/',
      '/^\.d-.*-inline$/',
      '/^\.d-.*-flex$/',
      '/^\.d-.*-grid$/',
      '/^\.d-.*-none$/',
      
      // Accessibility
      '/^\.visually-hidden.*/',
      '/^\.sr-only.*/',
    ];

    $existing_patterns = $config->get('global_patterns') ?: [];
    $merged_patterns = array_unique(array_merge($existing_patterns, $bootstrap_defaults));
    
    $config->set('global_patterns', $merged_patterns)->save();
    
    $this->messenger()->addStatus($this->t('Bootstrap default whitelist patterns have been imported.'));
    
    // Rebuild the form to show updated patterns
    $form_state->setRebuild();
  }

}