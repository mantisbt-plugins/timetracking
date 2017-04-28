<?php
namespace TTMigration;

access_ensure_global_level( config_get_global( 'admin_site_threshold' ) );

?>
<table class="table table-stripped">
	<colgroup>
		<col class="col-xs-4">
		<col class="col-xs-4">
		<col class="col-xs-4">
	</colgroup>
	<tbody>
	<?php
	for( $t_step = 1; $t_step <= STEPS; $t_step++ ) {
	?>
		<tr>
			<td>
				<h4>Step <?php echo $t_step ?>:</h4>
				<h5><?php echo step_description( $t_step ) ?></h5>
				<?php echo step_status_label( $t_step ) ?>
			</td>
			<td class="step_action">
				<?php print_buttons_for_step( $t_step ) ?>
			</td>
			<td class="step_details">
				<?php echo step_details( $t_step ) ?>
			</td>
		</tr>
	<?php
	}
	?>
	</tbody>
</table>