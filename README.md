# ArcWind Template
![Static Badge](https://img.shields.io/badge/Version-0.0.1-blue)

## Disclaimer
In the words of Abraham Lincoln:
> Pardon my French

My English, and technical terms in code, is not very good - I'm not a native speaker.  
Sorry for any confusion that may occur.

## Usage

### PHP 
```php
<?php 

    // Initialize the final JSON-like array to store all weather and additional data.
    array(
        'data' => array(
            'latitude' => null, // Latitude in decimal degrees.
            'longitude' => null, // Longitude in decimal degrees.
            'date' => null, // Add the current date and time in a readable format (e.g., 2025-01-09 14:00 UTC).
            "location" => null // Location
        ),
        'weather' => array(
            'current' => array(
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
            ), // Placeholder for current weather data.
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

?>
```