<?php
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
class TimeTrackingPlugin extends MantisPlugin {

	function register() {
		plugin_require_api( 'core/constants.php' );

		$this->name = 'Time Tracking';
		$this->description = 'Time tracking plugin that supports entering date worked, time and notes. Also includes limited permissions per user.';
		$this->page = 'config_page';

		$this->version = '3.0-dev';
		$this->requires = array(
			'MantisCore' => '2.0.0'
		);

		$this->author = 'Elmar Schumacher, Michael Baker, Erwann Penet';
		$this->contact = '';
		$this->url = 'https://github.com/mantisbt-plugins/timetracking';
	}

	function hooks() {
		return array(
			'EVENT_LAYOUT_RESOURCES' => 'resources',
			'EVENT_VIEW_BUG_EXTRA' => 'view_bug_time',
			'EVENT_MENU_ISSUE'     => 'timerecord_menu',
			'EVENT_MENU_MAIN'      => 'showreport_menu',
			'EVENT_VIEW_BUGNOTE'   => 'ev_view_bugnote',
			'EVENT_BUGNOTE_ADD_FORM' => 'ev_bugnote_add_form',
			'EVENT_BUGNOTE_DATA'   => 'ev_bugnote_add_validate',
			'EVENT_BUGNOTE_ADD'    => 'ev_bugnote_added',
			'EVENT_VIEW_BUG_DETAILS' => 'ev_view_bug_details',
			'EVENT_LAYOUT_CONTENT_BEGIN' => 'ev_layout_content_begin',
			'EVENT_FILTER_COLUMNS' => 'ev_register_columns',
		);
	}

	function config() {
		return array(
			# old thresholds
			'admin_own_threshold'   => DEVELOPER,
			'view_others_threshold' => MANAGER,
			'admin_threshold'       => ADMINISTRATOR,

			# new thresholds
			'view_threshold' => DEVELOPER,
			'edit_threshold' => DEVELOPER,
			'reporting_threshold' => MANAGER,

			'stopwatch_enabled' => ON,

			'categories'       => ''
		);
	}

	function init() {
		plugin_require_api( 'core/timetracking_api.php' );
		plugin_require_api( 'core/stopwatch_api.php' );
		plugin_require_api( 'core/columns.php' );
	}

	function errors() {
		return array(
			TimeTracking\ERROR_INVALID_TIME_FORMAT => plugin_lang_get( 'ERROR_INVALID_TIME_FORMAT' ),
			TimeTracking\ERROR_ID_NOT_EXISTS => plugin_lang_get( 'ERROR_ID_NOT_EXISTS' ),
		);
	}

	function resources() {
		$res  = '<link rel="stylesheet" type="text/css" href="'. plugin_file( 'timetracking.css' ) .'"/>';
		$res .= '<script type="text/javascript" src="'. plugin_file( 'timetracking.js' ) .'"></script>';
		$res .= '<script type="text/javascript" src="'. plugin_page( 'javascript_translations' ) .'"></script>';
		if( \TimeTracking\stopwatch_enabled() ) {
			$res .= '<script type="text/javascript" src="'. plugin_file( 'stopwatch.js' ) .'"></script>';
		}
		return $res;
	}

	function ev_register_columns( $p_event ) {
		return array(
			new TimeTracking\ColumnTotalTime(),
			new TimeTracking\ColumnMyTime(),
		);
	}

	/**
	 * Show time tracking info within the bugnote activity area
	 */
	function ev_view_bugnote( $p_event, $p_bug_id, $p_note_id, $p_is_private ) {
		$t_record = TimeTracking\get_record_for_bugnote( $p_note_id );
		if( !$t_record ) {
			return;
		}
		if( TimeTracking\user_can_view_record_id( $t_record['id'] ) ) {
			TimeTracking\print_bugnote_label_row( $t_record, $p_is_private );
		}
	}

	/**
	 * Prints the time tracking inputs within the bugnote-add form
	 * @param type $p_event
	 * @param type $p_bug_id
	 */
	function ev_bugnote_add_form( $p_event, $p_bug_id ) {
		if( TimeTracking\user_can_edit_bug_id( $p_bug_id ) ) {
			TimeTracking\print_bugnote_add_form();
		}
	}

	/**
	 * Validates time tracking submitted data when adding bugnotes
	 */
	function ev_bugnote_add_validate( $p_event, $p_bugnote_text, $p_bug_id ) {
		$t_time_imput = gpc_get_string( 'plugin_timetracking_time_input', '' );
		if( !is_blank( $t_time_imput ) ) {
			if( TimeTracking\user_can_edit_bug_id( $p_bug_id ) ) {
				$t_parsed = TimeTracking\parse_gpc_time_record();
			}
		}
		return $p_bugnote_text;
	}

	/**
	 * Creates a time tracking record from submitted data when adding bugnotes
	 */
	function ev_bugnote_added( $p_event, $p_bug_id, $p_bugnote_id ) {
		$t_time_imput = gpc_get_string( 'plugin_timetracking_time_input', '' );
		if( !is_blank( $t_time_imput ) ) {
			if( TimeTracking\user_can_edit_bug_id( $p_bug_id ) ) {
				$t_record = TimeTracking\parse_gpc_time_record();
				$t_record['bugnote_id'] = $p_bugnote_id;
				$t_record['bug_id'] = $p_bug_id;
				TimeTracking\create_record( $t_record );
			}
		}
	}

	function ev_view_bug_details( $p_event, $p_bug_id ) {
		if(TimeTracking\user_can_view_bug_id( $p_bug_id ) ) {
			$t_records = TimeTracking\get_records_for_bug( $p_bug_id );
			if( $t_records ) {
				TimeTracking\print_bug_details_row( $p_bug_id );
			}
		}
	}

	function ev_layout_content_begin( $p_event ) {
		if( TimeTracking\stopwatch_enabled() && TimeTracking\stopwatch_exists() ) {
			TimeTracking\print_stopwatch_header_control();
		}
	}

	/**
	 * Show TimeTracking information when viewing bugs.
	 * @param string Event name
	 * @param int Bug ID
	 */
	function view_bug_time( $p_event, $p_bug_id ) {
		$t_table = plugin_table('data');
		$t_user_id = auth_get_current_user_id();

		# Pull all Time-Record entries for the current Bug
		if( access_has_bug_level( plugin_config_get( 'view_threshold' ), $p_bug_id ) ) {
			db_param_push();
			$t_query = 'SELECT * FROM '.$t_table.' WHERE bug_id = ' . db_param() . ' ORDER BY date_created DESC';
			$t_result_pull_timerecords = db_query( $t_query, array($p_bug_id) );
		} else {
			// User has no access
			return;
		}

		//$result_pull_timerecords = db_query( $query_pull_timerecords );
		$t_num_timerecords = db_num_rows( $t_result_pull_timerecords );

		# Get Sum for this bug
		db_param_push();
		$t_query_pull_hours = 'SELECT SUM(time_count) as time_count FROM '.$t_table.' WHERE bug_id = '.db_param();
		$t_result_pull_hours = db_query( $t_query_pull_hours, array($p_bug_id) );
		$t_row_pull_hours = db_fetch_array( $t_result_pull_hours );

		$t_collapse_block = is_collapsed( 'timerecord' );
		$t_block_css = $t_collapse_block ? 'collapsed' : '';
		$t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';
?>

<div class="col-md-12 col-xs-12 noprint">
<a id="timerecord"></a>
<div class="space-10"></div>

	<div id="timerecord_add" class="widget-box widget-color-blue2 <?php echo $t_block_css ?>">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<i class="ace-icon fa fa-clock-o"></i>
				<?php echo plugin_lang_get( 'title' ) ?>
			</h4>
			<div class="widget-toolbar">
				<a data-action="collapse" href="#">
					<i class="1 ace-icon fa <?php echo $t_block_icon ?> bigger-125"></i>
				</a>
			</div>
		</div>

		<div class="widget-body">
		<div class="widget-main no-padding">



<?php
	if( TimeTracking\user_can_edit_bug_id( $p_bug_id ) ) {
	?>
	<div class="widget-toolbox padding-8 clearfix">
		<form name="time_tracking" method="post" action="<?php echo plugin_page('add_record') ?>" >
			<?php echo form_security_field( 'plugin_TimeTracking_add_record' ) ?>
			<input type="hidden" name="bug_id" value="<?php echo $p_bug_id; ?>"/>
				<?php TimeTracking\print_timetracking_inputs() ?>
				<input name="submit" class="btn btn-primary btn-white btn-round" type="submit" value="<?php echo plugin_lang_get( 'submit' ) ?>">
		</form>

	</div>
	<?php
	}
	?>
   <div class="table-responsive">
   <table class="table table-bordered table-condensed table-hover table-striped">
   <thead>
   <tr>
      <th class="small-caption"><?php echo plugin_lang_get( 'user' ); ?></th>
      <th class="small-caption"><?php echo plugin_lang_get( 'expenditure_date' ); ?></th>
      <th class="small-caption"><?php echo plugin_lang_get( 'hours' ); ?></th>
      <th class="small-caption"><?php echo plugin_lang_get( 'category' ); ?></th>
      <th class="small-caption"><?php echo plugin_lang_get( 'information' ); ?></th>
      <th class="small-caption"><?php echo plugin_lang_get( 'entry_date' ); ?></th>
      <th class="small-caption">&nbsp;</th>
   </tr>
   </thead>


<?php

		for ( $i=0; $i < $t_num_timerecords; $i++ ) {
			$t_row = db_fetch_array( $t_result_pull_timerecords );
?>


   <tbody>
   <tr>
      <td class="small-caption"><?php echo user_get_name($t_row["user_id"]); ?></td>
      <td class="small-caption"><?php echo date( config_get("short_date_format"), $t_row["time_exp_date"] ); ?> </td>
      <td class="small-caption"><?php echo TimeTracking\seconds_to_hhmm($t_row["time_count"]) ?> </td>
      <td class="small-caption"><?php echo string_display_links($t_row["category"]); ?></td>
      <td class="small-caption"><?php echo string_display_links($t_row["info"]); ?></td>
      <td class="small-caption"><?php echo date( config_get("complete_date_format"), $t_row["date_created"] ); ?> </td>

<?php
			if( access_has_bug_level( plugin_config_get( 'edit_threshold' ), $p_bug_id) ) {
?>


      <td class="small-caption"><a href="<?php echo plugin_page('delete_record') ?>&bug_id=<?php echo $p_bug_id; ?>&delete_id=<?php echo $t_row["id"]; ?><?php echo form_security_param( 'plugin_TimeTracking_delete_record' ) ?>"><?php echo plugin_lang_get( 'delete' ) ?>
</a></td>

<?php
			}
			else {
?>
      <td class="small-caption">&nbsp;</td>

<?php
			}
?>
   </tr>


<?php
		} # End for loop
?>


   </tbody>
   <tfoot>
   <tr class="row-category">
      <td class="small-caption"><?php echo plugin_lang_get( 'sum' ) ?></td>
      <td class="small-caption">&nbsp;</td>
      <td class="small-caption"><div align="right"><b><?php echo TimeTracking\seconds_to_hhmm( $t_row_pull_hours['time_count'] ); ?></b></div></td>
      <td class="small-caption">&nbsp;</td>
      <td class="small-caption">&nbsp;</td>
      <td class="small-caption">&nbsp;</td>
   </tr>
   </tfoot>
</table>
   </div>

</div>
</div>
</div>


</div>

<?php
	} # function end

	function schema() {
		$schema[0] =
			array( 'CreateTableSQL', array( plugin_table( 'data' ), "
				id                 I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				bug_id             I       DEFAULT NULL UNSIGNED,
				user               I       DEFAULT NULL UNSIGNED,
				expenditure_date   T       DEFAULT NULL,
				hours              F(15,3) DEFAULT NULL,
				timestamp          T       DEFAULT NULL,
				category           C(255)  DEFAULT NULL,
				info               C(255)  DEFAULT NULL
				" )
		);

		$schema[1] =
			array(
				'AddColumnSQL',
				array( plugin_table( 'data' ),
					"	user_id			I       UNSIGNED,
						date_created	I		UNSIGNED NOTNULL DEFAULT '1',
						time_count		I		UNSIGNED NOTNULL DEFAULT '0',
						time_exp_date	I		UNSIGNED NOTNULL DEFAULT '1',
						bugnote_id		I		UNSIGNED"
					)
				);

		$schema[2] = array( 'UpdateFunction', 'date_migrate', array( plugin_table( 'data' ), 'id', 'expenditure_date', 'time_exp_date' ) );
		$schema[3] = array( 'UpdateFunction', 'date_migrate', array( plugin_table( 'data' ), 'id', 'timestamp', 'date_created' ) );
		$schema[4] = array( 'UpdateFunction', 'timetracking_update_hours', array() );
		$schema[5] = array( 'UpdateFunction', 'timetracking_update_user_id', array() );

		$schema[6] = array( 'DropColumnSQL', array( plugin_table( 'data' ), 'user' ) );
		$schema[7] = array( 'DropColumnSQL', array( plugin_table( 'data' ), 'expenditure_date' ) );
		$schema[8] = array( 'DropColumnSQL', array( plugin_table( 'data' ), 'timestamp' ) );
		$schema[9] = array( 'DropColumnSQL', array( plugin_table( 'data' ), 'hours' ) );

		return $schema;
	}

	function timerecord_menu() {
		$bugid =  gpc_get_int( 'id' );
		if( access_has_bug_level( plugin_config_get( 'edit_threshold' ), $bugid )
		 || access_has_bug_level( plugin_config_get( 'view_threshold' ), $bugid ) ) {
			$import_page = 'view.php?';
			$import_page .= 'id=';
			$import_page .= $bugid ;
			$import_page .= '#timerecord';

			return array( plugin_lang_get( 'timerecord_menu' ) => $import_page);
		}
		else {
			return array ();
		}
	}

	function showreport_menu() {
		return array(
			array(
				'title' => plugin_lang_get( 'title' ),
				'access_level' => plugin_config_get( 'reporting_threshold' ),
				'url' => plugin_page( 'show_report' ),
				'icon' => 'fa-random'
			)
		);
	}


} # class end

function install_timetracking_update_hours() {
	$t_query = 'UPDATE ' . plugin_table( 'data' ) . ' SET time_count = hours*3600'
			. ' WHERE time_count = 0';
	db_query( $t_query );

	# Return 2 because that's what ADOdb/DataDict does when things happen properly
	return 2;
}

function install_timetracking_update_user_id() {
	$t_query = 'UPDATE ' . plugin_table( 'data' ) . ' SET user_id = user'
			. ' WHERE user_id IS NULL';
	db_query( $t_query );

	# Return 2 because that's what ADOdb/DataDict does when things happen properly
	return 2;
}
