<?php
namespace TimeTracking;

# Prevent output of HTML in the content if errors occur
define( 'DISABLE_INLINE_ERROR_REPORTING', true );

$f_action = gpc_get_string( 'action', '' );

if( !empty( $f_action ) ) {
	$t_response = null;
	switch( $f_action ) {
		case 'start':
			stopwatch_start();
			$t_response = stopwatch_get_status();
			break;
		case 'stop':
			stopwatch_stop();
			$t_response = stopwatch_get_status();
			break;
		case 'reset':
			stopwatch_reset();
			$t_response = stopwatch_get_status();
			break;
		case 'status':
			$t_response = stopwatch_get_status();
			break;
	}
	echo json_encode( $t_response );
}