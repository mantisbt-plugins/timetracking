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

$fid = 'plugin_TimeTracking_config_update';
form_security_validate( $fid );

access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );


$config = \TimeTrackingPlugin::getConfig();
foreach($config as $opt => $default ) {

    if( is_int($default) ) {
        $fn = 'gpc_get_int';        
        if( strpos($opt, 'enabled') !== FALSE ) {
            // is a bool like 1/0
            $default = OFF;
        }
    }

    if( is_string($default) ) {
        $fn = 'gpc_get_string';        
    }
    plugin_config_set( $opt, $fn( $opt, $default ) );        
}
form_security_purge( $fid );
print_successful_redirect( plugin_page( 'config_page', true ) );
