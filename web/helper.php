<?php
function averageGraph($array,$divider){
	$to_return = array();
	$prev_value = null;
	foreach($array as $var){
		if($var === null){
			array_push($to_return, null);
			continue;
		}
		if($prev_value === null){
			$prev_value=$var;
			array_push($to_return, $var);
			continue;
		}
		$value = ($prev_value + ($var-$prev_value)/$divider);
		array_push($to_return, $value);
		$prev_value=$value;
	}

	return $to_return;
}
?>
