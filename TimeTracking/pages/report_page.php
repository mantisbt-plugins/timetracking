<?php
namespace TimeTracking;

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin();

$t_report = new Report();
$t_report->selected_keys = array( 'user', 'project', 'time_category' );
$t_report->read_gpc_params();
?>

<div class="col-md-12 col-xs-12 noprint">

	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<i class="ace-icon fa fa-filter"></i>
				<?php echo lang_get( 'filters' ) ?>
			</h4>
		</div>

		<div class="widget-body">
			<form action="<?php echo plugin_page( 'report_page' ) ?>" method="post" class="form-" role="form">
				<div class="widget-main no-padding">
					<div class="table-responsive">
						<?php $t_report->print_inputs_time_filter() ?>
					</div>
				</div>
				<div class="widget-toolbox padding-8 clearfix">
					<div class="btn-toolbar">
						<div class="btn-group">
							<input name="apply_filter_button" type="submit" class="btn btn-primary btn-sm btn-white btn-round" value="<?php echo lang_get( 'filter_button' ) ?>">
							<input name="reset_filter_button" type="submit" class="btn btn-sm btn-primary btn-white btn-round" value="<?php echo lang_get( 'reset_query' ) ?>">
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="space-10"></div>

	<div id="result" class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<i class="ace-icon fa fa-clock-o"></i>
				<?php echo plugin_lang_get( 'title' ) ?>
			</h4>
		</div>

		<div class="widget-body">
			<div class="widget-toolbox padding-8 clearfix">
				<div class="btn-toolbar pull-left">
					<form action="<?php echo plugin_page( 'report_page' ) ?>" method="post" class="form-inline" role="form">
						<?php $t_report->print_inputs_group_by() ?>
						<input type="submit" class="btn btn-primary btn-sm btn-white btn-round no-float" value="<?php echo lang_get( 'apply_filter_button' ) ?>">
					</form>
				</div>
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