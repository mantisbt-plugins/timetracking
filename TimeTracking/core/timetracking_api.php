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
	if ( !access_has_global_level( plugin_config_get( 'view_others_threshold' ) ) ){
		$t_user_id = auth_get_current_user_id(); 
		$t_query .= " AND T.user_id = " . db_param();
		$t_query_parameters[] = $t_user_id;
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

function cache_records_bugnote_id( $p_bugnote_id ) {
	global $g_cache_records_by_bugnote;
	if( bugnote_exists( $p_bugnote_id ) ) {
		cache_records_bug_ids( array( bugnote_get_field( $p_bugnote_id, 'bug_id' ) ) );
	} else {
		$g_cache_records_by_bugnote[(int)$p_bugnote_id] = false;
	}
}

function get_record_for_bugnote( $p_bugnote_id ) {
	global $g_cache_records_by_bugnote;
	$c_bugnote_id = (int)$p_bugnote_id;
	if( !isset( $g_cache_records_by_bugnote[$c_bugnote_id] ) ) {
		cache_records_bugnote_id( $c_bugnote_id );
	}
	return $g_cache_records_by_bugnote[$c_bugnote_id];
}

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