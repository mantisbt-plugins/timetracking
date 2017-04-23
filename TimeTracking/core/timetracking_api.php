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
