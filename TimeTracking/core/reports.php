<?php
namespace TimeTracking;

/**
 * Class that encapsulates logic and presentation for report generation
 */
class Report {
	# these static fields are global definitions for this object and inherited

	/**
	 * Array of possible column keys
	 * @var array
	 */
	static $column_keys = array(
		'user', 'issue', 'project', 'time_category',
	);

	/**
	 * Default keys to use
	 * @var array
	 */
	static $default_keys = array(
		'user', 'time_category',
	);

	/**
	 * db fields to use in sql query, for each key
	 * using alias:
	 * 'TT' as plugin data table
	 * @var array
	 */
	static $column_db_fileds = array(
		'user' => '{user}.id',
		'issue' => 'TT.bug_id',
		'project' => '{project}.id',
		'time_category' => 'TT.category',
		'exp_date' => 'TT.time_exp_date',
		'date_created' => 'TT.date_created',
	);

	/**
	 * db fields to sort on, for each key
	 * @var array
	 */
	static $column_db_sort_fileds = array(
		'user' => '{user}.username',
		'issue' => 'TT.bug_id',
		'project' => '{project}.name',
		'time_category' => 'TT.category',
		'exp_date' => 'TT.time_exp_date',
		'date_created' => 'TT.date_created',
	);

	/**
	 * Current selection of keys for this object
	 * @var array
	 */
	public $selected_keys = array();

	/**
	 * pagination: rows per page
	 * @var integer
	 */
	public $rows_per_page = 100;

	/**
	 * pagination, current page
	 * @var integer
	 */
	public $page = 1; /* starts at 1 */

	/**
	 * Filter array, to filter current bug selection
	 * @var array
	 */
	public $bug_filter = null;

	# current values for timetracking filtering
	# if any is null, it won't be applied
	public $time_filter_from, $time_filter_to; # as integer timestamps
	public $time_filter_user_id = null;
	public $time_filter_category = null;

	/**
	 * After the query is executed, total number of rows will be sotred here
	 * Note: for consistency, use get_rows_count()
	 * @var integer
	 */
	protected $all_rows_count;

	/**
	 * After the query is executed, the raw result will be stored here
	 * Note: for consistency, use get_result()
	 * @var iterator
	 */
	protected $result;

	/**
	 * Constructor. Initialize defaults
	 */
	public function __construct() {
		$this->selected_keys = static::$default_keys;
	}

	/**
	 * Build a sql select based on the configured filter.
	 * This query is suitable to be used as IN clause
	 * Note: this mthod will call db_param_push()
	 * @param array $p_params	db_params array (output)
	 * @return string			SQL query for subselect
	 */
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

	/**
	 * Get the row count of the report result.
	 * If the query has not been executed yet, calls to build an execute it-
	 * @return type
	 */
	public function get_rows_count() {
		if( !$this->result ) {
			$this->fetch_result();
		}
		return $this->all_rows_count;
	}

	/**
	 * Get the query result
	 * If the query has not been executed yet, calls to build an execute it-
	 * @return iterator
	 */
	public function get_result() {
		if( !$this->result ) {
			$this->fetch_result();
		}
		return $this->result;
	}

	/**
	 * Builds the query, execute it and stores the result
	 */
	protected function fetch_result() {
		$t_select_columns = array();
		$t_group_columns = array();
		$t_order_columns = array();
		foreach( $this->selected_keys as $key ) {
			$t_select_columns[] = static::$column_db_fileds[$key] . ' AS ' . $key;
			$t_group_columns[] = static::$column_db_fileds[$key];
			$t_order_columns[] = static::$column_db_sort_fileds[$key];
		}

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

		# keeps db_params in the stack, for the next db_query
		$this->all_rows_count = db_result( db_query( $t_query_count, $t_params, -1, -1, false ) );

		# update current page if it is outside range
		$t_max_page = 1 + (int)floor( $this->all_rows_count / $this->rows_per_page );
		if( $this->page > $t_max_page ) {
			$this->page = $t_max_page;
		}

		$this->result = db_query( $t_query, $t_params, $this->rows_per_page, $this->rows_per_page * ( $this->page - 1 ) );
	}

	/**
	 * Process a result array and cache relevant data in core apis
	 * @param array $p_result_array		The query result in array form
	 */
	protected function cache_resut_array( array $p_result_array ) {
		foreach( $this->selected_keys as $t_key ) {
			switch( $t_key ) {
				case 'user':
					user_cache_array_rows( array_column( $p_result_array, $t_key ) );
					break;
				case 'project':
					project_cache_array_rows( array_column( $p_result_array, $t_key ) );
					break;
				case 'issue':
					bug_cache_array_rows( array_column( $p_result_array, $t_key ) );
			}
		}
	}

	/**
	 * Formats each column key for proper presentation
	 * @param string $p_key		Column key
	 * @param mixed $p_value	Value to format
	 * @return mixed		Formatted value
	 */
	public static function format_value( $p_key, $p_value ) {
		switch( $p_key ) {
			case 'user':
				$t_value = string_display_line( user_get_name( $p_value ) );
				break;
			case 'project':
				$t_value = string_display_line( project_get_name( $p_value ) );
				break;
			case 'issue':
				$t_value = string_get_bug_view_link( $p_value ) . ':' . lang_get( 'word_separator' ) . string_shorten( bug_get_field( $p_value, 'summary' ), 80 );
				break;
			case 'exp_date':
				$t_value = string_display_line( date( config_get( 'short_date_format' ), $p_value ) );
				break;
			case 'date_created':
				$t_value = string_display_line( date( config_get( 'normal_date_format' ), $p_value ) );
				break;
			default;
				$t_value = string_display_line( $p_value );
		}
		return $t_value;
	}

	/**
	 * Outputs the query result into an htm table
	 */
	public function print_table() {
		$t_result = $this->get_result();
		$t_result_array = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_result_array[] = $t_row;
		}
		$this->cache_resut_array( $t_result_array );

		echo '<table class="table table-striped table-bordered table-condensed table-hover">';
		echo '<thead>';
		echo '<tr>';
		foreach( $this->selected_keys as $t_col_name ) {
			echo '<th>', plugin_lang_get( $t_col_name ) , '</th>';
		}
		echo '<th colspan="2">', plugin_lang_get( 'time_count' ) , '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach( $t_result_array as $t_row ) {
			echo '<tr>';
			foreach( $t_row as $t_key => $t_value ) {
				echo '<td>';
				if( 'time_count' == $t_key ) {
					echo seconds_to_hours( $t_value );
					echo '</td>';
					echo '<td>';
					echo seconds_to_hms( $t_value );
				} else {
					echo static::format_value( $t_key, $t_value );
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	/**
	 * Prints the pagination controls for current report
	 * @return string	Html for pagination div
	 */
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

	/**
	 * Reads GET and POST parameters to update the status of current filter and properties
	 */
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

	/**
	 * Returns an array of key/value pairs representign the state of current filter and properties,
	 * suitable to build a query url
	 * @return type
	 */
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

	/**
	 * Prints html for current filter properties.
	 * It will print the inputs and supporting html, but not the main form tags
	 */
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
							<?php echo plugin_lang_get( 'filter_by_date' ) ?>
						</label>
					</th>
					<th>
						<label data-toggle="collapse" data-target="#ttreport_filter_by_category">
							<input type="checkbox" class="ace input-sm" <?php check_checked( $t_category_enabled )?>>
							<span class="lbl"></span>
							<?php echo plugin_lang_get( 'filter_by_category' ) ?>
						</label>
					</th>
					<th>
						<label data-toggle="collapse" data-target="#ttreport_filter_by_user">
							<input type="checkbox" class="ace input-sm" <?php check_checked( $t_user_enabled )?>>
							<span class="lbl"></span>
							<?php echo plugin_lang_get( 'filter_by_user' ) ?>
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

	/**
	 * Prints html for current column grouping
	 * It will print the inputs and supporting html, but not the main form tags
	 */
	public function print_inputs_group_by() {
		echo '<div class="form-group">';
		echo '<strong>' . plugin_lang_get( 'group_by' ) . ':</strong>';
		echo '<span class="ttreport_groupby_container">';
		# set a dummy group field to allow for empty group, and avoid applying deafults.
		echo '<input type="hidden" name="ttreport_groupby[0]" value="">';
		$t_elements = array();
		$t_par_index = 1;
		foreach( $this->selected_keys as $t_key ) {
			$t_el = '<span class="ttreport_groupby">';
			$t_el .= '<span class="label">';
			$t_el .= '<input type="hidden" name="ttreport_groupby[' . $t_par_index++ . ']" value="' . $t_key . '">';
			$t_el .= plugin_lang_get( $t_key );
			$t_el .= '<span class="ttreport_groupby_remove"><a href="#"><i class="ace-icon fa fa-times"></i></a></span>';
			$t_el .= '</span>';
			$t_el .= '</span>';
			$t_elements[] = $t_el;
		}
		$t_unused_keys = array_diff( static::$column_keys, $this->selected_keys );
		if( !empty( $t_unused_keys ) ) {
			$t_input = '<span class="ttreport_groupby">';
			$t_input .= '<select name="ttreport_groupby[' . $t_par_index++ . ']">';
			$t_input .= '<option value="">' . '[' . plugin_lang_get( 'add' )  . ']' . '</option>';
			foreach( $t_unused_keys as $t_key ) {
				$t_input .= '<option value="' . $t_key . '">' . plugin_lang_get( $t_key ) . '</option>';
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

/**
 * Class for a report suitable for a single issue
 */
class ReportForBug extends Report {
	# override parent definiiton for keys, only to those that make sens for a singe issue
	static $column_keys = array(
		'user', 'time_category',
	);
	static $default_keys = array(
		'user', 'time_category',
	);

	protected $bug_id;

	/**
	 * constructor, must be initialized with a bug id
	 * @param integer $p_bug_id		Bug id
	 */
	public function __construct( $p_bug_id ) {
		parent::__construct();
		$this->bug_id = $p_bug_id;
	}

	/**
	 * Overrides parent filter-based selection, to show only specified bug id
	 * @param array $p_params	db_params (output)
	 * @return string	sql string
	 */
	protected function build_filter_subselect( array &$p_params ) {
		db_param_push();
		# use only the bug id, as it will be placed inside a IN () clause
		$t_query = db_param();
		$p_params[] = (int)$this->bug_id;
		return $t_query;
	}
}