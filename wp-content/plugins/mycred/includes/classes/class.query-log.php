<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Query Log
 * @see http://codex.mycred.me/classes/mycred_query_log/ 
 * @since 0.1
 * @version 1.6
 */
if ( ! class_exists( 'myCRED_Query_Log' ) ) :
	class myCRED_Query_Log {

		public $now;
		public $args;
		public $request;
		public $num_rows;
		public $max_num_pages;
		public $total_rows;

		public $results;

		public $headers        = array();
		public $hidden_headers = array();
		public $core;
		public $is_admin       = false;
		public $references     = array();

		/**
		 * Construct
		 */
		public function __construct( $args = array(), $array = false ) {

			if ( empty( $args ) || ! MYCRED_ENABLE_LOGGING ) return false;

			global $wpdb, $mycred;

			$select           = $where = $sortby = $limits = '';
			$wheres           = array();
			$this->now        = current_time( 'timestamp' );
			$this->references = mycred_get_all_references();

			// Load General Settings
			$type = MYCRED_DEFAULT_TYPE_KEY;
			if ( isset( $args['ctype'] ) && mycred_point_type_exists( $args['ctype'] ) )
				$type = $args['ctype'];

			$this->core = mycred( $type );
			if ( $this->core->format['decimals'] > 0 )
				$format = '%f';
			else
				$format = '%d';

			// Prep Defaults
			$defaults  	= array(
				'entry_id' => NULL,
				'user_id'  => NULL,
				'ctype'    => MYCRED_DEFAULT_TYPE_KEY,
				'number'   => 25,
				'time'     => NULL,
				'ref'      => NULL,
				'ref_id'   => NULL,
				'amount'   => NULL,
				's'        => NULL,
				'data'     => NULL,
				'orderby'  => 'time',
				'offset'   => '',
				'order'    => 'DESC',
				'ids'      => false,
				'paged'    => $this->get_pagenum()
			);
			$this->args = wp_parse_args( apply_filters( 'mycred_query_log_args', $args ), $defaults );

			// Difference between default and given args
			$this->diff = array_diff_assoc( $this->args, $defaults );
			if ( isset( $this->diff['number'] ) )
				unset( $this->diff['number'] );

			// Point Type
			if ( $this->args['ctype'] !== NULL )
				$wheres[] = $wpdb->prepare( "ctype = %s", $this->args['ctype'] );

			if ( $this->args['entry_id'] !== NULL )
				$wheres[] = $wpdb->prepare( "id = %d", absint( $this->args['entry_id'] ) );

			// User ID
			if ( $this->args['user_id'] !== NULL ) {

				$user_id = $this->get_user_id( $this->args['user_id'] );

				if ( $user_id !== false )
					$wheres[] = $wpdb->prepare( "user_id = %d", $user_id );

			}

			// Reference(s) - single value, comma separated list or an array
			if ( $this->args['ref'] !== NULL ) {

				$_clean_refs = array();
				$references  = ( ( ! is_array( $this->args['ref'] ) ) ? explode( ',', $this->args['ref'] ) : $this->args['ref'] );

				if ( ! empty( $references ) ) {
					foreach ( $references as $reference ) {

						$reference = sanitize_key( $reference );
						if ( $reference == '' ) continue;
						$_clean_refs[] = $reference;

					}
				}

				if ( ! empty( $_clean_refs ) ) {

					if ( count( $_clean_refs ) == 1 )
						$wheres[] = $wpdb->prepare( "ref = %s", $_clean_refs[0] );

					else
						$wheres[] = $wpdb->prepare( "ref IN (%s" . str_repeat( ',%s', ( count( $_clean_refs ) - 1 ) ) . ")", $_clean_refs );


				}

			}

			// Reference ID(s) - single value, comma separated list or an array
			if ( $this->args['ref_id'] !== NULL ) {

				$_clean_ids = array();
				$ref_ids    = ( ( ! is_array( $this->args['ref'] ) ) ? explode( ',', $this->args['ref_id'] ) : $this->args['ref_id'] );

				if ( ! empty( $ref_ids ) ) {
					foreach ( $ref_ids as $ref_id ) {

						$ref_id = absint( $ref_id );
						if ( $ref_id === 0 ) continue;
						$_clean_ids[] = $ref_id;

					}
				}

				if ( ! empty( $_clean_ids ) ) {

					if ( count( $_clean_ids ) == 1 )
						$wheres[] = $wpdb->prepare( "ref_id = %d", $_clean_ids[0] );

					else
						$wheres[] = $wpdb->prepare( "ref IN (%d" . str_repeat( ',%d', ( count( $_clean_ids ) - 1 ) ) . ")", $_clean_ids );


				}

			}

			// Amount
			if ( $this->args['amount'] !== NULL ) {

				// Advanced query
				if ( is_array( $this->args['amount'] ) ) {

					// Range
					if ( isset( $this->args['amount']['start'] ) && isset( $this->args['amount']['end'] ) )
						$wheres[] = $wpdb->prepare( "creds BETWEEN {$format} AND {$format}", $this->core->number( sanitize_text_field( $this->args['amount']['start'] ) ), $this->core->number( sanitize_text_field( $this->args['amount']['end'] ) ) );

					// Compare
					elseif ( isset( $this->args['amount']['num'] ) && isset( $this->args['amount']['compare'] ) ) {

						$compare  = trim( urldecode( $this->args['amount']['compare'] ) );
						if ( in_array( $compare, array( '=', '!=', '>', '<', '<>') ) )
							$wheres[] = $wpdb->prepare( "creds {$compare} {$format}", $this->core->number( sanitize_text_field( $this->args['amount']['num'] ) ) );

					}

				}

				// Specific amount(s)
				else {

					$_clean_values = array();
					$point_values  = ( ( ! is_array( $this->args['amount'] ) ) ? explode( ',', $this->args['amount'] ) : $this->args['amount'] );

					if ( ! empty( $point_values ) ) {
						foreach ( $point_values as $amount )
							$_clean_values[] = $this->core->number( $amount );
					}

					if ( ! empty( $_clean_values ) ) {

						if ( count( $_clean_values ) == 1 )
							$wheres[] = $wpdb->prepare( "ref_id = {$format}", $_clean_values[0] );

						else
							$wheres[] = $wpdb->prepare( "ref IN ({$format}" . str_repeat( ",{$format}", ( count( $_clean_values ) - 1 ) ) . ")", $_clean_values );


					}

				}

			}

			// Time
			if ( $this->args['time'] !== NULL && $this->args['time'] != '' ) {

				$today       = strtotime( date( 'Y-m-d', $this->now ) . ' midnight' );
				$todays_date = date( 'd', $this->now );

				// Show todays entries
				if ( $this->args['time'] == 'today' )
					$wheres[] = "time BETWEEN {$today} AND {$this->now}";

				// Show yesterdays entries
				elseif ( $this->args['time'] == 'yesterday' ) {
					$yesterday = strtotime( '-1 day midnight', $this->now );
					$wheres[]  = "time BETWEEN {$yesterday} AND {$today}";
				}

				// Show this weeks entries
				elseif ( $this->args['time'] == 'thisweek' ) {

					$weekday = date( 'w', $this->now );

					// New week started today so show only todays
					if ( get_option( 'start_of_week' ) == $weekday )
						$wheres[] = "time BETWEEN {$today} AND {$this->now}";

					// Show rest of this week
					else {
						$week_start = strtotime( '-' . ( $weekday+1 ) . ' days midnight', $this->now );
						$wheres[]   = "time BETWEEN {$week_start} AND {$this->now}";
					}

				}

				// Show this months entries
				elseif ( $this->args['time'] == 'thismonth' ) {
					$start_of_month = strtotime( date( 'Y-m-01' ) . ' midnight', $this->now );
					$wheres[]       = "time BETWEEN {$start_of_month} AND {$this->now}";
				}

				// Show entries based on given time frames
				else {

					$timestamps = array();
					$times      = ( ( ! is_array( $this->args['time'] ) ) ? explode( ',', $this->args['time'] ) : $this->args['time'] );

					if ( ! empty( $times ) ) {
						foreach ( $times as $time_string ) {

							$time_string = $this->get_timestamp( $time_string );
							if ( $time_string === false && ! in_array( $this->now, $timestamps ) ) $time_string = $this->now;
							$timestamps[] = $time_string;

						}
					}
$this->timestamps = $timestamps;
					if ( count( $timestamps ) == 2 )
						$wheres[] = $wpdb->prepare( "time BETWEEN %d AND %d", $timestamps );

				}

			}

			// Entry Search
			if ( $this->args['s'] !== NULL && $this->args['s'] != '' ) {

				$search_query = sanitize_text_field( $this->args['s'] );

				if ( is_int( $search_query ) )
					$search_query = (string) $search_query;

				$wheres[] = $wpdb->prepare( "entry LIKE %s", "%$search_query%" );

			}

			// Data
			if ( $this->args['data'] !== NULL && $this->args['data'] != '' ) {

				$data_query = sanitize_text_field( $this->args['data'] );

				if ( is_int( $data_query ) )
					$data_query = (string) $data_query;

				$wheres[] = $wpdb->prepare( "entry LIKE %s", "%$data_query%" );

			}

			// Order by
			if ( strlen( $this->args['orderby'] ) > 0 ) {

				// Make sure $sortby is valid
				$allowed = apply_filters( 'mycred_allowed_sortby', array( 'id', 'ref', 'ref_id', 'user_id', 'creds', 'ctype', 'entry', 'data', 'time' ) );
				if ( in_array( $this->args['orderby'], $allowed ) )
					$sortby = "ORDER BY " . $this->args['orderby'] . " " . $this->args['order'];

			}

			// Number of results
			$number = $this->args['number'];
			if ( $number < -1 )
				$number = abs( $number );

			elseif ( $number == 0 || $number == -1 )
				$number = NULL;

			// Limits
			if ( $number !== NULL ) {

				$page = 1;
				if ( $this->args['paged'] !== NULL ) {
					$page = absint( $this->args['paged'] );
					if ( ! $page )
						$page = 1;
				}

				if ( $this->args['offset'] == '' ) {
					$pgstrt = ($page - 1) * $number . ', ';
				}

				else {
					$offset = absint( $this->args['offset'] );
					$pgstrt = $offset . ', ';
				}

				$limits = 'LIMIT ' . $pgstrt . $number;

			}

			// Prep return
			$select = '*';
			if ( $this->args['ids'] === true )
				$select = 'id';

			$found_rows = '';
			if ( $limits != '' )
				$found_rows = 'SQL_CALC_FOUND_ROWS';

			$where = 'WHERE ' . implode( ' AND ', $wheres );

			// Run
			$this->request = "SELECT {$found_rows} {$select} FROM {$mycred->log_table} {$where} {$sortby} {$limits};";
			$this->results = $wpdb->get_results( $this->request, $array ? ARRAY_A : OBJECT );

			if ( $limits != '' )
				$this->num_rows = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
			else
				$this->num_rows = count( $this->results );

			if ( $limits != '' )
				$this->max_num_pages = ceil( $this->num_rows / $number );

			$this->total_rows = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->core->log_table}" );

		}

		/**
		 * Table Headers
		 * Returns all table column headers.
		 * @filter mycred_log_column_headers
		 * @since 0.1
		 * @version 1.1.1
		 */
		public function table_headers() {

			// Headers already set
			if ( ! empty( $this->headers ) ) return;

			global $mycred_types;

			$columns = array(
				'username' => __( 'User', 'mycred' ),
				'time'     => __( 'Date', 'mycred' ),
				'creds'    => $this->core->plural(),
				'entry'    => __( 'Entry', 'mycred' )
			);

			if ( $this->is_admin )
				$columns = array(
					'cb'       => '',
					'username' => __( 'User', 'mycred' ),
					'ref'      => __( 'Reference', 'mycred' ),
					'time'     => __( 'Date', 'mycred' ),
					'creds'    => $this->core->plural(),
					'entry'    => __( 'Entry', 'mycred' )
				);

			$headers = $this->headers;
			if ( empty( $this->headers ) )
				$headers = $columns;

			$this->headers = apply_filters( 'mycred_log_column_headers', $headers, $this, $this->is_admin );

		}

		/**
		 * Has Entries
		 * @returns true or false
		 * @since 0.1
		 * @version 1.0
		 */
		public function have_entries() {

			if ( ! empty( $this->results ) ) return true;
			return false;

		}

		/**
		 * No Entries
		 * @since 0.1
		 * @version 1.0
		 */
		public function no_entries() {

			echo $this->get_no_entries();

		}

		/**
		 * Get No Entries
		 * @since 0.1
		 * @version 1.0
		 */
		public function get_no_entries() {

			return __( 'No log entries found', 'mycred' );

		}

		/**
		 * Get Page Number
		 * @since 1.4
		 * @version 1.0.1
		 */
		public function get_pagenum() {

			global $paged;

			if ( $paged > 0 )
				$pagenum = absint( $paged );

			elseif ( isset( $_REQUEST['paged'] ) )
				$pagenum = absint( $_REQUEST['paged'] );

			elseif ( isset( $_REQUEST['page'] ) )
				$pagenum = absint( $_REQUEST['page'] );

			else return 1;

			return max( 1, $pagenum );

		}

		/**
		 * Table Nav
		 * @since 0.1
		 * @version 1.1
		 */
		public function table_nav( $location = 'top', $is_profile = false ) {

			if ( ! $this->have_entries() ) return;

			if ( $location == 'top' ) {

				$this->bulk_actions();
				$this->filter_options( $is_profile );
				$this->navigation( $location );

			}
			else {

				$this->navigation( $location );

			}

		}

		/**
		 * Bulk Actions
		 * @since 1.7
		 * @version 1.0
		 */
		public function bulk_actions() {

			if ( ! $this->is_admin ) return;

			$bulk_actions = apply_filters( 'mycred_log_bulk_actions', array(
				'-1'            => __( 'Bulk Actions', 'mycred' ),
				'export-raw'    => __( 'Export Raw', 'mycred' ),
				'export-format' => __( 'Export Formatted', 'mycred' ),
				'delete'        => __( 'Delete', 'mycred' )
			), $this );

			if ( empty( $bulk_actions ) ) return;

?>
<div class="alignleft actions bulkactions">
	<select name="action" id="bulk-action-selector-top">
<?php

	foreach ( $bulk_actions as $action_id => $label )
		echo '<option value="' . $action_id . '">' . $label . '</option>';

?>
	</select>
	<input type="submit" class="button action" id="doaction" value="<?php _e( 'Apply', 'mycred' ); ?>" />
</div>
<?php

		}

		/**
		 * Filter Log options
		 * @since 0.1
		 * @version 1.3.1
		 */
		public function filter_options( $is_profile = false, $refs = array() ) {

			echo '<div class="alignleft actions">';
			$show = false;

			// Filter by reference
			$references = $this->get_refs( $refs );
			if ( ! empty( $references ) ) {

				echo '<select name="ref" id="myCRED-reference-filter"><option value="">' . __( 'Show all references', 'mycred' ) . '</option>';
				foreach ( $references as $ref ) {

					$label = str_replace( array( '_', '-' ), ' ', $ref );
					echo '<option value="' . $ref . '"';
					if ( isset( $_GET['ref'] ) && $_GET['ref'] == $ref ) echo ' selected="selected"';
					echo '>' . ucwords( $label ) . '</option>';

				}
				echo '</select>';
				$show = true;

			}

			// Filter by user
			if ( $this->core->can_edit_creds() && ! $is_profile && $this->num_rows > 0 ) {

				echo '<input type="text" class="form-control" name="user" id="myCRED-user-filter" size="22" placeholder="' . __( 'User ID, Username, Email or Nicename', 'mycred' ) . '" value="' . ( ( isset( $_GET['user'] ) ) ? $_GET['user'] : '' ) . '" /> ';
				$show = true;

			}

			// Filter Order
			if ( $this->num_rows > 0 ) {

				echo '<select name="order" id="myCRED-order-filter"><option value="">' . __( 'Show in order', 'mycred' ) . '</option>';
				foreach ( array( 'ASC' => __( 'Ascending', 'mycred' ), 'DESC' => __( 'Descending', 'mycred' ) ) as $value => $label ) {

					echo '<option value="' . $value . '"';
					if ( ! isset( $_GET['order'] ) && $value == 'DESC' ) echo ' selected="selected"';
					elseif ( isset( $_GET['order'] ) && $_GET['order'] == $value ) echo ' selected="selected"';
					echo '>' . $label . '</option>';

				}
				echo '</select>';
				$show = true;

			}

			// Let others play
			if ( has_action( 'mycred_filter_log_options' ) ) {
				do_action( 'mycred_filter_log_options', $this );
				$show = true;
			}

			if ( $show === true )
				echo '<input type="submit" class="btn btn-default button button-secondary" value="' . __( 'Filter', 'mycred' ) . '" />';

			echo '</div>';

		}

		/**
		 * Front Navigation
		 * Renders navigation with bootstrap support
		 * @since 1.7
		 * @version 1.0
		 */
		public function front_navigation( $location = 'top', $pagination = 10 ) {

			if ( ! $this->have_entries() || $this->max_num_pages == 1 ) return;

?>
<div class="row pagination-<?php echo $location; ?>">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

		<?php $this->front_pagination( $pagination ); ?>

	</div>
</div>
<?php

		}

		/**
		 * Navigation Wrapper
		 * @since 0.1
		 * @version 1.1
		 */
		public function navigation( $location = 'top', $id = '' ) {

?>
<h2 class="screen-reader-text sr-only"><?php _e( 'Log entries navigation', 'mycred' ); ?></h2>
<div class="tablenav-pages<?php if ( $this->max_num_pages == 1 ) echo ' one-page'; ?>">

	<?php $this->pagination( $location, $id ); ?>

</div>
<br class="clear" />
<?php

		}

		/**
		 * Front Pagination
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function front_pagination( $pages_to_show = 5 ) {

			if ( ! $this->have_entries() ) return;

			$page_links    = array();
			$total_pages   = $this->max_num_pages;
			$current       = $this->get_pagenum();

			$current_url   = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			$current_url   = str_replace( '/page/' . $current . '/', '/', $current_url );

			$pages_to_show = absint( $pages_to_show );
			if ( $pages_to_show === 0 ) $pages_to_show = 5;

			// We can not show more pages then whats available
			if ( $pages_to_show > $total_pages )
				$pages_to_show = $total_pages;

			$disable_first = $disable_last = '';
			if ( $current == 1 )
				$disable_first = ' disabled';

			if ( $current == $total_pages )
				$disable_last = ' disabled';

			if ( $current == 1 )
				$page_links[] = '<li><span aria-hidden="true">&laquo;</span></li>';
			else {
				$page_links[] = sprintf( '<li><a class="%s" href="%s">%s</a></li>',
					'first-page',
					esc_url( remove_query_arg( 'page', $current_url ) ),
					'&laquo;'
				);
			}

			if ( $current == 1 )
				$page_links[] = '<li><span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span></li>';
			else {
				$page_links[] = sprintf( '<li><a class="%s" href="%s">%s</a></li>',
					'prev-page',
					esc_url( add_query_arg( 'page', max( 1, $current-1 ), $current_url ) ),
					'&lsaquo;'
				);
			}

			$start_from    = 1;
			if ( $current > $pages_to_show ) {
				$diff          = (int) ( $current / $pages_to_show );
				$start_from    = $pages_to_show * $diff;
				$pages_to_show = $start_from + $pages_to_show;
			}

			for ( $i = $start_from; $i <= $pages_to_show; $i++ ) {

				if ( $i != $current )
					$page_links[] = sprintf( '<li><a class="%s" href="%s">%s</a></li>',
						'mycred-nav',
						esc_url( add_query_arg( 'page', $i, $current_url ) ),
						$i
					);

				else
					$page_links[] = '<li class="active"><span class="current">' . $current . '</span></li>';

			}

			if ( $current == $total_pages )
				$page_links[] = '<li><span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span></li>';
			else {
				$page_links[] = sprintf( '<li><a class="%s" href="%s">%s</a></li>',
					'next-page' . $disable_last,
					esc_url( add_query_arg( 'page', min( $total_pages, $current+1 ), $current_url ) ),
					'&rsaquo;'
				);
			}

			if ( $current == $total_pages )
				$page_links[] = '<li><span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span></li>';
			else {
				$page_links[] = sprintf( '<li><a class="%s" href="%s">%s</a></li>',
					'last-page' . $disable_last,
					esc_url( add_query_arg( 'page', $total_pages, $current_url ) ),
					'&raquo;'
				);
			}

			echo '<nav><ul class="pagination">' . implode( '', $page_links ) . '</ul></nav>';

		}

		/**
		 * Pagination
		 * @since 1.4
		 * @version 1.1
		 */
		public function pagination( $location = 'top', $id = '' ) {

			$page_links         = array();
			$output             = '';
			$total_pages        = $this->max_num_pages;
			$current            = $this->get_pagenum();
			$current_url        = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

			if ( ! is_admin() )
				$current_url = str_replace( '/page/' . $current . '/', '/', $current_url );

			$current_url        = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

			if ( $this->have_entries() )
				$output = '<span class="displaying-num">' . sprintf( _n( '1 entry', '%d entries', $this->num_rows, 'mycred' ), $this->num_rows ) . '</span>';

			$current_url        = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			$current_url        = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );
	
			$total_pages_before = '<span class="paging-input">';
			$total_pages_after  = '</span>';
	
			$disable_first = $disable_last = $disable_prev = $disable_next = false;
	
	 		if ( $current == 1 ) {
				$disable_first = true;
				$disable_prev  = true;
	 		}
			if ( $current == 2 ) {
				$disable_first = true;
			}
	 		if ( $current == $total_pages ) {
				$disable_last = true;
				$disable_next = true;
	 		}
			if ( $current == $total_pages - 1 ) {
				$disable_last = true;
			}
	
			if ( $disable_first ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( remove_query_arg( 'paged', $current_url ) ),
					__( 'First page' ),
					'&laquo;'
				);
			}
	
			if ( $disable_prev ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
					__( 'Previous page' ),
					'&lsaquo;'
				);
			}
	
			if ( 'bottom' === $location ) {
				$html_current_page  = $current;
				$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input">';
			} else {
				$html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' />",
					'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
					$current,
					strlen( $total_pages )
				);
			}
			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
			$page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;
	
			if ( $disable_next ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
					__( 'Next page' ),
					'&rsaquo;'
				);
			}
	
			if ( $disable_last ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
					__( 'Last page' ),
					'&raquo;'
				);
			}
	
			$pagination_links_class = 'pagination-links';
			if ( ! empty( $infinite_scroll ) ) {
				$pagination_links_class = ' hide-if-js';
			}
			$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';
	
			if ( $total_pages ) {
				$page_class = $total_pages < 2 ? ' one-page' : '';
			} else {
				$page_class = ' no-pages';
			}
	
			echo $output;

		}

		/**
		 * Display
		 * @since 0.1
		 * @version 1.0
		 */
		public function display() {

			echo $this->get_display();

		}

		/**
		 * Get Display
		 * Generates a table for our results.
		 * @since 0.1
		 * @version 1.1.1
		 */
		public function get_display() {

			$this->table_headers();

			$table_class = 'table table-condensed mycred-table';
			if ( is_admin() )
				$table_class = 'mycred-table wp-list-table widefat fixed striped users';

			$output = '
<div class="table-responsive">
	<table class="' . apply_filters( 'mycred_log_table_classes', $table_class, $this ) . '" cellspacing="0" cellspacing="0">
		<thead>
			<tr>';

			// Table header
			foreach ( $this->headers as $col_id => $col_title ) {

				$class = '';
				if ( $col_id != 'username' && in_array( $col_id, $this->hidden_headers ) )
					$class = ' hidden';

				if ( $col_id == 'cb' )
					$output .= '<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">' . __( 'Select all', 'mycred' ) . '</label><input type="checkbox" id="cb-select-all-1" /></td>';

				else
					$output .= '<th scope="col" id="' . $col_id . '" class="manage-column' . ( ( $col_id == 'username' ) ? ' column-primary' : '' ) . ' column-' . $col_id . $class . '">' . $col_title . '</th>';

			}

			$output .= '
			</tr>
		</thead>
		<tfoot>
			<tr>';

			// Table footer
			foreach ( $this->headers as $col_id => $col_title ) {

				$class = '';
				if ( $col_id != 'username' && in_array( $col_id, $this->hidden_headers ) )
					$class = ' hidden';

				if ( $col_id == 'cb' )
					$output .= '<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">' . __( 'Select all', 'mycred' ) . '</label><input type="checkbox" id="cb-select-all-2" /></td>';

				else
					$output .= '<th scope="col" class="manage-column' . ( ( $col_id == 'username' ) ? ' column-primary' : '' ) . ' column-' . $col_id . $class . '">' . $col_title . '</th>';

			}

			$output .= '
			</tr>
		</tfoot>
		<tbody id="the-list">';

			// Loop
			if ( $this->have_entries() ) {
				$alt = 0;

				foreach ( $this->results as $log_entry ) {

					$row_class = apply_filters( 'mycred_log_row_classes', array( 'iedit', 'level-0', 'post-' . $log_entry->id, 'type-post', 'status-publish', 'format-standard', 'hentry' ), $log_entry );

					$alt = $alt+1;
					if ( $alt % 2 == 0 )
						$row_class[] = ' alt';

					$output .= '<tr class="' . implode( ' ', $row_class ) . '" id="mycred-log-entry-row-' . $log_entry->id . '">' . $this->get_the_entry( $log_entry ) . '</tr>';

				}

			}
			// No log entry
			else {

				$output .= '<tr><td colspan="' . count( $this->headers ) . '" class="no-entries">' . $this->get_no_entries() . '</td></tr>';

			}

			$output .= '
		</tbody>
	</table>
</div>' . "\n";

			return $output;

		}

		/**
		 * The Entry
		 * @since 0.1
		 * @version 1.1
		 */
		public function the_entry( $log_entry, $wrap = 'td' ) {

			echo $this->get_the_entry( $log_entry, $wrap );

		}

		/**
		 * Get The Entry
		 * Generated a single entry row depending on the columns used / requested.
		 * @filter mycred_log_date
		 * @since 0.1
		 * @version 1.4.1
		 */
		public function get_the_entry( $log_entry, $wrap = 'td' ) {

			$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$entry_data  = '';

			// Run though columns
			foreach ( $this->headers as $column_id => $column_name ) {

				$hidden = '';
				if ( $column_id != 'username' && in_array( $column_id, $this->hidden_headers ) )
					$hidden = ' hidden';

				$content = false;
				$data    = '';

				switch ( $column_id ) {

					// Checkbox column for bulk actions
					case 'cb' :

						$entry_data .= '<th scope="row" class="check-column"><label class="screen-reader-text" for="mycred-log-entry' . $log_entry->id . '">' . __( 'Select entry', 'mycred' ) . '</label><input type="checkbox" name="entry[]" id="mycred-log-entry' . $log_entry->id . '" value="' . $log_entry->id . '" /></th>';

					break;

					// Username Column
					case 'username' :

						$user = get_userdata( $log_entry->user_id );
						$display_name = '<span>' . __( 'User Missing', 'mycred' ) . ' (ID: ' . $log_entry->user_id . ')</span>';
						if ( isset( $user->display_name ) )
							$display_name = $user->display_name;

						if ( ! $this->is_admin )
							$content = '<span>' . $display_name . '</span>';

						else {
							$actions = $this->get_row_actions( $log_entry, $user );
							$content = '<strong>' . $display_name . '</strong>' . $actions;
						}

						if ( $this->is_admin )
							$content .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'mycred' ) . '</span></button>';

						$content = apply_filters( 'mycred_log_username', $content, $log_entry->user_id, $log_entry );

					break;

					// Log Entry Column
					case 'ref' :

						$reference = ucwords( str_replace( array( '-', '_' ), ' ', $log_entry->ref ) );
						if ( array_key_exists( $log_entry->ref, $this->references ) )
							$reference = $this->references[ $log_entry->ref ];

						$content = apply_filters( 'mycred_log_ref', $reference, $log_entry->ref, $log_entry );

					break;

					// Date & Time Column
					case 'time' :

						$content = $time = apply_filters( 'mycred_log_date', date( $date_format, $log_entry->time ), $log_entry->time, $log_entry );
						$content = '<time>' . $content . '</time>';

						if ( $this->is_admin )
							$content .= '<div class="row-actions"><span class="view"><a href="' . add_query_arg( array( 'page' => $_REQUEST['page'], 'time' => $this->get_time_for_filter( $log_entry->time ) ), admin_url( 'admin.php' ) ) . '">' . __( 'Filter by Date', 'mycred' ) . '</a></span></div>';

					break;

					// Amount Column
					case 'creds' :

						$content = $creds = $this->core->format_creds( $log_entry->creds );
						$content = apply_filters( 'mycred_log_creds', $content, $log_entry->creds, $log_entry );
						$data    = ' data-raw="' . esc_attr( $log_entry->creds ) . '"';

					break;

					// Log Entry Column
					case 'entry' :

						$content = $this->core->parse_template_tags( $log_entry->entry, $log_entry );
						$content = apply_filters( 'mycred_log_entry', $content, $log_entry->entry, $log_entry );
						$data    = ' data-raw="' . esc_attr( $log_entry->entry ) . '"';

					break;

					// Let others play
					default :

						$content = apply_filters( 'mycred_log_' . $column_id, false, $log_entry );

					break;

				}

				if ( $content !== false )
					$entry_data .= '<' . $wrap . ' class="' . ( ( $column_id == 'username' ) ? 'column-primary ' : '' ) . 'column-' . $column_id . $hidden . '"' . $data . '>' . $content . '</' . $wrap . '>';

			}

			return $entry_data;

		}

		/**
		 * Row Actions
		 * @since 1.7
		 * @version 1.0
		 */
		public function get_row_actions( $entry, $user ) {

			if ( ! $this->is_admin ) return;

			$filter_label = __( 'Filter by User', 'mycred' );
			if ( $user === false )
				$filter_label = __( 'Filter by ID', 'mycred' );

			$actions = array();

			if ( ! isset( $_REQUEST['user'] ) || $_REQUEST['user'] == '' )
				$actions['view']   = '<a href="' . add_query_arg( array( 'page' => $_REQUEST['page'], 'user' => $entry->user_id ), admin_url( 'admin.php' ) ) . '">' . $filter_label . '</a>';

			$actions['edit']   = '<a href="javascript:void(0);" class="mycred-open-log-entry-editor" data-id="' . $entry->id . '" data-ref="' . $entry->ref . '">' . __( 'Edit', 'mycred' ) . '</a>';
			$actions['delete'] = '<a href="javascript:void(0);" class="mycred-delete-row" data-id="' . $entry->id . '">' . __( 'Delete', 'mycred' ) . '</a>';

			if ( ! empty( $actions ) ) {

				$output  = '';
				$counter = 0;
				$count   = count( $actions );
				foreach ( $actions as $id => $link ) {

					$end = ' | ';
					if ( $counter+1 == $count )
						$end = '';

						$output .= '<span class="' . $id . '">' . $link . $end . '</span>';
						$counter ++;

				}

				return '<div class="row-actions">' . $output . '</div>';

			}

		}

		/**
		 * Exporter
		 * Displays all available export options.
		 * @since 0.1
		 * @version 1.1
		 */
		public function exporter( $title = '', $is_profile = false ) {

			// Must be logged in
			if ( ! is_user_logged_in() ) return;

			// Export options
			$exports     = mycred_get_log_exports();
			$search_args = mycred_get_search_args();

			if ( array_key_exists( 'user', $exports ) && $this->args['user_id'] === NULL )
				unset( $exports['user'] );

?>
<div style="display:none;" class="clear" id="export-log-history">
	<?php if ( ! empty( $title ) ) : ?><h3 class="group-title"><?php echo $title; ?></h3><?php endif; ?>
<?php

			if ( ! empty( $exports ) ) {

				foreach ( (array) $exports as $id => $data ) {

					// Label
					if ( $is_profile )
						$label = $data['my_label'];
					else
						$label = $data['label'];

					$url = mycred_get_export_url( $id );
					if ( $url === false ) continue;

					echo '<a href="" class="' . $data['class'] . '">' . $label . '</a> ';

				}

?>
	<p><span class="description"><?php _e( 'Log entries are exported to a CSV file and depending on the number of entries selected, the process may take a few seconds.', 'mycred' ); ?></span></p>
<?php

			}

			else {

				echo '<p>' . __( 'No export options available.', 'mycred' ) . '</p>';

			}

?>
</div>
<script type="text/javascript">
jQuery(function($) {
	$( '.toggle-exporter' ).click(function(){
		$( '#export-log-history' ).toggle();
	});
});
</script>
<?php

		}

		/**
		 * Log Search
		 * @since 0.1
		 * @version 1.0.4
		 */
		public function search() {

			if ( isset( $_GET['s'] ) && $_GET['s'] != '' )
				$serarch_string = $_GET['s'];
			else
				$serarch_string = '';

?>
<p class="search-box">
	<label class="screen-reader-text"><?php _e( 'Search Log', 'mycred' ); ?>:</label>
	<input type="search" name="s" value="<?php echo esc_attr( $serarch_string ); ?>" placeholder="<?php _e( 'search log entries', 'mycred' ); ?>" />
	<input type="submit" id="search-submit" class="button button-medium button-secondary" value="<?php _e( 'Search Log', 'mycred' ); ?>" />
</p>
<?php

		}

		/**
		 * Filter by Dates
		 * @since 0.1
		 * @version 1.0
		 */
		public function filter_dates( $url = '' ) {

			$date_sorting = apply_filters( 'mycred_sort_by_time', array(
				''          => __( 'All', 'mycred' ),
				'today'     => __( 'Today', 'mycred' ),
				'yesterday' => __( 'Yesterday', 'mycred' ),
				'thisweek'  => __( 'This Week', 'mycred' ),
				'thismonth' => __( 'This Month', 'mycred' )
			) );

			if ( ! empty( $date_sorting ) ) {

				$total = count( $date_sorting );
				$count = 0;

				echo '<ul class="subsubsub">';

				foreach ( $date_sorting as $sorting_id => $sorting_name ) {

					$count = $count+1;

					echo '<li class="' . $sorting_id . '"><a href="';

					// Build Query Args
					$url_args = array();
					if ( isset( $_GET['user_id'] ) && $_GET['user_id'] != '' )
						$url_args['user_id'] = $_GET['user_id'];

					if ( isset( $_GET['ref'] ) && $_GET['ref'] != '' )
						$url_args['ref'] = $_GET['ref'];

					if ( isset( $_GET['order'] ) && $_GET['order'] != '' )
						$url_args['order'] = $_GET['order'];

					if ( isset( $_GET['s'] ) && $_GET['s'] != '' )
						$url_args['s'] = $_GET['s'];

					if ( $sorting_id != '' )
						$url_args['show'] = $sorting_id;

					// Build URL
					if ( ! empty( $url_args ) )
						echo esc_url( add_query_arg( $url_args, $url ) );

					else
						echo esc_url( $url );

					echo '"';

					if ( isset( $_GET['show'] ) && $_GET['show'] == $sorting_id ) echo ' class="current"';
					elseif ( ! isset( $_GET['show'] ) && $sorting_id == '' ) echo ' class="current"';

					echo '>' . $sorting_name . '</a>';
					if ( $count != $total ) echo ' | ';
					echo '</li>';

				}
				echo '</ul>';

			}

		}

		/**
		 * Get References
		 * Returns all available references in the database.
		 * @since 0.1
		 * @version 1.1
		 */
		protected function get_refs( $req = array() ) {

			$refs = mycred_get_used_references( $this->args['ctype'] );

			foreach ( $refs as $i => $ref ) {
				if ( ! empty( $req ) && ! in_array( $ref, $req ) )
					unset( $refs[ $i ] );
			}
			$refs = array_values( $refs );

			return apply_filters( 'mycred_log_get_refs', $refs );

		}

		/**
		 * Get Time from Filter
		 * @since 0.1
		 * @version 1.0
		 */
		protected function get_time_for_filter( $timestamp ) {

			$start = strtotime( date( 'Y-m-d 00:00:00', $timestamp ) );
			$end   = $start + ( DAY_IN_SECONDS - 1 );

			return $start . ',' . $end;

		}

		/**
		 * Get User ID
		 * Converts username, email or userlogin into an ID if possible
		 * @since 1.6.3
		 * @version 1.0
		 */
		protected function get_user_id( $string = '' ) {

			if ( ! is_numeric( $string ) ) {

				$user = get_user_by( 'login', $string );
				if ( ! isset( $user->ID ) ) {

					$user = get_user_by( 'email', $string );
					if ( ! isset( $user->ID ) ) {
						$user = get_user_by( 'slug', $string );
						if ( ! isset( $user->ID ) )
							return false;
					}

				}
				return absint( $user->ID );

			}

			return $string;

		}

		/**
		 * Get Timestamp
		 * @since 1.7
		 * @version 1.0
		 */
		protected function get_timestamp( $string = '' ) {

			if ( is_numeric( $string ) && strtotime( date( 'd-m-Y H:i:s', $string ) ) === (int) $string )
				return $string;

			$timestamp = strtotime( $string, current_time( 'timestamp' ) );

			if ( $timestamp <= 0 )
				$timestamp = false;

			return $timestamp;

		}

		/**
		 * Reset Query
		 * @since 1.3
		 * @version 1.0
		 */
		public function reset_query() {

			$this->args          = NULL;
			$this->request       = NULL;
			$this->prep          = NULL;
			$this->num_rows      = NULL;
			$this->max_num_pages = NULL;
			$this->total_rows    = NULL;
			$this->results       = NULL;
			$this->headers       = NULL;

		}

	}
endif;

/**
 * Get Total Points by Time
 * Counts the total amount of points that has been entered into the log between
 * two given UNIX timestamps. Optionally you can restrict counting to a specific user
 * or specific reference (or both).
 *
 * Will return false if the time stamps are incorrectly formated same for user id (must be int).
 * If you do not want to filter by reference pass NULL and not an empty string or this function will
 * return false. Same goes for the user id!
 *
 * @param $from (int|string) UNIX timestamp from when to start counting. The string 'today' can also
 * be used to start counting from the start of today.
 * @param $to (int|string) UNIX timestamp for when to stop counting. The string 'now' can also be used
 * to count up until now.
 * @param $ref (string) reference to filter by.
 * @param $user_id (int|NULL) user id to filter by.
 * @param $type (string) point type to filer by.
 * @returns total points (int|float) or error message (string)
 * @since 1.1.1
 * @version 1.4.1
 */
if ( ! function_exists( 'mycred_get_total_by_time' ) ) :
	function mycred_get_total_by_time( $from = 'today', $to = 'now', $ref = NULL, $user_id = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( ! MYCRED_ENABLE_LOGGING ) return 0;

		global $wpdb;

		// Prep
		$mycred = mycred( $type );
		$wheres = array();
		$now    = current_time( 'timestamp' );

		// Reference
		if ( $ref !== NULL && strlen( $ref ) > 0 )
			$wheres[] = $wpdb->prepare( 'ref = %s', $ref );

		// User
		if ( $user_id !== NULL && strlen( $user_id ) > 0 ) {

			// No use to run a calculation if the user is excluded
			if ( $mycred->exclude_user( $user_id ) ) return 0;

			$wheres[] = $wpdb->prepare( 'user_id = %d', $user_id );

		}

		// Default from start of today
		if ( $from == 'today' )
			$from  = strtotime( 'today midnight', $now );

		// From
		else {

			$_from = strtotime( $from, $now );
			if ( $_from === false || $_from < 0 ) return 'Invalid Time ($from)';

			$from = $_from;

		}

		if ( is_numeric( $from ) )
			$wheres[] = $wpdb->prepare( 'time >= %d', $from );

		// Until
		if ( $to == 'now' )
			$to = $now;

		else {

			$_to = strtotime( $to );
			if ( $_to === false || $_to < 0 ) return 'Invalid Time ($to)';

			$to = $_to;

		}

		if ( is_numeric( $to ) )
			$wheres[] = $wpdb->prepare( 'time <= %d', $to );

		if ( mycred_point_type_exists( $type ) )
			$wheres[] = $wpdb->prepare( 'ctype = %s', $type );

		// Construct
		$where = implode( ' AND ', $wheres );

		// Query
		$query = $wpdb->get_var( "
			SELECT SUM( creds ) 
			FROM {$mycred->log_table} 
			WHERE {$where} 
			ORDER BY time;" );

		if ( $query === NULL || $query == 0 )
			return $mycred->zero();

		return $mycred->number( $query );

	}
endif;

/**
 * Get users total creds
 * Returns the users total creds unformated. If no total is fuond,
 * the users current balance is returned instead.
 *
 * @param $user_id (int), required user id
 * @param $type (string), optional cred type to check for
 * @returns zero if user id is not set or if no total were found, else returns creds
 * @since 1.2
 * @version 1.3.1
 */
if ( ! function_exists( 'mycred_get_users_total' ) ) :
	function mycred_get_users_total( $user_id = '', $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id == '' ) return 0;

		$mycred = mycred( $type );
		$total  = mycred_get_user_meta( $user_id, $type, '_total' );

		if ( $total == '' ) {
			$total = mycred_query_users_total( $user_id, $type );
			mycred_update_user_meta( $user_id, $type, '_total', $total );
		}

		$total = apply_filters( 'mycred_get_users_total', $total, $user_id, $type );
		return $mycred->number( $total );

	}
endif;

/**
 * Query Users Total
 * Queries the database for the users total acculimated points.
 *
 * @param $user_id (int), required user id
 * @param $type (string), required point type
 * @since 1.4.7
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_query_users_total' ) ) :
	function mycred_query_users_total( $user_id, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( ! MYCRED_ENABLE_LOGGING ) return 0;

		global $wpdb;

		$mycred = mycred( $type );

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( creds ) 
			FROM {$mycred->log_table} 
			WHERE user_id = %d
				AND ( ( creds > 0 ) OR ( creds < 0 AND ref = 'manual' ) )
				AND ctype = %s;", $user_id, mycred_get_meta_key( $type ) ) );

		if ( $total === NULL ) {

			$total = $wpdb->get_var( $wpdb->prepare( "
				SELECT meta_value 
				FROM {$wpdb->usermeta} 
				WHERE user_id = %d 
					AND meta_key = %s;", $user_id, mycred_get_meta_key( $type ) ) );

			if ( $total === NULL )
				$total = 0;

		}

		return apply_filters( 'mycred_query_users_total', $total, $user_id, $type, $mycred );

	}
endif;

/**
 * Get All References
 * Returns an array of references currently existing in the log
 * for a particular point type. Will return false if empty.
 * @since 1.5
 * @version 1.3
 */
if ( ! function_exists( 'mycred_get_all_references' ) ) :
	function mycred_get_all_references() {

		// Hooks
		$hooks = array(
			'registration'        => __( 'Website Registration', 'mycred' ),
			'site_visit'          => __( 'Website Visit', 'mycred' ),
			'view_content'        => __( 'Viewing Content (Member)', 'mycred' ),
			'view_content_author' => __( 'Viewing Content (Author)', 'mycred' ),
			'logging_in'          => __( 'Logging in', 'mycred' ),
			'publishing_content'  => __( 'Publishing Content', 'mycred' ),
			'approved_comment'    => __( 'Approved Comment', 'mycred' ),
			'unapproved_comment'  => __( 'Unapproved Comment', 'mycred' ),
			'spam_comment'        => __( 'SPAM Comment', 'mycred' ),
			'deleted_comment'     => __( 'Deleted Comment', 'mycred' ),
			'link_click'          => __( 'Link Click', 'mycred' ),
			'watching_video'      => __( 'Watching Video', 'mycred' ),
			'visitor_referral'    => __( 'Visitor Referral', 'mycred' ),
			'signup_referral'     => __( 'Signup Referral', 'mycred' )
		);

		if ( class_exists( 'BuddyPress' ) ) {
			$hooks['new_profile_update']     = __( 'New Profile Update', 'mycred' );
			$hooks['deleted_profile_update'] = __( 'Profile Update Removal', 'mycred' );
			$hooks['upload_avatar']          = __( 'Avatar Upload', 'mycred' );
			$hooks['upload_cover']           = __( 'Profile Cover Upload', 'mycred' );
			$hooks['new_friendship']         = __( 'New Friendship', 'mycred' );
			$hooks['ended_friendship']       = __( 'Ended Friendship', 'mycred' );
			$hooks['new_comment']            = __( 'New Profile Comment', 'mycred' );
			$hooks['comment_deletion']       = __( 'Profile Comment Deletion', 'mycred' );
			$hooks['fave_activity']          = __( 'Add Activity to Favorites', 'mycred' );
			$hooks['unfave_activity']        = __( 'Remove Activity from Favorites', 'mycred' );
			$hooks['new_message']            = __( 'New Message', 'mycred' );
			$hooks['sending_gift']           = __( 'Sending Gift', 'mycred' );
			$hooks['creation_of_new_group']  = __( 'New Group', 'mycred' );
			$hooks['deletion_of_group']      = __( 'Deleted Group', 'mycred' );
			$hooks['new_group_forum_topic']  = __( 'New Group Forum Topic', 'mycred' );
			$hooks['edit_group_forum_topic'] = __( 'Edit Group Forum Topic', 'mycred' );
			$hooks['new_group_forum_post']   = __( 'New Group Forum Post', 'mycred' );
			$hooks['edit_group_forum_post']  = __( 'Edit Group Forum Post', 'mycred' );
			$hooks['joining_group']          = __( 'Joining Group', 'mycred' );
			$hooks['leaving_group']          = __( 'Leaving Group', 'mycred' );
			$hooks['upload_group_avatar']    = __( 'New Group Avatar', 'mycred' );
			$hooks['upload_group_cover']     = __( 'New Group Cover', 'mycred' );
			$hooks['new_group_comment']      = __( 'New Group Comment', 'mycred' );
		}

		if ( function_exists( 'bpa_init' ) || function_exists( 'bpgpls_init' ) ) {
			$hooks['photo_upload'] = __( 'Photo Upload', 'mycred' );
			$hooks['video_upload'] = __( 'Video Upload', 'mycred' );
			$hooks['music_upload'] = __( 'Music Upload', 'mycred' );
		}

		if ( function_exists( 'bp_links_setup_root_component' ) ) {
			$hooks['new_link']    = __( 'New Link', 'mycred' );
			$hooks['link_voting'] = __( 'Link Voting', 'mycred' );
			$hooks['update_link'] = __( 'Link Update', 'mycred' );
		}

		if ( class_exists( 'bbPress' ) ) {
			$hooks['new_forum'] = __( 'New Forum (bbPress)', 'mycred' );
			$hooks['new_forum_topic'] = __( 'New Forum Topic (bbPress)', 'mycred' );
			$hooks['topic_favorited'] = __( 'Favorited Topic (bbPress)', 'mycred' );
			$hooks['new_forum_reply'] = __( 'New Topic Reply (bbPress)', 'mycred' );
		}

		if ( function_exists( 'wpcf7' ) )
			$hooks['contact_form_submission'] = __( 'Form Submission (Contact Form 7)', 'mycred' );

		if ( class_exists( 'GFForms' ) )
			$hooks['gravity_form_submission'] = __( 'Form Submission (Gravity Form)', 'mycred' );

		if ( defined( 'SFTOPICS' ) ) {
			$hooks['new_forum_topic'] = __( 'New Forum Topic (SimplePress)', 'mycred' );
			$hooks['new_topic_post']  = __( 'New Forum Post (SimplePress)', 'mycred' );
		}

		if ( function_exists( 'install_ShareThis' ) ) {
			$share = mycred_get_share_service_names();
			$hooks = array_merge_recursive( $share, $hooks );
		}

		if ( class_exists( 'Affiliate_WP' ) ) {
			$hooks['affiliate_signup']          = __( 'Affiliate Signup (AffiliateWP)', 'mycred' );
			$hooks['affiliate_visit_referral']  = __( 'Referred Visit (AffiliateWP)', 'mycred' );
			$hooks['affiliate_referral']        = __( 'Affiliate Referral (AffiliateWP)', 'mycred' );
			$hooks['affiliate_referral_refund'] = __( 'Referral Refund (AffiliateWP)', 'mycred' );
		}

		if ( defined( 'WP_POSTRATINGS_VERSION' ) ) {
			$hooks['post_rating']        = __( 'Adding a Rating', 'mycred' );
			$hooks['post_rating_author'] = __( 'Receiving a Rating', 'mycred' );
		}

		if ( function_exists( 'vote_poll' ) )
			$hooks['poll_voting'] = __( 'Poll Voting', 'mycred' );

		if ( function_exists( 'invite_anyone_init' ) ) {
			$hooks['sending_an_invite']   = __( 'Sending an Invite', 'mycred' );
			$hooks['accepting_an_invite'] = __( 'Accepting an Invite', 'mycred' );
		}

		// Addons
		$addons = array();
		if ( class_exists( 'myCRED_Banking_Module' ) ) {
			$addons['interest']  = __( 'Compound Interest', 'mycred' );
			$addons['recurring'] = __( 'Recurring Payout', 'mycred' );
		}

		if ( class_exists( 'myCRED_Badge_Module' ) )
			$hooks['badge_reward'] = __( 'Badge Reward', 'mycred' );

		if ( class_exists( 'myCRED_buyCRED_Module' ) ) {
			$addons['buy_creds_with_paypal_standard'] = __( 'buyCRED Purchase (PayPal Standard)', 'mycred' );
			$addons['buy_creds_with_skrill']          = __( 'buyCRED Purchase (Skrill)', 'mycred' );
			$addons['buy_creds_with_zombaio']         = __( 'buyCRED Purchase (Zombaio)', 'mycred' );
			$addons['buy_creds_with_netbilling']      = __( 'buyCRED Purchase (NETBilling)', 'mycred' );
			$addons['buy_creds_with_bitpay']          = __( 'buyCRED Purchase (BitPay)', 'mycred' );
			$addons = apply_filters( 'mycred_buycred_refs', $addons );
		}

		if ( class_exists( 'myCRED_Coupons_Module' ) )
			$addons['coupon'] = __( 'Coupon Purchase', 'mycred' );

		if ( defined( 'myCRED_GATE' ) ) {
			if ( class_exists( 'WooCommerce' ) ) {
				$addons['woocommerce_payment'] = __( 'Store Purchase (WooCommerce)', 'mycred' );
				$addons['reward']              = __( 'Store Reward (WooCommerce)', 'mycred' );
				$addons['product_review']      = __( 'Product Review (WooCommerce)', 'mycred' );
			}
			if ( class_exists( 'MarketPress' ) ) {
				$addons['marketpress_payment'] = __( 'Store Purchase (MarketPress)', 'mycred' );
				$addons['marketpress_reward']  = __( 'Store Reward (MarketPress)', 'mycred' );
			}
			if ( class_exists( 'wpsc_merchant' ) )
				$addons['wpecom_payment']      = __( 'Store Purchase (WP E-Commerce)', 'mycred' );

			$addons = apply_filters( 'mycred_gateway_refs', $addons );
		}

		if ( defined( 'EVENT_ESPRESSO_VERSION' ) ) {
			$addons['event_payment']   = __( 'Event Payment (Event Espresso)', 'mycred' );
			$addons['event_sale']      = __( 'Event Sale (Event Espresso)', 'mycred' );
		}

		if ( defined( 'EM_VERSION' ) ) {
			$addons['ticket_purchase'] = __( 'Event Payment (Events Manager)', 'mycred' );
			$addons['ticket_sale']     = __( 'Event Sale (Events Manager)', 'mycred' );
		}

		if ( class_exists( 'myCRED_Sell_Content_Module' ) ) {
			$addons['buy_content']  = __( 'Content Purchase', 'mycred' );
			$addons['sell_content'] = __( 'Content Sale', 'mycred' );
		}

		if ( class_exists( 'myCRED_Transfer_Module' ) )
			$addons['transfer'] = __( 'Transfer', 'mycred' );

		$references = array_merge( $hooks, $addons );

		$references['manual'] = __( 'Manual Adjustment by Admin', 'mycred' );

		return apply_filters( 'mycred_all_references', $references );

	}
endif;

/**
 * Get Used References
 * Returns an array of references currently existing in the log
 * for a particular point type. Will return false if empty.
 * @since 1.5
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_used_references' ) ) :
	function mycred_get_used_references( $type = MYCRED_DEFAULT_TYPE_KEY ) {

		$references = wp_cache_get( 'mycred_references' );

		if ( false === $references ) {

			global $wpdb;

			$mycred     = mycred( $type );
			$references = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT ref 
				FROM {$mycred->log_table} 
				WHERE ref != ''
					AND ctype = %s;", $type ) );

			if ( $references ) wp_cache_set( 'mycred_references', $references );

		}

		return apply_filters( 'mycred_used_references', $references );

	}
endif;

/**
 * Get Used Log Entry Count
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_user_has_log_entries' ) ) :
	function mycred_user_has_log_entries( $user_id = NULL ) {

		$user_id = absint( $user_id );
		if ( $user_id === 0 ) return 0;

		$count = mycred_get_user_meta( $user_id, 'mycred-log-count' );
		if ( $count == '' ) {

			global $wpdb, $mycred;

			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$mycred->log_table} WHERE user_id = %d;", $user_id ) );
			if ( $count === NULL ) $count = 0;

			mycred_add_user_meta( $user_id, 'mycred-log-count', '', $count, true );

		}

		return $count;

	}
endif;

/**
 * Count Reference Instances
 * Counts the total number of occurrences of a specific reference for a user.
 * @see http://codex.mycred.me/functions/mycred_count_ref_instances/
 * @param $reference (string) required reference to check
 * @param $user_id (int) option to check references for a specific user
 * @uses get_var()
 * @since 1.1
 * @version 1.0.2
 */
if ( ! function_exists( 'mycred_count_ref_instances' ) ) :
	function mycred_count_ref_instances( $reference = '', $user_id = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( empty( $reference ) ) return 0;

		$mycred = mycred( $type );

		global $wpdb;

		if ( $user_id !== NULL )
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$mycred->log_table} WHERE ref = %s AND user_id = %d AND ctype = %s;", $reference, $user_id, $mycred->cred_id ) );

		else
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$mycred->log_table} WHERE ref = %s AND ctype = %s;", $reference, $mycred->cred_id ) );

		if ( $count === NULL )
			$count = 0;

		return $count;

	}
endif;

/**
 * Count All Reference Instances
 * Counts all the reference instances in the log returning the result
 * in an assosiative array.
 * @see http://codex.mycred.me/functions/mycred_count_all_ref_instances/
 * @param $number (int) number of references to return. Defaults to 5. Use '-1' for all.
 * @param $order (string) order to return ASC or DESC
 * @filter mycred_count_all_refs
 * @since 1.3.3
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_count_all_ref_instances' ) ) :
	function mycred_count_all_ref_instances( $number = 5, $order = 'DESC', $type = MYCRED_DEFAULT_TYPE_KEY ) {

		global $wpdb;

		$results = array();
		$mycred  = mycred( $type );

		$limit = '';
		if ( $number > 0 )
			$limit = ' LIMIT 0,' . absint( $number );

		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) )
			$order = 'DESC';

		if ( $type != 'all' )
			$type = $wpdb->prepare( 'WHERE ctype = %s', $mycred->cred_id );

		else
			$type = '';

		$query = $wpdb->get_results( "SELECT ref, COUNT(*) AS count FROM {$mycred->log_table} {$type} GROUP BY ref ORDER BY count {$order} {$limit};" );

		if ( $wpdb->num_rows > 0 ) {

			foreach ( $query as $num => $reference ) {

				$occurrence = $reference->count;
				if ( $reference->ref == 'transfer' )
					$occurrence = $occurrence/2;

				$results[ $reference->ref ] = $occurrence;

			}

			arsort( $results );

		}

		return apply_filters( 'mycred_count_all_refs', $results );

	}
endif;

/**
 * Count Reference ID Instances
 * Counts the total number of occurrences of a specific reference combined with a reference ID for a user.
 * @see http://codex.mycred.me/functions/mycred_count_ref_id_instances/
 * @param $reference (string) required reference to check
 * @param $user_id (int) option to check references for a specific user
 * @uses get_var()
 * @since 1.5.3
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_count_ref_id_instances' ) ) :
	function mycred_count_ref_id_instances( $reference = '', $ref_id = NULL, $user_id = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $reference == '' || $ref_id == '' ) return 0;

		$mycred = mycred( $type );

		global $wpdb;

		if ( $user_id !== NULL ) {
			$count = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(*) 
				FROM {$mycred->log_table} 
				WHERE ref = %s 
				AND ref_id = %d 
				AND user_id = %d 
				AND ctype = %s;", $reference, $ref_id, $user_id, $mycred->cred_id ) );
		}
		else {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$mycred->log_table} WHERE ref = %s AND ref_id = %d;", $reference, $ref_id ) );
		}

		if ( $count === NULL )
			$count = 0;

		return $count;

	}
endif;

/**
 * Get Users Reference Count
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_users_reference_count' ) ) :
	function mycred_get_users_reference_count( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL ) return false;

		$references = (array) mycred_get_user_meta( $user_id, 'mycred_ref_counts-' . $point_type, '', true );
		$references = maybe_unserialize( $references );

		if ( empty( $references ) ) {

			global $wpdb;

			$query = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(*) AS total, ref AS reference FROM {$mycred->log_table} WHERE user_id = %d AND ctype = %s GROUP BY ref ORDER BY total DESC;", $user_id, $point_type ) );
			if ( ! empty( $query ) ) {
				foreach ( $query as $result ) {
					$references[ $result->reference ] = $result->total;
				}
			}

			mycred_update_user_meta( $user_id, 'mycred_ref_counts-' . $point_type, '', $references );

		}

		return $references;

	}
endif;

/**
 * Get Users Reference Sum
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_users_reference_sum' ) ) :
	function mycred_get_users_reference_sum( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL ) return false;

		$references = (array) mycred_get_user_meta( $user_id, 'mycred_ref_sums-' . $point_type, '', true );
		$references = maybe_unserialize( $references );

		if ( empty( $references ) ) {

			global $wpdb;

			$query = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(creds) AS total, ref AS reference FROM {$mycred->log_table} WHERE user_id = %d AND ctype = %s GROUP BY ref ORDER BY total DESC;", $user_id, $point_type ) );
			if ( ! empty( $query ) ) {
				foreach ( $query as $result ) {
					$references[ $result->reference ] = $result->total;
				}
			}

			mycred_update_user_meta( $user_id, 'mycred_ref_sums-' . $point_type, '', $references );

		}

		return $references;

	}
endif;

/**
 * Get Search Args
 * Converts URL arguments into an array of log query friendly arguments.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_search_args' ) ) :
	function mycred_get_search_args( $exclude = NULL ) {

		if ( $exclude === NULL )
			$exclude = array( 'page', 'mycred-export', 'mycred-action', 'action', 'set', '_token' );

		$search_args = array();
		if ( ! empty( $_GET ) ) {
			foreach ( $_GET as $key => $value ) {

				$key   = sanitize_key( $key );
				
				if ( $key === '' || in_array( $key, array( 'page', 'mycred-export', 'mycred-action', 'action', 'set', '_token' ) ) ) continue;

				if ( in_array( $value, array( 'compare' ) ) )
					$value = urldecode( $value );

				if ( in_array( $key, array( 'user_id', 'paged', 'number' ) ) ) {
					$value = absint( $value );
					if ( $value === 0 ) continue;
				}
				else {
					$value = sanitize_text_field( $value );
					if ( strlen( $value ) == 0 ) continue;
				}

				if ( $key === 'user' )
					$key = 'user_id';

				elseif ( $key === 'show' )
					$key = 'time';

				elseif ( $key === 'show' )
					$key = 'time';

				$search_args[ $key ] = $value;

			}
		}

		if ( ! empty( $search_args ) ) {

			if ( array_key_exists( 'start', $search_args ) && array_key_exists( 'end', $search_args ) ) {
				$search_args['amount'] = array( 'start' => $search_args['start'], 'end' => $search_args['end'] );
				unset( $search_args['start'] );
				unset( $search_args['end'] );
			}

			elseif ( array_key_exists( 'num', $search_args ) && array_key_exists( 'compare', $search_args ) ) {
				$search_args['amount'] = array( 'num' => $search_args['num'], 'compare' => $search_args['compare'] );
				unset( $search_args['num'] );
				unset( $search_args['compare'] );
			}

		}

		return $search_args;

	}
endif;

?>