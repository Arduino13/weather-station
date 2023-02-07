<!DOCTYPE html>
<html>
	<head>
		<title>Weather station</title>
	</head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js" type="text/javascript"></script>
	<link rel="stylesheet" href="css/main.css">
	<script src="js/resizer.js" type="text/javascript"></script>
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
				<td id="time_d">Date: <input type="date" name="date"/></td>
				<td id="time"><p>00:00:00</p></td>
			</tr>
			<tr>
				<?php
					include('actual_info.php');
				?>
			</tr>
			<tr>
				<?php
					include('graphs_view.php');
				?>
			</tr>
		</table>
		<script>
			//Script that updates current values every minute
			var now = new Date();

			var day = ("0" + now.getDate()).slice(-2);
			var month = ("0" + (now.getMonth() + 1)).slice(-2);

			var today = now.getFullYear()+"-"+(month)+"-"+(day) ;

			$("input[type='date']").val(today);
		</script>
		<script type="text/javascript" src="js/helper.js"></script>
	</body>
</html>	
