<?php
namespace TimeTracking;

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

$t_report = new Report();
$t_report->selected_keys = array( 'user', 'project', 'time_category' );
$t_report->read_gpc_params();
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
			<div class="widget-toolbox padding-8 clearfix">
				<form action="<?php echo plugin_page( 'report_page' ) ?>" method="post" class="form-" role="form">
					<?php $t_report->print_inputs_time_filter() ?>
					<?php $t_report->print_inputs_group_by() ?>
					<div class="clearfix"></div>
					<div class="btn-toolbar">
						<input type="submit" class="btn btn-primary btn-sm btn-white btn-round no-float">
					</div>
				</form>
			</div>
			<div class="widget-toolbox padding-8 clearfix">
				<?php $t_report->print_report_pagination() ?>
			</div>
			<div class="widget-main no-padding">
				<div class="table-responsive">
				<?php $t_report->print_table() ?>
				</div>
			</div>
			<div class="widget-toolbox padding-8 clearfix">
				<?php $t_report->print_report_pagination() ?>
			</div>
		</div>
	</div>
</div>


<?php
layout_page_end();