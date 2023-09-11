<?php

namespace Drupal\advancedweatherwidget\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to set setting to connect to the API.
 */
class AdvancedWeatherWidgetForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advanced_weather.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced_weather_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API KEY'),
      '#required' => TRUE,
      '#description' => $this->t('Firstly need to create an account at https://openweathermap.org and copy KEY from the page https://home.openweathermap.org/api_keys'),
      '#default_value' => $this->config('advanced_weather.settings')->get('apikey'),
    ];

    $form['testMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test Mode'),
      '#description' => $this->t('To use most part of the https://openweathermap.org need to pay. So for the free testing use this option.'),
      '#default_value' => $this->config('advanced_weather.settings')->get('testMode'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('advanced_weather.settings')
      ->set('apikey', $form_state->getValue('apikey'))
      ->set('testMode', $form_state->getValue('testMode'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
