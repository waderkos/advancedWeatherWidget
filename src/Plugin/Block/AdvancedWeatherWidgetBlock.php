<?php

namespace Drupal\advancedweatherwidget\Plugin\Block;

use Drupal\advancedweatherwidget\AdvancedWeatherWidgetService;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Advanced Weather Widget Block.
 *
 * @Block(
 *   id = "advanced_weather_widget_block",
 *   admin_label = @Translation("Advanced Weather Widget Block"),
 * )
 */
class AdvancedWeatherWidgetBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * AdvancedWeatherWidget Service.
   *
   * @var \Drupal\advancedweatherwidget\AdvancedWeatherWidgetService
   */
  private $weatherService;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param AdvancedWeatherWidgetService $advancedweatherservice
   *   The service provides data from openweathermap.org.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AdvancedWeatherWidgetService $advancedweatherservice) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->weatherService = $advancedweatherservice;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('advancedweatherwidget.advanced_weather_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $weather = $this->weatherService->getInfo();

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['weather-app']],
    ];
    $build['form'] = $this->buildSearchForm();
    $build['content'] = [
      '#theme' => 'advanced_weather_widget',
      '#weather' => $weather,
    ];
    $build['chart_form'] = $this->buildChartForm();
    $build['charts'] = [
      '#theme' => 'advanced_weather_charts',
    ];

    $build['#attached']['library'][] = 'advancedweatherwidget/chartjs';

    return $build;
  }

  /**
   * Build form of weather.
   */
  protected function buildSearchForm() {
    $location = $this->weatherService->getUserLocation();

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['weather-form']],
    ];

    $build['q'] = [
      '#type' => 'textfield',
      '#name' => 'q',
      '#title' => $this->t('City'),
    ];

    $build['lat'] = [
      '#type' => 'textfield',
      '#name' => 'lat',
      '#title' => $this->t('Latitude'),
      '#value' => $location['lat'],
    ];

    $build['lon'] = [
      '#type' => 'textfield',
      '#name' => 'lon',
      '#title' => $this->t('Longitude'),
      '#value' => $location['lon'],
    ];

    return $build;
  }

  /**
   * Build form of chart.
   */
  protected function buildChartForm() {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['charts-form']],
    ];

    $build['charts'] = [
      '#type' => 'select',
      '#title' => $this->t('Charts'),
      '#name' => 'charts',
      '#options' => [
        'temp' => $this->t('Temperature'),
        'wind' => $this->t('Wind'),
        'humidity' => $this->t('Humidity'),
        'pressure' => $this->t('Pressure'),
      ],
    ];

    $build['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Chart Type'),
      '#name' => 'type',
      '#options' => [
        'bar' => $this->t('Bar'),
        'line' => $this->t('Line'),
      ],
    ];

    $options = [];
    for ($i = 1; $i <= 16; $i++) {
      $options[$i] = $i;
    }

    $build['cnt'] = [
      '#type' => 'select',
      '#name' => 'cnt',
      '#title' => $this->t('Count of days'),
      '#value' => 16,
      '#options' => $options,
    ];

    return $build;
  }

}
