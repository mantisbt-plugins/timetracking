<?php
namespace TTMigration;

require_api( 'install_helper_functions_api.php' );
require_api( 'bugnote_api.php' );

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
	if( step_is_started_unfinished( $p_step ) || step_is_completed( $p_step ) ) {
		switch( $p_step ) {
			case 1:
				$t_query = 'SELECT count(*) FROM ' . plugin_table( 'conv' );
				$t_count = db_result( db_query( $t_query ) );
				$t_query = 'SELECT count( distinct bug_id ) FROM ' . plugin_table( 'conv' );
				$t_count_distinct = db_result( db_query( $t_query ) );
				$t_det[] = $t_count . ' records selected / ' . $t_count_distinct . ' issues';
				$t_query = 'SELECT action, count(*) AS cnt FROM ' . plugin_table( 'action' ) . ' GROUP BY action';
				$t_result = db_query( $t_query );
				$t_actions = array();
				while( $t_row = db_fetch_array( $t_result ) ) {
					$t_actions[] = $t_row['cnt'] . ' ' . $t_row['action'];
				}
				$t_det[] = 'Actions: ' . implode( ', ', $t_actions );
				$t_det[] = '<em>Loaded in staging tables: ' . plugin_table( 'conv' ) . ', ' . plugin_table( 'action' ) . '</em>';
				break;

			case 2:
				$t_query = 'SELECT action, count(*) AS cnt FROM ' . plugin_table( 'action' ) . ' WHERE processed = 1 GROUP BY action';
				$t_result = db_query( $t_query );
				$t_actions = array();
				while( $t_row = db_fetch_array( $t_result ) ) {
					$t_actions[] = $t_row['cnt'] . ' ' . $t_row['action'];
				}
				$t_det[] = 'Actions performed: ' . implode( ', ', $t_actions );
				break;

			case 3:
				$t_key = step_str_index( 3 );
				$t_status_array = get_key_data( $t_key );
				if( isset( $t_status_array['notes_deleted'] ) ) {
					$t_det[] = $t_status_array['notes_deleted'] . ' notes deleted';
				}
				if( isset( $t_status_array['notes_updated'] ) ) {
					$t_det[] = $t_status_array['notes_updated'] . ' notes updated';
				}
				break;
		}
	}
	return implode( '<br>', $t_det );
}

/**
 * Retunrs html for a badge reflecting this step state.
 * @param type $p_step
 * @return string
 */
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
 * All steps above requested are reset too.
 * Migration completion is reset.
 * @param integer $p_step	Step number
 */
function step_reset( $p_step ) {
	for( $i = STEPS; $i >= $p_step; $i-- ) {
		step_clean( $i );
		delete_key_data( step_str_index( $i ) );
	}
	$t_status_data = get_key_data( 'STATUS' );
	$t_status_data['completed'] = 0;
	if( isset( $t_status_data['date_completed'] ) ) {
		unset( $t_status_data['date_completed'] );
	}
	set_key_data( 'STATUS', $t_status_data );
}

/**
 * Performs specific clean-up task for steps
 * @param integer $p_step	Stem number
 */
function step_clean( $p_step ) {
	switch( $p_step ) {
		case 1:
			$t_query = 'DELETE FROM ' . plugin_table( 'action' );
			db_query( $t_query );
			$t_query = 'DELETE FROM ' . plugin_table( 'conv' );
			db_query( $t_query );
			break;
		case 2:
			$t_query = 'UPDATE ' . plugin_table( 'action' ) . ' SET processed = 0 ';
			db_query( $t_query );
			break;
		case 3:
			break;
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
			step_status_start( $p_step );
			migration_do_step_1();
			step_status_complete($p_step);
			break;

		case 2:
			step_status_start( $p_step );
			migration_do_step_2();
			step_status_complete($p_step);
			break;

		case 3:
			step_status_start( $p_step );
			migration_do_step_3();
			step_status_complete($p_step);
			migration_completed();
			break;
	}
}

/**
 * Migration step 1
 * Fetch all time tracking data from mantis, and fill "conv" table with the data
 * converted to the format expected by the plugin
 * Calculate actions into "action" table based on options and existing pluigin data
 * 
 * Actions will be created as needed for:
 * - insert operations, of time records into plugin data
 * - delete operations, for deleting plugin data when options are configured to do so 
 *   on bug_id conflict
 * - skip, when the time record will not be used.
 * 
 * Staged data from "conv" and "action" tables can be reviewed and modified manually 
 * before executing step 2 that effectively performs those actions.
 */
function migration_do_step_1() {
	$t_options = get_key_data( 'OPTIONS' );
	$t_category = null;
	if( isset( $t_options['category'] ) ) {
		$t_category = $t_options['category'];
	}
	$t_plugin_table = plugin_table( 'data', 'TimeTracking' );
	$t_conv_table = plugin_table( 'conv' );
	$t_action_table = plugin_table( 'action');

	step_clean( 1 );

	step_return_progress( 'Fetching data', 1, 5 );

	# Fetch and convert data into staging table
	db_param_push();
	$t_query = 'INSERT INTO ' . $t_conv_table
			. ' ( bugnote_id, bug_id, user_id, date_created, time_exp_date, time_count, category )'
			. ' SELECT id, bug_id, reporter_id, date_submitted, date_submitted, 60 * time_tracking, ' . db_param()
			. ' FROM {bugnote} WHERE time_tracking <> ' . db_param();
	db_query( $t_query, array( $t_category, 0 ) );

	# Prepare actions

	$t_collision_action = $t_options['existent'];

	step_return_progress( 'Calculating', 2, 5 );

	if( $t_collision_action == 'replace' ) {
		# existing records for selected bugs will be deleted
		db_param_push();
		$t_query = 'INSERT INTO ' . $t_action_table . ' ( action, bugnote_id, time_tracking_id, processed )'
				. ' SELECT ' . db_param() . ', ' . db_param() . ', id, ' . db_param() . ' FROM ' . $t_plugin_table
				. ' WHERE bug_id IN ( SELECT bug_id FROM ' . $t_conv_table . ')';
		db_query( $t_query, array( 'delete', null, 0 ) );
	}

	if( $t_collision_action == 'skip' ) {
		# new records for existing bugs will be skipped
		db_param_push();
		$t_query = 'INSERT INTO ' . $t_action_table . ' ( action, bugnote_id, time_tracking_id, processed )'
				. ' SELECT ' . db_param() . ', bugnote_id, ' . db_param() . ', ' . db_param() . ' FROM ' . $t_conv_table
				. ' WHERE bug_id IN ( SELECT bug_id FROM ' . $t_plugin_table . ')';
		db_query( $t_query, array( 'skip', null, 0 ) );
	}

	step_return_progress( 'Calculating', 3, 5 );

	# skip if the same bugnote_id already exist in plugin
	db_param_push();
	$t_query = 'INSERT INTO ' . $t_action_table . ' ( action, bugnote_id, time_tracking_id, processed )'
			. ' SELECT ' . db_param() . ', bugnote_id, ' . db_param() . ', ' . db_param() . ' FROM ' . $t_conv_table
			. ' WHERE bugnote_id IN ( SELECT bugnote_id FROM ' . $t_plugin_table . ')'
			. ' AND bugnote_id NOT IN ( SELECT bugnote_id FROM ' . $t_action_table . ')';
	db_query( $t_query, array( 'skip', null, 0 ) );

	step_return_progress( 'Calculating', 4, 5 );

	# The rest will be inserted as new records
	db_param_push();
	$t_query = 'INSERT INTO ' . $t_action_table . ' ( action, bugnote_id, time_tracking_id, processed )'
			. ' SELECT ' . db_param() . ', bugnote_id, ' . db_param() . ', ' . db_param() . ' FROM ' . $t_conv_table
			. ' WHERE bugnote_id NOT IN ( SELECT bugnote_id FROM ' . $t_action_table . ' WHERE action = ' . db_param() . ')';
	db_query( $t_query, array( 'insert', null, 0, 'skip' ) );

	step_return_progress( 'Finished', 5, 5 );
}

/**
 * Migration step 2
 * Based on the prefilled "action" table, perform the indicated actions:
 * - "Insert" a time record in the plugin data table.
 * - "Delete" a time record from the plugin data table, if options were configured
 *    to "delete on an already existing bug_id"
 * - "Skip" a time record if options were set to "delete on an already existing bug_id"
 *    or a time record already exists for that bugnote id
 */
function migration_do_step_2() {
	$t_plugin_table = plugin_table( 'data', 'TimeTracking' );
	$t_conv_table = plugin_table( 'conv' );
	$t_action_table = plugin_table( 'action');

	# count records
	$t_query = 'SELECT count(*) FROM ' . $t_action_table . ' WHERE processed = 0'  ;
	$t_count_total = db_result( db_query( $t_query ) );

	step_return_progress( 'Running', 0, $t_count_total );

	$t_query_next = 'SELECT * FROM ' . $t_action_table . ' WHERE processed = 0'  ;
	$t_result_next = db_query( $t_query_next, null, 100, 0 );
	$t_progress_count = 0;
	$t_utime = microtime( true );

	while( !$t_result_next->EOF ) {

		$t_ids_delete = array();
		$t_ids_insert = array();
		$t_ids_process = array();
		# Build grouped queries for current block
		while( $t_row = db_fetch_array( $t_result_next ) ) {
			switch( $t_row['action'] ) {
				case 'delete':
					$t_ids_delete[] = (int)$t_row['time_tracking_id'];
					break;
				case 'insert':
					$t_ids_insert[] = (int)$t_row['bugnote_id'];
					break;
				case 'skip':
					# do nothing
					break;
			}
			$t_ids_process[] = (int)$t_row['id'];
		}

		$t_cnt_deletes = count( $t_ids_delete );
		if ( $t_cnt_deletes > 0 ) {
			db_param_push();
			$t_id_dbparams = array();
			for( $i = 0; $i < $t_cnt_deletes; $i++ ) {
				$t_id_dbparams[] = db_param();
			}
			$t_query = 'DELETE FROM ' . $t_plugin_table . ' WHERE id IN (' . implode( ',', $t_id_dbparams ) . ')';
			db_query( $t_query, $t_ids_delete );
		}

		$t_cnt_inserts = count( $t_ids_insert );
		if ( $t_cnt_inserts > 0 ) {
			db_param_push();
			$t_id_dbparams = array();
			for( $i = 0; $i < $t_cnt_inserts; $i++ ) {
				$t_id_dbparams[] = db_param();
			}
			$t_query = 'INSERT INTO ' . $t_plugin_table
					. ' ( bug_id, category, info, user_id, date_created, time_count, time_exp_date, bugnote_id ) '
					. ' SELECT bug_id, category, ' . db_param() . ', user_id, date_created, time_count, time_exp_date, bugnote_id'
					. ' FROM ' . $t_conv_table
					. ' WHERE bugnote_id IN (' . implode( ',', $t_id_dbparams ) . ')';
			$t_params = array_merge( array( null ), $t_ids_insert );
			db_query( $t_query, $t_params );
		}

		# update processed rows
		$t_cnt_process = count( $t_ids_process );
		if ( $t_cnt_process > 0 ) {
			db_param_push();
			$t_id_dbparams = array();
			for( $i = 0; $i < $t_cnt_process; $i++ ) {
				$t_id_dbparams[] = db_param();
			}
			$t_query = 'UPDATE ' . $t_action_table . ' SET processed = ' . db_param()
					. ' WHERE id IN (' . implode( ',', $t_id_dbparams ) . ')';
			$t_params = array_merge( array( 1 ), $t_ids_process );
			db_query( $t_query, $t_params );
		}

		# if query result is empty, fetch another block
		# offset is 0 because the query is run again, and processed rows are not selected
		if( $t_result_next->EOF ) {
			$t_result_next = db_query( $t_query_next, null, 100, 0 );
		}

		$t_progress_count += $t_cnt_process;
		# check for periodic feedback
		$t_new_utime = microtime( true );
		if( $t_new_utime - $t_utime > 0.5 ) {
			step_return_progress( 'Running', $t_progress_count, $t_count_total );
			$t_utime = $t_new_utime;
		}
	}
}

/**
 * Migration step 3
 * Deletes time tracking data from mantis core tables
 * Based on the processed bugnotes_id, from "action" table, for those that were
 * processed in previous step:
 * - Delete bugnotes that are empty. Those were created with the only purpose
 *   of keeping a time record. This will leave a history record for note deletion.
 * - Update non empty notes, removing the associated time data, and changing it's
 *   note type from "time tracking" to a standard "bug note".
 * These changes won't modify the last-updated timestamp on issues.
 */
function migration_do_step_3() {
	$t_action_table = plugin_table( 'action');

	# Delete time tracking notes without text

	db_param_push();
	$t_query = 'SELECT count(*) FROM {bugnote} B'
			. ' JOIN ' . $t_action_table . ' A ON B.id = A.bugnote_id'
			. ' JOIN {bugnote_text} BT ON B.bugnote_text_id = BT.id'
			. ' WHERE A.processed = ' . db_param() . ' AND A.bugnote_id IS NOT NULL'
			. ' AND BT.note = ' . db_param();
	$t_count_total = db_result( db_query( $t_query, array( 1, '' ) ) );

	if( $t_count_total > 0 ) {
		step_return_progress( 'Deleting empty notes', 0, $t_count_total );

		db_param_push();
		$t_query = 'SELECT B.id FROM {bugnote} B'
				. ' JOIN ' . $t_action_table . ' A ON B.id = A.bugnote_id'
				. ' JOIN {bugnote_text} BT ON B.bugnote_text_id = BT.id'
				. ' WHERE A.processed = ' . db_param() . ' AND A.bugnote_id IS NOT NULL'
				. ' AND BT.note = ' . db_param();
		$t_result_ids = db_query( $t_query, array( 1, '' ) );

		$t_utime = microtime( true );
		$t_count_deleted = 0;
		while( $t_row = db_fetch_array( $t_result_ids ) ) {
			bugnote_delete( $t_row['id'] );
			$t_count_deleted++;

			# check for periodic feedback
			$t_new_utime = microtime( true );
			if( $t_new_utime - $t_utime > 0.5 ) {
				$t_utime = $t_new_utime;
			}
			step_return_progress( 'Deleting empty notes', $t_count_deleted, $t_count_total );
		}
	}

	# Update remaining time tracking notes
	db_param_push();
	$t_query = 'SELECT count(*) FROM {bugnote} WHERE id IN'
			. ' ( SELECT bugnote_id FROM ' . $t_action_table
			. ' WHERE processed = ' . db_param() . ' AND bugnote_id IS NOT NULL )';
	$t_count_all_updates = db_result( db_query( $t_query, array( 1 ) ) );

	if( $t_count_all_updates > 0) {
		step_return_progress( 'Updating notes', 0, $t_count_all_updates );

		db_param_push();
		$t_query_next = 'SELECT id FROM {bugnote}'
				. ' WHERE id IN ( SELECT bugnote_id FROM ' . $t_action_table
				. ' WHERE processed = 1 AND bugnote_id IS NOT NULL )';
		$t_offset = 0;
		$t_result_next = db_query( $t_query_next, null, 100, $t_offset );
		$t_count_updated = 0;
		while( !$t_result_next->EOF ) {
			db_param_push();
			$t_params = array( 0, BUGNOTE );
			# fetch some ids to do a group update
			$t_count = 0;
			$t_ids_dbparam = array();
			while( !$t_result_next->EOF ) {
				$t_row = db_fetch_array( $t_result_next );
				$t_params[] = (int)$t_row['id'];
				$t_ids_dbparam[] = db_param();
				$t_count++;
			}
			$t_query = 'UPDATE {bugnote} SET time_tracking = ' . db_param() . ', note_type = ' . db_param()
					. ' WHERE id IN ( ' . implode( ',', $t_ids_dbparam ) . ' )';
			db_query( $t_query, $t_params );

			$t_count_updated += $t_count;
			step_return_progress( 'Updating notes', $t_count_updated, $t_count_all_updates );

			# if query result is empty, fetch another block
			$t_offset += 100;
			$t_result_next = db_query( $t_query_next, null, 100, $t_offset );
		}
	}

	$t_key = step_str_index( 3 );
	$t_status_array = get_key_data( $t_key );
	$t_status_array['notes_deleted'] = $t_count_deleted;
	$t_status_array['notes_updated'] = $t_count_updated;
	set_key_data( $t_key, $t_status_array );
}
