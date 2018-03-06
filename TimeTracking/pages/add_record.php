<?php
namespace TimeTracking;

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
form_security_validate( 'plugin_TimeTracking_add_record' );

$f_from_stopwatch = gpc_get_int( 'plugin_timetracking_from_stopwatch', 0 );

$t_record = parse_gpc_time_record();
create_record( $t_record );

if(stopwatch_exists() && $f_from_stopwatch == 1 ) {
	stopwatch_reset();
}

form_security_purge( 'plugin_TimeTracking_add_record' );

$f_bug_id = gpc_get_int( 'plugin_timetracking_time_input_bug_id', 0 );
$t_url = string_get_bug_view_url( $f_bug_id, auth_get_current_user_id() );

print_successful_redirect( $t_url . "#timerecord" );
