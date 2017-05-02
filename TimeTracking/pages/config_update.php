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

form_security_validate( 'plugin_TimeTracking_config_update' );

access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

function maybe_set_option( $name, $value ) {
	if ( $value != plugin_config_get( $name ) ) {
		plugin_config_set( $name, $value );
	}
}

maybe_set_option( 'edit_threshold', gpc_get_int( 'edit_threshold' ) );
maybe_set_option( 'view_threshold', gpc_get_int( 'view_threshold' ) );
maybe_set_option( 'reporting_threshold', gpc_get_int( 'reporting_threshold' ) );
maybe_set_option( 'categories', gpc_get_string( 'categories' ) );
maybe_set_option( 'stopwatch_enabled', gpc_get_int( 'stopwatch_enabled', OFF ) );

form_security_purge( 'plugin_TimeTracking_config_update' );
print_successful_redirect( plugin_page( 'config_page', true ) );
