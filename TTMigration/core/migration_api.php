<?php
namespace TTMigration;

$g_cache_status_data = array();

/**
 * Returns data array for a specific key
 * @global array $g_cache_status_data	Data cache
 * @param string $p_key		Lookup key
 * @return array
 */
function get_key_data( $p_key ) {
	global $g_cache_status_data;
	if( isset( $g_cache_status_data[$p_key] ) ) {
		return $g_cache_status_data[$p_key];
	}
	db_param_push();
	$t_query = 'SELECT status_data FROM ' . plugin_table( 'status' )
			. ' WHERE status_key = ' . db_param();
	$t_result = db_query( $t_query, array( $p_key ) );
	$t_data = db_result( $t_result );
	if( !$t_data ) {
		$g_cache_status_data[$p_key] = false;
	} else {
		$g_cache_status_data[$p_key] = json_decode( $t_data, true );
	}

	return $g_cache_status_data[$p_key];
}

/**
 * Stores a data array for a specific key
 * @global array $g_cache_status_data	Data cache
 * @param string $p_key		Lookup key
 * @param array $p_data		Data to store
 */
function set_key_data( $p_key, array $p_data ) {
	global $g_cache_status_data;
	if( get_key_data( $p_key ) ) {
		db_param_push();
		$t_query = 'UPDATE ' . plugin_table( 'status' ) . ' SET status_data = ' . db_param()
				. ' WHERE status_key = ' . db_param();
		db_query( $t_query, array( json_encode( $p_data ), $p_key ) );
	} else {
		db_param_push();
		$t_query = 'INSERT INTO ' . plugin_table( 'status' ) . ' ( status_key, status_data )'
				. ' VALUES ( ' . db_param() . ', ' . db_param() . ')';
		db_query( $t_query, array( $p_key, json_encode( $p_data ) ) );
	}
	$g_cache_status_data[$p_key] = $p_data;
}

/**
 * Deletes data for a specific key
 * @global array $g_cache_status_data	Data cache
 * @param string $p_key		Lookup key
 */
function delete_key_data( $p_key ) {
	global $g_cache_status_data;
	db_param_push();
	$t_query = 'DELETE FROM ' . plugin_table( 'status' )
			. ' WHERE status_key = ' . db_param();
	db_query( $t_query, array( $p_key ) );
	unset( $g_cache_status_data[$p_key] );
}

/**
 * Returns all stored data for migration status and steps records
 * @staticvar array $t_status_array
 * @return array	All data: array( key => array() )
 */
function migration_get_status_info() {
	static $t_status_array = null;
	if( $t_status_array ) {
		return $t_status_array;
	}
	$t_query = 'SELECT status_key, status_data FROM ' . plugin_table( 'status' );
	$t_result = db_query( $t_query );
	$t_status_array = array();
	while( $t_row = db_fetch_array( $t_result ) ) {
		$t_status_array[$t_row['status_key']] = json_decode( $t_row['status_data'], true );
	}
	return $t_status_array;
}

/**
 * Returns true if native time tracking is enabled
 * @return boolean
 */
function migration_is_tt_enabled() {
	# look for global flag
	$t_enabled_global = config_get_global( 'time_tracking_enabled' );

	# look for config overrides
	db_param_push();
	$t_query = 'SELECT count(*) FROM {config} WHERE config_id = ' . db_param() . ' AND value = ' . db_param();
	$t_result = db_query( $t_query, array( 'time_tracking_enabled', ON ) );
	$t_count = db_result( $t_result );
	$t_enabled_override = $t_count > 0;

	return $t_enabled_global || $t_enabled_override;
}

/**
 * Returns true if there is data stored as parto of native time tracking
 * @return boolean
 */
function migration_tt_data_exists() {
	$t_query = 'SELECT count(*) from {bugnote} WHERE time_tracking <> 0';
	$t_result = db_query( $t_query );
	$t_count = db_result( $t_result );
	return $t_count > 0;
}

/**
 * Prints a set of action buttons for each step, based on step status
 * @param integer $p_step	Step number
 */
function print_buttons_for_step( $p_step ) {
	$t_step = (int)$p_step;
	$t_previous_completed = step_is_previous_completed( $t_step );
	$t_current_completed = step_is_completed( $t_step );
	$t_in_progress = step_is_started_unfinished( $t_step );
	$t_token = form_security_token( 'ttmigration_migration_step_exec' );
	$t_params = array( 'ttmigration_migration_step_exec_token' => $t_token, 'step' => $t_step );
	$t_url = plugin_page( 'migration_step_exec' );

	if( $t_previous_completed && !$t_current_completed && !$t_in_progress ) {
		$t_params['action'] = 'start';
		$t_remote = $t_url . '&' . http_build_query( $t_params );
		echo '<a class="trigger_exec btn btn-sm btn-primary" href="#"'
			. ' data-remote="' . $t_remote . '">'
			. 'Start'
			. '</a>';
	}
	if( $t_previous_completed && !$t_current_completed && $t_in_progress ) {
		$t_params['action'] = 'resume';
		$t_remote = $t_url . '&' . http_build_query( $t_params );
		echo '<a class="trigger_exec btn btn-sm btn-warning" href="#"'
			. ' data-remote="' . $t_remote . '">'
			. 'Resume'
			. '</a>';
	}
	if( $t_current_completed ) {
		$t_params['action'] = 'reset';
		$t_remote = $t_url . '&' . http_build_query( $t_params );
		echo '<a class="trigger_exec btn btn-sm btn-link" href="#"'
			. ' data-remote="' . $t_remote . '">'
			. 'Reset'
			. '</a>';	}
}

function print_options_inputs( $p_readonly = false ) {
	$t_options_array = get_key_data( 'OPTIONS' );
	if( !$t_options_array ) {
		$t_options_array = array();
	}

	if( isset( $t_options_array['option1'] ) ) {
		$t_option1 = $t_options_array['option1'];
	} else {
		$t_option1 = null;
	}
	#test options
	?>
	<div class="form-group">
		<label for="option1">Example label</label>
		<input type="text" class="form-control" id="option1" name="option1" placeholder="option1" value="<?php echo $t_option1 ?>">
		<span class="help-block">Contextual help</span>
	</div>
	<div class="form-group">
		<label>Radio buttons</label>
		<span class="help-block">Contextual help</span>
		<div class="form-check">
			<label class="form-check-label">
			<input type="radio" class="form-check-input" name="option2" id="optionsRadios1" value="value1" checked>
				Value 1
			</label>
		</div>
		<div class="form-check">
			<label class="form-check-label">
				<input type="radio" class="form-check-input" name="option2" id="optionsRadios2" value="value2">
				Value 2
			</label>
		</div>
		<div class="form-check">
			<label class="form-check-label">
				<input type="radio" class="form-check-input" name="option2" id="optionsRadios3" value="value3">
				Value 3
			</label>
		</div>
	</div>
	<?php
}