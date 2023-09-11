<?php

namespace Drupal\advancedweatherwidget;

/**
 * Service decorate information from openweathermap.org API to widget.
 */
class AdvancedWeatherDecorator {

  /**
   * Prepares weather info.
   *
   * @param array $weather_info
   *    The HTTP request options.
   * @param string $type
   *    The type of API will use.
   *
   * @return array
   *    Normalized weather info.
   */
  public function prepareData($weather_info, string $type = 'weather') {
    if ($weather_info) {
      switch ($type) {
        case 'forecast':
          return $this->prepareForecastInfo($weather_info);

        case 'weather':
          return $this->prepareWeatherInfo($weather_info);
      }
    }

    return [];
  }

  /**
   * Prepare info for the forecast weather.
   *
   * @param array $weather_info
   *   The response data from  openweathermap.org.
   *
   * @return array
   *   Weather info.
   */
  protected function prepareForecastInfo(array $weather_info) {
    // Need just some specific info.
    $options = [
      'date',
      'description',
      'icon',
      'min',
      'max',
      'eve',
      'humidity',
      'pressure',
      'wind',
    ];

    // Check responce status code.
    $code = $weather_info['cod'];
    if ($code != 200) {
      return [];
    }

    $list = $weather_info['list'];

    $info['general'] = [
      'name' => $weather_info['city']['name'],
      'country' => $weather_info['city']['country']
    ];

    $daily_info = [];
    $labels = [];
    $i = 0;

    // Prepare info.
    foreach ($list as $day) {
      $daily = [];

      foreach ($options as $key) {
        switch ($key) {
          case 'date':
            $daily[$key] = date('m/d', $day['dt']);
            $labels[] = $daily[$key];
            break;

          case 'description':
          case 'icon':
            $daily[$key] = $day['weather'][0][$key];
            break;

          case 'min':
          case 'max':
          case 'eve':
            $daily[$key] = round($day['temp'][$key] - 273.15);
            break;

          case 'pressure':
          case 'humidity':
            $daily[$key] = $day[$key];
            break;

          case 'wind':
            $daily[$key]['speed'] = $day['speed'];
            $daily[$key]['deg'] = $day['deg'];
            $daily[$key]['gust'] = $day['gust'];
            break;

        }
      }

      $daily_info[$i] = $daily;
      $i ++;
    }

    $info['list'] = $daily_info;
    $info['labels'] = $labels;

    return $info;
  }

  /**
   * Prepare info for the current weather.
   *
   * @param array $weather_info
   *    The response data from  openweathermap.org.
   *
   * @return array
   */
  protected function prepareWeatherInfo(array $weather_info) {
    // Need just some specific info.
    $options = [
      'temp',
      'description',
      'icon',
      'temp_max',
      'temp_min',
      'name',
      'humidity',
      'name',
      'pressure',
      'wind',
      'country',
      'dt',
    ];

    $info = [];
    $code = $weather_info['cod'];
    if ($code !== 200) {
      return [];
    }

    $main = $weather_info['main'];

    foreach ($options as $key) {
      switch ($key) {
        case 'temp':
        case 'temp_max':
        case 'temp_min':
          $info[$key] = round($main[$key] - 273.15) . '°C';
          break;

        case 'description':
        case 'icon':
          $info[$key] = $weather_info['weather'][0][$key];
          break;

        case 'humidity':
          $info[$key] = $main[$key] . '%';
          break;

        case 'pressure':
          $info[$key] = $main[$key];
          break;

        case 'country':
          $info[$key] = $weather_info['sys'][$key] ?? '';
          break;

        case 'wind':
        case 'name':
          $info[$key] = $weather_info[$key];
          break;

        case 'dt': {
          $info[$key] = date('m/d', $weather_info['dt']);
        }
      }
    }

    return $info;
  }

}
