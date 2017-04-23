<?php
namespace TTMigration;

/**
 * Configure script to be able to send unbuffered output
 */
function step_prepare_headers() {
	# Make sure output is not buffered

	# Prevent output of HTML in the content if errors occur
	define( 'DISABLE_INLINE_ERROR_REPORTING', true );
	$g_bypass_headers = true; # suppress headers as we will send our own later
	define( 'COMPRESSION_DISABLED', true );
	# throw away output buffer contents (and disable it) to protect download
	while( @ob_end_clean() ) {
	}
	if( ini_get( 'zlib.output_compression' ) && function_exists( 'ini_set' ) ) {
		ini_set( 'zlib.output_compression', false );
	}

	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache');
}


$g_msg_id = 0;

/**
 * Write a message for a client status progress indicator
 * Formatted for an EventSource client connection
 * @global integer $g_msg_id
 * @param string $p_status		Status text
 * @param integer $p_current	Current progress value
 * @param integer $p_max		Max progress value
 */
function step_return_progress( $p_status, $p_current = null, $p_max = null ) {
	global $g_msg_id;
	$t_ret = array(
		'status' => $p_status,
	);
	if( null !== $p_current && null !== $p_max ) {
		$t_ret['current'] = $p_current;
		$t_ret['max'] = $p_max;
	}
	echo 'id: ' . ++$g_msg_id . PHP_EOL;
	echo "data: " . json_encode( $t_ret ) . PHP_EOL;
	echo PHP_EOL;
	ob_flush();
    flush();
}

/**
 * Writes a message for the client to stop listening
 */
function step_return_close() {
	echo 'id: CLOSE' . PHP_EOL;
	echo "data: " . json_encode(null) . PHP_EOL;
	echo PHP_EOL;
	ob_flush();
    flush();
}

/**
 * Test function
 */
function dummy() {
	for($i = 1; $i <= 5; $i++) {
		step_return_progress( 'TEST', $i, 5 );
		sleep(1);
	}
}

/**
 * Maps a step number to a data key
 * @param integer $p_step	Step number
 * @return string
 */
function step_str_index( $p_step ) {
	$t_step = (int)$p_step;
	if( $t_step > 0 && $t_step <= STEPS ) {
		return 'STEP' . $t_step;
	} else {
		return false;
	}
}

/**
 * Returns true if the step is marked as completed
 * @param ineger $p_step	Step number
 * @return boolean
 */
function step_is_completed( $p_step ) {
	$t_info_array = migration_get_status_info();
	$t_key = step_str_index( $p_step );
	if( isset( $t_info_array[$t_key]['completed'] ) ) {
		return $t_info_array[$t_key]['completed'] == 1;
	}
}

function step_is_previous_completed( $p_step ) {
	if( $p_step == 1 ) {
		$t_previous_completed = true;
	} else {
		$t_previous_completed = step_is_completed( $p_step - 1 );
	}
	return $t_previous_completed;
}

/**
 * Returns true if the srep is marked as started but still not completed
 * @param integer $p_step	Step number
 * @return boolean
 */
function step_is_started_unfinished( $p_step ) {
	$t_info_array = migration_get_status_info();
	$t_key = step_str_index( $p_step );
	if( isset( $t_info_array[$t_key]['start_date'] ) ) {
		return !step_is_completed( $p_step );
	}
}

/**
 * Marks the step as tarted, also updates main status
 * @param integer $p_step	Step number
 */
function step_status_start ( $p_step ) {
	$t_step_key = step_str_index( $p_step );
	$t_step_data = get_key_data( $t_step_key );
	if( !isset( $t_step_data['start_date'] ) ) {
		$t_step_data['start_date'] = db_now();
	}
	$t_step_data['completed'] = 0;
	$t_step_data['last_executed'] = db_now();
	set_key_data( $t_step_key, $t_step_data );

	$t_status_data = get_key_data( 'STATUS' );
	$t_status_data['last_executed'] = db_now();
	set_key_data( 'STATUS', $t_status_data );
}

/**
 * Marks the step as completed, also updates main status
 * @param integer $p_step	Step number
 */
function step_status_complete( $p_step ) {
	$t_step_key = step_str_index( $p_step );
	$t_step_data = get_key_data( $t_step_key );
	$t_step_data['date_completed'] = db_now();
	$t_step_data['completed'] = 1;
	set_key_data( $t_step_key, $t_step_data );

	$t_status_data = get_key_data( 'STATUS' );
	$t_status_data['last_executed'] = db_now();
	set_key_data( 'STATUS', $t_status_data );
}

/**
 * Marks main status as completed
 */
function migration_completed() {
	$t_status_data = get_key_data( 'STATUS' );
	$t_status_data['completed'] = 1;
	$t_status_data['date_completed'] = db_now();
	set_key_data( 'STATUS', $t_status_data );
}

/**
 * Returns details of actions performed by a step
 * @param integer $p_step	Step number
 * @return string	HTML output
 */
function step_details( $p_step ) {
	$t_det = array();
	$t_step_key = step_str_index( $p_step );
	$t_step_data = get_key_data( $t_step_key );
	if( step_is_started_unfinished( $p_step ) ) {
		$t_det[] = '<strong>Started: </strong>' . date( config_get("normal_date_format"), $t_step_data['start_date'] );
	}
	if( step_is_completed( $p_step ) ) {
		$t_det[] = '<strong>Completed: </strong>' . date( config_get("normal_date_format"), $t_step_data['date_completed'] );
	}
	return implode( '<br>', $t_det );
}

function step_status_label( $p_step ) {
	$t_step_key = step_str_index( $p_step );
	$t_step_data = get_key_data( $t_step_key );
	$t_det = '';
	if( step_is_completed( $p_step ) ) {
		$t_det = '<span class="label label-success">Completed</span>';
	} elseif( step_is_started_unfinished( $p_step ) ) {
		$t_det = '<span class="label label-warning"><strong>Started</strong></span>';
	}
	return $t_det;
}

/**
 * Performs reset and clean up of steps.
 * All steps above requested are reset too
 * @param integer $p_step	Step number
 */
function step_reset( $p_step ) {
	for( $i = STEPS; $i >= $p_step; $i-- ) {
		delete_key_data( step_str_index( $i ) );
	}
}

/**
 * Performs execution of a step
 * @param integer $p_step	Step number
 */
function step_execute( $p_step ) {
	# check previous step is completed
	if( !step_is_previous_completed( $p_step ) ) {
		plugin_error( ERROR_MANTISTT_PREVIOUS_STEP_INCOMPLETE, ERROR);
	}
	switch( $p_step ) {
		case 1:
		case 2:
		case 3:
			step_status_start( $p_step );
			dummy();
			step_status_complete($p_step);
			break;

		case 4:
			step_status_start( $p_step );
			dummy();
			step_status_complete($p_step);
			migration_completed();
			break;
	}
}
