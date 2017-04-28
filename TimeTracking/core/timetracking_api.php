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
	$c_project_id = db_prepare_int( $p_project_id );
	$c_to = date("Y-m-d", strtotime($p_to)+ SECONDS_PER_DAY - 1);
	$c_from = date("Y-m-d", strtotime($p_from));
	if ( $c_to === false || $c_from === false ) {
		error_parameters( array( $p_form, $p_to ) );
		trigger_error( ERROR_GENERIC, ERROR );
	}
	$t_timereport_table = plugin_table('data', 'TimeTracking');
	$t_bug_table = db_get_table( 'mantis_bug_table' );
	$t_user_table = db_get_table( 'mantis_user_table' );
	$t_project_table = db_get_table( 'mantis_project_table' );

	$t_query = 'SELECT u.username, p.name as project_name, bug_id, expenditure_date, hours, timestamp, info
	FROM '.$t_timereport_table.' tr
	LEFT JOIN {bug} b ON tr.bug_id=b.id
	LEFT JOIN {user} u ON tr.user=u.id
	LEFT JOIN {project} p ON p.id = b.project_id
	WHERE 1=1 ';

	db_param_push();
	$t_query_parameters = array();

	if( !is_blank( $c_from ) ) {
		$t_query .= " AND expenditure_date >= " . db_param();
		$t_query_parameters[] = $c_from;
	}
	if( !is_blank( $c_to ) ) {
		$t_query .= " AND expenditure_date <= " . db_param();
		$t_query_parameters[] = $c_to;
	}
	if( ALL_PROJECTS != $c_project_id ) {
		$t_query .= " AND b.project_id = " . db_param();
		$t_query_parameters[] = $c_project_id;
	}
	if ( !access_has_global_level( plugin_config_get( 'view_others_threshold' ) ) ){
		$t_user_id = auth_get_current_user_id(); 
		$t_query .= " AND user = " . db_param();
		$t_query_parameters[] = $t_user_id;
	}
	$t_query .= ' ORDER BY user, expenditure_date, bug_id';

	$t_results = array();

	$result = db_query( $t_query, $t_query_parameters );
	while( $row = db_fetch_array( $result ) ) {
		$t_results[] = $row;
	}
	return $t_results;
}

/**
* Returns an array of time tracking stats
* @param int $p_project_id project id
* @param string $p_from Starting date (yyyy-mm-dd) inclusive, if blank, then ignored.
* @param string $p_to Ending date (yyyy-mm-dd) inclusive, if blank, then ignored.
* @return array array of bugnote stats
* @access public
*/
function plugin_TimeTracking_hhmm_to_minutes( $p_hhmm) {
	sscanf($p_hhmm, "%d:%d", $hours, $minutes); 
	return $hours * 60 + $minutes;
}
?>