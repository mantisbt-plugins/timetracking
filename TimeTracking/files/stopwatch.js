/**
 * Stopwacth class
 */

/**
 * Stopwatch constructor.
 * Create with: new Stopwatch(..)
 * @param function display_cb	Callback function to display current time
 * @param string remote_url		url to server side syncronization
 * @returns {Stopwatch}
 */
function Stopwatch( display_cb, remote_url ) {
	this.display_cb = display_cb;
	this.remote = remote_url;
	this.runtime = 0;
	this.timer = null;
	this.INCREMENT = 1000;
	this.sync_interval = 30;
	this.sync_counter = 0;
	this.text_start = '<i class="stopwatch-icon fa fa-play"></i>' + timetracking_translations['start'];
	this.text_resume = '<i class="stopwatch-icon fa fa-play"></i>' + timetracking_translations['resume'];
	this.text_stop = '<i class="stopwatch-icon fa fa-stop"></i>' + timetracking_translations['stop'];
	this.text_reset = '<i class="stopwatch-icon fa fa-undo"></i>' + timetracking_translations['reset'];
	this.showTime();
	this.pullStatus();
}

/**
 * call the display callback
 */
Stopwatch.prototype.showTime = function() {
	this.display_cb( this.runtime );
}

/**
 * This function is called after every time interval
 * @returns {undefined}
 */
Stopwatch.prototype.tick = function() {
	if( ++this.sync_counter > this.sync_interval ) {
		this.sync_counter = 0;
		this.pullStatus();
	}
}

/**
 * Fetch server-side status and update client data
 */
Stopwatch.prototype.pullStatus = function() {
	var instance = this;
	$.get( this.remote,
		{ 'action': 'status' },
		function( data ) {
			instance.setTime( data.runtime * 1000 );
			if( data.status == '1' /*running*/ && instance.timer === null ) {
				instance.start();
			}
			if( data.status == '0' /*stopped*/ && instance.timer !== null ) {
				instance.stop();
			}
		},
		'json'
	);
}

/**
 * Send and action to server.
 * Response also includes current status, so update time.
 */
Stopwatch.prototype.pushAction = function( action ) {
	var instance = this;
	$.get( this.remote,
		{ 'action': action },
		function( data ) {
			instance.setTime( data.runtime * 1000 );
		},
		'json'
	);
}

/**
 * Update internal time counter
 */
Stopwatch.prototype.setTime = function( millis ) {
	this.runtime = millis;
	this.showTime();
}

/**
 * General start action: start the timer, update ui and sync to server
 */
Stopwatch.prototype.start = function(){
	var instance = this;
	this.timer = window.setInterval(function(){
		instance.runtime += instance.INCREMENT;
		instance.showTime();
		instance.tick();
	}, instance.INCREMENT);
	this.drawBtnStop();
	this.pushAction('start');
}

/**
 * General stop action: stop the timer, update ui and sync to server
 */
Stopwatch.prototype.stop = function(){
	window.clearInterval(this.timer);
    this.timer = null;
    this.showTime();
	this.drawBtnStart();
	this.pushAction('stop');
}

/**
 * General reset action: stop the timer, update ui and sync to server
 */
Stopwatch.prototype.reset = function(){
	window.clearInterval(this.timer);
    this.timer = null;
	this.setTime( 0 );
	this.drawBtnStart();
	this.pushAction('reset');
}

/**
 * Binds clickable event for the start/stop button(s)
 * @param {type} jqElems	jquery element collection to bind
 */
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

/**
 * Binds clickable event for the reset button(s)
 * @param {type} jqElems	jquery element collection to bind
 */
Stopwatch.prototype.bindReset = function( jqElems ) {
	var instance = this;
	this.btnReset = jqElems;
	$(this.btnReset).html( this.text_reset );
	jqElems.click( function(e){
		e.preventDefault();
		instance.reset();
	});
}

/**
 * Update UI buttons according to current status
 */
Stopwatch.prototype.drawBtnStart = function() {
	if( this.runtime == 0 ) {
		this.btnStartStop.html( this.text_start );
	} else {
		this.btnStartStop.html( this.text_resume );
	}
}

/**
 * Update UI buttons according to current status
 */
Stopwatch.prototype.drawBtnStop = function() {
	this.btnStartStop.html( this.text_stop);
}

/* End of Stopwatch object defintion */

/**
 * Formats milliseconds into string "Xh Xm Xs"
 * @param integer runtime	Time in milliseconds
 * @returns string	Formatted output
 */
function format_milliseconds( runtime ) {
  var hours = Math.floor(runtime / 3600000);
  var minutes = Math.floor(runtime / 60000) - hours * 60;
  var seconds = Math.floor(runtime % 60000 / 1000);
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

/**
 * Function to draw time into suitable targets
 * and into the time tracking input if it exists
 * @param {type} runtime
 * @param {type} target
 * @returns {undefined}
 */
function stopwatch_draw_time( runtime, target ) {
	var str = format_milliseconds(runtime);
	target.html( str );
	//$('input.timetracking_time_input').val( str );
}

/**
 * Stopwatch object initialization
 * @param {type} container	The ui container
 */
function stopwatch_init( container ) {
	var draw_target = $(container).find('.stopwatch_time_display');
	var remote = $(container).data('remote');
	var sw_object = new Stopwatch(
			function(runtime){
				stopwatch_draw_time( runtime, draw_target);
			},
			remote
	);
	sw_object.bindStartStop( $(container).find('.stopwatch_btn_start') );
	sw_object.bindReset( $(container).find('.stopwatch_btn_reset') );
}

$(document).ready(function() {
	/* Initialize the stopwatch ui. If theres an autoinit mark provided, show the ui inmediatly */
	if( $('div.stopwatch_control .stopwatch_ui.autoinit').length ) {
		var sw_div = $('div.stopwatch_control:has(.stopwatch_ui.autoinit)');
		var sw_ui = sw_div.find('.stopwatch_ui');
		sw_div.find('.stopwatch_open').hide();
		stopwatch_init( sw_ui );
		sw_ui.show();
	} else if( $('a.stopwatch_open').length ) {
		/* setup the click event for the main button */
		$('a.stopwatch_open').click(function(e){
			e.preventDefault();
			var sw_div = $('a.stopwatch_open').closest('div.stopwatch_control');
			var sw_ui = sw_div.find('.stopwatch_ui');
			sw_div.find('.stopwatch_open').hide();
			stopwatch_init( sw_ui );
			sw_ui.show();
		});
	}
	/* Bind stopwatch time button to copy time value into time tracking form */
	if( $('button.stopwatch_time_display').length ) {
		$('button.stopwatch_time_display').click( function(e){
			e.preventDefault();
			$('input.timetracking_time_input').val( $(e.currentTarget).html() );
			$('input.timetracking_from_stopwatch').val( 1 );
		});
	}
});
