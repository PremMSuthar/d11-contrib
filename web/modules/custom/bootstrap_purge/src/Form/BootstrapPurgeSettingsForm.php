<?php

namespace Drupal\bootstrap_purge\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Bootstrap Purge settings.
 */
class BootstrapPurgeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bootstrap_purge.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bootstrap_purge_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_purge.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    ];

    $form['general']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Bootstrap Purge'),
      '#description' => $this->t('Enable the Bootstrap Purge functionality.'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['general']['auto_apply'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically apply approved purges'),
      '#description' => $this->t('Automatically apply purges that meet the confidence threshold.'),
      '#default_value' => $config->get('auto_apply'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['analysis'] = [
      '#type' => 'details',
      '#title' => $this->t('Analysis Settings'),
      '#open' => TRUE,
    ];

    $form['analysis']['analysis_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Analysis Mode'),
      '#description' => $this->t('Choose the analysis method for detecting unused CSS/JS.'),
      '#options' => [
        'static' => $this->t('Static Analysis (Server-side)'),
        'runtime' => $this->t('Runtime Analysis (Client-side)'),
        'combined' => $this->t('Combined (Static + Runtime)'),
      ],
      '#default_value' => $config->get('analysis_mode'),
    ];

    $form['analysis']['snapshot_routes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Routes to Snapshot'),
      '#description' => $this->t('Enter route names (one per line) to analyze for static analysis. Use "key" for automatic key routes selection.'),
      '#default_value' => implode("\n", $config->get('snapshot_routes') ?: []),
      '#states' => [
        'visible' => [
          [':input[name="analysis_mode"]' => ['value' => 'static']],
          'or',
          [':input[name="analysis_mode"]' => ['value' => 'combined']],
        ],
      ],
    ];

    $form['analysis']['confidence_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Confidence Threshold'),
      '#description' => $this->t('Minimum confidence score (0-100) required for auto-approval.'),
      '#default_value' => $config->get('confidence_threshold'),
      '#min' => 0,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="auto_apply"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['runtime'] = [
      '#type' => 'details',
      '#title' => $this->t('Runtime Collection'),
      '#open' => FALSE,
    ];

    $form['runtime']['runtime_collection_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Runtime Data Collection'),
      '#description' => $this->t('Collect usage data from real user sessions to improve analysis accuracy.'),
      '#default_value' => $config->get('runtime_collection_enabled'),
    ];

    $form['runtime']['runtime_sample_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Sample Rate (%)'),
      '#description' => $this->t('Percentage of users to include in runtime data collection.'),
      '#default_value' => $config->get('runtime_sample_rate'),
      '#min' => 1,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="runtime_collection_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['automation'] = [
      '#type' => 'details',
      '#title' => $this->t('Automation'),
      '#open' => FALSE,
    ];

    $form['automation']['auto_analyze_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run Analysis During Cron'),
      '#description' => $this->t('Automatically run analysis during cron runs.'),
      '#default_value' => $config->get('auto_analyze_cron'),
    ];

    $form['automation']['cron_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Cron Analysis Interval'),
      '#description' => $this->t('How often to run automatic analysis.'),
      '#options' => [
        3600 => $this->t('Every hour'),
        21600 => $this->t('Every 6 hours'),
        43200 => $this->t('Every 12 hours'),
        86400 => $this->t('Daily'),
        604800 => $this->t('Weekly'),
      ],
      '#default_value' => $config->get('cron_interval'),
      '#states' => [
        'visible' => [
          ':input[name="auto_analyze_cron"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['storage'] = [
      '#type' => 'details',
      '#title' => $this->t('Storage Settings'),
      '#open' => FALSE,
    ];

    $form['storage']['storage_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Storage Path'),
      '#description' => $this->t('Directory to store purged assets. Use stream wrapper format (e.g., public://bootstrap_purge).'),
      '#default_value' => $config->get('storage_path'),
      '#required' => TRUE,
    ];

    $form['tools'] = [
      '#type' => 'details',
      '#title' => $this->t('External Tools'),
      '#open' => FALSE,
    ];

    $form['tools']['purgecss_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PurgeCSS Path'),
      '#description' => $this->t('Path to PurgeCSS executable. Leave empty to use PHP-based purging.'),
      '#default_value' => $config->get('purgecss_path'),
    ];

    $form['tools']['node_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node.js Path'),
      '#description' => $this->t('Path to Node.js executable (required for PurgeCSS).'),
      '#default_value' => $config->get('node_path'),
    ];

    $form['whitelist'] = [
      '#type' => 'details',
      '#title' => $this->t('Whitelist Patterns'),
      '#open' => FALSE,
    ];

    $form['whitelist']['whitelist_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Global Whitelist Patterns'),
      '#description' => $this->t('CSS selector patterns to never purge (one per line). Supports regex patterns enclosed in forward slashes.'),
      '#default_value' => implode("\n", $config->get('whitelist_patterns') ?: []),
      '#rows' => 10,
    ];

    $form['whitelist']['bootstrap_whitelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Bootstrap Whitelist Patterns'),
      '#description' => $this->t('Bootstrap-specific patterns to preserve (one per line).'),
      '#default_value' => implode("\n", $config->get('bootstrap_whitelist') ?: []),
      '#rows' => 15,
    ];

    $form['actions']['test_tools'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test External Tools'),
      '#submit' => ['::testTools'],
      '#limit_validation_errors' => [['purgecss_path'], ['node_path']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate storage path
    $storage_path = $form_state->getValue('storage_path');
    if (!empty($storage_path)) {
      $file_system = \Drupal::service('file_system');
      if (!$file_system->prepareDirectory($storage_path, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY)) {
        $form_state->setErrorByName('storage_path', $this->t('The storage path is not writable or cannot be created.'));
      }
    }

    // Validate confidence threshold
    $threshold = $form_state->getValue('confidence_threshold');
    if ($threshold < 0 || $threshold > 100) {
      $form_state->setErrorByName('confidence_threshold', $this->t('Confidence threshold must be between 0 and 100.'));
    }

    // Validate sample rate
    $sample_rate = $form_state->getValue('runtime_sample_rate');
    if ($sample_rate < 1 || $sample_rate > 100) {
      $form_state->setErrorByName('runtime_sample_rate', $this->t('Sample rate must be between 1 and 100.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_purge.settings');

    // Process array fields
    $snapshot_routes = array_filter(array_map('trim', explode("\n", $form_state->getValue('snapshot_routes'))));
    $whitelist_patterns = array_filter(array_map('trim', explode("\n", $form_state->getValue('whitelist_patterns'))));
    $bootstrap_whitelist = array_filter(array_map('trim', explode("\n", $form_state->getValue('bootstrap_whitelist'))));

    $config
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('auto_apply', $form_state->getValue('auto_apply'))
      ->set('analysis_mode', $form_state->getValue('analysis_mode'))
      ->set('snapshot_routes', $snapshot_routes)
      ->set('runtime_collection_enabled', $form_state->getValue('runtime_collection_enabled'))
      ->set('runtime_sample_rate', $form_state->getValue('runtime_sample_rate'))
      ->set('auto_analyze_cron', $form_state->getValue('auto_analyze_cron'))
      ->set('cron_interval', $form_state->getValue('cron_interval'))
      ->set('confidence_threshold', $form_state->getValue('confidence_threshold'))
      ->set('storage_path', $form_state->getValue('storage_path'))
      ->set('purgecss_path', $form_state->getValue('purgecss_path'))
      ->set('node_path', $form_state->getValue('node_path'))
      ->set('whitelist_patterns', $whitelist_patterns)
      ->set('bootstrap_whitelist', $bootstrap_whitelist)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Tests external tools configuration.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function testTools(array &$form, FormStateInterface $form_state) {
    $purgecss_path = $form_state->getValue('purgecss_path');
    $node_path = $form_state->getValue('node_path');

    $messages = [];

    // Test Node.js
    if (!empty($node_path)) {
      try {
        $process = new \Symfony\Component\Process\Process([$node_path, '--version']);
        $process->run();
        if ($process->isSuccessful()) {
          $version = trim($process->getOutput());
          $messages[] = $this->t('Node.js found: @version', ['@version' => $version]);
        } else {
          $messages[] = $this->t('Node.js test failed: @error', ['@error' => $process->getErrorOutput()]);
        }
      } catch (\Exception $e) {
        $messages[] = $this->t('Node.js test error: @message', ['@message' => $e->getMessage()]);
      }
    } else {
      $messages[] = $this->t('Node.js path not configured.');
    }

    // Test PurgeCSS
    if (!empty($purgecss_path)) {
      try {
        $process = new \Symfony\Component\Process\Process([$purgecss_path, '--version']);
        $process->run();
        if ($process->isSuccessful()) {
          $version = trim($process->getOutput());
          $messages[] = $this->t('PurgeCSS found: @version', ['@version' => $version]);
        } else {
          $messages[] = $this->t('PurgeCSS test failed: @error', ['@error' => $process->getErrorOutput()]);
        }
      } catch (\Exception $e) {
        $messages[] = $this->t('PurgeCSS test error: @message', ['@message' => $e->getMessage()]);
      }
    } else {
      $messages[] = $this->t('PurgeCSS path not configured. PHP-based purging will be used.');
    }

    foreach ($messages as $message) {
      $this->messenger()->addStatus($message);
    }
  }

}