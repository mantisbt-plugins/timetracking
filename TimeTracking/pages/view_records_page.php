<?php
namespace TimeTracking;

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

$f_bug_id = gpc_get_int( 'bug_id', 0 );
if( 0 == $f_bug_id ) {
	$f_bug_ids = gpc_get_int_array( 'bug_ids', array() );
} else {
	$f_bug_ids = array( $f_bug_id );
}

$t_url_self = url_self();
$t_url_self = url_safe_link( $t_url_self, $_GET );

function print_details_table( $p_bug_id ) {
	global $t_url_self;
	$t_records = get_records_for_bug( $p_bug_id );
	if( $t_records ) {

		?>
		<table class="table table-striped table-bordered table-condensed table-hover">
			<thead>
				<tr>
					<th><?php echo lang_get( 'date_created' ) ?></th>
					<th><?php echo plugin_lang_get( 'user' ) ?></th>
					<th><?php echo plugin_lang_get( 'category' ) ?></th>
					<th colspan="2"><?php echo plugin_lang_get( 'time_count' ) ?></th>
					<th><?php echo plugin_lang_get( 'exp_date' ) ?></th>
					<th><?php echo lang_get( 'bugnote' ) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach( $t_records as $t_row ) {
					echo '<tr class="hover_visibility_toggle">';
					echo '<td>', Report::format_value( 'date_created', $t_row['date_created'] ), '</td>';
					echo '<td>', Report::format_value( 'user', $t_row['user_id'] ), '</td>';
					echo '<td>', Report::format_value( 'category', $t_row['category'] ), '</td>';
					echo '<td>', seconds_to_hours( $t_row['time_count'] ), '</td>';
					echo '<td>', seconds_to_hms( $t_row['time_count'] ), '</td>';
					echo '<td>', Report::format_value( 'exp_date', $t_row['time_exp_date'] ), '</td>';
					if( bugnote_exists( $t_row['bugnote_id'] ) ) {
						$t_str_note = '<a rel="bookmark" href="' . string_get_bugnote_view_url( $p_bug_id, $t_row['bugnote_id'] ) . '" class="lighter" title="' . string_shorten( bugnote_get_text( $t_row['bugnote_id'] ), 100 ) . '">';
						$t_str_note .= htmlentities( config_get_global( 'bugnote_link_tag' ) ) . bugnote_format_id( $t_row['bugnote_id'] );
						$t_str_note .= '</a>';
					} else {
						$t_str_note = '';
					}
					echo '<td>', $t_str_note, '</td>';

					# edit and delete buttons
					$t_token = form_security_token( 'plugin_TimeTracking_delete_record' );
					echo '<td>';
					echo '<div class="btn-group inline">';
					//print_link_button( plugin_page( 'edit_record_page' ) . '&id=' . $t_row['id'] . '&return=' . htmlentities( $t_url_self ), '<i class="fa fa-pencil" title="' . lang_get( 'edit_link' ) . '"></i>', 'btn-sm invisible hover_visibility_target' );
					print_form_button(
							plugin_page( 'edit_record_page' ),
							'<i class="fa fa-pencil" title="' . lang_get( 'edit_link' ) . '"></i>',
							array( 'id' => $t_row['id'], 'return' => $t_url_self ),
							OFF,
							'btn btn-primary btn-sm btn-white btn-round invisible hover_visibility_target'
							);
					print_form_button(
							plugin_page( 'delete_record' ),
							'<i class="fa fa-times" title="' . lang_get( 'delete_link' ) . '"></i>',
							array( 'id' => $t_row['id'], 'return' => $t_url_self, 'plugin_TimeTracking_delete_record_token' => $t_token ),
							OFF,
							'btn btn-primary btn-sm btn-white btn-round invisible hover_visibility_target'
							);
					echo '</div>';
					echo '</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
		<?php
	}
}

?>

<div class="col-md-12 col-xs-12 noprint">
	<div id="result" class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<i class="ace-icon fa fa-clock-o"></i>
				<?php echo plugin_lang_get( 'title' ) ?>
			</h4>
		</div>

		<div class="widget-body">
		<?php
		foreach( $f_bug_ids as $t_bug_id ) {
			?>
			<div class="widget-main">
			<h5><?php echo Report::format_value( 'issue', $t_bug_id ) ?></h5>
			</div>
			<div class="widget-main no-padding">
				<div class="table-responsive">
					<?php print_details_table( $t_bug_id ) ?>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
</div>

<?php
layout_page_end();