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

	$f_option1 = gpc_get( 'option1' );
	$f_option2 = gpc_get( 'option2' );

	$t_options_array = get_key_data( 'OPTIONS' );
	$t_options_array['option1'] = $f_option1;
	$t_options_array['option2'] = $f_option2;
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
