<?php

namespace Drupal\site_analyzer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Site Analyzer settings.
 */
class AnalyzerConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['site_analyzer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_analyzer_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('site_analyzer.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    ];

    $form['general']['configured'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Module is configured'),
      '#description' => $this->t('Check this box to indicate that the module has been properly configured.'),
      '#default_value' => $config->get('configured'),
    ];

    $form['general']['analysis_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Analysis Depth'),
      '#description' => $this->t('Choose the default depth of analysis to perform.'),
      '#options' => [
        'quick' => $this->t('Quick Scan - Basic compatibility check'),
        'standard' => $this->t('Standard Analysis - Comprehensive review'),
        'deep' => $this->t('Deep Dive - Full code analysis and performance audit'),
      ],
      '#default_value' => $config->get('analysis_depth') ?: 'standard',
    ];

    $form['monitoring'] = [
      '#type' => 'details',
      '#title' => $this->t('Automated Monitoring'),
      '#open' => FALSE,
    ];

    $form['monitoring']['automated_monitoring'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable automated monitoring'),
      '#description' => $this->t('Automatically run analysis during cron and generate reports.'),
      '#default_value' => $config->get('automated_monitoring'),
    ];

    $form['monitoring']['monitoring_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Monitoring Interval'),
      '#description' => $this->t('How often to run automated analysis.'),
      '#options' => [
        3600 => $this->t('Every hour'),
        21600 => $this->t('Every 6 hours'),
        43200 => $this->t('Every 12 hours'),
        86400 => $this->t('Daily'),
        604800 => $this->t('Weekly'),
        2592000 => $this->t('Monthly'),
      ],
      '#default_value' => $config->get('monitoring_interval') ?: 86400,
      '#states' => [
        'visible' => [
          ':input[name="automated_monitoring"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['alerts'] = [
      '#type' => 'details',
      '#title' => $this->t('Alert Settings'),
      '#open' => FALSE,
    ];

    $form['alerts']['send_alerts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email alerts'),
      '#description' => $this->t('Send email notifications when critical issues are detected.'),
      '#default_value' => $config->get('send_alerts'),
    ];

    $form['alerts']['alert_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Alert Email Address'),
      '#description' => $this->t('Email address to send alerts to.'),
      '#default_value' => $config->get('alert_email'),
      '#states' => [
        'visible' => [
          ':input[name="send_alerts"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="send_alerts"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['alerts']['alert_thresholds'] = [
      '#type' => 'details',
      '#title' => $this->t('Alert Thresholds'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="send_alerts"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['alerts']['alert_thresholds']['memory_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Memory Usage Threshold (%)'),
      '#description' => $this->t('Send alert when memory usage exceeds this percentage.'),
      '#default_value' => $config->get('alert_thresholds.memory_threshold') ?: 80,
      '#min' => 1,
      '#max' => 100,
    ];

    $form['alerts']['alert_thresholds']['disk_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Disk Usage Threshold (%)'),
      '#description' => $this->t('Send alert when disk usage exceeds this percentage.'),
      '#default_value' => $config->get('alert_thresholds.disk_threshold') ?: 90,
      '#min' => 1,
      '#max' => 100,
    ];

    $form['alerts']['alert_thresholds']['security_updates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Alert on security updates'),
      '#description' => $this->t('Send alert when security updates are available.'),
      '#default_value' => $config->get('alert_thresholds.security_updates') !== FALSE,
    ];

    $form['reports'] = [
      '#type' => 'details',
      '#title' => $this->t('Report Settings'),
      '#open' => FALSE,
    ];

    $form['reports']['report_retention'] = [
      '#type' => 'select',
      '#title' => $this->t('Report Retention Period'),
      '#description' => $this->t('How long to keep automated reports.'),
      '#options' => [
        604800 => $this->t('1 week'),
        2592000 => $this->t('1 month'),
        7776000 => $this->t('3 months'),
        15552000 => $this->t('6 months'),
        31536000 => $this->t('1 year'),
      ],
      '#default_value' => $config->get('report_retention') ?: 2592000,
    ];

    $form['reports']['include_sensitive_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include sensitive data in reports'),
      '#description' => $this->t('Include database connection details and other sensitive information in reports. Only enable if reports are properly secured.'),
      '#default_value' => $config->get('include_sensitive_data'),
    ];

    $form['performance'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance Settings'),
      '#open' => FALSE,
    ];

    $form['performance']['max_execution_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Execution Time (seconds)'),
      '#description' => $this->t('Maximum time to spend on analysis. Set to 0 for no limit.'),
      '#default_value' => $config->get('max_execution_time') ?: 300,
      '#min' => 0,
    ];

    $form['performance']['memory_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Memory Limit'),
      '#description' => $this->t('Memory limit for analysis operations (e.g., 512M). Leave empty to use system default.'),
      '#default_value' => $config->get('memory_limit'),
    ];

    $form['performance']['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch Size'),
      '#description' => $this->t('Number of items to process in each batch operation.'),
      '#default_value' => $config->get('batch_size') ?: 100,
      '#min' => 1,
      '#max' => 1000,
    ];

    $form['security'] = [
      '#type' => 'details',
      '#title' => $this->t('Security Settings'),
      '#open' => FALSE,
    ];

    $form['security']['enable_code_scanning'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable code scanning'),
      '#description' => $this->t('Scan custom code for security vulnerabilities and deprecated functions.'),
      '#default_value' => $config->get('enable_code_scanning') !== FALSE,
    ];

    $form['security']['scan_custom_modules'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scan custom modules'),
      '#description' => $this->t('Include custom modules in code scanning.'),
      '#default_value' => $config->get('scan_custom_modules') !== FALSE,
      '#states' => [
        'visible' => [
          ':input[name="enable_code_scanning"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['security']['scan_custom_themes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scan custom themes'),
      '#description' => $this->t('Include custom themes in code scanning.'),
      '#default_value' => $config->get('scan_custom_themes') !== FALSE,
      '#states' => [
        'visible' => [
          ':input[name="enable_code_scanning"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['integration'] = [
      '#type' => 'details',
      '#title' => $this->t('External Integrations'),
      '#open' => FALSE,
    ];

    $form['integration']['drupal_org_api'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Drupal.org API integration'),
      '#description' => $this->t('Check for module updates and security advisories from Drupal.org.'),
      '#default_value' => $config->get('drupal_org_api') !== FALSE,
    ];

    $form['integration']['api_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('API Timeout (seconds)'),
      '#description' => $this->t('Timeout for external API requests.'),
      '#default_value' => $config->get('api_timeout') ?: 30,
      '#min' => 1,
      '#max' => 300,
      '#states' => [
        'visible' => [
          ':input[name="drupal_org_api"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['branding'] = [
      '#type' => 'details',
      '#title' => $this->t('Report Branding'),
      '#open' => FALSE,
    ];

    $form['branding']['custom_logo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Custom Logo'),
      '#description' => $this->t('Upload a custom logo to include in reports.'),
      '#upload_location' => 'public://site_analyzer/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif svg'],
        'file_validate_size' => [2 * 1024 * 1024], // 2MB
      ],
      '#default_value' => $config->get('custom_logo') ? [$config->get('custom_logo')] : NULL,
    ];

    $form['branding']['company_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company Name'),
      '#description' => $this->t('Company name to include in reports.'),
      '#default_value' => $config->get('company_name'),
    ];

    $form['branding']['report_footer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Report Footer'),
      '#description' => $this->t('Custom footer text to include in reports.'),
      '#default_value' => $config->get('report_footer'),
      '#rows' => 3,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate memory limit format
    $memory_limit = $form_state->getValue('memory_limit');
    if (!empty($memory_limit) && !preg_match('/^\d+[KMG]?$/i', $memory_limit)) {
      $form_state->setErrorByName('memory_limit', $this->t('Memory limit must be in format like 512M, 1G, etc.'));
    }

    // Validate email if alerts are enabled
    if ($form_state->getValue('send_alerts') && empty($form_state->getValue('alert_email'))) {
      $form_state->setErrorByName('alert_email', $this->t('Alert email address is required when alerts are enabled.'));
    }

    // Validate thresholds
    $memory_threshold = $form_state->getValue(['alert_thresholds', 'memory_threshold']);
    if (!empty($memory_threshold) && is_numeric($memory_threshold) && ($memory_threshold < 1 || $memory_threshold > 100)) {
      $form_state->setErrorByName('alert_thresholds][memory_threshold', $this->t('Memory threshold must be between 1 and 100.'));
    }

    $disk_threshold = $form_state->getValue(['alert_thresholds', 'disk_threshold']);
    if (!empty($disk_threshold) && is_numeric($disk_threshold) && ($disk_threshold < 1 || $disk_threshold > 100)) {
      $form_state->setErrorByName('alert_thresholds][disk_threshold', $this->t('Disk threshold must be between 1 and 100.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('site_analyzer.settings');

    // Handle file upload
    $logo_fid = NULL;
    if ($form_state->getValue('custom_logo')) {
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($form_state->getValue('custom_logo')[0]);
      if ($file) {
        $file->setPermanent();
        $file->save();
        $logo_fid = $file->id();
      }
    }

    $config
      ->set('configured', $form_state->getValue('configured'))
      ->set('analysis_depth', $form_state->getValue('analysis_depth'))
      ->set('automated_monitoring', $form_state->getValue('automated_monitoring'))
      ->set('monitoring_interval', $form_state->getValue('monitoring_interval'))
      ->set('send_alerts', $form_state->getValue('send_alerts'))
      ->set('alert_email', $form_state->getValue('alert_email'))
      ->set('alert_thresholds.memory_threshold', $form_state->getValue(['alert_thresholds', 'memory_threshold']))
      ->set('alert_thresholds.disk_threshold', $form_state->getValue(['alert_thresholds', 'disk_threshold']))
      ->set('alert_thresholds.security_updates', $form_state->getValue(['alert_thresholds', 'security_updates']))
      ->set('report_retention', $form_state->getValue('report_retention'))
      ->set('include_sensitive_data', $form_state->getValue('include_sensitive_data'))
      ->set('max_execution_time', $form_state->getValue('max_execution_time'))
      ->set('memory_limit', $form_state->getValue('memory_limit'))
      ->set('batch_size', $form_state->getValue('batch_size'))
      ->set('enable_code_scanning', $form_state->getValue('enable_code_scanning'))
      ->set('scan_custom_modules', $form_state->getValue('scan_custom_modules'))
      ->set('scan_custom_themes', $form_state->getValue('scan_custom_themes'))
      ->set('drupal_org_api', $form_state->getValue('drupal_org_api'))
      ->set('api_timeout', $form_state->getValue('api_timeout'))
      ->set('custom_logo', $logo_fid)
      ->set('company_name', $form_state->getValue('company_name'))
      ->set('report_footer', $form_state->getValue('report_footer'))
      ->save();

    parent::submitForm($form, $form_state);

    $this->messenger()->addMessage($this->t('Site Analyzer configuration has been saved.'));
  }

}