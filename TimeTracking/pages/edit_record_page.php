<?php
namespace TimeTracking;

$f_id = gpc_get_int( 'id' );
$f_return = string_sanitize_url( gpc_get_string( 'return', '' ) );

$t_record = get_record_by_id( $f_id );
if( !$t_record ) {
	plugin_error( ERROR_ID_NOT_EXISTS, ERROR );
}

if( !user_can_view_record_id( $t_record['id'] ) ) {
	access_denied();
}

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

$t_can_edit = user_can_edit_record_id( $t_record['id'] );
?>
<div class="col-md-12 col-xs-12">

	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php echo plugin_lang_get( 'title' ) . ': ' . plugin_lang_get( 'edit_record' ) . ' (id ' . $t_record['id'] . ')'  ?>
			</h4>
		</div>

		<div class="widget-body">
			<?php if( $t_can_edit ) {
				echo '<form method="post" action="' . plugin_page( 'update_record' ) . '">';
				echo form_security_field( 'plugin_TimeTracking_update_record' );
				print_hidden_input( 'return', $f_return );
				print_hidden_input( 'id', $t_record['id'] );
				print_hidden_input( 'plugin_timetracking_time_input_bug_id', $t_record['bug_id'] );
			}
			?>
			<fieldset <?php echo $t_can_edit ? '' : 'disabled' ?>>
				<div class="widget-main no-padding">
					<div class="table-responsive">
						<table class="table table-bordered table-condensed table-striped">
							<tbody>
								<tr>
									<th class="category"><?php echo plugin_lang_get( 'user' ) ?></th>
									<td>
										<input type="text" name="plugin_timetracking_user" class="form-control input-sm" value="<?php echo user_get_name( $t_record['user_id'] ) ?>" disabled>
									</td>
								</tr>
								<tr>
									<th class="category"><?php echo plugin_lang_get( 'issue' ) ?></th>
									<td>
										<input type="text" name="plugin_timetracking_bug_id" class="form-control input-sm" value="<?php echo $t_record['bug_id'] ?>" disabled>
									</td>
								</tr>
								<tr>
									<th class="category"><?php echo plugin_lang_get( 'time_count' ) ?></th>
									<td><?php print_input_time_count( $t_record['time_count'] ) ?></td>
								</tr>
								<tr>
									<th class="category"><?php echo plugin_lang_get( 'category' ) ?></th>
									<td><?php print_input_category( $t_record['category'] ) ?></td>
								</tr>
								<tr>
									<th class="category"><?php echo plugin_lang_get( 'exp_date' ) ?></th>
									<td><?php print_input_date( 'plugin_timetracking_exp_date', $t_record['time_exp_date'] ) ?></td>
								</tr>
								<tr>
									<th class="category"><?php echo plugin_lang_get( 'bugnote_id' ) ?></th>
									<td>
										<?php
										$t_note_value = '';
										if( bugnote_exists( $t_record['bugnote_id'] ) ) {
											$t_note_value = bugnote_format_id( $t_record['bugnote_id'] );
										}
										?>
										<input type="text" name="plugin_timetracking_bugnote_id" class="form-control input-sm" value="<?php echo $t_note_value ?>">
									</td>
								</tr>
								<tr>
									<th class="category"><?php echo plugin_lang_get( 'information' ) ?></th>
									<td><?php print_input_info( $t_record['info'] ) ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<?php if( $t_can_edit ) { ?>
					<div class="widget-toolbox padding-8 clearfix">
						<input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo lang_get( 'update' ) ?>">
					</div>
				<?php } ?>
			</fieldset>
			<?php if( $t_can_edit ) {
				echo '</form>';
			}
			?>
		</div>
	</div>
</div>

<?php
layout_page_end();