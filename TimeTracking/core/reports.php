<?php
namespace TimeTracking;

class Report {
	static $column_keys = array(
		'user', 'issue', 'project', 'time_category',
	);

	static $default_keys = array(
		'user', 'time_category',
	);

	# using alias:
	# 'TT' as plugin data table
	static $column_db_fileds = array(
		'user' => '{user}.username',
		'issue' => 'TT.bug_id',
		'project' => '{project}.name',
		'time_category' => 'TT.category',
		'exp_date' => 'TT.time_exp_date',
	);

	static $column_db_sort_fileds = array(
		'user' => '{user}.username',
		'issue' => 'TT.bug_id',
		'project' => '{project}.name',
		'time_category' => 'TT.category',
		'exp_date' => 'TT.time_exp_date',
	);

	public $selected_keys = array();
	public $rows_per_page = 100;
	public $page = 1; /* starts at 1 */
	public $bug_filter = null;

	public $time_filter_from, $time_filter_to; # as integer timestamps
	public $time_filter_user_id = null;
	public $time_filter_category = null;

	protected $all_rows_count;
	protected $result;

	public function __construct() {
		$this->selected_keys = static::$default_keys;
		/*
		$t_date_from = new \DateTime( 'first day of this month' );
		$t_date_to = new \DateTime( 'tomorrow' );
		$this->time_filter_from = $t_date_from->getTimestamp();
		$this->time_filter_to = $t_date_to->getTimestamp();
		 */
	}

	protected function build_filter_subselect( array &$p_params ) {
		# prepare filter subselect
		if( !$this->bug_filter ) {
			$t_filter = array();
			$t_filter[FILTER_PROPERTY_HIDE_STATUS] = array( META_FILTER_NONE );
			$t_filter = filter_ensure_valid_filter( $t_filter );
			$this->bug_filter = $t_filter;
		}
		# Note: filter_get_bug_rows_query_clauses() calls db_param_push();
		$t_query_clauses = filter_get_bug_rows_query_clauses( $this->bug_filter, null, null, null );
		# if the query can't be formed, there are no results
		if( empty( $t_query_clauses ) ) {
			# reset the db_param stack that was initialized by "filter_get_bug_rows_query_clauses()"
			db_param_pop();
			return db_empty_result();
		}
		$t_select_string = 'SELECT {bug}.id ';
		$t_from_string = ' FROM ' . implode( ', ', $t_query_clauses['from'] );
		$t_join_string = count( $t_query_clauses['join'] ) > 0 ? implode( ' ', $t_query_clauses['join'] ) : ' ';
		$t_where_string = ' WHERE '. implode( ' AND ', $t_query_clauses['project_where'] );
		if( count( $t_query_clauses['where'] ) > 0 ) {
			$t_where_string .= ' AND ( ';
			$t_where_string .= implode( $t_query_clauses['operator'], $t_query_clauses['where'] );
			$t_where_string .= ' ) ';
		}
		$t_query = $t_select_string . $t_from_string . $t_join_string . $t_where_string;
		$p_params = $t_query_clauses['where_values'];
		return $t_query;
	}

	public function get_rows_count() {
		if( !$this->result ) {
			$this->fetch_result();
		}
		return $this->all_rows_count;
	}

	public function get_result() {
		if( !$this->result ) {
			$this->fetch_result();
		}
		return $this->result;
	}

	protected function fetch_result() {
		$t_select_columns = array();
		$t_group_columns = array();
		$t_order_columns = array();
		foreach( $this->selected_keys as $key ) {
			$t_select_columns[] = static::$column_db_fileds[$key] . ' AS ' . $key;
			$t_group_columns[] = static::$column_db_fileds[$key];
			$t_order_columns[] = static::$column_db_sort_fileds[$key];
		}
		/*
		if( empty( $t_select_columns ) ) {
			return db_empty_result();
		}
		 */

		$t_where= array();
		$t_params = array();

		# bug filter
		$t_where[] = 'TT.bug_id IN ( ' . $this->build_filter_subselect( $t_params ) . ' )';

		# timetracking date
		if( $this->time_filter_from ) {
			$t_where[] = 'TT.time_exp_date >= ' . db_param();
			$t_params[] = (int)$this->time_filter_from;
		}
		if( $this->time_filter_to ) {
			$t_where[] = 'TT.time_exp_date < ' . db_param();
			$t_params[] = (int)$this->time_filter_to;
		}

		# timetracking user
		if( $this->time_filter_user_id ) {
			$t_where[] = 'TT.user_id = ' . db_param();
			$t_params[] = (int)$this->time_filter_user_id;
		}

		# timetracking category
		if( $this->time_filter_category ) {
			$t_where[] = 'TT.category = ' . db_param();
			$t_params[] = $this->time_filter_category;
		}

		# main query
		$t_cols_select = implode( ', ', $t_select_columns );
		if( !empty( $t_select_columns ) ) {
			$t_cols_select .= ', ';
		}
		$t_cols_group = implode( ', ', $t_group_columns );
		$t_cols_order = implode( ', ', $t_order_columns );
		$t_query = 'SELECT ' . $t_cols_select . 'SUM( TT.time_count ) AS time_count'
				. ' FROM {bug} JOIN ' . plugin_table( 'data' ) . ' TT ON {bug}.id = TT.bug_id'
				. ' JOIN {user} ON TT.user_id = {user}.id'
				. ' JOIN {project} ON {bug}.project_id = {project}.id'
				. ' WHERE ' . implode( ' AND ', $t_where );
		if( !empty( $t_select_columns ) ) {
			$t_query .= ' GROUP BY ' . $t_cols_group . ' ORDER BY ' . $t_cols_order;
		}

		$t_query_count = 'SELECT count(*) FROM ( ' . $t_query . ' ) C';

		$this->all_rows_count = db_result( db_query( $t_query_count, $t_params, -1, -1, false ) );

		$t_max_page = 1 + (int)floor( $this->all_rows_count / $this->rows_per_page );
		if( $this->page > $t_max_page ) {
			$this->page = $t_max_page;
		}
		$this->result = db_query( $t_query, $t_params, $this->rows_per_page, $this->rows_per_page * ( $this->page - 1 ) );
	}

	public function print_table() {
		echo '<table class="table table-striped table-bordered table-condensed table-hover">';
		echo '<thead>';
		echo '<tr>';
		foreach( $this->selected_keys as $t_col_name ) {
			echo '<th>', $t_col_name , '</th>';
		}
		echo '<th colspan="2">', 'time_count' , '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		$t_result = $this->get_result();
		while( $t_row = db_fetch_array( $t_result ) ) {
			echo '<tr>';
			foreach( $t_row as $t_key => $t_value ) {
				echo '<td>';
				if( 'time_count' == $t_key ) {
					echo seconds_to_hours( $t_value );
					echo '</td>';
					echo '<td>';
					echo seconds_to_hms( $t_value );
				} else {
					echo string_display_line( $t_value );
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	public function print_report_pagination() {
		$t_count = $this->get_rows_count();
		$t_pages = 1 + (int)floor( $t_count / $this->rows_per_page );
		if( $t_pages == 1 ) {
			return;
		}

		$t_url_page = url_self();
		$t_url_params = $this->get_current_params() + $_GET;

		$t_lang_first = lang_get( 'first' );
		$t_lang_last = lang_get( 'last' );
		$t_lang_prev = lang_get( 'prev' );
		$t_lang_next = lang_get( 'next' );
		$t_show_pages = 10;
		$t_show_from = max( 1, floor( $this->page - $t_show_pages / 2 ) );
		$t_show_to = min( $t_pages, 1+ floor( $this->page + $t_show_pages / 2 ) );
		echo '<div class="btn-group pull-right">';
		echo '<ul class="pagination small no-margin">';
			if( $t_show_to < $t_pages ) {
				$t_link_params = array( 'ttreport_page' =>  $t_pages ) + $t_url_params;
				echo '<li class="pull-right"><a href="' . url_safe_link( $t_url_page, $t_link_params ) . '">' . $t_lang_last . '</a></li>';
			}
			if( $this->page < $t_show_to ) {
				$t_link_params = array( 'ttreport_page' =>  $this->page + 1 ) + $t_url_params;
				echo '<li class="pull-right"><a href="' . url_safe_link( $t_url_page, $t_link_params ) . '">' . $t_lang_next . '</a></li>';
			}
			if( $t_show_to < $t_pages ) {
				echo '<li class="pull-right"><a>...</a></li>';
			}
			for( $i = $t_show_to; $i >= $t_show_from; $i-- ) {
				$t_active = ( $i == $this->page ) ? 'active ' : '';
				$t_link_params = array( 'ttreport_page' =>  $i ) + $t_url_params;
				echo '<li class="' . $t_active . 'pull-right"><a href="' . url_safe_link( $t_url_page, $t_link_params ) . '">' . $i . '</a></li>';
			}
			if( $t_show_from > 1 ) {
				echo '<li class="pull-right"><a>...</a></li>';
			}
			if( $this->page > $t_show_from ) {
				$t_link_params = array( 'ttreport_page' =>  $this->page - 1 ) + $t_url_params;
				echo '<li class="pull-right"><a href="' . url_safe_link( $t_url_page, $t_link_params ) . '">' . $t_lang_prev . '</a></li>';
			}
			if( $t_show_from > 1 ) {
				$t_link_params = array( 'ttreport_page' =>  1 ) + $t_url_params;
				echo '<li class="pull-right"><a href="' . url_safe_link( $t_url_page, $t_link_params ) . '">' . $t_lang_first . '</a></li>';
			}
		echo '</ul>';
		echo '</div>';
	}

	public function read_gpc_params() {
		$f_page = gpc_get_int( 'ttreport_page', 1 );
		$this->page = $f_page;

		$f_groupby = gpc_get_string_array( 'ttreport_groupby', array() );
		# if empty, no value were submitted, then use defaults
		if( empty( $f_groupby ) ) {
			$this->selected_keys = static::$default_keys;
		} else {
			$c_groupby = array();
			foreach( $f_groupby as $t_key ) {
				if( in_array( $t_key, static::$column_keys ) ) {
					$c_groupby[] = $t_key;
				}
			}
			# here the group fields may be empty, but this was submitted intentionally
			$this->selected_keys = $c_groupby;
		}

		# Read filter parameters
		$f_reset = gpc_isset( 'reset_filter_button' );
		if( $f_reset ) {
			$this->time_filter_from = null;
			$this->time_filter_to = null;
			$this->time_filter_category = null;
			$this->time_filter_user_id = null;
		} else {
			# dates as d/m/Y
			$f_date_from_d = gpc_get_int( 'ttreport_date_from_d', 0 );
			$f_date_from_m = gpc_get_int( 'ttreport_date_from_m', 0 );
			$f_date_from_y = gpc_get_int( 'ttreport_date_from_y', 0 );
			$f_date_to_d = gpc_get_int( 'ttreport_date_to_d', 0 );
			$f_date_to_m = gpc_get_int( 'ttreport_date_to_m', 0 );
			$f_date_to_y = gpc_get_int( 'ttreport_date_to_y', 0 );
			if( $f_date_from_d && $f_date_from_m && $f_date_from_y ) {
				$this->time_filter_from = parse_date_parts( $f_date_from_y, $f_date_from_m, $f_date_from_d );
			}
			if( $f_date_to_d && $f_date_to_m && $f_date_to_y ) {
				$this->time_filter_to = parse_date_parts( $f_date_to_y, $f_date_to_m, $f_date_to_d );
			}

			# dates as timestamp
			$f_timestamp_from = gpc_get_int( 'ttreport_date_from', 0 );
			if( $f_timestamp_from > 0 ) {
				$this->time_filter_from = $f_timestamp_from;
			}
			$f_timestamp_to = gpc_get_int( 'ttreport_date_to', 0 );
			if( $f_timestamp_to > 0 ) {
				$this->time_filter_to = $f_timestamp_to;
			}

			# timetracking category
			$f_category = gpc_get_string( 'ttreport_category', '' );
			if( !empty( $f_category ) ) {
				$this->time_filter_category = string_html_entities( $f_category );
			}

			#timetracking user
			$f_user_id = gpc_get_int( 'ttreport_user_id', 0 );
			if( $f_user_id > 0 ) {
				$this->time_filter_user_id = $f_user_id;
			}
		}
	}

	public function get_current_params() {
		$t_params = array();
		$t_params['ttreport_page'] = $this->page;
		$t_params['ttreport_groupby'] = $this->selected_keys;
		if( $this->time_filter_user_id ) {
			$t_params['ttreport_user_id'] = $this->time_filter_user_id;
		}
		if( $this->time_filter_category ) {
			$t_params['ttreport_category'] = $this->time_filter_category;
		}
		if( $this->time_filter_from ) {
			$t_params['ttreport_date_from'] = $this->time_filter_from;
		}
		if( $this->time_filter_to ) {
			$t_params['ttreport_date_to'] = $this->time_filter_to;
		}
		return $t_params;
	}

	function print_inputs_time_filter() {
		if( $this->time_filter_from ) {
			$t_date_enabled = true;
			$t_date_from = new \DateTime();
			$t_date_from->settimestamp( $this->time_filter_from );
		} else {
			$t_date_enabled = false;
			$t_date_from = new \DateTime( 'yesterday' );
		}
		if( $this->time_filter_to ) {
			$t_date_to = new \DateTime();
			$t_date_to->settimestamp( $this->time_filter_to );
		} else {
			$t_date_to = new \DateTime( 'today' );
		}
		$t_category_enabled = isset( $this->time_filter_category );
		$t_user_enabled =  isset( $this->time_filter_user_id );
		?>
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
					<th>
						<label data-toggle="collapse" data-target="#ttreport_filter_by_date">
							<input type="checkbox" class="ace input-sm" <?php check_checked( $t_date_enabled )?>>
							<span class="lbl"></span>
							Filter by date
						</label>
					</th>
					<th>
						<label data-toggle="collapse" data-target="#ttreport_filter_by_category">
							<input type="checkbox" class="ace input-sm" <?php check_checked( $t_category_enabled )?>>
							<span class="lbl"></span>
							Filter by category
						</label>
					</th>
					<th>
						<label data-toggle="collapse" data-target="#ttreport_filter_by_user">
							<input type="checkbox" class="ace input-sm" <?php check_checked( $t_user_enabled )?>>
							<span class="lbl"></span>
							Filter by user
						</label>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<span id="ttreport_filter_by_date" class="collapse collapse-inline disable-collapsed-inputs <?php echo $t_date_enabled ? 'in' : '' ?>">
							<table>
								<tr>
									<td><?php echo lang_get( 'start_date_label' ) ?></td>
									<td>
										<select name="ttreport_date_from_d"><?php print_day_option_list( $t_date_from->format( 'd' ) ) ?></select>
									</td>
									<td>
										<select name="ttreport_date_from_m"><?php print_month_option_list( $t_date_from->format( 'm' ) ) ?></select>
									</td>
									<td>
										<select name="ttreport_date_from_y"><?php print_year_option_list( $t_date_from->format( 'Y' ) ) ?></select>
									</td>
								</tr>
								<tr>
									<td><?php echo lang_get( 'end_date_label' ) ?></td>
									<td>
										<select name="ttreport_date_to_d"><?php print_day_option_list( $t_date_to->format( 'd' ) ) ?></select>
									</td>
									<td>
										<select name="ttreport_date_to_m"><?php print_month_option_list( $t_date_to->format( 'm' ) ) ?></select>
									</td>
									<td>
									<select name="ttreport_date_to_y"><?php print_year_option_list( $t_date_to->format( 'Y' ) ) ?></select>
									</td>
								</tr>
							</table>
						</span>
					</td>
					<td>
						<span id="ttreport_filter_by_category" class="collapse collapse-inline disable-collapsed-inputs <?php echo $t_category_enabled ? 'in' : '' ?>">
							<label>Category
								<select name="ttreport_category" class="input-sm">
								<?php print_timetracking_category_option_list() ?>
							</select>
							</label>
						</span>
					</td>
					<td>
						<span id="ttreport_filter_by_user" class="collapse collapse-inline disable-collapsed-inputs <?php echo $t_user_enabled ? 'in' : '' ?>">
							<label>User
								<select name="ttreport_user_id" class="input-sm">
								<?php print_timetracking_user_option_list() ?>
							</select>
							</label>
						</span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function print_inputs_group_by() {
		echo '<div class="form-group">';
		echo '<strong>Group by</strong>';
		echo '<span class="ttreport_groupby_container">';
		# set a dummy group field to allow for empty group, and avoid applying deafults.
		echo '<input type="hidden" name="ttreport_groupby[0]" value="">';
		$t_elements = array();
		$t_par_index = 1;
		foreach( $this->selected_keys as $t_key ) {
			$t_el = '<span class="ttreport_groupby">';
			$t_el .= '<span class="label">';
			$t_el .= '<input type="hidden" name="ttreport_groupby[' . $t_par_index++ . ']" value="' . $t_key . '">';
			$t_el .= $t_key;
			$t_el .= '<span class="ttreport_groupby_remove"><a href="#"><i class="ace-icon fa fa-times"></i></a></span>';
			$t_el .= '</span>';
			$t_el .= '</span>';
			$t_elements[] = $t_el;
		}
		$t_unused_keys = array_diff( static::$column_keys, $this->selected_keys );
		if( !empty( $t_unused_keys ) ) {
			$t_input = '<span class="ttreport_groupby">';
			$t_input .= '<select name="ttreport_groupby[' . $t_par_index++ . ']">';
			$t_input .= '<option value="">' . '(add)' . '</option>';
			foreach( $t_unused_keys as $t_key ) {
				$t_input .= '<option value="' . $t_key . '">' . $t_key . '</option>';
			}
			$t_input .= '</select>';
			$t_input .= '</span>';
			$t_elements[] = $t_input;
		}
		echo implode ( '', $t_elements );
		echo '</span>';
		echo '</div>';
	}
}

class ReportForBug extends Report {
	static $column_keys = array(
		'user', 'time_category',
	);
	static $default_keys = array(
		'user', 'time_category',
	);

	protected $bug_id;

	public function __construct( $p_bug_id ) {
		parent::__construct();
		$this->bug_id = $p_bug_id;
	}

	protected function build_filter_subselect( array &$p_params ) {
		db_param_push();
		$t_query = db_param();
		$p_params[] = (int)$this->bug_id;
		return $t_query;
	}
}