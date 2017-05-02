<?php
namespace TimeTracking;

/**
 * Start counting on server-side stopwatch for current user
 */
function stopwatch_start() {
	$t_status = stopwatch_get_status();
	if( $t_status['status'] != STOPWATCH_RUNNING ) {
		$t_status['last_started'] = db_now();
		$t_status['status'] = STOPWATCH_RUNNING;
		stopwatch_set_tatus( $t_status );
	}
}

/**
 * Stop counting on server-side stopwatch for current user
 */
function stopwatch_stop() {
	$t_status = stopwatch_get_status();
	$t_status['status'] = STOPWATCH_STOPPED;
	if( $t_status['last_started'] > 1 ) {
		$t_status['total_time'] += db_now() - $t_status['last_started'];
		$t_status['runtime'] = $t_status['total_time'];
	}
	$t_status['last_started'] = 0;
	stopwatch_set_tatus( $t_status );
}

/**
 * Reset server-side stopwatch. Actually, it detes the register.
 */
function stopwatch_reset() {
	token_delete( TOKEN_STOPWATCH_STATUS );
}

/**
 * Returns true if a stopwatch register exists for current user
 * @return boolean
 */
function stopwatch_exists() {
	$t_status = token_get_value( TOKEN_STOPWATCH_STATUS );
	if( $t_status ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Returns the array of info for the server-side stopwatch.
 * If the register does not exists, a defaulted array is returned.
 * @return array	Status info array
 */
function stopwatch_get_status() {
	$t_j_status = token_get_value( TOKEN_STOPWATCH_STATUS );
	if( $t_j_status ) {
		$t_status = json_decode( $t_j_status, true );
		if( $t_status['last_started'] > 1 ) {
			$t_status['runtime'] = $t_status['total_time'] + db_now() - $t_status['last_started'];
		}
		return $t_status;
	} else {
		# return initial status
		return array( 'status' => STOPWATCH_STOPPED, 'total_time' => 0, 'last_started' => 0, 'runtime' => 0 );
	}
}

/**
 * Stopres the status array for a stopwatch
 * @param array $t_status_array	Status info array
 */
function stopwatch_set_tatus( $t_status_array ) {
	$t_encoded = json_encode( $t_status_array );
	token_set( TOKEN_STOPWATCH_STATUS, $t_encoded, STOPWATCH_EXPIRY );
}

/**
 * Prints the html stub for a stopwatch UI.
 * Note the labels on buttons will be modified later by javascript.
 * @param type $p_time_dispay
 */
function print_stopwatch_ui( $p_time_dispay = null ) {
	if( stopwatch_exists() ) {
		$t_class_autoinit = 'autoinit';
	} else {
		$t_class_autoinit = '';
	}
	?>
	<span class="stopwatch_ui <?php echo $t_class_autoinit ?>" data-remote="<?php echo plugin_page( 'stopwatch' ) ?>">
		<?php
		switch( $p_time_dispay ) {
			case 'span':
				echo '<span class="stopwatch_time_display"></span>';
				break;
			case 'button':
				echo '<button class="stopwatch_time_display btn btn-primary btn-sm btn-white btn-round" title="Copy to time tracking input"></button>';
				break;
		}
		?>
		<button class="stopwatch_btn_start btn btn-primary btn-sm btn-white btn-round"></button>
		<button class="stopwatch_btn_reset btn btn-primary btn-sm btn-white btn-round"></button>
	</span>
	<?php
}

/**
 * Prints html to show the stopwatch button in some forms.
 * This button is used to open the main UI and start a stopwatch
 * @param type $p_time_dispay
 */
function print_stopwatch_control( $p_time_dispay = null ) {
	?>
	<div class="stopwatch_control">
		<i class="ace-icon fa fa-clock-o bigger-110 red"></i>
		<?php print_stopwatch_ui( $p_time_dispay ) ?>
		<a href="#" class="stopwatch_open btn btn-primary btn-sm btn-white btn-round">
			<i class="ace-icon fa fa-clock-o bigger-110"></i>
			Stopwatch
		</a>
	</div>
	<?php
}

/**
 * Prints html to render a header div that shows a previously started stopwatch when loading any page
 */
function print_stopwatch_header_control() {
	echo '<div class="stopwatch_control alert alert-warning text-center">';
	echo '<span>' . 'Stopwatch is enabled: ' . '</span>';
	print_stopwatch_ui( 'span' );
	echo '</div>';
}

