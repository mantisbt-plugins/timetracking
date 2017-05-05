<?php
namespace TimeTracking;

class ColumnTotalTime extends \MantisColumn {
	public $title = 'Total time spent';
	public $column = 'total_time';
	public $sortable = true;

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

	public function sortquery( $p_direction ) {
		/**
		 * sorting need that the final value exists at query time, so the total times
		 * must be calculated in the query.
		 * For each project, access_levels and configured threshold may be different,
		 * so first a list of projects where the user has permissions is built.
		 * This list is used to calculate times only for bugs in those projects
		 */
		$t_accessible_projects = user_get_all_accessible_projects();
		$t_valid_projects = array();
		plugin_push_current( 'TimeTracking' );
		foreach( $t_accessible_projects as $t_project_id ) {
			if( user_can_view_project_id( $t_project_id ) ) {
				$t_valid_projects[] = (int)$t_project_id;
			}
		}
		plugin_pop_current();
		if( empty( $t_valid_projects ) ) {
			return;
		}
		$t_table = plugin_table( 'data', 'TimeTracking' );
		$t_map_nulls = ( $p_direction == 'ASC' ) ? DB_MAX_INT : 0;
		/**
		 * Sorting requires an outer join, otherwise, the query would not return all bugs!
		 * We cannot reference bug_table from within the join, so the bug_table is used inside
		 * the join subquery, to match the allowed projects previously generated
		 *
		 * Null values appear when bugs have no time calculation available (by not having records,
		 * or dont have access). To avoid them appearing as first result in any ordering, nulls
		 * are mapped (coalesce) to the opposite end of the sort direction.
		 *
		 * Notes:
		 * - we cannot use db_params within the filter join clause (it's not supported)
		 * - table aliases are used to avoid name conflicts in the final query
		 */
		$t_join_clause =
			'LEFT OUTER JOIN ( SELECT TTB1.id, SUM(time_count) AS tt_total_time'
			. ' FROM {bug} TTB1'
			. ' JOIN ' . $t_table . ' TTP1 ON TTB1.id = TTP1.bug_id'
			. ' WHERE TTB1.project_id IN (' . implode( ',', $t_valid_projects ) . ')'
			. ' GROUP BY TTB1.id ) TTS1'
			. ' ON {bug}.id = TTS1.id';
		$t_order_clause = 'COALESCE( TTS1.tt_total_time, ' . $t_map_nulls . ' ) ' . $p_direction;
		return array(
			'join' => $t_join_clause,
			'order' => $t_order_clause
		);
	}
}

class ColumnMyTime extends \MantisColumn {
	public $title = 'My time spent';
	public $column = 'my_time';
	public $sortable = true;

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

	public function sortquery( $p_direction ) {
		$t_user_id = auth_get_current_user_id();
		$t_accessible_projects = user_get_all_accessible_projects();
		$t_valid_projects = array();
		plugin_push_current( 'TimeTracking' );
		foreach( $t_accessible_projects as $t_project_id ) {
			if( user_can_view_project_id( $t_project_id ) ) {
				$t_valid_projects[] = (int)$t_project_id;
			}
		}
		plugin_pop_current();
		if( empty( $t_valid_projects ) ) {
			return;
		}
		$t_table = plugin_table( 'data', 'TimeTracking' );
		$t_map_nulls = ( $p_direction == 'ASC' ) ? DB_MAX_INT : 0;
		/**
		 * This is the same query as total-time, but adding restriction by user id
		 */
		$t_join_clause =
			'LEFT OUTER JOIN ( SELECT TTB2.id, SUM(time_count) AS tt_total_time'
			. ' FROM {bug} TTB2'
			. ' JOIN ' . $t_table . ' TTP2 ON TTB2.id = TTP2.bug_id'
			. ' WHERE TTP2.user_id = ' . $t_user_id
			. ' AND TTB2.project_id IN (' . implode( ',', $t_valid_projects ) . ')'
			. ' GROUP BY TTB2.id) TTS2'
			. ' ON {bug}.id = TTS2.id';

		$t_order_clause = 'COALESCE( TTS2.tt_total_time, ' . $t_map_nulls . ') ' . $p_direction;
		return array(
			'join' => $t_join_clause,
			'order' => $t_order_clause
		);
	}
}