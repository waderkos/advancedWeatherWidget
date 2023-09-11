/**
 * @file
 * Contains js for chartjs.
 */

(function ($, Drupal) {

  let weatherChart = {};

  // Grep settings from the forms.
  function getSettings() {
    const $form = $('.weather-form, .charts-form');
    let $elements = $('input, select', $form)

    let values = {};
    $elements.each(function () {
      const $this = $(this);
      const val = $this.val();
      const name = $this.attr('name');
      values[name] = val;
    })

    return values;
  }
// Build Charts according to settings selected from forms.
 function buildChart() {
   const weatherSettings = getSettings();
   const forecast = '/advancedweatherwidget/weather_data/forecast';
   $.get(forecast, {'options': {'lat': weatherSettings.lat, 'lon': weatherSettings.lon, 'q': weatherSettings.q, 'cnt': weatherSettings.cnt}}, function (weatherData, status) {
     if (weatherData) {
       const data = {
         'temp': {
           labels: weatherData.data.labels,
           datasets: [
             {
               label: Drupal.t('Temperature °C'),
               data: weatherData.data.list.map(row => row.eve),
               backgroundColor: "rgba(75,192,192,0.4)",
               borderColor: "rgba(75,192,192,1)",
               spanGaps: false,
             },
             {
               label: Drupal.t('Temperature Min °C'),
               data: weatherData.data.list.map(row => row.min),
               backgroundColor: "rgba(192,75,192,0.4)",
               borderColor: "rgba(192,75,192,1)",
               spanGaps: false,
             },
             {
               label: Drupal.t('Temperature Max °C'),
               data: weatherData.data.list.map(row => row.max),
               backgroundColor: "rgba(192,192,75,0.4)",
               borderColor: "rgba(192,192,75,1)",
               spanGaps: false,
             }
           ]
         },

         'wind': {
           labels: weatherData.data.labels,
           datasets: [
             {
               label: Drupal.t('Wind speed km/h'),
               data: weatherData.data.list.map(row => row.wind.speed),
               backgroundColor: "rgba(75,192,192,0.4)",
               borderColor: "rgba(75,192,192,1)",
               spanGaps: false,
             },
             {
               label: Drupal.t('Wind gust km/h'),
               data: weatherData.data.list.map(row => row.wind.gust),
               backgroundColor: "rgba(192,192,75,0.4)",
               borderColor: "rgba(192,192,75,1)",
               spanGaps: false,
             }
           ]
         },

         'humidity': {
           labels: weatherData.data.labels,
           datasets: [
             {
               label: Drupal.t('humidity'),
               data: weatherData.data.list.map(row => row.humidity),
               backgroundColor: "rgba(75,192,192,0.4)",
               borderColor: "rgba(75,192,192,1)",
               spanGaps: false,
             }
           ]
         },

         'pressure': {
           labels: weatherData.data.labels,
           datasets: [
             {
               label: Drupal.t('Pressure'),
               data: weatherData.data.list.map(row => row.pressure),
               backgroundColor: "rgba(75,192,192,0.4)",
               borderColor: "rgba(75,192,192,1)",
               spanGaps: false,
             }
           ]
         }
       }

       let options = {}

       const ctx = 'weatherChart';

       weatherChart = new Chart(ctx, {
         type: weatherSettings.type,
         data: data[weatherSettings.charts],
         options: options
       });
     }
   })
 }

 // Build main weather information.
  function getWeatherData() {
    const weatherSettings = getSettings();
    const $weather_app = $('.weather-app');
    const weather = '/advancedweatherwidget/weather_data/weather';

    if ($weather_app.length) {
      $.get(weather, {'options': {'lat': weatherSettings.lat, 'lon': weatherSettings.lon, 'q': weatherSettings.q}}, function (data, status) {
        $( "div.advanced-weather-widget" ).replaceWith(data.html)
      });

      buildChart();
    }
  }

  // Rebuild weather information each time when something were changed.
  const $settingsForms = $('.weather-form');
  $('input, select', $settingsForms).change(function() {
    // Destroy previous chart.
    if (typeof weatherChart.destroy === 'function') {
       weatherChart.destroy();
    }
    getWeatherData();
  })

  const $chartsForm = $('.charts-form');
  $('input, select', $chartsForm).change(function() {
    if (typeof weatherChart.destroy === 'function') {
      weatherChart.destroy();
    }
    buildChart()
  })

  getWeatherData();

})(jQuery, Drupal);
