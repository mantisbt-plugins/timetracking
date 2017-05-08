<?php
namespace TTMigration;

access_ensure_global_level( config_get_global( 'admin_site_threshold' ) );

$t_existing = migration_get_status_info();
if( isset( $t_existing['STATUS'] ) ) {
	plugin_error( ERROR_MIGRATION_ALREADY_EXISTS, ERROR);
}

if( !migration_tt_data_exists() ) {
	plugin_error( ERROR_MANTISTT_NO_DATA, ERROR);
}

if( migration_is_tt_enabled() ) {
	plugin_error( ERROR_MANTISTT_ENABLED, ERROR);
}

$t_save = gpc_get_int( 'save', 0 );
if( $t_save == 1 ) {
	form_security_validate( 'ttmigration_migration_create' );

	$f_op_category = gpc_get_string( 'category', null );
	if( empty( $f_op_category ) ) {
		$f_op_category = null;
	}
	$f_op_existent = gpc_get_string( 'existent', null );
	if( null === $f_op_existent ) {
		trigger_error( ERROR_EMPTY_FIELD, ERROR );
	}

	$t_options_array = get_key_data( 'OPTIONS' );
	$t_options_array['category'] = $f_op_category;
	$t_options_array['existent'] = $f_op_existent;
	set_key_data( 'OPTIONS', $t_options_array );

	step_reset( 1 );
	$t_status_array = array();
	$t_status_array['date_created'] = db_now();
	$t_status_array['last_executed'] = null;
	set_key_data( 'STATUS', $t_status_array );

	form_security_purge( 'ttmigration_migration_create' );
	print_successful_redirect( plugin_page( 'migration_steps_page', true ) );
	exit;
}

layout_page_header( 'Time Tracker Plugin Migration Tool' );
layout_admin_page_begin();

?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

	<p class="lead">Time Tracking Plugin: New Migration</p>
	<p><a href="<?php echo plugin_page( 'migration_overview_page' ) ?>">Back to Migration Overview</a></p>

	<div class="widget-box widget-color-blue2">
		<div class="widget-body">
			<div class="widget-main">
				<h4>Migration options</h4>
				<hr>
				<form method="post" action="<?php echo plugin_page( 'migration_create' ) ?>">
					<?php echo form_security_field( 'ttmigration_migration_create' ) ?>
					<input type="hidden" name="save" value="1">
					<?php print_options_inputs() ?>
					<input type="submit" class="btn btn-sm btn-inverse" value="Create">
				</form>
			</div>
		</div>
	</div>
</div>
