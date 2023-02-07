<?php
	/*
 	 * Page for displaying charts pregenerated or in case of selected day generated on demand	 
	 */
	$fields = array('temperature','wind_s','wind_d','humidity','pressure','rain');
	$intervals = array('temperature' =>'10m',
		'wind_s' => '10m',
		'wind_d' => '2h',
		'humidity' => '10m',
		'pressure' => '10m',
		'rain' => '2h');

	echo '<th>Charts</th></tr><tr>';

	foreach($fields as $key){
		echo '<td>';
		if(isset($_POST['date'])){
			$time_from = DateTime::createFromFormat('d/m/Y', $_POST['date']);
			$time_to = DateTime::createFromFormat('d/m/Y', $_POST['date']);
			$time_from->setTime(0,0);
			$time_to->setTime(0,0);
			$time_to->add(new DateInterval('P1D'));


			$page = "graphs.php?key={$key}&width=360&height=180&step={$intervals[$key]}&step_G=7200";
			$page = "{$page}&date_from={$time_from->format('Y-m-d')}&date_to={$time_to->format('Y-m-d')}";

			echo "<img src={$page}>";
		}
		else echo '<img src=web_shots/'.$key.'>';
		echo '</td>';
	}
	echo '<td><div class="clear"></div></td>';
?>
