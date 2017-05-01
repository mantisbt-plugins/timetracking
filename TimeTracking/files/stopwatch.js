function Stopwatch( display_cb ) {
	this.timer_callback = function(){};
	this.display_cb = display_cb;
	this.runtime = 0;
	this.timer = null;
	this.INCREMENT = 1000;
	this.display_cb( this.runtime );
	this.text_start = '<i class="stopwatch-icon fa fa-play"></i>' + timetracking_translations['start'];
	this.text_resume = '<i class="stopwatch-icon fa fa-play"></i>' + timetracking_translations['resume'];
	this.text_stop = '<i class="stopwatch-icon fa fa-stop"></i>' + timetracking_translations['stop'];
	this.text_reset = '<i class="stopwatch-icon fa fa-undo"></i>' + timetracking_translations['reset'];
}

Stopwatch.prototype.setTime = function( millis ) {
	this.runtime = millis;
}

Stopwatch.prototype.setCallback = function( cb ) {
	this.timer_callback = cb;
}

Stopwatch.prototype.start = function(){
	var instance = this;
	this.timer = window.setInterval(function(){
		instance.runtime += instance.INCREMENT;
		instance.display_cb( instance.runtime );
		instance.timer_callback( instance.runtime );
	}, instance.INCREMENT);
	this.drawBtnStop();
}

Stopwatch.prototype.stop = function(){
	window.clearInterval(this.timer);
    this.timer = null;
    this.display_cb( this.runtime );
	this.drawBtnStart();
}

Stopwatch.prototype.bindStartStop = function( jqElems ) {
	var instance = this;
	this.btnStartStop = jqElems;
	this.drawBtnStart();
	jqElems.click( function(e){
		e.preventDefault();
		if( instance.timer === null ) {
			instance.start();
		} else {
			instance.stop();
		}
	});
}

Stopwatch.prototype.bindReset = function( jqElems ) {
	var instance = this;
	this.btnReset = jqElems;
	$(this.btnReset).html( this.text_reset );
	jqElems.click( function(e){
		e.preventDefault();
		instance.stop();
		instance.setTime( 0 );
		instance.drawBtnStart();
		instance.display_cb( instance.runtime );
	});
}

Stopwatch.prototype.drawBtnStart = function() {
	if( this.runtime == 0 ) {
		this.btnStartStop.html( this.text_start );
	} else {
		this.btnStartStop.html( this.text_resume );
	}
}

Stopwatch.prototype.drawBtnStop = function() {
	this.btnStartStop.html( this.text_stop);
}

/* ----------- */

function format_milliseconds( runtime ) {
  var hours = Math.floor(runtime / 3600000);
  var minutes = Math.floor(runtime / 60000);
  var seconds = Math.floor(runtime % 60000 / 1000);
  //var displayText =  hours + ":" +  (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
  var displayText = '';
  if( hours > 0 ) {
	  displayText += hours + 'h ';
  }
  if( hours > 0 || minutes > 0) {
	  displayText += minutes + 'm ';
  }
  displayText += seconds + 's';
  return displayText;
}

function stopwatch_draw_time( runtime, target ) {
	var str = format_milliseconds(runtime);
	target.html( str );
	$('input.timetracking_time_input').val( str );
}

function stopwatch_init( container ) {
	var draw_target = $(container).find('.stopwatch_time_display');
	var sw_object = new Stopwatch(
			function(runtime){
				stopwatch_draw_time( runtime, draw_target);
			}
	);
	sw_object.bindStartStop( $(container).find('.stopwatch_btn_start') );
	sw_object.bindReset( $(container).find('.stopwatch_btn_reset') );
}

