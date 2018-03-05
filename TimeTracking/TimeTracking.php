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

        $this->name = plugin_lang_get( 'plugin_title' );
        $this->description = plugin_lang_get( 'plugin_description' );
        $this->page = 'config_page';

        $this->version = '3.0-dev-03-2018';
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
            'EVENT_VIEW_BUG_EXTRA' => 'ev_view_bug',
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

    /**
     *
     */
    function config() {
        return self::getConfig();
    }

    /**
     *
     */
    function init() {
        plugin_require_api( 'core/timetracking_api.php' );
        plugin_require_api( 'core/stopwatch_api.php' );
        plugin_require_api( 'core/columns.php' );
        plugin_require_api( 'core/reports.php' );
    }

    /**
     *
     */
    function errors() {
        return array(
            TimeTracking\ERROR_INVALID_TIME_FORMAT => plugin_lang_get( 'ERROR_INVALID_TIME_FORMAT' ),
            TimeTracking\ERROR_ID_NOT_EXISTS => plugin_lang_get( 'ERROR_ID_NOT_EXISTS' ),
        );
    }

    /**
     *
     */
    function resources() {
        $res  = '<link rel="stylesheet" type="text/css" href="'. plugin_file( 'timetracking.css' ) .'"/>';
        $res .= '<script type="text/javascript" src="'. plugin_file( 'timetracking.js' ) .'"></script>';
        $res .= '<script type="text/javascript" src="'. plugin_page( 'javascript_translations' ) .'"></script>';
        if( \TimeTracking\stopwatch_enabled() ) {
            $res .= '<script type="text/javascript" src="'. plugin_file( 'stopwatch.js' ) .'"></script>';
        }
        return $res;
    }

    /**
     *
     */ 
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

        $t_project_id = bug_get_field( $p_bug_id, 'project_id' );
        $t_enabled_on_bugnote_add_form = plugin_config_get('enabled_on_bugnote_add_form',null,false,null,$t_project_id);
        if( $t_enabled_on_bugnote_add_form ) {
            if( TimeTracking\user_can_edit_bug_id( $p_bug_id ) ) {
                TimeTracking\print_bugnote_add_form( $p_bug_id );
            }           
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
        if( TimeTracking\user_can_view_bug_id( $p_bug_id ) ) {
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

    function ev_view_bug( $p_event, $p_bug_id ) {
        if( TimeTracking\user_can_view_bug_id( $p_bug_id ) ) {
            TimeTracking\print_bug_timetracking_section( $p_bug_id );
        }
    }

    /**
     *
     */
    static function getConfig() {
        return array(
            # old thresholds
            /*
            'admin_own_threshold'   => DEVELOPER,
            'view_others_threshold' => MANAGER,
            'admin_threshold'       => ADMINISTRATOR,
            */
            # new thresholds
            'view_threshold'    => DEVELOPER,
            'edit_threshold'    => DEVELOPER,
            'reporting_threshold'   => MANAGER,

            'stopwatch_enabled' => ON,

            'categories'    => '',
            'enabled_on_bugnote_add_form'    => ON
        );

    }

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
                    "   user_id         I       UNSIGNED,
                        date_created    I       UNSIGNED NOTNULL DEFAULT '1',
                        time_count      I       UNSIGNED NOTNULL DEFAULT '0',
                        time_exp_date   I       UNSIGNED NOTNULL DEFAULT '1',
                        bugnote_id      I       UNSIGNED"
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

    function timerecord_menu( $p_event, $p_bug_id ) {
        if( TimeTracking\user_can_view_bug_id( $p_bug_id ) ) {
            $t_href = '#timerecord';
            return array( plugin_lang_get( 'timerecord_menu' ) => $t_href );
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
                'url' => plugin_page( 'report_page' ),
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
