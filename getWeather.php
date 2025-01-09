<?php 
/**
 * Weather API Integration
 *
 * @see https://github.com/arcwindab/weather/ GitHub project
 *
 * @description Fetches and processes weather forecast data from WeatherAPI's service, designed for easy integration with applications requiring detailed weather and air quality information. The script transforms raw API responses into structured and developer-friendly formats, including current conditions, forecasts, and air quality data where available. Features include hourly breakdowns, sunrise/sunset calculations, and Font Awesome icon mappings for visual representations of weather conditions.
 * 
 * @author    Tobias Jonson
 * @copyright 2025 Tobias Jonson @ ArcWind AB
 * @note      This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY
 */

 
require_once('src/php/smhi.php');
require_once('src/php/weatherapi.php');
require_once('src/php/aurora.php');
require_once('src/php/kp.php');
require_once('src/php/place.php');

$latitude            = 59.127241;
$longitude           = 18.102768;
$weatherApiKey       = ""; // https://www.weatherapi.com/my/

$data = getWeatherData($latitude, $longitude, array('weatherDataApi' => $weatherApiKey));
print_r($data);

/**
 * Function to retrieve and process weather data for a specific location.
 * 
 * @param float $latitude Latitude of the location.
 * @param float $longitude Longitude of the location.
 * @param array $keys Optional API keys (e.g., 'weatherDataApi' for Weather API).
 * @return array Returns a structured JSON-like array containing weather, air quality, and aurora data.
 */
function getWeatherData($latitude, $longitude, $keys = array('weatherDataApi' => ''), $cacheDir = false) {
    // Format latitude and longitude: round to 2 decimal places, pad with zeros to ensure consistent length.
    $latitude = str_pad(round(str_replace(',', '.', $latitude), 2), 5, "0");
    $longitude = str_pad(round(str_replace(',', '.', $longitude), 2), 5, "0");

    $cacheFile = false;
    $json = null;

    // Check if a cache directory is defined
    if ($cacheDir !== false) {
        // Ensure the cache directory ends with a trailing slash
        $cacheDir = rtrim(trim($cacheDir), '/') . '/';

        // Verify that the provided cache directory exists
        if (is_dir($cacheDir)) {
            // Construct the cache file name based on latitude and longitude
            $filename = 'weatherCache' . '-' . $latitude . '-' . $longitude . '.json';
            $cacheFile = $cacheDir . $filename;

            // Check if the cache file exists
            if (file_exists($cacheFile)) {
                // If the cache file is older than 1 hour
                if (filemtime($cacheFile) > strtotime('-1 hour')) {
                    // Read and decode the JSON content from the cache file
                    $json = json_decode(file_get_contents($cacheFile), true);

                    // Handle JSON decoding errors
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("JSON decoding error: " . json_last_error_msg());
                        $json = null; // Reset JSON to null as a fallback
                    } else {
                        return $json;
                    }
                } else {
                    // If the file is too old, delete it
                    unlink($cacheFile);
                    $json = null;
                }
            }
        }
    }

    // Retrieve weather data from SMHI and WeatherAPI.
    $smhi = getWeatherDataSMHI($latitude, $longitude); // Fetch weather data from the SMHI service.
    $wd = getWeatherDataWeatherAPI( $latitude, $longitude, (isset($keys['weatherDataApi']) ? $keys['weatherDataApi'] : '' ) );

    // Initialize a template for weather data with default null values.
    $weatherApi = array(
        'time' => null, // Unix timestamp of the forecast time.
        'time_formatted' => null, // Human-readable datetime (e.g., 2025-01-09 14:00).
        'temperature' => null, // Temperature in degrees Celsius (°C).
        'humidity' => null, // Relative humidity in percentage (%).
        'visibility' => null, // Visibility in kilometers (km).
        'gust' => null, // Wind gust speed in meters per second (m/s).
        'windSpeed' => null, // Wind speed in meters per second (m/s).
        'windDirection' => null, // Wind direction in degrees (°).
        'pressure' => null, // Atmospheric pressure in hectopascals (hPa).
        'cloudiness' => null, // Cloud cover percentage (%).
        'thunderRisk' => null, // Thunderstorm probability in percentage (%).
        'precipitation' => null, // Precipitation amount in millimeters (mm).
        'spp' => null, // Probability of frozen precipitation in percentage (%).
        'condition' => null, // Textual description of the weather condition (e.g., "Partly cloudy").
        'uvIndex' => null, // UV index, a measure of ultraviolet radiation risk.
        'feelsLike' => null, // "Feels like" temperature in degrees Celsius (°C).
        'dewPoint' => null, // Dew point temperature in degrees Celsius (°C).
        'heatIndex' => null, // Heat index temperature in degrees Celsius (°C).
        'windChill' => null, // Wind chill temperature in degrees Celsius (°C).
        'icon' => null // Font Awesome icon or similar representation of the weather condition.
    );

    // Initialize the final JSON-like array to store all weather and additional data.
    $json = array(
        'data' => array(
            'latitude' => $latitude, // Latitude in decimal degrees.
            'longitude' => $longitude, // Longitude in decimal degrees.
            'date' => date('Y-m-d H:i:s e'), // Add the current date and time in a readable format (e.g., 2025-01-09 14:00 UTC).
            "location" => getPlace($latitude, $longitude) // Add location
        ),
        'weather' => array(
            'current' => $weatherApi, // Placeholder for current weather data.
            'forecast' => array() // Placeholder for forecast data.
        ),
        'air_quality' => array( // Placeholder for air quality metrics.
            'pm2_5' => null, // Particulate Matter 2.5 in micrograms per cubic meter (µg/m³).
            'pm10' => null, // Particulate Matter 10 in micrograms per cubic meter (µg/m³).
            'o3' => null, // Ozone (O₃) concentration in micrograms per cubic meter (µg/m³).
            'no2' => null, // Nitrogen dioxide (NO₂) concentration in micrograms per cubic meter (µg/m³).
            'so2' => null, // Sulfur dioxide (SO₂) concentration in micrograms per cubic meter (µg/m³).
            'co' => null // Carbon monoxide (CO) concentration in micrograms per cubic meter (µg/m³).
        ),
        'kp' => array(
            'index' => null // Kp index, a unitless measure of geomagnetic activity (scale: 0–9).
        ),
        'aurora' => array(
            'probability' => null // Aurora probability in percentage (%).
        )
    );

    // Merge SMHI current weather data into the JSON array.
    if(!empty($smhi)) {
        foreach ($smhi['current']['weather'] as $key => $value) {
            if ($json['weather']['current'][$key] === null) { // Only update if value is null.
                $json['weather']['current'][$key] = $value;
            }
        }
    }

    // Merge WeatherAPI current weather data into the JSON array.
    if(!empty($wd)) {
        foreach ($wd['current']['weather'] as $key => $value) {
            if ($json['weather']['current'][$key] === null) {
                $json['weather']['current'][$key] = $value;
            }
        }
    }

    // Merge air quality data from WeatherAPI into the JSON array.
    if(!empty($wd)) {
        foreach ($wd['current']['air_quality'] as $key => $value) {
            if ($json['air_quality'][$key] === null) {
                $json['air_quality'][$key] = $value;
            }
        }
    }

    // Add Kp index (geomagnetic activity) to the JSON array.
    $json['kp']['index'] = getKPindex();

    // Add aurora probability for the given location to the JSON array.
    $json['aurora']['probability'] = getAuroraProbability($latitude, $longitude);

    // Process SMHI forecast data and merge it into the JSON array.
    if(!empty($smhi)) {
        foreach ($smhi['forecast'] as $time => $array) {
            // Ensure the time key exists in the forecast array, initialize if missing.
            if (!isset($json['weather']['forecast'][$time])) {
                $json['weather']['forecast'][$time] = $weatherApi;
            }

            // Merge forecast data for the specific time.
            foreach ($array as $a) {
                foreach ($a as $key => $value) {
                    if ($json['weather']['forecast'][$time][$key] === null) {
                        $json['weather']['forecast'][$time][$key] = $value;
                    }
                }
            }
        }
    }

    // Process WeatherAPI forecast data and merge it into the JSON array.
    if(!empty($wd)) {
        foreach ($wd['forecast'] as $time => $array) {
            if (!isset($json['weather']['forecast'][$time])) {
                $json['weather']['forecast'][$time] = $weatherApi;
            }

            foreach ($array as $a) {
                foreach ($a as $key => $value) {
                    if ($json['weather']['forecast'][$time][$key] === null) {
                        $json['weather']['forecast'][$time][$key] = $value;
                    }
                }
            }
        }
    }
    
    // Check if a valid cache file path is provided
    if ($cacheFile !== false) {
        // Check if the $json data is not empty or null
        if ($json) {
            // Save the $json data to the cache file in JSON format
            file_put_contents($cacheFile, json_encode($json));
        }
    }

    // Return the final processed JSON array containing all weather, air quality, and aurora data.
    return $json;
}