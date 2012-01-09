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
		$this->name = 'Time Tracking';
		$this->description = 'Time tracking plugin that supports entering date worked, time and notes. Also includes limited permissions per user.';
		$this->page = 'config_page';

		$this->version = '1.0.5';
		$this->requires = array(
			'MantisCore' => '1.2.0'
		);

		$this->author = 'Michael Baker';
		$this->contact = 'mykbaker@gmail.com';
		$this->url = '';
	}

	function hooks() {
		return array(
			'EVENT_VIEW_BUG_EXTRA' => 'view_bug_time',
			'EVENT_MENU_ISSUE'     => 'timerecord_menu',
			'EVENT_MENU_MAIN'      => 'showreport_menu',
		);
	}

	function config() {
		return array(
			'admin_own_threshold'   => DEVELOPER,
			'view_others_threshold' => MANAGER,
			'admin_threshold'       => ADMINISTRATOR
		);
	}

	function init() {
		$t_path = config_get_global('plugin_path' ). plugin_get_current() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;
		set_include_path(get_include_path() . PATH_SEPARATOR . $t_path);
	}


	/**
	 * Show TimeTracking information when viewing bugs.
	 * @param string Event name
	 * @param int Bug ID
	 */
	function view_bug_time( $p_event, $p_bug_id ) {
		$table = plugin_table('data');
		$t_user_id = auth_get_current_user_id();

		# Pull all Time-Record entries for the current Bug
		if( access_has_bug_level( plugin_config_get( 'view_others_threshold' ), $p_bug_id ) ) {
			$query_pull_timerecords = "SELECT * FROM $table WHERE bug_id = $p_bug_id ORDER BY timestamp DESC";
		} else if( access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id ) ) {
			$query_pull_timerecords = "SELECT * FROM $table WHERE bug_id = $p_bug_id and user = $t_user_id ORDER BY timestamp DESC";
		} else {
			// User has no access
			return;
		}

		$result_pull_timerecords = db_query( $query_pull_timerecords );
		$num_timerecords = db_num_rows( $result_pull_timerecords );

		# Get Sum for this bug
		$query_pull_hours = "SELECT SUM(hours) as hours FROM $table WHERE bug_id = $p_bug_id";
		$result_pull_hours = db_query( $query_pull_hours );
		$row_pull_hours = db_fetch_array( $result_pull_hours );

?>


   <a name="timerecord" id="timerecord" /><br />

<?php
		collapse_open( 'timerecord' );
?>
   <table class="width100" cellspacing="1">
   <tr>
      <td colspan="6" class="form-title">
<?php
		collapse_icon( 'timerecord' );
		echo plugin_lang_get( 'title' );
?>
      </td>
   </tr>
   <tr class="row-category">
      <td><div align="center"><?php echo plugin_lang_get( 'user' ); ?></div></td>
      <td><div align="center"><?php echo plugin_lang_get( 'expenditure_date' ); ?></div></td>
      <td><div align="center"><?php echo plugin_lang_get( 'hours' ); ?></div></td>
      <td><div align="center"><?php echo plugin_lang_get( 'information' ); ?></div></td>
      <td><div align="center"><?php echo plugin_lang_get( 'entry_date' ); ?></div></td>
      <td>&nbsp;</td>
   </tr>


<?php
		if ( access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id ) ) {
			$current_date = explode("-", date("Y-m-d"));
?>


   <form name="time_tracking" method="post" action="<?php echo plugin_page('add_record') ?>" >
      <?php echo form_security_field( 'plugin_TimeTracking_add_record' ) ?>

      <input type="hidden" name="bug_id" value="<?php echo $p_bug_id; ?>">

   <tr <?php echo helper_alternate_class() ?>>
     <td><?php echo user_get_name( auth_get_current_user_id() ) ?></td>
     <td nowrap>
        <div align="center">
           <select tabindex="5" name="day"><?php print_day_option_list( $current_date[2] ) ?></select>
           <select tabindex="6" name="month"><?php print_month_option_list( $current_date[1] ) ?></select>
           <select tabindex="7" name="year"><?php print_year_option_list( $current_date[0] ) ?></select>
        </div>
     </td>
     <td><div align="right"><input type="text" name="time_value" value="00:00" size="5"></div></td>
     <td><div align="center"><input type="text" name="time_info"></div></td>
     <td>&nbsp;</td>
     <td><input name="<?php echo plugin_lang_get( 'submit' ) ?>" type="submit" value="<?php echo plugin_lang_get( 'submit' ) ?>"></td>
   </tr>
</form>

<?php
		} # END Access Control

		for ( $i=0; $i < $num_timerecords; $i++ ) {
			$row = db_fetch_array( $result_pull_timerecords );
?>


   <tr <?php echo helper_alternate_class() ?>>
      <td><?php echo user_get_name($row["user"]); ?></td>
      <td><div align="center"><?php echo date( config_get("short_date_format"), strtotime($row["expenditure_date"])); ?> </div></td>
      <td><div align="right"><?php echo db_minutes_to_hhmm($row["hours"] * 60) ?> </div></td>
      <td><div align="center"><?php echo string_display_links($row["info"]); ?></div></td>
      <td><div align="center"><?php echo date( config_get("complete_date_format"), strtotime($row["timestamp"])); ?> </div></td>

<?php
			$user = auth_get_current_user_id();
			if( ($user == $row["user"] && access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $p_bug_id) )
			 || access_has_bug_level( plugin_config_get( 'admin_threshold' ), $p_bug_id) ) {
?>


      <td><a href="<?php echo plugin_page('delete_record') ?>&bug_id=<?php echo $p_bug_id; ?>&delete_id=<?php echo $row["id"]; ?><?php echo form_security_param( 'plugin_TimeTracking_delete_record' ) ?>"><?php echo plugin_lang_get( 'delete' ) ?>
</a></td>

<?php
			}
			else {
?>
      <td>&nbsp;</td>

<?php
			}
?>
   </tr>


<?php
		} # End for loop
?>


   <tr class="row-category">
      <td><?php echo plugin_lang_get( 'sum' ) ?></td>
      <td>&nbsp;</td>
      <td><div align="right"><b><?php echo db_minutes_to_hhmm($row_pull_hours['hours']* 60); ?></b></div></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
   </tr>
</table>

<?php
		collapse_closed( 'timerecord' );
?>

<table class="width100" cellspacing="1">
<tr>
   <td class="form-title" colspan="2">
          <?php collapse_icon( 'timerecord' ); ?>
          <?php echo plugin_lang_get( 'title' ); ?>
	</td>
</tr>
</table>

<?php
		collapse_end( 'timerecord' );

	} # function end

	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'data' ), "
				id                 I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				bug_id             I       DEFAULT NULL UNSIGNED,
				user               I       DEFAULT NULL UNSIGNED,
				expenditure_date   T       DEFAULT NULL,
				hours              F(15,3) DEFAULT NULL,
				timestamp          T       DEFAULT NULL,
				info               C(255)  DEFAULT NULL
				" )
			),
		);
	}

	function timerecord_menu() {
		$bugid =  gpc_get_int( 'id' );
		if( access_has_bug_level( plugin_config_get( 'admin_own_threshold' ), $bugid )
		 || access_has_bug_level( plugin_config_get( 'view_others_threshold' ), $bugid ) ) {
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
		if ( access_has_global_level( plugin_config_get( 'admin_own_threshold' ) ) ){
			return array( '<a href="' . plugin_page( 'show_report' ) . '">' . plugin_lang_get( 'title' ) . '</a>', );
		}
		else {
			return array ('');
		}
	}


} # class end
?>
