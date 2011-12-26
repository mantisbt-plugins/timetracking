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
   require_once( 'timetracking_api.php' );    
   form_security_validate( 'plugin_TimeTracking_add_record' );

	$f_bug_id     = gpc_get_int( 'bug_id' );
   $f_time_info  = gpc_get_string( 'time_info' );
   $f_time_value = gpc_get_string( 'time_value' );
   $f_year       = gpc_get_int( 'year' );
   $f_month      = gpc_get_int( 'month' );
   $f_day        = gpc_get_int( 'day' );

   access_ensure_bug_level( plugin_config_get( 'admin_own_threshold' ), $f_bug_id );
	
   # Current UserID
   $user = auth_get_current_user_id();
   $t_time_info = db_prepare_string($f_time_info);
  
   # Work on Time-Entry so we can eval it
   $t_time_value = plugin_TimeTracking_hhmm_to_minutes($f_time_value);
   $t_time_value = doubleval($t_time_value / 60);

   # Trigger in case of non-evaluable entry
   if ( $t_time_value == 0 ) {
      trigger_error( plugin_lang_get( 'value_error' ), ERROR );
   }
   
   # Write Post-Data to DB
   $now = date("Y-m-d G:i:s");
   $expend = date("Y-m-d", strtotime("$f_year-$f_month-$f_day"));

   $table = plugin_table('data', 'TimeTracking');
   $query = "INSERT INTO $table ( user, bug_id, expenditure_date, hours, timestamp, info ) 
      VALUES ( '$user', '$f_bug_id', '$expend', '$t_time_value', '$now', '$t_time_info')";

   if(!db_query($query)){
      trigger_error( ERROR_DB_QUERY_FAILED, ERROR );
   }

   # Event is logged in the project
   history_log_event_direct( $bug_id, plugin_lang_get( 'history' ), "$f_day.$f_month.$f_year: $t_time_value h.", "set", $user );

   form_security_purge( 'plugin_TimeTracking_add_record');
   
   $t_url = string_get_bug_view_url( $f_bug_id, auth_get_current_user_id() );

   print_successful_redirect( $t_url . "#timerecord" );
	
?>
