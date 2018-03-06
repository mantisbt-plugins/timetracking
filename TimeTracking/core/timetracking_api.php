<?php
namespace TimeTracking;

/**
 * Convert seconds to hours, formatted as "d.dd"
 * @param integer $p_seconds	Time in seconds
 * @return string	Formatted number
 */
function seconds_to_hours( $p_seconds ) {
	$t_hours = $p_seconds/3600;
	return number_format( $t_hours, 2, '.', ',' );
}

/**
 * Convert seconds to a time format "[h]h:mm"
 * @param integer $p_seconds	Time in seconds
 * @return string	Formatted string
 */
function seconds_to_hhmm( $p_seconds ) {
	$t_h = floor( $p_seconds / 3600 );
	$t_m = floor( ( $p_seconds - $t_h * 3600 ) / 60 );
	return sprintf( '%02d:%02d', $t_h, $t_m );
}

/**
 * Convert seconds to a time format "h:mm:ss"
 * @param integer $p_seconds	Time in seconds
 * @return string	Formatted string
 */
function seconds_to_hhmmss( $p_seconds ) {
	$t_h = floor( $p_seconds / 3600 );
	$t_m = floor( ( $p_seconds - $t_h * 3600) / 60 );
	$t_s = $p_seconds - ( $t_h * 3600 + $t_m * 60 );
	return sprintf( '%02d:%02d:%02d', $t_h, $t_m, $t_s );
}

/**
 * Convert seconds to a time format "Xh Xm Xs"
 * least significant units will be omitted if value is zero
 * @param type $p_seconds
 * @return type
 */
function seconds_to_hms( $p_seconds ) {
	$t_h = floor( $p_seconds / 3600 );
	$t_m = floor( ( $p_seconds - $t_h * 3600 ) / 60 );
	$t_s = $p_seconds - ( $t_h * 3600 + $t_m * 60 );
	$t_str = '';
	if( $t_h > 0 ) {
		$t_str .= $t_h . 'h';
	}
	if( $t_m > 0 || ( $t_h > 0 && $t_s > 0 ) ) {
		$t_str .= ' ' . $t_m . 'm';
	}
	if( $t_s > 0 ) {
		$t_str .= ' ' . $t_s . 's';
	}
	return $t_str;
}


/**
 * Convert a formatted string "[h]h:mm" to seconds
 * @param string $p_hhmm	Formatted string
 * @return integer	Seconds value
 */
function hhmm_to_seconds( $p_hhmm ) {
	sscanf( $p_hhmm, "%d:%d", $hours, $minutes );
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
	$t_query = 'SELECT T.* FROM {bug} B JOIN ' . plugin_table( 'data', __NAMESPACE__ ) . ' T'
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

function get_records_for_bug( $p_bug_id ) {
	global $g_cache_records_by_bug;

	if( !isset( $g_cache_records_by_bug[$p_bug_id] ) ) {
		cache_records_bug_ids( array( $p_bug_id ) );
	}
	return $g_cache_records_by_bug[$p_bug_id];
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
 * Returns true if current user can edit or add time tracking records to a bug id
 * @param integer $p_bug_id	Bug id
 * @return boolean
 */
function user_can_edit_bug_id( $p_bug_id ) {
	return access_has_bug_level( plugin_config_get( 'edit_threshold' ), $p_bug_id );
}

/**
 * Returns true if current user can view time tracking records for a bug
 * It accounts for bug access (public/private, limit reporters, etc)
 * @param integer $p_bug_id		Bug id
 * @return boolean
 */
function user_can_view_bug_id( $p_bug_id ) {
	return access_has_bug_level( plugin_config_get( 'view_threshold' ), $p_bug_id )
		|| access_has_bug_level( plugin_config_get( 'edit_threshold' ), $p_bug_id );
}

/**
 * Returns true if current user can view time tracking records in a project
 * @param integer $p_project_id		Project id
 * @return boolean
 */
function user_can_view_project_id( $p_project_id ) {
	return access_has_project_level( plugin_config_get( 'view_threshold' ), $p_project_id )
		|| access_has_project_level( plugin_config_get( 'edit_threshold' ), $p_project_id );
}

/**
 * Prints the html to be included in bugnote form, to add a time tracking record
 * @param integer $p_bug_id		Bug id
 */
function print_bugnote_add_form( $p_bug_id ) {
	?>
	<tr>
		<th class="category">
			<?php echo plugin_lang_get( 'time_tracking' ) ?>
		</th>
		<td>
			<?php print_timetracking_inputs( $p_bug_id ) ?>
		</td>
	</tr>
	<?php
}


function print_input_time_count( $p_time = null ) {
	$t_time_value = '';
	if( $p_time ) {
		$t_time_value = seconds_to_hms( $p_time );
	}
	echo '<input type="text" name="plugin_timetracking_time_input" class="form-control input-sm timetracking_time_input" value="' . $t_time_value . '">';
	echo '<a data-toggle="tooltip" data-placement="bottom" title="' . plugin_lang_get( 'time_input_tooltip' ) . '">';
	echo '<i class="glyphicon glyphicon-info-sign"></i>';
	echo '</a>';
}

function print_input_category( $p_category= null ) {
	echo '<select name="plugin_timetracking_category" class="form-control input-sm">';
	echo '<option value=""></option>';
	print_timetracking_category_option_list( $p_category );
	echo '</select>';
}

function print_input_date( $p_input_basename, $p_time = null ) {
	$t_date = new \DateTime();
	if( null !== $p_time ) {
		$t_date->setTimestamp( $p_time );
	}
	echo '<select name="' . $p_input_basename . '_d" class="form-control input-sm">';
	print_day_option_list( $t_date->format( 'd' ) );
	echo '</select>';
	echo '<select name="' . $p_input_basename . '_m" class="form-control input-sm">';
	print_month_option_list( $t_date->format( 'm' ) );
	echo '</select>';
	echo '<select name="' . $p_input_basename . '_y" class="form-control input-sm">';
	print_year_option_list( $t_current_date[0] );
	echo '</select>';
}

function print_input_info( $p_info_text = null ) {
	echo '<input class="form-control input-sm" type="text" name="plugin_timetracking_info" value="' . $p_info_text . '">';
}

/**
 * Prints the inputs to enter a time record.
 * This does not include the form tags
 * @param integer $p_bug_id		Bug id
 */
function print_timetracking_inputs( $p_bug_id ) {
	# use a random number becasue this form may exist in several places in the page
	# and collapse scripting is based in element ids.
	$t_id_prefix = 'timetracking_add_' . rand();
	?>
		<span><?php echo plugin_lang_get( 'time_spent' ) ?></span>

		<?php print_input_time_count() ?>
		<input type="hidden" name="plugin_timetracking_from_stopwatch" class="timetracking_from_stopwatch" value="0">
		<?php print_hidden_input( 'plugin_timetracking_time_input_bug_id', $p_bug_id ) ?>

		<span class="collapsed-input-group">
			<a class="btn btn-xs btn-link collapsed" data-toggle="collapse" data-target="#<?php echo $t_id_prefix ?>_category" title="<?php echo plugin_lang_get( 'category' ) ?>">
				<i class="fa fa-tag"></i>
			</a>
			<span id="<?php echo $t_id_prefix ?>_category" class="collapse collapse-inline disable-collapsed-inputs">
				<span><?php echo plugin_lang_get( 'category' ) ?></span>
				<?php print_input_category() ?>
			</span>
		</span>

		<span class="collapsed-input-group">
			<a class="btn btn-xs btn-link collapsed" data-toggle="collapse" data-target="#<?php echo $t_id_prefix ?>_date" title="<?php echo plugin_lang_get( 'expenditure_date' ) ?>">
				<i class="fa fa-calendar-o"></i>
			</a>
			<span id="<?php echo $t_id_prefix ?>_date" class="collapse collapse-inline disable-collapsed-inputs">
				<span><?php echo plugin_lang_get( 'expenditure_date' ) ?></span>
				<?php print_input_date( 'plugin_timetracking_exp_date' ) ?>
			</span>
		</span>

		<span class="collapsed-input-group">
			<a class="btn btn-xs btn-link collapsed" data-toggle="collapse" data-target="#<?php echo $t_id_prefix ?>_info" title="<?php echo plugin_lang_get( 'information' ) ?>">
				<i class="fa fa-sticky-note-o"></i>
			</a>
			<span id="<?php echo $t_id_prefix ?>_info" class="collapse collapse-inline disable-collapsed-inputs">
				<span><?php echo plugin_lang_get( 'information' ) ?></span>
				<?php print_input_info() ?>
			</span>
		</span>
		<?php if( stopwatch_enabled() ) { ?>
		<div class="pull-right">
			<?php print_stopwatch_control( 'button' ) ?>
		</div>
		<?php } ?>
	<?php
}

/**
 * Parses gpc request parameters from a time tracking form
 * Returns a partial record array with values filled from parsed parameters, or defaulted if not present
 * @return array	Time record array
 */
function parse_gpc_time_record() {
	$t_record = array();

	$t_input_bug_id = gpc_get_int( 'plugin_timetracking_time_input_bug_id', 0 );
	bug_ensure_exists( $t_input_bug_id );
	$t_record['bug_id'] = $t_input_bug_id;

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

	$t_input_bugnote_id = gpc_get_int( 'plugin_timetracking_bugnote_id', 0 );
	if( $t_input_bugnote_id > 0 ) {
		if( !bugnote_exists( $t_input_bugnote_id ) ) {
			trigger_error( ERROR_BUGNOTE_NOT_FOUND, ERROR );
		}
		$t_note_bug_id = bugnote_get_field( $t_input_bugnote_id, 'bug_id' );
		if( $t_note_bug_id != $t_record['bug_id'] ) {
			trigger_error( ERROR_BUGNOTE_NOT_FOUND, ERROR );
		}
		$t_record['bugnote_id']	= $t_input_bugnote_id;
	} else {
		$t_record['bugnote_id'] = null;
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
	$t_date_string = sprintf( '%04d-%02d-%02d', $p_y, $p_m, $p_d );
	$t_parse_date = \DateTime::createFromFormat( 'Y-m-d', $t_date_string );
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
	$t_string = string_normalize( $t_string );
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

	# avoid eg: "1h 90s"; allow "90s"
	if( $t_s >= 60 && ( $t_m > 0 || $t_h > 0 ) ) {
		plugin_error( ERROR_INVALID_TIME_FORMAT );
	}
	# avoid eg: 1h 90m; allow "90m"
	if( $t_m>= 60 && $t_h > 0 ) {
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
	db_query( $t_query, $t_params );

	plugin_history_log( (int)$p_record['bug_id'], 'add_time_record', '', seconds_to_hhmmss( (int)$p_record['time_count'] ) );
}

function update_record( array $p_record ) {
	db_param_push();
	$t_table = plugin_table( 'data' );
	$t_query = 'UPDATE ' . $t_table . ' SET user_id = ' . db_param() . ', bug_id = ' . db_param() . ', bugnote_id = ' . db_param() . ', time_exp_date = ' . db_param()
			. ', time_count = ' . db_param() . ', category = ' . db_param() . ', info = ' . db_param()
			. ' WHERE id = ' . db_param();
	$t_params = array( (int)$p_record['user_id'], (int)$p_record['bug_id'], (int)$p_record['bugnote_id'], (int)$p_record['time_exp_date'], (int)$p_record['time_count'], $p_record['category'], $p_record['info'], $p_record['id'] );
	db_query( $t_query, $t_params );
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
	db_query( $t_query, array( (int)$p_record_id ) );

	plugin_history_log( $t_bug_id, 'delete_time_record', seconds_to_hhmmss( $t_time ), '' );
}

/**
 * Returns total time for a bug id
 * If a user_id is provided, only that user's time will be added
 * @param type $p_bug_id	Bug id
 * @param type $p_user_id	User id
 * @return integer		Total time in seconds
 */
function get_total_time_for_bug_id( $p_bug_id, $p_user_id = null ) {
	$t_records = get_records_for_bug( $p_bug_id );
	if( !$t_records ) {
		return false;
	}
	$t_time = 0;
	foreach( $t_records as $t_record ) {
		if( $p_user_id && $p_user_id != $t_record['user_id'] ) {
			continue;
		}
		$t_time += $t_record['time_count'];
	}
	return $t_time;
}

/**
 * Prints a row to be placed within the main bug fields in bug-view page
 * @param integer $p_bug_id		Current bug id
 */
function print_bug_details_row( $p_bug_id ) {
	$t_time = get_total_time_for_bug_id( $p_bug_id );
	if( $t_time ) {
	?>
		<tr>
			<th class="category"><?php echo plugin_lang_get( 'time_tracking' ) ?></th>
			<td colspan="5">
				<?php echo plugin_lang_get( 'total_time_for_issue' ) ?> = 
				<span class="time-tracked"><?php echo seconds_to_hms( $t_time ) ?></span>
				<small><a href="#timerecord">(<?php echo plugin_lang_get( 'details_link' ) ?>)	</a></small>
			</td>
		</tr>
	<?php
	}
}

/**
 * Prints the main widget that is placed in bug-view page
 * @param integer $p_bug_id		Current bug id
 */
function print_bug_timetracking_section( $p_bug_id ) {
	$t_collapse_block = is_collapsed( 'timerecord' );
	$t_block_css = $t_collapse_block ? 'collapsed' : '';
	$t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';
	?>
	<div class="col-md-12 col-xs-12 noprint">
		<a id="timerecord"></a>
		<div class="space-10"></div>

		<div id="timerecord_add" class="widget-box widget-color-blue2 <?php echo $t_block_css ?>">
			<div class="widget-header widget-header-small">
				<h4 class="widget-title lighter">
					<i class="ace-icon fa fa-clock-o"></i>
					<?php echo plugin_lang_get( 'title' ) ?>
				</h4>
				<div class="widget-toolbar">
					<a data-action="collapse" href="#">
						<i class="1 ace-icon fa <?php echo $t_block_icon ?> bigger-125"></i>
					</a>
				</div>
			</div>

			<div class="widget-body">
			<?php
			if( user_can_edit_bug_id( $p_bug_id ) ) {
			?>
				<div class="widget-main">
				<span><h5><?php echo plugin_lang_get( 'add_entry' ) ?></i></h5></span>
					<form name="time_tracking" method="post" action="<?php echo plugin_page('add_record') ?>" >
						<?php echo form_security_field( 'plugin_TimeTracking_add_record' ) ?>
						<?php print_timetracking_inputs( $p_bug_id ) ?>
						<input name="submit" class="btn btn-primary btn-white btn-round" type="submit" value="<?php echo plugin_lang_get( 'submit' ) ?>">
					</form>
				</div>
			<?php
			}

			$t_records = get_records_for_bug( $p_bug_id );
			if( $t_records ) {
				$t_report = new ReportForBug( $p_bug_id );
				$t_report->read_gpc_params();
				?>
				<div class="widget-main">
					<span><h5><?php echo plugin_lang_get( 'title' ) . ': ' . lang_get( 'summary' ) ?></h5></span>
					<form action="<?php url_self() ?>#timerecord" method="post" class="form-inline" role="form">
						<?php $t_report->print_inputs_group_by() ?>
						<input type="submit" class="btn btn-primary btn-sm btn-white btn-round no-float" value="<?php echo lang_get( 'apply_filter_button' ) ?>">
					</form>
					<?php $t_report->print_report_pagination() ?>
				</div>

				<div class="widget-main no-padding">
					<div class="table-responsive">
					<?php
					$t_report->print_table();
					?>
					</div>
				</div>
				<div class="widget-main">
					<?php
					$t_link = url_safe_link( plugin_page( 'view_records_page' ), array( 'bug_id' => $p_bug_id ) );
					print_link_button( $t_link, plugin_lang_get( 'details_link' ), 'btn-sm' );
					?>
				</div>
				<?php
			}
			?>
			</div>
		</div>
	</div>
		<?php
}

/**
 * Returns a sanitized url for current script url
 * @return string	url
 */
function url_self() {
	$t_url_page = string_sanitize_url( basename( $_SERVER['SCRIPT_NAME'] ) );
	return $t_url_page;
}

/**
 * Returns a sanitized url, and appends key/value pairs to the request part
 * @param string $p_url		Base url
 * @param array $p_params	Array of key/value pairs
 * @return string			Composed url
 */
function url_safe_link( $p_url, array $p_params = null ) {
	$t_url = $p_url;
	if( !empty( $p_params ) ) {
		$t_delimiter = ( strpos( $t_url, '?' ) ? '&' : '?' ) ;
		$t_url .= $t_delimiter . http_build_query( $p_params );
	}
	return string_sanitize_url( $t_url );
}

/**
 * Prints option tags for a category selection, for existing time tracking categories
 */
function print_timetracking_category_option_list( $p_category = null ){
	foreach ( explode( PHP_EOL, plugin_config_get( 'categories' ) ) as $t_key ) {
		echo '<option value="' . $t_key . '" ' . check_selected( $t_key, $p_category, false ) . '>' . string_display_line( $t_key ) . '</option>';
	}
}

/**
 * Prints option tags for a user selection, for users creators of time records
 * to keep it simple, returns all users that currently have entered time records
 */
function print_timetracking_user_option_list() {
	$t_query = 'SELECT DISTINCT user_id FROM ' . plugin_table( 'data' )
			. ' WHERE user_id IS NOT NULL';
	$t_result = db_query( $t_query );
	$t_user_ids = array();
	while( $t_row = db_fetch_array( $t_result ) ) {
		$t_user_ids[] = (int)$t_row['user_id'];
	}
	user_cache_array_rows( $t_user_ids );
	$t_usernames = array();
	foreach( $t_user_ids as $t_id ) {
		$t_usernames[$t_id] = user_get_name( $t_id );
	}
	asort( $t_usernames, SORT_STRING | SORT_FLAG_CASE );
	foreach( $t_usernames as $t_id => $t_name ) {
		echo '<option value="' . $t_id . '">' . string_display_line( $t_name ) . '</option>';
	}
}

/**
 * Override standard print_form_button() to allow for button tags
 * @param string $p_action_page    The action page.
 * @param string $p_label          The button label.
 * @param array  $p_args_to_post   Associative array of arguments to be posted, with
 *                                 arg name => value, defaults to null (no args).
 * @param mixed  $p_security_token **not used here, as it wont work as expected with plugin pages**
 * @param string $p_class          The CSS class of the button.
 */
function print_form_button( $p_action_page, $p_label, $p_args_to_post = null, $p_security_token = null, $p_class = '' ) {
	echo '<form method="post" action="', htmlspecialchars( $p_action_page ), '" class="form-inline inline">';
	echo '<fieldset>';
	if( $p_class !== '') {
		$t_class = $p_class;
	} else {
		$t_class = 'btn btn-primary btn-xs btn-white btn-round';
	}
	echo '<button type="submit" class="' . $t_class . '">' . $p_label . '</button>';
	print_hidden_inputs( $p_args_to_post );
	echo '</fieldset>';
	echo '</form>';
}