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
   
form_security_validate( 'plugin_TimeTracking_delete_record' );

$f_redirect_bug_id = gpc_get_int( 'bug_id' );
$f_delete_id = gpc_get_int( 'delete_id' );

$t_record = get_record_by_id( $f_delete_id );
if( !$t_record ) {
	plugin_error( ERROR_ID_NOT_EXISTS, ERROR );
}

$t_bug_id = $t_record['bug_id'];
access_ensure_bug_level( plugin_config_get( 'edit_threshold' ), $t_bug_id );

delete_record( $t_record['id'] );

form_security_purge( 'plugin_TimeTracking_delete_record');

$t_url = string_get_bug_view_url( $f_redirect_bug_id, auth_get_current_user_id() );
print_successful_redirect( $t_url . "#timerecord" );
