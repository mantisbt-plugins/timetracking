<a name="bugnotestats" id="bugnotestats" />
<?php 
require_once( 'core.php' ); 
require_once( 'bug_api.php' );
require_once( 'timetracking_api.php' ); 
html_page_top( plugin_lang_get( 'title' ) ); 
$t_today = date( "d:m:Y" );
$t_date_submitted = isset( $t_bug ) ? date( "d:m:Y", $t_bug->date_submitted ) : $t_today;
$t_plugin_TimeTracking_stats_from_def = $t_date_submitted;
$t_plugin_TimeTracking_stats_from_def_ar = explode ( ":", $t_plugin_TimeTracking_stats_from_def );
$t_plugin_TimeTracking_stats_from_def_d = $t_plugin_TimeTracking_stats_from_def_ar[0];
$t_plugin_TimeTracking_stats_from_def_m = $t_plugin_TimeTracking_stats_from_def_ar[1];
$t_plugin_TimeTracking_stats_from_def_y = $t_plugin_TimeTracking_stats_from_def_ar[2];
$t_plugin_TimeTracking_stats_from_d = gpc_get_int('start_day', $t_plugin_TimeTracking_stats_from_def_d);
$t_plugin_TimeTracking_stats_from_m = gpc_get_int('start_month', $t_plugin_TimeTracking_stats_from_def_m);
$t_plugin_TimeTracking_stats_from_y = gpc_get_int('start_year', $t_plugin_TimeTracking_stats_from_def_y);
$t_plugin_TimeTracking_stats_to_def = $t_today;
$t_plugin_TimeTracking_stats_to_def_ar = explode ( ":", $t_plugin_TimeTracking_stats_to_def );
$t_plugin_TimeTracking_stats_to_def_d = $t_plugin_TimeTracking_stats_to_def_ar[0];
$t_plugin_TimeTracking_stats_to_def_m = $t_plugin_TimeTracking_stats_to_def_ar[1];
$t_plugin_TimeTracking_stats_to_def_y = $t_plugin_TimeTracking_stats_to_def_ar[2];
$t_plugin_TimeTracking_stats_to_d = gpc_get_int('end_day', $t_plugin_TimeTracking_stats_to_def_d);
$t_plugin_TimeTracking_stats_to_m = gpc_get_int('end_month', $t_plugin_TimeTracking_stats_to_def_m);
$t_plugin_TimeTracking_stats_to_y = gpc_get_int('end_year', $t_plugin_TimeTracking_stats_to_def_y);
$f_plugin_TimeTracking_stats_button = gpc_get_string('plugin_TimeTracking_stats_button', '');
$f_project_id = helper_get_current_project();
?> 
<form method="post" action="<?php echo plugin_page( 'show_report' )?>">
<?php collapse_open( 'bugnotestats' ); ?>
<table border="1" class="width100" cellspacing="0">
<tr>
<td class="form-title" colspan="4">
<?php
collapse_icon( 'bugnotestats' );
echo plugin_lang_get( 'title' )
?>
</td>
</tr>
<tr class="row-2">
<td class="category" width="25%">
<?php
$t_filter = array();
$t_filter['do_filter_by_date'] = 'on';
$t_filter['start_day'] = $t_plugin_TimeTracking_stats_from_d;
$t_filter['start_month'] = $t_plugin_TimeTracking_stats_from_m;
$t_filter['start_year'] = $t_plugin_TimeTracking_stats_from_y;
$t_filter['end_day'] = $t_plugin_TimeTracking_stats_to_d;
$t_filter['end_month'] = $t_plugin_TimeTracking_stats_to_m;
$t_filter['end_year'] = $t_plugin_TimeTracking_stats_to_y;
print_filter_do_filter_by_date(true);
?>
</td>
</tr>
<tr>
<td>
<input type="submit" class="button"
name="plugin_TimeTracking_stats_button"
value="<?php echo plugin_lang_get( 'get_info' ) ?>"  />
</td>
</tr>
</table>
<?php
collapse_closed( 'bugnotestats' ); ?>
<table class="width100" cellspacing="0">
<tr>
<td class="form-title" colspan="4">
<?php
collapse_icon( 'bugnotestats' );
echo plugin_lang_get( 'title' )
?>
</td>
</tr>
</table>
<?php 
collapse_end( 'bugnotestats' ); 

if ( !is_blank( $f_plugin_TimeTracking_stats_button ) ) {
# Retrieve time tracking information
$t_from = "$t_plugin_TimeTracking_stats_from_y-$t_plugin_TimeTracking_stats_from_m-$t_plugin_TimeTracking_stats_from_d";
$t_to = "$t_plugin_TimeTracking_stats_to_y-$t_plugin_TimeTracking_stats_to_m-$t_plugin_TimeTracking_stats_to_d";
$t_plugin_TimeTracking_stats = plugin_TimeTracking_stats_get_project_array( $f_project_id, $t_from, $t_to);
//$t_sort_bug = $t_sort_name = array();
//array_multisort( $t_sort_bug, SORT_NUMERIC, $t_sort_name, $t_plugin_TimeTracking_stats );
//unset( $t_sort_bug, $t_sort_name );
?>
<br />
<table border="1" class="width100" cellspacing="0">
<tr class="row-category-history">
<td class="small-caption">
<?php echo plugin_lang_get( 'user' ) ?>
</td>
<td class="small-caption">
<?php echo plugin_lang_get( 'expenditure_date' ) ?>
</td>
<td class="small-caption">
<?php echo lang_get( 'issue_id' ) ?>
</td>
<td class="small-caption">
<?php echo plugin_lang_get( 'hours' ) ?>
</td>
<td class="small-caption">
<?php echo plugin_lang_get( 'information' ) ?>
</td>
</tr>
<?php
$t_sum_in_hours = 0;
$t_user_summary = array();
$t_bug_summary = array();
# Initialize the user summary array
foreach ( $t_plugin_TimeTracking_stats as $t_item ) {
$t_user_summary[$t_item['username']] = 0;
$t_bug_summary[$t_item['bug_id']] = 0;
}
foreach ( $t_plugin_TimeTracking_stats as $t_key => $t_item ) {
$t_sum_in_hours += $t_item['hours'];
$t_user_summary[$t_item['username']] += $t_item['hours'];
$t_bug_summary[$t_item['bug_id']] += $t_item['hours'];
?>
<tr <?php echo helper_alternate_class() ?>>
<td class="small-caption">
<?php echo $t_item['username'] ?>
</td>
<td class="small-caption">
<?php echo date( config_get("short_date_format"), strtotime($t_item['expenditure_date'])) ?>
</td>
<td class="small-caption">
<?php echo bug_format_summary( $t_item['bug_id'], SUMMARY_FIELD ) ?>
</td>
<td class="small-caption">
<?php echo number_format($t_item['hours'], 2, '.', ',') ?>
</td>
<td class="small-caption">
<?php echo $t_item['info'] ?>
</td>
</tr>
<?php } ?>

<tr <?php echo helper_alternate_class() ?>>
<td class="small-caption">
<?php echo lang_get( 'total_time' ); ?>
</td>
<td></td><td></td><td class="small-caption">
<?php echo number_format($t_sum_in_hours, 2, '.', ','); ?> (<?php echo db_minutes_to_hhmm( $t_sum_in_hours * 60); ?>)
</td><td></td>
</tr>

</table>

<BR>
<table border="1" class="width100" cellspacing="0">
<tr class="row-category-history">
<td class="small-caption">
<?php echo plugin_lang_get( 'user' ) ?>
</td>
<td class="small-caption">
<?php echo plugin_lang_get( 'hours' ) ?>
</td>
</tr>

<?php foreach ( $t_user_summary as $t_user_key => $t_user_value ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td class="small-caption">
<?php echo lang_get( 'total_time' ); ?>(<?php echo $t_user_key; ?>)
</td>
<td class="small-caption">
<?php echo number_format($t_user_value, 2, '.', ','); ?> (<?php echo db_minutes_to_hhmm( $t_user_value * 60); ?>)
</td>
</tr>
<?php } ?>
</table>

<BR>
<table border="1" class="width100" cellspacing="0">
<tr class="row-category-history">
<td class="small-caption">
<?php echo lang_get( 'issue_id' ) ?>
</td>
<td class="small-caption">
<?php echo plugin_lang_get( 'hours' ) ?>
</td>
</tr>
<?php foreach ( $t_bug_summary as $t_bug_key => $t_bug_value ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td class="small-caption">
<?php echo lang_get( 'total_time' ); ?>(<?php echo bug_format_id( $t_bug_key ); ?>)
</td>
<td class="small-caption">
<?php echo number_format($t_bug_value, 2, '.', ','); ?> (<?php echo db_minutes_to_hhmm( $t_bug_value * 60); ?>)
</td>
</tr>
<?php } ?>

</table>

<?php } ?>

</form>

<?php
html_page_bottom();
?>
