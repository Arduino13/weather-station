//Script for updating values every minute and to redraw charts when date is selected
$("input[type='date']").change( function(){
	var date = new Date($("input[type='date']").val());
	var day = date.getDate();
	var month = date.getMonth()+1;
	if(day<10) day='0'+day;
	if(month<10) month='0'+month;
	var datum = ([day,month,date.getFullYear()].join('/'));
	
	$('.MainTable tr').eq(1).empty();
	$('.MainTable tr').eq(1).append('<th>Averaged values</th>');

	clearInterval(update);
	actual_inf(datum);
	graphs_inf(datum);
});


function updateTime(){
	var currentTime = new Date();
	var hours = currentTime.getHours();
	var minutes = currentTime.getMinutes();
	var seconds = currentTime.getSeconds();
	
	if (hours < 10){
		hours = "0" + hours;
	}
	if (minutes < 10){
		minutes = "0" + minutes;
	}
	if (seconds < 10){
		seconds = "0" + seconds;
	}
	var t_str = hours + ":" + minutes + ":" + seconds;
 	
	$('#time').empty();
	$('#time').append(t_str);
}

function actual_inf(datum){
	$.post("actual_info.php", {date: datum}, function(result){
		$('.MainTable tr').eq(2).empty();
		$('.MainTable tr').eq(2).append(result);
	});
}

function graphs_inf(datum){
	$.post("graphs_view.php", {date: datum}, function(result){
		$('.MainTable tr').eq(4).empty();
		$('.MainTable tr').eq(4).append(result);
		resize();
	});
}

setInterval(updateTime,1000);
var update = setInterval(actual_inf,60000,'nodef');

