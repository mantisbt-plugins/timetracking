<?php
namespace TimeTracking;

/*
   Copyright 2011 Michael L. Baker

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

   Notes: Based on the Time Tracking plugin by Elmar:
   2005 by Elmar Schumacher - GAMBIT Consulting GmbH
   http://www.mantisbt.org/forums/viewtopic.php?f=4&t=589	
*/


access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
layout_page_header( plugin_lang_get( 'title' ) . ': ' . plugin_lang_get( 'configuration' ) );
layout_page_begin( 'manage_overview_page.php' );
print_manage_menu( 'manage_plugin_page.php' );
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>
	<div class="form-container" class="form-inline">
		<form action="<?php echo plugin_page( 'config_update' ) ?>" method="post">
			<div class="widget-box widget-color-blue2">
				<div class="widget-header widget-header-small">
					<h4 class="widget-title lighter">
						<i class="ace-icon fa fa-exchange"></i>
						<?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration' ) ?>
					</h4>
				</div>
			<div class="widget-body">
				<div class="widget-main no-padding">
					<div class="table-responsive">
						<fieldset>
						   <?php echo form_security_field( 'plugin_TimeTracking_config_update' ) ?>
						</fieldset>
						<table class="table table-bordered table-condensed table-striped">
							<tbody>
								<tr>
									<td class="category"><?php echo plugin_lang_get( 'view_threshold' ) ?></td>
									<td>
										<select name="view_threshold" class="input-sm">
											<?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'view_threshold' ) ) ?>
										</select>
									</td>
								</tr>
								<tr>
									<td class="category"><?php echo plugin_lang_get( 'edit_threshold' ) ?></td>
									<td><select name="edit_threshold" class="input-sm">
										<?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'edit_threshold' ) ) ?>
										</select>
									</td>
								</tr>
								<tr>
									<td class="category"><?php echo plugin_lang_get( 'reporting_threshold' ) ?></td>
									<td><select name="reporting_threshold" class="input-sm">
										<?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'reporting_threshold' ) ) ?>
										</select>
									</td>
								</tr>
								<tr>
									<td class="category"><?php echo plugin_lang_get( 'config_enable_stopwatch' ) ?></td>
									<td>
										<input type="checkbox" class="ace" name="stopwatch_enabled" value="<?php echo ON ?>" <?php check_checked( plugin_config_get( 'stopwatch_enabled' ), ON ) ?>>
										<span class="lbl"></span>
									</td>
								</tr>
								<tr>
									<td class="category"><?php echo plugin_lang_get( 'categories' ) ?></td>
									<td>
										<textarea class="form-control" id="categories" name="categories" cols="80" rows="10">
											<?php echo plugin_config_get( 'categories' ) ?>
										</textarea>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			<div class="widget-toolbox padding-8 clearfix">
				<input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo lang_get( 'update' ) ?>" />
			</div>
			</div>
			</div>

		</form>
	</div>
</div>

<?php
layout_page_end();
