<?php
namespace TimeTracking;

$f_id = gpc_get_int( 'id' );
$f_return = string_sanitize_url( gpc_get_string( 'return', '' ) );

form_security_validate( 'plugin_TimeTracking_update_record' );

$t_record = get_record_by_id( $f_id );
if( !$t_record ) {
	plugin_error( ERROR_ID_NOT_EXISTS, ERROR );
}

if( !user_can_edit_record_id( $t_record['id'] ) ) {
	access_denied();
}

$f_gpc_record = parse_gpc_time_record();
$t_record['time_count'] = $f_gpc_record['time_count'];
$t_record['category'] = $f_gpc_record['category'];
$t_record['time_exp_date'] = $f_gpc_record['time_exp_date'];
$t_record['bugnote_id'] = $f_gpc_record['bugnote_id'];
$t_record['info'] = $f_gpc_record['info'];

update_record( $t_record );

form_security_purge( 'plugin_TimeTracking_update_record' );

print_successful_redirect( $f_return );

