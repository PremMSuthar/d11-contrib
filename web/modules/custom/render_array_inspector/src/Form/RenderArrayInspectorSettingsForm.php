<?php

namespace Drupal\render_array_inspector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Render Array Inspector settings.
 */
class RenderArrayInspectorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'render_array_inspector_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['render_array_inspector.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('render_array_inspector.settings');

    $form['max_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum depth'),
      '#description' => $this->t('Maximum depth to traverse when displaying render arrays. Higher values may impact performance.'),
      '#default_value' => $config->get('max_depth') ?: 10,
      '#min' => 1,
      '#max' => 20,
    ];

    $form['auto_expand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-expand first level'),
      '#description' => $this->t('Automatically expand the first level of the render array tree.'),
      '#default_value' => $config->get('auto_expand') ?: FALSE,
    ];

    $form['show_cache_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show cache information'),
      '#description' => $this->t('Include cache tags, contexts, and max-age information in the display.'),
      '#default_value' => $config->get('show_cache_info') ?: FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('render_array_inspector.settings')
      ->set('max_depth', $form_state->getValue('max_depth'))
      ->set('auto_expand', $form_state->getValue('auto_expand'))
      ->set('show_cache_info', $form_state->getValue('show_cache_info'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}