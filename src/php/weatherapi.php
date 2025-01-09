<?php 
/**
 * Weather API Integration
 * @file: src/php/weatherapi.php
 *
 * @see https://github.com/arcwindab/weather/ GitHub project
 * @see https://www.weatherapi.com/docs/ API Documentation
 *
 * @description Fetches and processes weather forecast data from WeatherAPI's service, designed for easy integration with applications requiring detailed weather and air quality information. The script transforms raw API responses into structured and developer-friendly formats, including current conditions, forecasts, and air quality data where available. Features include hourly breakdowns, sunrise/sunset calculations, and Font Awesome icon mappings for visual representations of weather conditions.
 * 
 * @author    Tobias Jonson
 * @copyright 2025 Tobias Jonson @ ArcWind AB
 * @note      This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY
 */

 /**
 * Retrieves weather data using the WeatherAPI service.
 *
 * @param float $latitude The latitude coordinate for the weather location (valid range: -90 to 90).
 * @param float $longitude The longitude coordinate for the weather location (valid range: -180 to 180).
 * @param string $apiKey A valid API key for accessing the WeatherAPI service.
 * @return array|null Returns an associative array with weather data or null if an error occurs.
 *
 * Expected return format:
 * [
 *   'current' => [
 *     'air_quality' => [  // Optional, available only if AQI data exists.
 *       'pm2_5' => float|false,  // Particulate matter <2.5 µm.
 *       'pm10' => float|false,   // Particulate matter <10 µm.
 *       'o3' => float|false,     // Ozone (O3) concentration.
 *       'no2' => float|false,    // Nitrogen dioxide (NO2).
 *       'so2' => float|false,    // Sulfur dioxide (SO2).
 *       'co' => float|false,     // Carbon monoxide (CO).
 *     ],
 *   ],
 *   'forecast' => [
 *     <timestamp> => [
 *       'weather' => [
 *         'temperature' => float,      // Temperature in Celsius.
 *         'humidity' => int|false,     // Humidity percentage.
 *         'visibility' => float|false, // Visibility in kilometers.
 *         'gust' => float|false,       // Wind gust speed in m/s.
 *         'windSpeed' => float|false,  // Wind speed in m/s.
 *         'windDirection' => int|false,// Wind direction in degrees.
 *         'pressure' => float|false,   // Atmospheric pressure in millibars.
 *         'cloudiness' => int|false,   // Cloud cover percentage.
 *         'thunderRisk' => bool,       // True if thunder is in the weather condition text.
 *         'precipitation' => float|false, // Precipitation in millimeters.
 *         'condition' => string|false, // Weather condition description.
 *         'uvIndex' => float|false,    // UV index.
 *         'feelsLike' => float|false,  // Feels like temperature in Celsius.
 *         'dewPoint' => float|false,   // Dew point temperature in Celsius.
 *         'heatIndex' => float|false,  // Heat index in Celsius.
 *         'windChill' => float|false,  // Wind chill temperature in Celsius.
 *         'icon' => string,            // Font Awesome icon for the condition.
 *       ],
 *     ],
 *   ],
 * ]
 *
 * Input Constraints:
 * - $latitude: Must be a float between -90 and 90.
 * - $longitude: Must be a float between -180 and 180.
 * - $apiKey: Must be a non-empty string.
 */

function getWeatherDataWeatherAPI($latitude, $longitude, $apiKey) {
    if(trim($apiKey) == '') {
        return false;
    }

    // API URL for WeatherAPI with required parameters
    $weatherApiUrl = "http://api.weatherapi.com/v1/forecast.json?key=$apiKey&q=$latitude,$longitude&days=14&aqi=yes&alerts=no";

    try {
        // Fetch weather data from WeatherAPI
        $response = file_get_contents($weatherApiUrl);

        // Check if the response is valid
        if ($response === false) {
            throw new Exception("Failed to retrieve data from WeatherAPI.");
            return null;
        }

        // Decode the JSON response into a PHP array
        $data = json_decode($response, true);

        // Verify the response contains necessary data
        if (!isset($data['forecast']['forecastday'])) {
            throw new Exception("The response lacks required weather forecast data.");
            return null;
        }
    } catch (Exception $e) {
        // Handle errors by outputting the error message
        echo $e->getMessage();
        return null;
    }

    // Initialize the forecast array
    $forecast = ['current' => [], 'forecast' => []];

    // Round current time to the nearest hour
    $currentTime = floor(time() / 3600) * 3600;

    // Process forecast data
    foreach ($data['forecast']['forecastday'] as $day) {
        // Get the date for the current day in the forecast
        $dayDate = strtotime($day['date']);

        // Use date_sun_info to calculate sunrise and sunset for the specific location and date
        $sunInfo = date_sun_info($dayDate, $latitude, $longitude);
        $sunrise = $sunInfo['sunrise'];
        $sunset = $sunInfo['sunset'];

        // Process hourly weather data
        foreach ($day['hour'] as $hour) {
            $forecastTime = strtotime($hour['time']);

            // Only include future weather data rounded to the nearest hour
            if ($currentTime < $forecastTime) {
                $isDay = $forecastTime > $sunrise && $forecastTime < $sunset;

                // Determine Font Awesome icon for the weather condition
                $iconMap = [
                    'clear' => $isDay ? 'sun' : 'moon',
                    'partly cloudy' => $isDay ? 'cloud-sun' : 'cloud-moon',
                    'cloudy' => 'cloud',
                    'overcast' => 'cloud',
                    'mist' => 'smog',
                    'fog' => 'smog',
                    'rain' => 'cloud-showers-heavy',
                    'thunder' => 'cloud-bolt',
                    'sleet' => 'cloud-sleet',
                    'snow' => 'snowflake',
                    'drizzle' => 'cloud-drizzle',
                ];

                $icon = $isDay ? 'cloud-sun' : 'cloud-moon'; // Default icon
                foreach ($iconMap as $key => $mappedIcon) {
                    if (strpos(strtolower($hour['condition']['text']), $key) !== false) {
                        $icon = $mappedIcon;
                        break;
                    }
                }

                // Add weather data for this hour
                $forecast['forecast'][$forecastTime]['weather'] = [
                    'time' => $forecastTime, // Unix timestamp of the forecast time.
                    'time_formatted' => date('Y-m-d H:i', $forecastTime), // Human-readable format (e.g., 2025-01-09 14:00).
                    
                    // Weather parameters:
                    'temperature' => round($hour['temp_c'], 1), // Temperature in degrees Celsius.
                    'humidity' => $hour['humidity'] ?? false, // Relative humidity in percentage (%).
                    'visibility' => $hour['vis_km'] ?? false, // Visibility in kilometers (km).
                    
                    // Wind data:
                    'gust' => round(($hour['gust_kph'] ?? 0) / 3.6, 2), // Wind gust speed, converted from km/h to m/s.
                    'windSpeed' => round(($hour['wind_kph'] ?? 0) / 3.6, 2), // Wind speed, converted from km/h to m/s.
                    'windDirection' => $hour['wind_degree'] ?? false, // Wind direction in degrees (°).
                    
                    // Atmospheric conditions:
                    'pressure' => $hour['pressure_mb'] ?? false, // Atmospheric pressure in millibars (mb).
                    'cloudiness' => $hour['cloud'] ?? false, // Cloud cover in percentage (%).
                    'thunderRisk' => strpos(strtolower($hour['condition']['text']), 'thunder') !== false, // Boolean, true if the condition mentions thunder.
                    'precipitation' => $hour['precip_mm'] ?? false, // Precipitation amount in millimeters (mm).

                    // Weather condition:
                    'condition' => $hour['condition']['text'] ?? false, // Textual description of the weather condition (e.g., "Partly cloudy").
                    'uvIndex' => $hour['uv'] ?? false, // UV index, a measure of ultraviolet radiation risk.

                    // Derived metrics:
                    'feelsLike' => $hour['feelslike_c'] ?? false, // "Feels like" temperature in degrees Celsius.
                    'dewPoint' => $hour['dewpoint_c'] ?? false, // Dew point temperature in degrees Celsius.
                    'heatIndex' => $hour['heatindex_c'] ?? false, // Heat index temperature in degrees Celsius.
                    'windChill' => $hour['windchill_c'] ?? false, // Wind chill temperature in degrees Celsius.

                    // Icon:
                    'icon' => $icon, // Font Awesome icon representation of the weather condition.
                ];

                // Set the first future forecast as the current weather if not already set
                if (!isset($forecast['current']['weather'])) {
                    $forecast['current']['weather'] = $forecast['forecast'][$forecastTime]['weather'];
                }
            }
        }
    }

    // Add current air quality data if available in the API response
    if (isset($data['current']['air_quality'])) {
        $forecast['current']['air_quality'] = [
            'pm2_5' => $data['current']['air_quality']['pm2_5'] ?? false, // Particulate Matter 2.5 in micrograms per cubic meter (µg/m³)
            'pm10' => $data['current']['air_quality']['pm10'] ?? false,   // Particulate Matter 10 in micrograms per cubic meter (µg/m³)
            'o3' => $data['current']['air_quality']['o3'] ?? false,       // Ozone (O₃) concentration in micrograms per cubic meter (µg/m³)
            'no2' => $data['current']['air_quality']['no2'] ?? false,     // Nitrogen dioxide (NO₂) concentration in micrograms per cubic meter (µg/m³)
            'so2' => $data['current']['air_quality']['so2'] ?? false,     // Sulfur dioxide (SO₂) concentration in micrograms per cubic meter (µg/m³)
            'co' => $data['current']['air_quality']['co'] ?? false,       // Carbon monoxide (CO) concentration in micrograms per cubic meter (µg/m³)
        ];
    }

    // Return the structured forecast data
    return $forecast;
}
