<?php 
/**
 * Weather
 * @file: src/php/kp.php
 *
 * @see https://github.com/arcwindab/weather/ GitHub project
 *
 * @description Fetches and processes weather forecast data from WeatherAPI's service, designed for easy integration with applications requiring detailed weather and air quality information. The script transforms raw API responses into structured and developer-friendly formats, including current conditions, forecasts, and air quality data where available. Features include hourly breakdowns, sunrise/sunset calculations, and Font Awesome icon mappings for visual representations of weather conditions.
 * 
 * @author    Tobias Jonson
 * @copyright 2025 Tobias Jonson @ ArcWind AB
 * @note      This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY
 */

/**
 * Function to fetch the latest planetary Kp index (geomagnetic activity index) from NOAA API.
 * 
 * @return float|null The latest Kp index value, or null if the data cannot be fetched or an error occurs.
 */
function getKPindex() {
    // URL for the NOAA Planetary Kp Index API, providing real-time geomagnetic activity data.
    $kpApiUrl = "https://services.swpc.noaa.gov/json/planetary_k_index_1m.json";

    // Initialize the Kp index as null (default value if no data is available or an error occurs).
    $kpIndex = null;

    try {
        // Fetch the Kp index data from the API.
        $response = file_get_contents($kpApiUrl);

        // If the API call fails (e.g., network error), handle it gracefully.
        if ($response === false) {
            // You could uncomment the following line to throw an exception if desired:
            // throw new Exception("Could not fetch data from the Kp-index API.");
        }

        // Decode the JSON response into an associative array.
        $kpData = json_decode($response, true);

        // Retrieve the latest Kp index value from the data array.
        // The latest value is the last element in the array.
        return $kpIndex = $kpData[count($kpData) - 1]['kp_index'];
    } catch (Exception $e) {
        // Handle any exceptions (e.g., JSON decoding issues, network errors).
        $e->getMessage(); // Log or use this message for debugging if needed.
    }

    // Return null if an error occurred or no data was available.
    return null;
}
