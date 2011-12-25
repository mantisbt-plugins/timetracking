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
//date("Y-m-d", strtotime("$f_year-$f_month-$f_day"));
$c_to = "'" . date("Y-m-d", strtotime("$p_to")+ SECONDS_PER_DAY - 1) . "'"; 
//strtotime( $p_to ) + SECONDS_PER_DAY - 1;
$c_from = "'" . $p_from . "'"; //strtotime( $p_from ) 
if ( $c_to === false || $c_from === false ) {
error_parameters( array( $p_form, $p_to ) );
trigger_error( ERROR_GENERIC, ERROR );
}
//$c_cost = db_prepare_double( $p_cost );
//$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
$t_timereport_table = plugin_table('data', 'TimeTracking');
$t_bug_table = db_get_table( 'mantis_bug_table' );
$t_user_table = db_get_table( 'mantis_user_table' );
if( !is_blank( $c_from ) ) {
$t_from_where = " AND expenditure_date >= $c_from";
} else {
$t_from_where = '';
}
if( !is_blank( $c_to ) ) {
$t_to_where = " AND expenditure_date <= $c_to";
} else {
$t_to_where = '';
}
if( ALL_PROJECTS != $c_project_id ) {
$t_project_where = " AND b.project_id = '$c_project_id'  ";
} else {
$t_project_where = '';
}
if ( access_has_global_level( plugin_config_get( 'add_threshold' ) ) ){
$t_user_id = auth_get_current_user_id(); 
$t_user_where = " AND user = '$t_user_id'  ";
} else {
$t_user_where = '';
}

$t_results = array();
$query = "SELECT u.username, bug_id, expenditure_date, hours, timestamp, info 
FROM $t_timereport_table tr, $t_bug_table b, $t_user_table u
WHERE tr.bug_id=b.id and tr.user=u.id
$t_project_where $t_from_where $t_to_where $t_user_where
ORDER BY user, expenditure_date, bug_id";
//echo $query;
$result = db_query( $query );
//$t_cost_min = $c_cost / 60;
while( $row = db_fetch_array( $result ) ) {
//$t_total_cost = $t_cost_min * $row['sum_time_tracking'];
//$row['cost'] = $t_total_cost;
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