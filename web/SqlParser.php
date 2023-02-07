<?php
/*
 * Class for reading data from database
 * 'temp_g' - temperature of sensor outside housing
 * 'temp' - temperature of sensor inside housing
 * 'hum' - air humidity
 * 'press' - air pressure
 * 'rain' - amout of water in mm per meter squre in minute
 * 'avg' - average wind speed 
 * 'max' - wind gusts
 * 'real', 'imag' - real and imaginary part of angle of wind vane
 * it's better for averaging, because angles can be averaged as average of complex numbers 
 */
require __DIR__ . '/../vendor/autoload.php';
use InfluxDB\Client;
class SqlParser{
	private $tchp_tab = 'tchp_tab';
	private $wind_tab_dir = 'wind_tab_direction';
	private $wind_tab_speed = 'wind_tab_speed';
	private $rain_tab = 'rain_tab';

	private function openDB(){
		$client = new Client('127.0.0.1','8086');
		return $client->selectDB('weather');
	}

	//reads data and groups them by specific timestep, it also fills missing time sections with
	//previous record in case for example power outage happen
	private function reader($time_from, $time_to, $unit, $timestep, $table){
		$db = $this->openDB();
		$time_from_str = strval($time_from->format('U')).'000000000';
		$time_to_str = strval($time_to->format('U')).'000000000';

		$result = $db->query("SELECT time, {$unit} FROM {$table}".
		" WHERE time>{$time_from_str} AND time<{$time_to_str}".
		 " GROUP BY time({$timestep}) fill(previous);");

		$points = $result->getPoints();		
		return $points;
	}

	//reads data but don't group them useful for example for knowing maximum or minimum in 
	//given time section
	private function reader_noGroup($time_from, $time_to, $unit, $table){
		$db = $this->openDB();
		$time_from_str = strval($time_from->format('U')).'000000000'; //influxdb works with nanoseconds
		$time_to_str = strval($time_to->format('U')).'000000000';

		$result = $db->query("SELECT time, {$unit} FROM {$table}".
		" WHERE time>{$time_from_str} AND time<{$time_to_str};");

		$points = $result->getPoints();	
		return $points;
	}

	public function wind_s($time_from, $time_to, $timestep, $gusts=FALSE){
		if ($gusts === TRUE){
			return $this->reader($time_from, $time_to, 
				'MAX(max), LAST(avg)', $timestep, $this->wind_tab_speed);	
		} else{
			return $this->reader($time_from, $time_to, 
				'LAST(avg)', $timestep, $this->wind_tab_speed);
		}
	}

	public function wind_sAVG($time_from, $time_to){
		return $this->reader_noGroup($time_from, $time_to, 
			'MEAN(avg)', $this->wind_tab_speed);
	}

	public function wind_gMAX($time_from, $time_to){
		return $this->reader_noGroup($time_from, $time_to,
			'MAX(max)', $this->wind_tab_speed);
	}

	public function wind_sMAX($time_from, $time_to){
		return $this->reader_noGroup($time_from, $time_to,
		'MAX(avg)', $this->wind_tab_speed);
	}

	private function tchpINT($time_from, $time_to, $timestep, $unit = 'LAST(temp), LAST(temp_g), LAST(hum), LAST(press)'){
		return $this->reader($time_from, $time_to, 
			$unit, $timestep, $this->tchp_tab);
	}

	public function tchp($time_from, $time_to, $timestep, $unit = 'all'){
		if($unit == 'all'){
			return $this->tchpINT($time_from, $time_to, $timestep);
		} else if($unit =='temp'){
			return $this->tchpINT($time_from, $time_to, $timestep, $unit = 'LAST(temp)');
		} else if($unit == 'hum'){
			return $this->tchpINT($time_from, $time_to, $timestep, $unit = 'LAST(hum)');
		} else if($unit == 'temp_g'){
			return $this->tchpINT($time_from, $time_to, $timestep, $unit = 'LAST(temp_g)');
		} else if($unit == 'press'){
			return $this->tchpINT($time_from, $time_to, $timestep, $unit = 'LAST(press)');
		}
	}

	public function tchpAVG($time_from, $time_to){
		return $this->reader_noGroup($time_from, $time_to, 
			'MEAN(temp), MEAN(temp_g), MEAN(hum), MEAN(press)', $this->tchp_tab);
	}

	public function tchpMM($time_from, $time_to, $min = FALSE, $unit = null){
		if($unit === null){
			return null;
		}

		if ($min === TRUE){
			return $this->reader_noGroup($time_from, $time_to, 
				$unit="MIN({$unit})", $this->tchp_tab);
		} else{
			return $this->reader_noGroup($time_from, $time_to, 
				$unit="MAX({$unit})", $this->tchp_tab);
		}
	}

	public function rain($time_from, $time_to, $timestep){
		return $this->reader($time_from, $time_to, 'SUM(rain)', $timestep, $this->rain_tab);
	}

	public function rainSUM($time_from, $time_to){
		return $this->reader_noGroup($time_from, $time_to, 'SUM(rain)', $this->rain_tab);
	}

	//converts real and imag part to angle in degrees
	private function wind_angle($records){
		$toReturn = array();
		foreach($records as $record){
			$record = array_values($record);
			$tmp = array();
			array_push($tmp, $record[0]);
			if($record[1] != 0){
				$angle = atan($record[2]/$record[1])*(180/3.14);
			}else if($record[2] < 0){ //exceptions for infinite values
				$angle = -90;
			}else if($record[2] > 0){
				$angle = 90;
			}else{
				$angle = 0;
			}

			if($angle < 0){ //correction for negative angles
				$angle = 360-$angle;
			}
			$angle = ($angle + 180)%360; //correction so east is 0 degrees
			array_push($tmp, $angle);

			array_push($toReturn, $tmp);
		}

		return $toReturn;
	}

	public function wind_d($time_from, $time_to, $timestep){
		$result = $this->reader($time_from, $time_to, 
			'LAST(real), LAST(imag)', $timestep, $this->wind_tab_dir);
		return $this->wind_angle($result);
	}

	public function wind_dAVG($time_from, $time_to){
		$result = $this->reader_noGroup($time_from, $time_to,
			'MEAN(real), MEAN(imag)', $this->wind_tab_dir);
		return $this->wind_angle($result);
		
	}
}
?>
