<?php
namespace TimeTracking;

class ColumnTotalTime extends \MantisColumn {
	public $title = 'Total time spent';
	public $column = 'total_time';
	public $sortable = false;

	public function cache( array $p_bugs ) {
		if( empty( $p_bugs ) ) {
			return;
		}
		$t_ids = array();
		foreach( $p_bugs as $t_bug ) {
			$t_ids[] = $t_bug->id;
		}
		# This class is not executed in the context of a plugin, so it needs explicit push
		plugin_push_current( 'TimeTracking' );
		cache_records_bug_ids( $t_ids );
		plugin_pop_current();
	}

	public function display( \BugData $p_bug, $p_columns_target ) {
		$t_bug_id = $p_bug->id;
		plugin_push_current( 'TimeTracking' );
		if( user_can_view_bug_id( $t_bug_id ) ) {
			$t_time = get_total_time_for_bug_id( $t_bug_id );
			if( $t_time ) {
				echo seconds_to_hms( $t_time );
			}
		}
		plugin_pop_current();
	}
}

class ColumnMyTime extends \MantisColumn {
	public $title = 'My time spent';
	public $column = 'my_time';
	public $sortable = false;

	public function cache( array $p_bugs ) {
		if( empty( $p_bugs ) ) {
			return;
		}
		$t_ids = array();
		foreach( $p_bugs as $t_bug ) {
			$t_ids[] = $t_bug->id;
		}
		# This class is not executed in the context of a plugin, so it needs explicit push
		plugin_push_current( 'TimeTracking' );
		cache_records_bug_ids( $t_ids );
		plugin_pop_current();
	}

	public function display( \BugData $p_bug, $p_columns_target ) {
		$t_bug_id = $p_bug->id;
		$t_user_id = auth_get_current_user_id();
		plugin_push_current( 'TimeTracking' );
		if( user_can_view_bug_id( $t_bug_id ) ) {
			$t_time = get_total_time_for_bug_id( $t_bug_id, $t_user_id );
			if( $t_time ) {
				echo seconds_to_hms( $t_time );
			}
		}
		plugin_pop_current();
	}
}