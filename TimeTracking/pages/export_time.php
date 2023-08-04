<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
require_once( 'core.php' );
require_api( 'billing_api.php' );
require_api( 'bug_api.php' );
require_api( 'excel_api.php' );

helper_begin_long_process();

$f_plugin_project = helper_get_current_project();
$t_from = gpc_get_string('plugin_TimeTracking_tfrom_hidden');
$t_to = gpc_get_string('plugin_TimeTracking_tto_hidden');
$t_plugin_TimeTracking_stats = plugin_TimeTracking_stats_get_project_array($f_plugin_project, $t_from, $t_to);

$t_filename = excel_get_default_filename();
$t_date_format = config_get( 'normal_date_format' );

header( 'Content-Type: application/vnd.ms-excel; charset=UTF-8' );
header( 'Pragma: public' );
header( 'Content-Disposition: attachment; filename="' . urlencode( file_clean_name( $t_filename ) ) . '.xml"' ) ;

echo excel_get_header( $t_filename );
echo str_repeat('<Column ss:AutoFitWidth="1" ss:Width="110"/>', 8);
echo excel_get_start_row();
echo excel_format_column_title( lang_get( 'project_name' ) );
echo excel_format_column_title( lang_get( 'issue_id' ) );
echo excel_format_column_title( plugin_lang_get( 'user' ));
echo excel_format_column_title( plugin_lang_get( 'expenditure_date' ) );
echo excel_format_column_title( plugin_lang_get( 'hours' ));
echo excel_format_column_title( plugin_lang_get( 'category' ) );
echo excel_format_column_title( lang_get( 'timestamp' ) );
echo excel_format_column_title( plugin_lang_get( 'information' ));
echo '</Row>';

foreach( $t_plugin_TimeTracking_stats as $t_stat ) {
	echo "\n<Row>\n";
	echo excel_prepare_string( $t_stat['project_name'] );
	echo excel_prepare_string( bug_format_summary( $t_stat['bug_id'], SUMMARY_FIELD ) );
	echo excel_prepare_string( $t_stat['username'] );
	echo excel_prepare_string( date( config_get("short_date_format"), strtotime($t_stat['expenditure_date'])) );
	echo excel_prepare_string( $t_stat['hours'] );
	echo excel_prepare_string( $t_stat['category'] );
	echo excel_prepare_string( $t_stat['timestamp'] );
	echo excel_prepare_string( $t_stat['info'] );
	echo "</Row>\n";
}

echo excel_get_footer();

?>