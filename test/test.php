<?php

require_once('../src/php/smhi.php');
require_once('../src/php/weatherapi.php');
require_once('../src/php/aurora.php');
require_once('../src/php/kp.php');
require_once('../src/php/place.php');

$latitude            = 59.127241;
$longitude           = 18.102768;


$smhi = getWeatherDataSMHI($latitude, $longitude); // Fetch weather data from the SMHI service.
$wd = getWeatherDataWeatherAPI( $latitude, $longitude, '');
$place = getPlace($latitude, $longitude);
$kp = getKPindex();
$aurora = getAuroraProbability($latitude, $longitude);

if ((!empty($smhi)) && ($place != '') && ($kp != '') && ($aurora != '')) {
    echo "Test passed!\n";
    exit(0); // Lyckas
} else {
    echo "Test failed!\n";
    exit(1); // Misslyckas
}
?>
