<?php 
/**
 * Weather
 * @file: src/php/aurora.php
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
 * Function to retrieve the aurora probability for a specific location based on latitude and longitude.
 * 
 * @param float $latitude The latitude of the location.
 * @param float $longitude The longitude of the location.
 * @return float|null The aurora probability as a percentage (0-100), or null if no data is available.
 */
function getAuroraProbability($latitude, $longitude) {
    // URL for the NOAA Ovation Aurora API, providing real-time aurora data.
    $auroraApiUrl = "https://services.swpc.noaa.gov/json/ovation_aurora_latest.json";

    // Initialize the aurora probability as null (default value if no match is found or an error occurs).
    $auroraProbability = null;

    try {
        // Fetch data from the Aurora API.
        $response = file_get_contents($auroraApiUrl);

        // If fetching the API response fails, throw an exception.
        if ($response === false) {
            throw new Exception("Could not fetch data from the Aurora API.");
        }

        // Decode the JSON response from the API into an associative array.
        $auroraData = json_decode($response, true);

        // Loop through the 'coordinates' field in the API response.
        // Each 'coord' is an array where:
        //   - coord[0] = longitude
        //   - coord[1] = latitude
        //   - coord[2] = aurora probability at the coordinate
        foreach ($auroraData['coordinates'] as $coord) {
            // Check if the current coordinate matches the given latitude and longitude within a tolerance of 1 degree.
            if (abs($coord[1] - $latitude) <= 1 && abs($coord[0] - $longitude) <= 1) {
                // If a match is found, set the aurora probability.
                $auroraProbability = $coord[2]; // The probability is given as a percentage.
            }
        }
    } catch (Exception $e) {
        // Handle any exceptions (e.g., network errors or decoding issues) by logging or ignoring the error.
        $e->getMessage(); // Log or use this message for debugging if needed.
    }

    // Return the calculated aurora probability, or null if no match was found.
    return $auroraProbability;
}
