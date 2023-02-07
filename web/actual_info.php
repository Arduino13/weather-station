<?php
	/*
 	* Page for displaying current measurements and detailed history of selected day
	 */
	ini_set('display_erros', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	require_once('SqlParser.php');
	$sql = new SqlParser();

	$tchp_STR = 'tchp';
	$temp_STR = 'temperature';
	$hum_STR = 'humidity';
	$press_STR = 'pressure';
	$wind_s_STR = 'wind_s';
	$wind_d_STR = 'wind_d';
	$rain_STR = 'rain';

	function wind_direction($angle){ //converts angle into cardinal direction
		if($angle>337.5 || $angle<=22.5) return 'V';
		if($angle>22.5 && $angle<=67.5) return 'JV';
		if($angle>67.5 && $angle<=112.5) return 'J';
		if($angle>112.5 && $angle<=157.5) return 'JZ';
		if($angle>157.5 && $angle<=202.5) return 'Z';
		if($angle>202.5 && $angle<=247.5) return 'SZ';
		if($angle>247.5 && $angle<=292.5) return 'S';
		if($angle>292.5 && $angle<=337.5) return 'SV';
	}

	//string to function map for getting current values
	$fields=array(
		$tchp_STR => function($time_from, $time_to, $sql) {
			$result = array_values($sql->tchp($time_from, $time_to, '1m')[1]);
			return $result;
		},
		$wind_s_STR => function($time_from, $time_to, $sql) {
			$result = array_values($sql->wind_s($time_from, $time_to, '1m')[1]);
			return $result[1];
		},
		$wind_d_STR => function($time_from, $time_to, $sql) {
			$result = array_values($sql->wind_d($time_from, $time_to, '1m')[1]);
			return wind_direction($result[1]);
		},
		$rain_STR => function($time_from, $time_to, $sql) {
			$result = array_values($sql->rainSUM($time_from, $time_to)[0]);
			return $result[1];
		}
	);
	//string to function map for getting average values in given time section
	$fields_AVG=array(
		$tchp_STR => function($time_from, $time_to, $sql) {
			return array_values($sql->tchpAVG($time_from, $time_to)[0]);
		},
		$wind_s_STR => function($time_from, $time_to, $sql) {
			$result = array_values($sql->wind_sAVG($time_from, $time_to)[0]);
			return $result[1];
		},
		$wind_d_STR => function($time_from, $time_to, $sql) {
			$result = array_values($sql->wind_dAVG($time_from, $time_to)[0]);
			return wind_direction($result[1]);
		},
		$rain_STR => function($time_from, $time_to, $sql) {
			$result = array_values($sql->rainSUM($time_from, $time_to)[0]);
			return $result[1];
		}
	);
	//string to function map for getting maximum or minimum 
	//null means that given value won't be displayed because it doesn't make sense for example
	//minimal wind speed will be usually zero
	$fields_MM=array(
		$temp_STR => function($time_from, $time_to, $min, $sql) {
			$result = array_values($sql->tchpMM($time_from, $time_to, $min, $unit = 'temp_g')[0]);
			return $result[1];
		},
		$hum_STR => function($time_from, $time_to, $min, $sql) {
			$result = array_values($sql->tchpMM($time_from, $time_to, $min, $unit = 'hum')[0]);
			return $result[1];
		},
		$press_STR => function($time_from, $time_to, $min, $sql){
			$result = array_values($sql->tchpMM($time_from, $time_to, $min, $unit = 'press')[0]);
			return $result[1];
		},	
		$wind_s_STR => function($time_from, $time_to, $min, $sql) {
			if($min){
				return null; 
			}
			else{
				$result = array_values($sql->wind_gMAX($time_from, $time_to)[0]);
				return $result[1];
			}
		},
		$wind_d_STR => function($time_from, $time_to, $min, $sql) {
			return null;
		},
		$rain_STR => function($time_from, $time_to, $min, $sql) {
			return null;
		}
	);

	$fields_name=array(
		$temp_STR => "Temperature outside",
		$wind_s_STR => "Wind speed",
		$wind_d_STR => "Wind direction",
		$hum_STR => "Air humidity",
		$press_STR => "Air pressure",
		$rain_STR => "Precipitation"
	);
	$fields_unit=array(
		$temp_STR => "C°",
		$wind_s_STR => "m/s",
		$wind_d_STR => "",
		$hum_STR => "%",
		$press_STR => "hPa",
		$rain_STR => "mm"
	);

	date_default_timezone_set('Europe/Prague');

	$time_to = new DateTime();
	$time_to->sub(new DateInterval('PT1M'));

	$time_from = new DateTime();
	$time_from->sub(new DateInterval('PT3M'));

	$time_from_GUSTS = new DateTime();
	$time_from_GUSTS->sub(new DateInterval('PT10M'));

	$time_from_rain = new DateTime();
	$time_from_rain->setTime(0,0);
	$time_to_rain = new DateTime();
	$time_to_rain->setTime(0,0);
	$time_to_rain->add(new DateInterval('P1D'));

	//in case day is not selected it's displaying current values
	if(!isset($_POST['date']) || $_POST['date'] == 'nodef'){
		echo '<th>Current measurements</th></tr><tr>';

		$fields_loaded_MM = array(
			$wind_s_STR => $fields_MM[$wind_s_STR]($time_from_GUSTS, $time_to, FALSE, $sql)
		);

		$tchp_values = $fields[$tchp_STR]($time_from, $time_to, $sql);
		$fields_loaded = array(
			$temp_STR => $tchp_values[2],
			$wind_s_STR => $fields[$wind_s_STR]($time_from, $time_to, $sql),
			$wind_d_STR => $fields[$wind_d_STR]($time_from, $time_to, $sql),
			$hum_STR => $tchp_values[3],
			$press_STR => $tchp_values[4],
			$rain_STR => $fields[$rain_STR]($time_from_rain, $time_to_rain, $sql)
		);
		
		foreach($fields_loaded as $key => $value){
			echo '<td id="actual">';
			echo '<h5>'.$fields_name[$key].'</h5>';
			echo '<div id="values"><p>'.$value.' '.$fields_unit[$key].'</p></div>';
			if($key=='wind_s') {
				$max = $fields_loaded_MM[$key]; 
				echo '<div id="max"><p>Max: '.$max.' '.$fields_unit[$key].'</p></div>';
			}
			echo '</td>';
		}

		echo '<td><div class="clear"></div></td>';
	}
	else{
		echo '<th>Averaged values</th></tr><tr>';

		$time_from = DateTime::createFromFormat('d/m/Y', $_POST['date']);
		$time_to = DateTime::createFromFormat('d/m/Y', $_POST['date']);
		$time_from->setTime(0,0);
		$time_to->setTime(0,0);
		$time_to->add(new DateInterval('P1D'));

		$fields_loaded_Min = array(
			$temp_STR => $fields_MM[$temp_STR]($time_from, $time_to,TRUE, $sql),
			$wind_s_STR => $fields_MM[$wind_s_STR]($time_from, $time_to,TRUE, $sql),
			$wind_d_STR => $fields_MM[$wind_d_STR]($time_from, $time_to,TRUE, $sql),
			$hum_STR => $fields_MM[$hum_STR]($time_from, $time_to,TRUE, $sql),
			$press_STR => $fields_MM[$press_STR]($time_from, $time_to,TRUE, $sql),
			$rain_STR => $fields_MM[$rain_STR]($time_from, $time_to,TRUE, $sql)
		);

		$fields_loaded_Max = array(
			$temp_STR => $fields_MM[$temp_STR]($time_from, $time_to, FALSE, $sql),
			$wind_s_STR => $fields_MM[$wind_s_STR]($time_from, $time_to, FALSE, $sql),
			$wind_d_STR => $fields_MM[$wind_d_STR]($time_from, $time_to, FALSE, $sql),
			$hum_STR => $fields_MM[$hum_STR]($time_from, $time_to, FALSE, $sql),
			$press_STR => $fields_MM[$press_STR]($time_from, $time_to, FALSE, $sql),
			$rain_STR => $fields_MM[$rain_STR]($time_from, $time_to, FALSE, $sql)
		);

		$tchp_values_AVG = $fields_AVG[$tchp_STR]($time_from, $time_to, $sql);
		$fields_loaded_AVG = array(
			$temp_STR => $tchp_values_AVG[2],
			$wind_s_STR => $fields_AVG[$wind_s_STR]($time_from, $time_to, $sql),
			$wind_d_STR => $fields_AVG[$wind_d_STR]($time_from, $time_to, $sql),
			$hum_STR => $tchp_values_AVG[3],
			$press_STR => $tchp_values_AVG[4],
			$rain_STR => $fields_AVG[$rain_STR]($time_from, $time_to, $sql)
		);

		foreach($fields_loaded_AVG as $key => $value){
			echo '<td id="actual">';
			echo '<h5>'.$fields_name[$key].'</h5>';
			if(gettype($value) == 'double' || gettype($value) == 'float'){
				$value = round($value, 2);
			}
			echo '<div id="values"><p>'.$value.' '.$fields_unit[$key].'</p></div>';

			$min = $fields_loaded_Min[$key];
			if(!is_null($min)){
				echo '<div id="min"><p>Min: '.$min.' '.$fields_unit[$key].'</p></div>';
			}

			$max = $fields_loaded_Max[$key];
			if(!is_null($max)){ 
				echo '<div id="max"><p>Max: '.$max.' '.$fields_unit[$key].'</p></div>';
			}
			echo '</td>';
		}
		echo '<td><div class="clear"></div></td>';
	}
?>
	

