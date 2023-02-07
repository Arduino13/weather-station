<!DOCTYPE html>
<html>
	<head>
		<title>Meteorologická stanice</title>
	</head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js" type="text/javascript"></script>
	<link rel="stylesheet" href="css/main.css">
	<body>
		<header>
			<h1>Weather station</h1>
			<nav>
				<ul>
					<li><a href="index.php">Overview</a></li>
					<li><a href="history.php">History</a></li>
				</ul>
			</nav>	
		</header>
		<div class="clear"></div>
		<table class="MainTable">
			<tr>
				<td><form action="history.php">
					<?php
						if(isset($_GET['date_from'])){
							echo '<div id="extra_margin">From: <input type="date" name="date_from" value="'.$_GET['date_from'].'" /></div>';
						}else{ 
							echo '<div id="extra_margin">From: <input type="date" name="date_from"/></div>';
						}

						if(isset($_GET['date_to'])){
							echo '<div id="extra_margin">To: <input type="date" name="date_to" value="'.$_GET['date_to'].'" /></div>';
						}else{
							echo '<div id="extra_margin">To: <input type="date" name="date_to" /></div>';
						}
					?>
					<div id="extra_margin">Typ: <select name="type">
						<option value="temperature" <?php 
							if(isset($_GET['type']) && $_GET['type']=='temperature') echo 'selected';
						?> >Temperature</option>
						<option value="temp" <?php 
							if(isset($_GET['type']) && $_GET['type']=='temp') echo 'selected';
						?> >Station's temperature</option>
						<option value="humidity" <?php 
							if(isset($_GET['type']) && $_GET['type']=='humidity') echo 'selected';
						?> >Air humidity</option>
						<option value="pressure" <?php 
							if(isset($_GET['type']) && $_GET['type']=='pressure') echo 'selected';
						?> >Air pressure</option>
						<option value="wind_s" <?php 
							if(isset($_GET['type']) && $_GET['type']=='wind_s') echo 'selected';
						?> >Wind speed</option>
						<option value="wind_g" <?php 
							if(isset($_GET['type']) && $_GET['type']=='wind_g') echo 'selected';
						?> >Wind gusts</option>
						<option value="wind_d" <?php 
							if(isset($_GET['type']) && $_GET['type']=='wind_d') echo 'selected';
						?> >Wind direction</option>
						<option value="rain" <?php 
							if(isset($_GET['type']) && $_GET['type']=='rain') echo 'selected';
						?> >Precipitation</option>
					</select></div>
					<div id="extra_margin">Smoothing: <input type="range" min="1" max="40" <?php echo'value='; if(isset($_GET['divider'])) echo $_GET['divider']; else echo 0?> class="slider" id="divider" name="divider"></div>
					<div id="extra_margin"><input type="submit" value="submit"/></div>
					<div class="clear"></div>
				</form></td>
			</tr>
			<tr>
				<td>
				<?php
					if(isset($_GET['date_from'])){
						if(strtotime($_GET['date_from'])>=strtotime($_GET['date_to'])){
							echo '<h1 style="color: red;">To must be greater than From</h1>';
						}
						else{
							$width = 1080;
							$height = 720;

							$date_from = DateTime::createFromFormat('Y-m-d', $_GET['date_from']);
							$date_to = DateTime::createFromFormat('Y-m-d', $_GET['date_to']);
							$date_from->setTime(0,0);
							$date_to->setTime(0,0);
							
							$num_of_ticks = $width * (6/360); //because in smaller graph is density of 6 ticks per 360 pixels
							$diff_u = ($date_to->format('U') - $date_from->format('U'));
							$step_G = intval($diff_u/$num_of_ticks); 

							if($_GET['type'] == 'wind_d' || $_GET['type'] == 'rain'){ 
								$step = intval($step_G/60).'m'; //same as density of ticks for rain bars and wind direciton arrows
							}else{
								$step = intval(($step_G/(3*720))).'m'; //constant found by try and error so graph looks detailed but not too much 
							}
							
							$page = "graphs.php?key={$_GET['type']}&width={$width}&height={$height}&step={$step}&step_G={$step_G}";
							$page = "{$page}&date_from={$date_from->format('Y-m-d')}&date_to={$date_to->format('Y-m-d')}";

							echo "<img src={$page}&avg={$_GET['divider']}/>";
						}
					}
				?>
				</td>
			</tr>
		</table>
	</body>
</html>	
