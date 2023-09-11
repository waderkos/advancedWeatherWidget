<?php

namespace Drupal\advancedweatherwidget;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to connect to the openweathermap.org API and manage data from there.
 */
class AdvancedWeatherWidgetService {

  /**
   * URL to the openweathermap.org API.
   *
   * @var string
   */
  public static $apiUrl = 'http://api.openweathermap.org/';

  /**
   * URL to the IP addresses locatin.
   *
   * @var string
   */
  public static $ipUrl = 'http://www.geoplugin.net/php.gp';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Cache duration. To save API calls because it isn't unlimited.
   *
   * @var int
   */

  protected $cacheTime = 5000;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current Request object.
   *
   * @var \Drupal\advancedweatherwidget\AdvancedWeatherDecorator
   */
  protected $decorator;

  /**
   * Constructs an AdvancedWeatherWidgetService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *    The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *    The logger factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *    The file handler.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *    The extension path resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *    The request stack.
   */
  public function __construct(
    ClientInterface $http_client,
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    FileSystemInterface $file_system,
    ExtensionPathResolver $extension_path_resolver,
    RequestStack $request_stack,
    AdvancedWeatherDecorator $decorator
  ) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->config = $config_factory->get('advanced_weather.settings');
    $this->logger = $logger_factory->get('advanced_weather');
    $this->fileSystem = $file_system;
    $this->extensionPathResolver = $extension_path_resolver;
    $this->request = $request_stack->getCurrentRequest();
    $this->decorator = $decorator;
  }

  /**
   * Get data from local file for the testing.
   */
  protected function testModeData($options, $type) {
    $path = $this->extensionPathResolver->getPath('module', 'advancedweatherwidget');
    $real_path = $this->fileSystem->realpath($path);
    $json_data = file_get_contents($real_path . '/files/farecast_test.json');
    $data = json_decode($json_data, TRUE);

    if (!empty($options['cnt']) && !empty($data['list']) && $options['cnt'] < 16) {
      $data['list'] = array_slice($data['list'], 0, $options['cnt']);
    }

    $time = time();
    foreach ($data['list'] as &$day) {
      $day['dt'] = $time;
      $time += 86400;
    }

    return json_encode($data);
  }

  /**
   * Get data from openweathermap.org API
   *
   * @param array $query
   *   The HTTP request options.
   * @param string $type
   *   The type of API will use.
   *
   * @return array|bool
   */
  public function getData(array $query, string $type = 'forecast') {
    $cid = md5(serialize([$query, $type]));
    if ($cache = $this->cache->get($cid)) {
      $weather_info = $cache->weather_info;
    }
    else {
      $apiKey = $this->config->get('apikey');
      $testMode = $this->config->get('testMode');
      $query += [
        'appid' => $apiKey,
      ];
      try {
        switch ($type) {
          case 'forecast':
            if ($testMode) {
              $response = $this->testModeData($query, $type);
            }
            else {
              $response = $this->httpClient->request(
                'GET',
                self::$apiUrl . 'data/2.5/forecast/daily',
                [
                  'query' => $query,
                ]
              );
            }
            $weather_info = $testMode ? $response : $response->getBody()->getContents();
            break;

          case 'weather':
            $response = $this->httpClient->request(
              'GET',
              self::$apiUrl . '/data/2.5/weather',
              [
                'query' => $query,
              ]
            );
            $weather_info = $response->getBody()->getContents();
            break;
        }
      } catch (GuzzleException $e) {
        $this->logger->error($e->getMessage());
        return FALSE;
      }

      $this->cache->set($cid, $weather_info, $this->cacheTime);
    }

    return json_decode($weather_info, TRUE);
  }

  /**
   * Calculate user coordinates.
   *
   * @return array
   *  Lon, Lat of user by IP address.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getUserLocation() {
    $ip = $this->request->getClientIp();
    $testMode = $this->config->get('testMode');

    // For the local environment, or when testMode enabled - use some random IP.
    $ip = $ip == '127.0.0.1' || $testMode ? '159.223.176.237' : $ip;
    $query = ['ip' => $ip];
    $response = $this->httpClient->request(
      'GET',
      self::$ipUrl,
      [
        'query' => $query,
      ]
    );

    $ip_info = unserialize($response->getBody()->getContents());

    return ['lat' => $ip_info['geoplugin_latitude'], 'lon' => $ip_info['geoplugin_longitude']];
  }

  /**
   * Get weather info.
   *
   * @param array $options
   *    The HTTP request options.
   * @param string $type
   *    The type of API will use.
   *
   * @return array
   *    Weather info.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getInfo(array $options = [], string $type = 'weather') {
    if (empty($options)) {
      $options = $this->getUserLocation();
    }
    $weather_info = $this->getData($options, $type);
    return $this->decorator->prepareData($weather_info,  $type);
  }

}
