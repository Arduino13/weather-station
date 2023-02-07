<?php
/*
 * Script that's spawned once per hour to generate graphs 
 */
$server = "http://127.0.0.1/";
$dir_prefix = "/var/www/html/web_shots/";

$names = array('temperature','wind_s','wind_d','humidity','pressure','rain');
$intervals = array('temperature' =>'10m',
	'wind_s' => '10m',
	'wind_d' => '2h',
	'humidity' => '10m',
	'pressure' => '10m',
       	'rain' => '2h');

$date_from = new DateTime();
$date_to = new DateTime();
$date_to->add(new DateInterval('P1D'));

foreach($names as $key){
	$page = "{$server}graphs.php?key={$key}&width=360&height=180&step={$intervals[$key]}&step_G=7200";
	$page = "{$page}&date_from={$date_from->format('Y-m-d')}&date_to={$date_to->format('Y-m-d')}";

	$output = $dir_prefix.$key;
	file_put_contents($output,file_get_contents($page));
}
?>
