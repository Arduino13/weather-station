<?php
	/*
 	* Script for generating charts using jpgraph library 
	* It takes multiple parameters
	* height, width - resolutiong of graph
	* key - which value to plot
	* step - used as timestep in SqlParser
	* step_G - distance between two tick on x axis of graph in seconds
	* date_from, date_to - defines time section to plot
	* avg - optional parameter that draws smoothed plot and max and min points of plotted value
	 */
	require_once('jpgraph/src/jpgraph.php');
	require_once('jpgraph/src/jpgraph_date.php');
	require_once('jpgraph/src/jpgraph_line.php');
	require_once('jpgraph/src/jpgraph_bar.php');
	require_once('jpgraph/src/jpgraph_scatter.php');
	require_once('SqlParser.php');
	require_once('helper.php');

	ini_set('display_erros', 0);
	ini_set('display_startup_errors', 0);
	error_reporting(E_ERROR);

	$names=array('temperature'=>'Temperature',
		'wind_s'=>'Wind speed',
		'wind_d'=>'Wind direction',
		'humidity'=>'Air humidity',
		'pressure'=>'Air pressure',
		'rain'=>'Precipitation',
		'temp'=>"Station's temperature",
		'wind_g'=>'Wind gusts');

	//helper function that converts influxdb output to simpler array structure
	function data_extract($input){
		$toReturn = array();
		foreach($input as $record){
			$record = array_values($record);
			array_push(
				$toReturn,
				array($record[0], $record[1])
			);
		}

		return $toReturn;
	}

	$fields=array(
		'temperature' => function($time, $time_to, $sql, $timestep) {
			$result = $sql->tchp($time, $time_to, $timestep, $unit = 'temp_g');
			return data_extract($result);
		},
		'wind_s' => function($time, $time_to, $sql, $timestep) {
			$result = $sql->wind_s($time, $time_to, $timestep);
			return data_extract($result);
		},
		'wind_d' => function($time, $time_to, $sql, $timestep) {
			$result = $sql->wind_d($time, $time_to, $timestep);	
			return data_extract($result);
		},
		'humidity' => function($time, $time_to, $sql, $timestep) {
			$result = $sql->tchp($time, $time_to, $timestep, $unit = 'hum');
			return data_extract($result);
		},
		'pressure' => function($time, $time_to, $sql, $timestep) {
			$result = $sql->tchp($time, $time_to, $timestep, $unit = 'press');
			return data_extract($result);
		},
		'rain' => function($time, $time_to, $sql, $timestep) {
			$result = $sql->rain($time,$time_to, $timestep);	
			return data_extract($result);
		},
		'temp' => function($time, $time_to, $sql, $timestep) {
			$result = $sql->tchp($time, $time_to, $timestep, $unit = 'temp');
			return data_extract($result);
		},
		'wind_g' => function($time, $time_to, $sql, $timestep) {
			$result = $sql->wind_s($time, $time_to, $timestep, TRUE);
			return data_extract($result);
		}
	);

	$fields_extreme=array(
		'temperature' => function($time, $time_to, $sql){
			return array(
				array_values($sql->tchpMM($time, $time_to, $min = FALSE, $unit = 'temp_g')[0]),
				array_values($sql->tchpMM($time, $time_to, $min = TRUE, $unit = 'temp_g')[0])
			);
		},
		'wind_s' => function($time, $time_to, $sql){
			return array(
				array_values($sql->wind_sMAX($time, $time_to)[0]),
				null
			);
		},
		'wind_d' => null,
		'humidity' => function($time, $time_to, $sql){
			return array(
				array_values($sql->tchpMM($time, $time_to, $min = false, $unit = 'hum')[0]),
				array_values($sql->tchpMM($time, $time_to, $min = true, $unit = 'hum')[0])
			);
		},
		'pressure' => function($time, $time_to, $sql){
			return array(
				array_values($sql->tchpMM($time, $time_to, $min = false, $unit = 'press')[0]),
				array_values($sql->tchpMM($time, $time_to, $min = true, $unit = 'press')[0])
			);
		},
		'rain' => null,
		'temp' => function($time, $time_to, $sql){
			return array(
				array_values($sql->tchpMM($time, $time_to, $min = false, $unit = 'temp')[0]),
				array_values($sql->tchpMM($time, $time_to, $min = true, $unit = 'temp'))	
			);
		},
		'wind_g' => function($time, $time_to, $sql){
			return array(
				array_values($sql->wind_gMAX($time, $time_to)[0]),
				null
			);
		}
	);

	$fields_types=array('temperature' => 'lineplot',
			'wind_s' => 'lineplot',
			'wind_d' => 'dotplot',
			'humidity' => 'lineplot',
			'pressure' => 'lineplot',
			'rain' => 'barplot',
			'temp' => 'lineplot',
			'wind_g' => 'lineplot');
	$fields_names=array('temperature' => 'C°',
			'wind_s' => 'm/s',
			'wind_d' => '',
			'humidity' => '%',
			'pressure' => 'hPa',
			'rain' => 'mm',
			'temp' => 'C°',
			'wind_g' => 'm/s');

$minValue = null;
$maxValue = null;
$x_name = 'Time';

error_reporting(E_ERROR);

//calback function for plot with max and min point, it return's mark color
function markCallback($value){
	global $minValue;
	global $maxValue;
	
	if($value == null){
		return null;
	}
	if($maxValue == $value){
		return array(5,'','red');
	} else if($minValue == $value){
		return array(5,'','blue');
	}
}

//formats data to have one place precision
function formatCallback($label){
	return round((float)$label, $precision=1);
}

function createPlot($type, $datax, $datay, $angles = null){
	$plot = null;

	if($type == 'lineplot'){
		$plot = new LinePlot($datay, $datax);
	} else if($type == 'barplot'){
		$plot = new BarPlot($datay, $datax);
	} else if($type == 'dotplot'){
		$angles_new = array();
		foreach($angles as $angle){
			array_push($angles_new, 360-$angle); //in database angle is measured anticlokwise
		}
		$plot = new FieldPlot($datay, $datax, $angles_new);
	} else if($type == 'scatterPlot'){
		$plot = new ScatterPlot($datay, $datax);
	}

	return $plot;
}

function createGraph($name, $type, $width, $height, $x_name, $y_name,
	$tick_x_step, $plots, $aXMin = 0, $aXMax = 0){
	
	$graph = new Graph($width, $height);

	$graph->title->Set($name);
	$graph->SetScale('datlin', 0, 0, $aXMin, $aXMax);
	if($tick_x_step < (24*60*60)){ //for smaller graphs on page with current values
		$graph->xaxis->scale->SetTimeAlign(HOURADJ_2);
		$graph->xaxis->scale->SetDateFormat('H');
	}else{
		$graph->xaxis->scale->ticks->Set($tick_x_step);
		$graph->xaxis->scale->SetDateFormat('y-m-d');
	}
	$graph->xaxis->SetPos('min');
	$graph->xaxis->title->Set($x_name);
	$graph->yaxis->title->Set($y_name);

	for($i=0; $i < count($plots); $i++){
		$graph->Add($plots[$i]);
	}

	$graph->Stroke();
}

if(isset($_GET['date_from']) && isset($_GET['date_to']) && isset($_GET['step']) &&
	isset($_GET['key']) && isset($_GET['step_G']) && isset($_GET['width']) &&
	isset($_GET['height'])){
//--------------------------------LOADING DATA---------------------------------
	$sql = new SqlParser();
	
	$date_from = DateTime::createFromFormat('Y-m-d',$_GET['date_from']);
	$date_to = DateTime::createFromFormat('Y-m-d',$_GET['date_to']);
	$date_from->setTime(0,0);
	$date_to->setTime(0,0);

	$step = $_GET['step'];
	$step_x = $_GET['step_G'];
	$key = $_GET['key'];
	$avg = $_GET['avg'];


	$width = $_GET['width'];
	$height = $_GET['height'];

	$records = $fields[$key]($date_from, $date_to, $sql, $step);
	$extremes = null;
	if($fields_extreme[$key] != null){
		$extremes = $fields_extreme[$key]($date_from, $date_to, $sql);
	}

	$datax = array();
	$values = array();
	$datax_ext = array();
	$values_ext = array();
	$plots = array();
//--------------------------------PREPARING DATA------------------------------
	foreach($records as $record){
		$time = new DateTime($record[0]);
		$time->setTimezone(new DateTimeZone('Europe/Prague'));

		array_push($datax, $time->format('U'));
		array_push($values, $record[1]);
	}
	if($extremes != null){
		foreach($extremes as $record){
			$record_time = substr($record[0],0,19).'Z'; //cutting off nanosecond part
			$time_ext = new DateTime($record_time);
			$time_ext->setTimezone(new DateTimeZone('Europe/Prague'));

			array_push($datax_ext, $time_ext->format('U'));
			array_push($values_ext, $record[1]);
		}
	}
	$now = new DateTime();
	for($i = 0; $i < count($values); $i++){ //null parts of plot that are in future
		$time = new DateTime($records[$i][0]);
		if($time > $now){
			$values[$i] = null;
		}
	}
//--------------------------------CREATING PLOTS------------------------------
	
	if($extremes != null && $avg != ''){
		global $maxValue;
		global $minValue;

		$maxValue = $values_ext[0];
		$minValue = $values_ext[1];

		$plot_ext = createPlot(
			'scatterPlot',
			$datax_ext,
			$values_ext
		);
		$plot_ext->mark->SetType(MARK_FILLEDCIRCLE);
		$plot_ext->mark->SetCallBack('markCallback');
		$plot_ext->value->SetFormatCallback('formatCallback');
		$plot_ext->mark->Show();
		$plot_ext->value->Show();
		$plot_ext->value->SetAlign('right');
		array_push($plots, $plot_ext);
	}
	
	if($fields_types[$key] == 'dotplot'){
		$datay = array();
		$angles = $values;
		for($i = 0; $i < count($angles); $i++){
			if($angles[$i] != null) array_push($datay, 1);
			else array_push($datay, null);
		}

		array_push(
			$plots,
			createPlot($fields_types[$key], $datax, $datay, $angles)
		);
	} else if($fields_types[$key] == 'barplot') {
		array_push(
			$plots,
			createPlot($fields_types[$key], $datax, $values)
		);
		end($plots)->SetWidth(
			(int)($width/($date_to->format('U') - $date_from->format('U'))*$step_x)*0.25
		); //because axis is date in seconds, bars would be very thin
	} else if($fields_types[$key] == 'lineplot'){
		array_push(
			$plots,
			createPlot($fields_types[$key], $datax, $values)
		);

		if($avg != ''){
			$plot_avg = createPlot(
				$fields_types[$key],
				$datax,
				averageGraph($values, $avg)
			);
			end($plots)->color = array(209,238,238);
			array_push($plots, $plot_avg);
		}
	}
//----------------------------------CREATING GRAPH----------------------------	
	createGraph($names[$key], $fields_types[$key], $width, $height, 
		$x_name, $fields_names[$key], $step_x, $plots, $datax[0], end($datax));
}	
?>
