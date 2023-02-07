function resize() {
	var x = parseInt($('body').width()/360);
	var width = $('body').width();
	var result = parseInt((width-360*x)/x)-5;
	$('img').css({'margin-left':(result/2).toString()+'px'});
	$('img').css({'margin-right':(result/2).toString()+'px'});
}
$(window).on('load',resize);
$(window).addEventListener("resize", resize);
