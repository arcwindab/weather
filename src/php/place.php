<?php
/**
 * Weather
 * @file: src/php/place.php
 *
 * @see https://github.com/arcwindab/weather/ GitHub project
 *
 * @description Retrieves a human-readable place name (e.g., "City, Country") for a specific location based on its latitude and longitude. Utilizes the OpenStreetMap Nominatim API to perform reverse geocoding. If no city or town is available, it defaults to only the country name. Designed to be used in applications where location details are required for given coordinates.
 * 
 * @author    Tobias Jonson
 * @copyright 2025 Tobias Jonson @ ArcWind AB
 * @note      This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY
 */

/**
 * Retrieves the name of a place (e.g., city and country) based on latitude and longitude using the OpenStreetMap Nominatim API.
 * 
 * @param float $latitude The latitude of the location.
 * @param float $longitude The longitude of the location.
 * @return string|false The place name in the format "City, Country", or false if no data is available.
 */
function getPlace($latitude, $longitude) {
    $place = false; // Default to false if no place information is found

    // Construct the API URL with the provided latitude and longitude
    $url = "https://nominatim.openstreetmap.org/reverse?lat=$latitude&lon=$longitude&format=json";

    // Create a context with a User-Agent header for the API request
    $options = [
        'http' => [
            'header' => "User-Agent: AppName/1.0\r\n", // Required by Nominatim to identify the application
            'timeout' => 10 // Timeout for the request in seconds
        ]
    ];

    // Create the stream context
    $context = stream_context_create($options);

    // Perform the API request
    $response = file_get_contents($url, false, $context);

    // Check if the API request was successful
    if ($response === FALSE) {
        echo "API request failed."; // Output an error message if the request fails
    } else {
        // Decode the JSON response into a PHP associative array
        $data = json_decode($response, true);

        // Check if the response contains address information
        if (isset($data['address'])) {
            // Extract the city (or town) and country, if available
            $place = (isset($data['address']['city']) ? $data['address']['city'] . ", " : 
                      (isset($data['address']['town']) ? $data['address']['town'] . ", " : ''))
                   . $data['address']['country'];
        }
    }

    // Return the place information or false if no data was found
    return $place;
}
