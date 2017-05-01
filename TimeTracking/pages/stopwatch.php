<?php
namespace TimeTracking;

# Prevent output of HTML in the content if errors occur
define( 'DISABLE_INLINE_ERROR_REPORTING', true );

$f_getui = gpc_isset( 'getui' );

//<div class="col-xs-12 alert alert-warning text-center">

if( $f_getui ) {
	?>
		<span class="stopwatch_time_display"></span>
		<button class="stopwatch_btn_start btn btn-primary btn-sm btn-white btn-round"></button>
		<button class="stopwatch_btn_reset btn btn-primary btn-sm btn-white btn-round"></button>
	<?php
}

//echo json_encode( $t_response );
