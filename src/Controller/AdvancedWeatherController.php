<?php

namespace Drupal\advancedweatherwidget\Controller;

use Drupal\advancedweatherwidget\AdvancedWeatherWidgetService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller for the weather data.
 */
class AdvancedWeatherController extends ControllerBase {

  /**
   * Advanced Weather Widget Service.
   *
   * @var \Drupal\advancedweatherwidget\AdvancedWeatherWidgetService
   */
  protected $weatherService;

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new AdvancedWeatherController object.
   *
   * @param AdvancedWeatherWidgetService $advancedweatherservice
   *    The service provides data from openweathermap.org.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *    The current request.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *    The renderer.
   */
  public function __construct( AdvancedWeatherWidgetService $advancedweatherservice, Request $request, RendererInterface $renderer) {
    $this->weatherService = $advancedweatherservice;
    $this->request = $request;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('advancedweatherwidget.advanced_weather_service'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('renderer')
    );
  }

  /**
   * AJAX weather data.
   *
   * @return JsonResponse
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function weatherData($type, $options = []) {
    $options = $this->request->query->get('options');

    if (empty($options) || (empty($options['q']) && (empty($options['lat']) || empty($options['lon'])))) {
      $options = $this->weatherService->getUserLocation();
    }
    $options = array_filter($options);

    switch ($type) {
      case 'forecast':
        $options['cnt'] = $options['cnt'] ?? 16;
        $forecast = $this->weatherService->getInfo($options, 'forecast');
        return new JsonResponse(['data' => $forecast, 'status' => 200]);

      default:
        $weather = $this->weatherService->getInfo($options);
        $html = $this->curentWeatherInfo($weather);
        return new JsonResponse([ 'html' => $html, 'status'=> 200]);
    }
  }

  /**
   * Render HTML for the current weather block.
   */
  protected function curentWeatherInfo($weather) {
    $build = ['#theme' => 'advanced_weather_widget', '#weather' => $weather];
    return $this->renderer->render($build);
  }

}
