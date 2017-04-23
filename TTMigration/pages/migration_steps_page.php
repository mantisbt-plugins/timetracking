<?php
namespace TTMigration;

access_ensure_global_level( config_get_global( 'admin_site_threshold' ) );

layout_page_header( 'Time Tracker Plugin Migration Tool' );
layout_admin_page_begin();

$t_steps_array = migration_get_status_info();

if ( !isset( $t_steps_array['STATUS'] ) ) {
	print_successful_redirect( plugin_page( 'migration_overview_page', true ) );
	exit;
}

?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

	<p class="lead">Time Tracking Plugin: Migration Steps</p>
	<p><a href="<?php echo plugin_page( 'migration_overview_page' ) ?>">Back to Migration Overview</a></p>

	<div class="widget-box widget-color-blue2">
		<div class="widget-body">
			<div class="widget-main">
				<h3>Migration progress</h3>

				<div id="steps_table" class="table-responsive" data-remote="<?php echo plugin_page( 'migration_step_table' ) ?>">
				</div>

			</div>
		</div>
	</div>
</div>

<?php
layout_admin_page_end();