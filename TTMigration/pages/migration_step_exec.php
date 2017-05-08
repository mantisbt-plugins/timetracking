<?php
namespace TTMigration;

step_prepare_headers();

access_ensure_global_level( config_get_global( 'admin_site_threshold' ) );
form_security_validate( 'ttmigration_migration_step_exec' );


$f_step = gpc_get_int( 'step', -1 );
if( $f_step > 0 && $f_step <= STEPS ) {
	$t_step = $f_step;
} else {
	trigger_error( ERROR_GENERIC, ERROR );
}

$f_action = gpc_get_string( 'action', '' );
if( !in_array( $f_action, array( 'start', 'resume', 'reset' ) ) ) {
	trigger_error( ERROR_GENERIC, ERROR );
}


helper_begin_long_process();

$t_step_key = step_str_index( $t_step );

if( 'reset' == $f_action ) {
	step_return_progress( 'Starting' );
	step_reset( $t_step );
	step_return_progress( 'Finished' );
	step_return_close();
}


if( 'start' == $f_action || 'resume' == $f_action  ) {
	step_execute( $t_step );
	step_return_close();
}

form_security_purge( 'ttmigration_migration_step_exec' );