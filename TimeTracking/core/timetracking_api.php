<?php
namespace TimeTracking;

/**
* Returns an array of time tracking stats
* @param int $p_project_id project id
* @param integer $p_from	Starting date, integer timestamp
* @param integer $p_to		Ending date, integer timestamp
* @return array		Array of bugnote stats
* @access public
*/
function stats_get_project_array( $p_project_id, $p_from, $p_to) {
	$t_project_id = db_prepare_int( $p_project_id );
	$t_to = $p_to;
	$t_from = $p_from;
	$t_timereport_table = plugin_table('data', 'TimeTracking');
	$t_bug_table = db_get_table( 'mantis_bug_table' );
	$t_user_table = db_get_table( 'mantis_user_table' );
	$t_project_table = db_get_table( 'mantis_project_table' );

	$t_query = 'SELECT u.username, p.name AS project_name, T.bug_id, T.time_exp_date, T.time_count, T.date_created, T.category, T.info
	FROM '.$t_timereport_table.' T
	LEFT JOIN '.$t_bug_table.' b ON T.bug_id=b.id
	LEFT JOIN '.$t_user_table.' u ON T.user_id=u.id
	LEFT JOIN '.$t_project_table.' p ON p.id = b.project_id
	WHERE 1=1 ';
	
	db_param_push();
	$t_query_parameters = array();

	if( !is_blank( $t_from ) ) {
		$t_query .= " AND T.time_exp_date >= " . db_param();
		$t_query_parameters[] = $t_from;
	}
	if( !is_blank( $t_to ) ) {
		$t_query .= " AND T.time_exp_date < " . db_param();
		$t_query_parameters[] = $t_to;
	}
	if( ALL_PROJECTS != $t_project_id ) {
		$t_query .= " AND b.project_id = " . db_param();
		$t_query_parameters[] = $t_project_id;
	}
	$t_query .= ' ORDER BY T.user_id, T.time_exp_date, T.bug_id';

	$t_results = array();
	
	//$t_project_where $t_from_where $t_to_where $t_user_where
	

	$t_dbresult = db_query( $t_query, $t_query_parameters );
	while( $row = db_fetch_array( $t_dbresult ) ) {
		$t_results[] = $row;
	}
	return $t_results;
}

/**
 * Convert seconds to hours, formatted as "d.dd"
 * @param integer $p_seconds	Time in seconds
 * @return string	Formatted number
 */
function seconds_to_hours( $p_seconds ) {
	$t_hours = $p_seconds/3600;
	return number_format($t_hours, 2, '.', ',');
}

/**
 * Convert seconds to a time format "[h]h:mm"
 * @param integer $p_seconds	Time in seconds
 * @return string	Formatted string
 */
function seconds_to_hhmm( $p_seconds ) {
	$t_h = floor( $p_seconds /3600 );
	$t_m = floor(($p_seconds - $t_h *3600) / 60);
	return sprintf( '%02d:%02d', $t_h, $t_m );
}

/**
 * Convert seconds to a time format "h:mm:ss"
 * @param integer $p_seconds	Time in seconds
 * @return string	Formatted string
 */
function seconds_to_hhmmss( $p_seconds ) {
	$t_h = floor( $p_seconds /3600 );
	$t_m = floor(($p_seconds - $t_h * 3600) / 60);
	$t_s = $p_seconds - ($t_h * 3600 + $t_m * 60);
	return sprintf( '%02d:%02d:%02d', $t_h, $t_m, $t_s );
}

/**
 * Convert a formatted string "[h]h:mm" to seconds
 * @param string $p_hhmm	Formatted string
 * @return integer	Seconds value
 */
function hhmm_to_seconds( $p_hhmm ) {
	sscanf($p_hhmm, "%d:%d", $hours, $minutes);
	return $hours * 3600 + $minutes * 60;
}

$g_cache_records_by_id = array();
$g_cache_records_by_bug = array();
$g_cache_records_by_bugnote = array();

/**
 * Loads into cache the time records related to the bug id
 * @global array $g_cache_records_by_bug
 * @global array $g_cache_records_by_id
 * @global array $g_cache_records_by_bugnote
 * @param integer $p_bug_id	Bug id
 * @return void
 */
function cache_records_bug_ids( array $p_bug_id ) {
	global $g_cache_records_by_bug, $g_cache_records_by_id, $g_cache_records_by_bugnote;

	$t_bug_ids_to_search = array();
	foreach( $p_bug_id as $t_id ) {
		$c_id = (int)$t_id;
		if( !isset( $g_cache_records_by_bug[$c_id] ) ) {
			$t_bug_ids_to_search[$c_id] = $c_id;
		}
	}
	if( empty( $t_bug_ids_to_search ) ) {
		return;
	}

	db_param_push();
	$t_count = count( $t_bug_ids_to_search );
	$t_ids_dbparams = array();
	for( $i = 0; $i < $t_count; $i++ ) {
		$t_ids_dbparams[] =  db_param();
	}
	$t_query = 'SELECT T.* FROM {bug} B JOIN ' . plugin_table( 'data' ) . ' T'
			. ' ON B.id = T.bug_id WHERE T.bug_id IN (' . implode( ',', $t_ids_dbparams ) . ')';
	$t_result = db_query( $t_query, array_values( $t_bug_ids_to_search)  );

	while( $t_row = db_fetch_array( $t_result ) ) {
		$c_id = (int)$t_row['id'];
		$c_bug_id = (int)$t_row['bug_id'];
		if( !isset( $g_cache_records_by_bug[$c_bug_id] ) ) {
			$g_cache_records_by_bug[$c_bug_id] = array();
		}
		$g_cache_records_by_id[$c_id] = $t_row;
		$g_cache_records_by_bug[$c_bug_id][$c_id] = $t_row;
		if( null !== $t_row['bugnote_id'] ) {
			$g_cache_records_by_bugnote[(int)$t_row['bugnote_id']] = $t_row;
		}
		unset( $t_bug_ids_to_search[$c_bug_id] );
	}
	# ids remaining in the array are those that don't have records
	foreach( $t_bug_ids_to_search as $t_id ) {
		$g_cache_records_by_bug[$t_id] = false;
	}
}

/**
 * Loads into cache the time record related to the bugnote id
 * @global array $g_cache_records_by_bugnote
 * @param integer $p_bugnote_id	Bugnote id
 */
function cache_records_bugnote_id( $p_bugnote_id ) {
	global $g_cache_records_by_bugnote;
	if( bugnote_exists( $p_bugnote_id ) ) {
		cache_records_bug_ids( array( bugnote_get_field( $p_bugnote_id, 'bug_id' ) ) );
	}
	If( !isset( $g_cache_records_by_bugnote[(int)$p_bugnote_id] ) ) {
		$g_cache_records_by_bugnote[(int)$p_bugnote_id] = false;
	}
}

/**
 * Returns a time tracking record array for specified bugnote id
 * @global array $g_cache_records_by_bugnote
 * @param integer $p_bugnote_id	Bugnote id
 * @return array	Time record array
 */
function get_record_for_bugnote( $p_bugnote_id ) {
	global $g_cache_records_by_bugnote;
	$c_bugnote_id = (int)$p_bugnote_id;
	if( !isset( $g_cache_records_by_bugnote[$c_bugnote_id] ) ) {
		cache_records_bugnote_id( $c_bugnote_id );
	}
	return $g_cache_records_by_bugnote[$c_bugnote_id];
}

/**
 * Loads into cache a set of time records ids
 * @global array $g_cache_records_by_id
 * @param array $p_ids	IDs to load
 * @return void
 */
function cache_record_ids( array $p_ids ) {
	global $g_cache_records_by_id;

	$t_ids_to_search = array();
	foreach( $p_ids as $t_id ) {
		$c_id = (int)$t_id;
		if( !isset( $g_cache_records_by_id[$c_id] ) ) {
			$t_ids_to_search[$c_id] = $c_id;
		}
	}
	if( empty( $t_ids_to_search ) ) {
		return;
	}

	db_param_push();
	$t_count = count( $t_ids_to_search );
	$t_ids_dbparams = array();
	for( $i = 0; $i < $t_count; $i++ ) {
		$t_ids_dbparams[] =  db_param();
	}
	$t_query = 'SELECT * FROM ' . plugin_table( 'data' )
			. ' WHERE id IN (' . implode( ',', $t_ids_dbparams ) . ')';
	$t_result = db_query( $t_query, array_values( $t_ids_to_search)  );

	while( $t_row = db_fetch_array( $t_result ) ) {
		$c_id = (int)$t_row['id'];
		$g_cache_records_by_id[$c_id] = $t_row;
		unset( $t_ids_to_search[$c_id] );
	}
	# ids remaining in the array are those that don't have records
	foreach( $t_ids_to_search as $t_id ) {
		$g_cache_records_by_id[$t_id] = false;
	}
}

/**
 * Returns a time tracking record array for specified databsse id
 * @global array $g_cache_records_by_id
 * @param integer $p_record_id	Time record id
 * @return array	Array containing record data
 */
function get_record_by_id( $p_record_id ) {
	global $g_cache_records_by_id;
	$c_id = (int)$p_record_id;
	if( !isset( $g_cache_records_by_id[$c_id] ) ) {
		cache_record_ids( array( $c_id ) );
	}
	return $g_cache_records_by_id[$c_id];
}

/**
 * Prints html for showing time tracking data in the bugnote activity area
 * @param array $p_record	A time tracking record array
 * @param boolean $p_is_private	Indicates the bugnote is private
 */
function print_bugnote_label_row( $p_record, $p_is_private ) {
	if( $p_is_private ) {
		$t_bugnote_css		= 'bugnote-private';
	} else {
		$t_bugnote_css		= 'bugnote-public';
	}
	$t_time_tracking_hhmm = seconds_to_hhmm( $p_record['time_count'] );
	?>
	<tr class="bugnote <?php echo $t_bugnote_css ?>">
		<td class="category">
		</td>
		<td class="<?php echo $t_bugnote_css ?> bugnote-note bugnote-time-tracking">
			<?php
			echo '<span class="time-tracked label label-grey label-sm">', lang_get( 'time_tracking_time_spent' ) . ' ' . $t_time_tracking_hhmm, '</span>';
			?>
		</td>
	</tr>
	<?php
}

/**
 * Returns true if current user can view a specific time record
 * @param integer $p_record_id	Id of the time record
 * @return boolean
 */
function user_can_view_record_id( $p_record_id ) {
	$t_record = get_record_by_id( $p_record_id );
	if( $t_record ) {
		$t_can_view = access_has_bug_level( plugin_config_get( 'view_threshold' ), $t_record['bug_id'] );
		$t_can_edit = access_has_bug_level( plugin_config_get( 'edit_threshold' ), $t_record['bug_id'] );
		return $t_can_view || $t_can_edit;
	}
	return false;
}

/**
 * Returns true if current user can edit a specific time record
 * @param integer $p_record_id	Id of the time record
 * @return boolean
 */
function user_can_edit_record_id( $p_record_id ) {
	$t_record = get_record_by_id( $p_record_id );
	if( $t_record ) {
		$t_can_edit = access_has_bug_level( plugin_config_get( 'edit_threshold' ), $t_record['bug_id'] );
		return $t_can_edit;
	}
	return false;
}

/**
 * Returns true if current user can edit or add tima tracking records to a bug id
 * @param integer $p_bug_id	Bug id
 * @return boolean
 */
function user_can_edit_bug_id( $p_bug_id ) {
	return access_has_bug_level( plugin_config_get( 'edit_threshold' ), $p_bug_id );
}

/**
 * Prints the html to be included in bugnote form, to add a time tracking record
 */
function print_bugnote_add_form() {
	?>
	<tr>
		<th class="category">
			<?php echo lang_get( 'time_tracking' ) ?>
		</th>
		<td>
			<?php print_timetracking_inputs() ?>
		</td>
	</tr>
	<?php
}

function print_timetracking_inputs() {
	$t_current_date = explode("-", date("Y-m-d"));
	# use a random number becasue this form may exist in several places in the page
	# and collapse scripting is based in element ids.
	$t_id_prefix = 'timetracking_add_' . rand();
	?>
			<span><?php echo lang_get( 'time_tracking_time_spent' ) ?></span>
			<a data-toggle="tooltip" data-placement="bottom" title="<?php echo plugin_lang_get( 'time_input_tooltip' ) ?>">
				<i class='glyphicon glyphicon-info-sign'></i>
			</a>
			<input type="text" name="plugin_timetracking_time_input" class="form-control input-sm">

			<span class="collapsed-input-group">
				<a class="btn btn-xs btn-link collapsed" data-toggle="collapse" data-target="#<?php echo $t_id_prefix ?>_category" title="Category">
					<i class="fa fa-tag"></i>
				</a>
				<span id="<?php echo $t_id_prefix ?>_category" class="collapse collapse-inline disable-collapsed-inputs">
					<span>Category</span>
					<select name="plugin_timetracking_category" class="input-sm">
						<?php
						foreach ( explode(PHP_EOL,plugin_config_get( 'categories' )) as $t_key ) {
							echo '<option value="' . $t_key . '">' . $t_key . '</option>';
						} ?>
					</select>
				</span>
			</span>

			<span class="collapsed-input-group">
				<a class="btn btn-xs btn-link collapsed" data-toggle="collapse" data-target="#<?php echo $t_id_prefix ?>_date" title="Date">
					<i class="fa fa-calendar-o"></i>
				</a>
				<span id="<?php echo $t_id_prefix ?>_date" class="collapse collapse-inline disable-collapsed-inputs">
					<span>Date</span>
					<select tabindex="5" name="plugin_timetracking_exp_date_d"><?php print_day_option_list( $t_current_date[2] ) ?></select>
					<select tabindex="6" name="plugin_timetracking_exp_date_m"><?php print_month_option_list( $t_current_date[1] ) ?></select>
					<select tabindex="7" name="plugin_timetracking_exp_date_y"><?php print_year_option_list( $t_current_date[0] ) ?></select>
				</span>
			</span>

			<span class="collapsed-input-group">
				<a class="btn btn-xs btn-link collapsed" data-toggle="collapse" data-target="#<?php echo $t_id_prefix ?>_info" title="Info">
					<i class="fa fa-sticky-note-o"></i>
				</a>
				<span id="<?php echo $t_id_prefix ?>_info" class="collapse collapse-inline disable-collapsed-inputs">
					<span>Info</span>
					<input class="form-control input-sm" type="text" name="plugin_timetracking_info">
				</span>
			</span>
	<?php
}

/**
 * Parses gpc request parameters from a time tracking form
 * Returns a partial record array with values filled from parsed parameters, or defaulted if not present
 * @return array	Time record array
 */
function parse_gpc_time_record() {
	$t_record = array();
	$t_input_time = gpc_get_string( 'plugin_timetracking_time_input', '' );
	if( is_blank( $t_input_time ) ) {
		error_parameters( 'time tracking time value' );
		trigger_error( ERROR_EMPTY_FIELD, ERROR );
	}
	$t_record['time_count'] = parse_time_string( $t_input_time );

	$t_input_category = gpc_get_string( 'plugin_timetracking_category', '' );
	if( is_blank( $t_input_category ) ) {
		$t_record['category'] = null;
	} else {
		$t_record['category'] = $t_input_category;
	}

	$t_input_exp_y = gpc_get_string( 'plugin_timetracking_exp_date_y', '' );
	$t_input_exp_m = gpc_get_string( 'plugin_timetracking_exp_date_m', '' );
	$t_input_exp_d = gpc_get_string( 'plugin_timetracking_exp_date_d', '' );
	if( is_blank( $t_input_exp_y ) && is_blank( $t_input_exp_m ) && is_blank( $t_input_exp_d ) ) {
		$t_record['time_exp_date'] = db_now();
	} else {
		$t_record['time_exp_date'] = parse_date_parts( $t_input_exp_y, $t_input_exp_m, $t_input_exp_d );
	}

	$t_input_info = gpc_get_string( 'plugin_timetracking_info', '' );
	if( is_blank( $t_input_info ) ) {
		$t_record['info'] = null;
	} else {
		$t_record['info'] = $t_input_info;
	}

	$t_record['user_id'] = auth_get_current_user_id();

	return $t_record;
}

/**
 * Parses date parts for year, month and day, into a timestamp date
 * @param string $p_y	String part for year
 * @param type $p_m		String part for month
 * @param type $p_d		String part for day
 * @return integer	Converted date as timestamp
 */
function parse_date_parts( $p_y, $p_m, $p_d ) {
	$t_date_string = sprintf('%04d-%02d-%02d', $p_y, $p_m, $p_d );
	$t_parse_date = \DateTime::createFromFormat( 'Y-m-d', $p_y . '-' . $p_m . '-' . $p_d );
	$t_string_verify = $t_parse_date->format( 'Y-m-d' );
	if( $t_date_string != $t_string_verify ) {
		trigger_error( ERROR_INVALID_DATE_FORMAT, ERROR );
	}
	return $t_parse_date->getTimeStamp();
}

/**
 * Parses a string containing a time value, and converts it into seconds value
 * Accepted formats are: "hh:mm:ss", "N h N m N s"
 * Examples:
 *   "1:20:30"    -> 1 hour + 20 minutes + 30 seconds
 *   "1:20"       -> 1 hour + 20 minutes
 *   "20"         -> 20 minutes
 *   "1h 20m 30s" -> 1 hour + 20 minutes + 30 seconds
 *   "1 h 20 m"   -> 1 hour + 20 minutes
 *   "20 m 30s"   -> 20 minutes + 30 seconds
 *   "20m"        -> 20 minutes
 *   "1h"         -> 1 hour
 * @param string $t_string	String value for time
 * @return integer	Time in seconds
 */
function parse_time_string( $t_string ) {
	if ( preg_match( '/^(?:(?<hours>\d+):)?(?:(?<minutes>\d+))?(?::(?<seconds>\d+))?$/', $t_string, $t_matches1 ) ) {
		# test for hh:mm:ss, where hh: or :ss are optional
		$t_h = empty( $t_matches1['hours'] ) ? 0 : (int)$t_matches1['hours'];
		$t_m = empty( $t_matches1['minutes'] ) ? 0 : (int)$t_matches1['minutes'];
		$t_s = empty( $t_matches1['seconds'] ) ? 0 : (int)$t_matches1['seconds'];
	} elseif ( preg_match( '/^(?:(?<hours>\d+)\s*h\s*)?(?:(?<minutes>\d+)\s*m\s*)?(?:(?<seconds>\d+)\s*s\s*)?$/', $t_string, $t_matches2 ) ) {
		# test for "Nh Nm Ns", any part is optional
		$t_h = empty( $t_matches2['hours'] ) ? 0 : (int)$t_matches2['hours'];
		$t_m = empty( $t_matches2['minutes'] ) ? 0 : (int)$t_matches2['minutes'];
		$t_s = empty( $t_matches2['seconds'] ) ? 0 : (int)$t_matches2['seconds'];
	} else {
		plugin_error( ERROR_INVALID_TIME_FORMAT );
	}

	if( $t_s >= 60 || $t_m>= 60 ) {
		plugin_error( ERROR_INVALID_TIME_FORMAT );
	}
	$t_time = 3600 * $t_h + 60 * $t_m + $t_s;
	if( $t_time == 0 ) {
		plugin_error( ERROR_INVALID_TIME_FORMAT );
	}
	return $t_time;
}

/**
 * Insert a time record in database. Also, logs in bug history.
 * Record parameter is an associative array that must have the needed information:
 *   'user_id' => user id who creates the record
 *   'bug_id' => bug id associated to the record
 *   'bugnote_id' => associated bugnote id, or null
 *   'time_exp_date' => timestamp for effective date for the time record
 *   'time_count' => time value in seconds
 *   'category' => associated category string, or null
 *   'info' => associated info string, or null
 * @param array $p_record	Record array
 */
function create_record( array $p_record ) {
	db_param_push();
	$t_table = plugin_table( 'data' );
	$t_query = 'INSERT INTO ' . $t_table . ' ( user_id, bug_id, bugnote_id, time_exp_date, time_count, date_created, category, info )'
			. ' VALUES ( ' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ')';
	$t_params = array( (int)$p_record['user_id'], (int)$p_record['bug_id'], (int)$p_record['bugnote_id'], (int)$p_record['time_exp_date'], (int)$p_record['time_count'], db_now(), $p_record['category'], $p_record['info'] );
	db_query($t_query, $t_params );

	plugin_history_log( (int)$p_record['bug_id'], 'add_time_record', '', seconds_to_hhmmss( (int)$p_record['time_count'] ) );
}

/**
 * Deletes a record from database. Also, logs in bug history
 * @param integer $p_record_id	Record id
 */
function delete_record( $p_record_id ) {
	$t_record = get_record_by_id( $p_record_id );
	$t_bug_id = (int)$t_record['bug_id'];
	$t_time = (int)$t_record['time_count'];

	db_param_push();
	$t_query = 'DELETE FROM ' . plugin_table( 'data' ) . ' WHERE id = ' . db_param();
	db_query($t_query, array( (int)$p_record_id ) );

	plugin_history_log( $t_bug_id, 'delete_time_record', seconds_to_hhmmss( $t_time ), '' );
}