<?php
/**
* Returns an array of time tracking stats
* @param int $p_project_id project id
* @param string $p_from Starting date (yyyy-mm-dd) inclusive, if blank, then ignored.
* @param string $p_to Ending date (yyyy-mm-dd) inclusive, if blank, then ignored.
* @return array array of bugnote stats
* @access public
*/
function plugin_TimeTracking_stats_get_project_array( $p_project_id, $p_from, $p_to) {
	$t_project_id = db_prepare_int( $p_project_id );
	$t_to = date("Y-m-d", strtotime("$p_to")+ SECONDS_PER_DAY - 1); 
	$t_from = $p_from; //strtotime( $p_from ) 
	if ( $t_to === false || $t_from === false ) {
		error_parameters( array( $p_form, $p_to ) );
		trigger_error( ERROR_GENERIC, ERROR );
	}
	$t_timereport_table = plugin_table('data', 'TimeTracking');
	$t_bug_table = db_get_table( 'bug' );
	$t_user_table = db_get_table( 'user' );
	$t_project_table = db_get_table( 'project' );

	$t_query = 'SELECT u.username, p.name as project_name, bug_id, expenditure_date, hours, timestamp, category, info 
	FROM '.$t_timereport_table.' tr
	LEFT JOIN '.$t_bug_table.' b ON tr.bug_id=b.id
	LEFT JOIN '.$t_user_table.' u ON tr.user=u.id
	LEFT JOIN '.$t_project_table.' p ON p.id = b.project_id
	WHERE 1=1 ';
	
	db_param_push();
	$t_query_parameters = array();

	if( !is_blank( $t_from ) ) {
		$t_query .= " AND expenditure_date >= " . db_param();
		$t_query_parameters[] = $t_from;
	}
	if( !is_blank( $t_to ) ) {
		$t_query .= " AND expenditure_date <= " . db_param();
		$t_query_parameters[] = $t_to;
	}
	if( ALL_PROJECTS != $t_project_id ) {
		$t_query .= " AND b.project_id = " . db_param();
		$t_query_parameters[] = $t_project_id;
	}
	if ( !access_has_global_level( plugin_config_get( 'view_others_threshold' ) ) ){
		$t_user_id = auth_get_current_user_id(); 
		$t_query .= " AND user = " . db_param();
		$t_query_parameters[] = $t_user_id;
	}
	$t_query .= ' ORDER BY user, expenditure_date, bug_id';

	$t_results = array();
	
	//$t_project_where $t_from_where $t_to_where $t_user_where
	

	$t_dbresult = db_query( $t_query, $t_query_parameters );
	while( $row = db_fetch_array( $t_dbresult ) ) {
		$t_results[] = $row;
	}
	return $t_results;
}

/**
* Returns an integer of minutes
* @param string $p_hhmm Time (hh:mm)
* @return integer integer of minutes
* @access public
*/
function plugin_TimeTracking_hhmm_to_minutes( $p_hhmm) {
	sscanf($p_hhmm, "%d:%d", $hours, $minutes); 
	return $hours * 60 + $minutes;
}

/**
* convert hours to a time format [h]h:mm
* @param string $p_hhmm Time (hh:mm)
* @return integer integer of minutes
* @access public
*/
function plugin_TimeTracking_hours_to_hhmm( $p_hours ) {
	$t_min = round( $p_hours * 60 );
	return sprintf( '%02d:%02d', $t_min / 60, $t_min % 60 );
}

?>
