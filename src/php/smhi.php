<?php 
/**
 * SMHI Weather Data Integration
 * @file: src/php/smhi.php
 *
 * @see https://github.com/arcwindab/weather/ GitHub project
 * @see https://opendata.smhi.se/apidocs/metfcst/parameters.html API Documentation
 *
 * @description Fetches and processes weather forecast data from SMHI's open data API, specifically designed for point-based geographical forecasts. This script transforms the raw API response into a structured and developer-friendly format, making it easier to integrate with other applications.
 *
 * @author    Tobias Jonson
 * @copyright 2025 Tobias Jonson @ ArcWind AB
 * @note      This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY
 */

 /**
 * Retrieves weather data using the SMHI Open Data API.
 *
 * @param float $latitude The latitude coordinate for the weather location (valid range: -90 to 90).
 * @param float $longitude The longitude coordinate for the weather location (valid range: -180 to 180).
 * @return array|null Returns an associative array with weather data or null if an error occurs.
 *
 * Expected return format:
 * [
 *   'current' => [
 *       'weather' => [
 *           'temperature' => float|false,      // Temperature in Celsius.
 *           'humidity' => int|false,           // Humidity percentage.
 *           'visibility' => float|false,       // Horizontal visibility in kilometers.
 *           'gust' => float|false,             // Wind gust speed in m/s.
 *           'windSpeed' => float|false,        // Wind speed in m/s.
 *           'windDirection' => int|false,      // Wind direction in degrees.
 *           'pressure' => float|false,         // Atmospheric pressure in hPa.
 *           'spp' => int|false,                // Probability of frozen precipitation (%).
 *           'cloudiness' => int|false,         // Cloud cover percentage.
 *           'thunderRisk' => int|false,        // Thunderstorm probability (%).
 *           'precipitation' => float|false,    // Precipitation amount in millimeters.
 *           'icon' => string|null,             // Font Awesome weather icon name.
 *       ],
 *   ],
 *   'forecast' => [
 *       <timestamp> => [
 *           'weather' => [ // Same structure as 'current.weather'.
 *           ],
 *       ],
 *   ],
 * ]
 *
 * Input Constraints:
 * - $latitude: Must be a float between -90 and 90.
 * - $longitude: Must be a float between -180 and 180.
 *
 * Notes:
 * - Sunrise and sunset times are dynamically calculated using PHP's `date_sun_info` function.
 * - Weather conditions are represented with Font Awesome icons based on the SMHI 'Wsymb2' parameter.
 * - Handles all parameters provided by SMHI, such as 't', 'r', 'vis', 'ws', 'gust', 'wd', 'msl', 'spp', 'tcc_mean', 'tstm', 'pmean', and 'Wsymb2'.
 * - Returns `null` and outputs error messages if API requests fail or responses are invalid.
 */
function getWeatherDataSMHI($latitude, $longitude) {
   // Construct the SMHI API URL with the provided latitude and longitude
   $SMHIApiUrl = "https://opendata-download-metfcst.smhi.se/api/category/pmp3g/version/2/geotype/point/lon/$longitude/lat/$latitude/data.json";

   try {
       // Fetch weather data from SMHI API
       $response = file_get_contents($SMHIApiUrl);

       // Check if the API response is valid
       if ($response === false) {
           throw new Exception("Failed to retrieve data from SMHI.");
       }

       // Decode the JSON response into a PHP associative array
       $data = json_decode($response, true);

       // Verify that the response contains the required 'timeSeries' data
       if (!isset($data['timeSeries'])) {
           throw new Exception("The response lacks required time series data.");
       }
   } catch (Exception $e) {
       // Output the error message and return null if an error occurs
       echo $e->getMessage();
       return null;
   }

   // Initialize an array to hold the structured forecast data
   $forecast = ['current' => [], 'forecast' => []];

   try {
       // Iterate through each time series entry provided by the SMHI API
       foreach ($data['timeSeries'] as $timeSeries) {
           // Extract the valid time for the forecast and convert it to a UNIX timestamp
           $time = $timeSeries['validTime'];
           $forecastTime = strtotime($time);

           // Calculate sunrise and sunset for the given forecast time using date_sun_info
           $sunInfo = date_sun_info($forecastTime, $latitude, $longitude);
           $sunrise = $sunInfo['sunrise'];
           $sunset = $sunInfo['sunset'];

           // Determine if the forecast time is during the day
           $isDay = $forecastTime > $sunrise && $forecastTime < $sunset;

           // Initialize the weather data structure for this forecast time
           $forecast['forecast'][$forecastTime]['weather'] = [
               'time' => $forecastTime,
               'time_formatted' => date('Y-m-d H:i', $forecastTime),
               'temperature' => false,
               'humidity' => false,
               'visibility' => false,
               'gust' => false,
               'windSpeed' => false,
               'windDirection' => false,
               'pressure' => false,
               'spp' => false,
               'cloudiness' => false,
               'thunderRisk' => false,
               'precipitation' => false,
               'icon' => null,
           ];

           // Process all parameters for the current time series entry
           foreach ($timeSeries['parameters'] as $parameter) {
               // Match the parameter name and populate the corresponding weather data field
               switch ($parameter['name']) {
                  case 't': // Temperature in degrees Celsius
                     $forecast['forecast'][$forecastTime]['weather']['temperature'] = round($parameter['values'][0], 1);
                     break;
                 case 'r': // Humidity in percentage (%)
                     $forecast['forecast'][$forecastTime]['weather']['humidity'] = $parameter['values'][0];
                     break;
                 case 'vis': // Visibility in kilometers (km)
                     $forecast['forecast'][$forecastTime]['weather']['visibility'] = round($parameter['values'][0], 1);
                     break;
                 case 'ws': // Wind speed in meters per second (m/s)
                     $forecast['forecast'][$forecastTime]['weather']['windSpeed'] = round($parameter['values'][0], 1);
                     break;
                 case 'gust': // Wind gust speed in meters per second (m/s)
                     $forecast['forecast'][$forecastTime]['weather']['gust'] = round($parameter['values'][0], 1);
                     break;
                 case 'wd': // Wind direction in degrees (°)
                     $forecast['forecast'][$forecastTime]['weather']['windDirection'] = $parameter['values'][0];
                     break;
                 case 'msl': // Atmospheric pressure in hectopascals (hPa)
                     $forecast['forecast'][$forecastTime]['weather']['pressure'] = round($parameter['values'][0], 1);
                     break;
                 case 'spp': // Probability of frozen precipitation in percentage (%)
                     $forecast['forecast'][$forecastTime]['weather']['spp'] = ($parameter['values'][0] == '-9' ? false : $parameter['values'][0]);
                     break;
                 case 'tcc_mean': // Cloud cover percentage (%)
                     $forecast['forecast'][$forecastTime]['weather']['cloudiness'] = (($parameter['values'][0] / 8) * 100);
                     break;
                 case 'tstm': // Thunderstorm probability in percentage (%)
                     $forecast['forecast'][$forecastTime]['weather']['thunderRisk'] = $parameter['values'][0];
                     break;
                 case 'pmean': // Precipitation amount in millimeters (mm)
                     $forecast['forecast'][$forecastTime]['weather']['precipitation'] = $parameter['values'][0];
                     break;
                 
                   case 'Wsymb2': // Weather symbol for the current condition
                       /**
                        * `Wsymb2` is an integer that maps to specific weather conditions such as clear skies,
                        * rain, snow, or thunderstorms. This parameter is used to assign a corresponding Font Awesome icon.
                        *
                        * Icon Mapping:
                        * - 1: Clear sky (sun or moon depending on time of day)
                        * - 2: Partly cloudy (sun with clouds or moon with clouds)
                        * - 3-6: Increasing levels of cloudiness
                        * - 7: Fog or mist
                        * - 8-10: Rain showers of varying intensity
                        * - 11: Thunderstorm
                        * - 12-14: Sleet showers of varying intensity
                        * - 15-17: Snow showers of varying intensity
                        * - 18-20: Rain of varying intensity
                        * - 21: Thunderstorm with rain
                        * - 22-24: Sleet of varying intensity
                        * - 25-27: Snowfall of varying intensity
                        */
                       $iconMap = [
                           1 => $isDay ? 'sun' : 'moon',
                           2 => $isDay ? 'cloud-sun' : 'cloud-moon',
                           3 => 'cloud',
                           4 => 'cloud',
                           5 => 'cloud',
                           6 => 'cloud',
                           7 => 'smog',
                           8 => 'cloud-showers-heavy',
                           9 => 'cloud-showers-heavy',
                           10 => 'cloud-showers-heavy',
                           11 => 'cloud-bolt',
                           12 => 'cloud-sleet',
                           13 => 'cloud-sleet',
                           14 => 'cloud-sleet',
                           15 => 'snowflake',
                           16 => 'snowflake',
                           17 => 'snowflake',
                           18 => 'cloud-rain',
                           19 => 'cloud-rain',
                           20 => 'cloud-rain',
                           21 => 'cloud-bolt',
                           22 => 'cloud-sleet',
                           23 => 'cloud-sleet',
                           24 => 'cloud-sleet',
                           25 => 'snowflake',
                           26 => 'snowflake',
                           27 => 'snowflake',
                       ];
                       // Assign the appropriate icon based on the `Wsymb2` value
                       $forecast['forecast'][$forecastTime]['weather']['icon'] = $iconMap[$parameter['values'][0]] ?? null;
                       break;
               }
           }

           // Set the first future forecast as the current weather if not already set
           if (!isset($forecast['current']['weather'])) {
               $forecast['current']['weather'] = $forecast['forecast'][$forecastTime]['weather'];
           }
       }
   } catch (Exception $e) {
       // Handle exceptions during processing
       echo $e->getMessage();
       return null;
   }

   // Return the final structured forecast data
   return $forecast;
}
