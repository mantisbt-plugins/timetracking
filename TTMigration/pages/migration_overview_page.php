<?php
namespace TTMigration;

access_ensure_global_level( config_get_global( 'admin_site_threshold' ) );
layout_page_header( 'Time Tracker Plugin Migration Tool' );

layout_admin_page_begin();

$t_status_info_array = migration_get_status_info();
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

	<p class="lead">Time Tracking Plugin: Migration Overview</p>

	<div class="widget-box widget-color-blue2">
		<div class="widget-body">
			<div class="widget-main">
				<?php

				# UI for creating a new migration

				# A migration job does not exists
				if ( !isset( $t_status_info_array['STATUS'] ) ) {
					?>
					<?php
					$t_can_migrate = true;

					# Check if there is data to migrate
					if( !migration_tt_data_exists() ) {
						$t_can_migrate = false;
						?>
						<p>
							<span class="dependency_dated"><i class="fa fa-exclamation-circle fa-2x"></i></span>
							<span class="dependency_dated">
								There is no time-tracking data in Mantis to migrate.
							</span>
						</p>
						<?php
					} else {
						?>
						<p>
							<span class="dependency_met"><i class="fa fa-check fa-2x"></i></span>
							<span class="dependency_met">
								There is time-tracking data in Mantis that can be migrated.
							</span>
						</p>
						<?php
					}

					# Check if mantis time tracking is disabled
					if( $t_can_migrate && migration_is_tt_enabled() ) {
						$t_can_migrate = false;
						?>
						<p>
							<span class="dependency_dated"><i class="fa fa-exclamation-circle fa-2x"></i></span>
							<span class="dependency_dated">
								Configuration option "time_tracking_enabled" must be disabled globally and for any project.
							</span>
						</p>
						<?php
					} else {
						?>
						<p>
							<span class="dependency_met"><i class="fa fa-check fa-2x"></i></span>
							<span class="dependency_met">
								Mantis time-tracking is disabled globally and for any project.
							</span>
						</p>
						<?php
					}

					echo '<hr>';

					if( $t_can_migrate ) {
						echo '<a href="' . plugin_page( 'migration_create' ) . '" class="btn btn-sm btn-inverse">' . 'Create new migration job' . '</a>';
					} else {
						?>
						<p>
							<span class="alert-warning">
								A new migration can't be started until all tests are passed.
							</span>
						</p>
						<?php
					}
				}

				# A migration job already exists
				if ( isset( $t_status_info_array['STATUS'] ) ) {
					$t_status_array = $t_status_info_array['STATUS'];

					if( isset( $t_status_array['date_created'] ) ) {
						$t_str_date_created = date( config_get("normal_date_format"), $t_status_array['date_created'] );
					} else {
						$t_str_date_created = '--';
					}
					if( isset( $t_status_array['last_executed'] ) ) {
						$t_str_last_executed = date( config_get("normal_date_format"), $t_status_array['last_executed'] );
					} else {
						$t_str_last_executed = '--';
					}
					if( isset( $t_status_array['completed'] ) && $t_status_array['completed'] == 1 ) {
						$t_completed = true;
						$t_str_date_completed = date( config_get("normal_date_format"), $t_status_array['date_completed'] );
						$t_legend = 'Migration completed';
					} else {
						$t_str_date_completed = '--';
						$t_completed = false;
						$t_legend = 'Migration in progress';
					}
					?>
					<h4><?php echo $t_legend ?></h4>
					<hr>
					<p>
						<span><strong>Date created:</strong></span>
						<span><?php echo $t_str_date_created ?></span>
					</p>
					<p>
						<span><strong>Date of last execution:</strong></span>
						<span><?php echo $t_str_last_executed ?></span>
					</p>
					<p>
						<span><strong>Date of completion:</strong></span>
						<span><?php echo $t_str_date_completed ?></span>
					</p>

					<hr>
					<?php
					if( !$t_completed ) {
						?>
						<p><a href="<?php echo plugin_page( 'migration_steps_page' ) ?>" class="btn btn-sm btn-inverse">Details / Resume execution</a></p>
						<?php
					}
					$t_token = form_security_token( 'ttmigration_migration_reset' );
					$t_params = array( 'ttmigration_migration_reset_token' => $t_token );
					echo '<p>';
					print_form_button( plugin_page( 'migration_reset' ), 'Delete this migration job', $t_params, OFF, 'btn btn-sm btn-link' );
					echo '</p>';
				}
				?>
			</div>
		</div>
	</div>
</div>

<?php
layout_admin_page_end();
