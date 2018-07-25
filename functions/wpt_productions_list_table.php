<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * List Table for the Productions Admin Page (default view).
 *
 * @since	0.15
 * @extends	WP_List_Table
 * @see		WPT_Productions_Admin
 * @see		https://codex.wordpress.org/Class_Reference/WP_List_Table
 * @see		https://pippinsplugins.com/creating-wp-list-tables-by-hand/
 */
class WPT_Productions_List_Table extends WP_List_Table {

	function __construct() {
		parent::__construct(
			array(
				'singular' => 'production',
				'plural'   => 'productions',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Displays a categories drop-down for filtering on the Events list table.
	 *
	 * @since 0.16
	 */
	function categories_dropdown( ) {

		$dropdown_options = array(
			'show_option_all' => get_taxonomy( 'category' )->labels->all_items,
			'hide_empty'      => 0,
			'hierarchical'    => 1,
			'show_count'      => 0,
			'orderby'         => 'name',
			'selected'        => empty( $_REQUEST['cat'] ) ? '' :  $_REQUEST['cat'],
		);
		echo '<label class="screen-reader-text" for="cat">' . __( 'Filter by category' ) . '</label>';
		wp_dropdown_categories( $dropdown_options );
		
	}

	/**
	 * Displays a dates drop-down for filtering on the Events list table.
	 *
	 * @since 0.16
	 */
	function dates_dropdown( ) {

		$options = array (
			'0' => __( 'All dates' ),
			'upcoming' => __( 'Upcoming events', 'wdcdc' ),
			'past' => __( 'Past events', 'wdcdc' ),			
		);

		$date = false;
		if ( !empty( $_REQUEST['date'] ) ) {
			$date = $_REQUEST['date'];
		}

		?><label class="screen-reader-text" for="date"><?php
			_e( 'Filter by date', 'wp_theatre' ); 
		?></label>
		<select id="date" name="date"><?php
			foreach( $options as $key => $value ) {
				?><option value="<?php echo $key; ?>" <?php selected( $date, $key, true );?>><?php 
					echo $value;
				?></option><?php				
			}
		?></select><?php
					
	}

	/**
	 * Gets the HTML of the production categories for use in the category column.
	 *
	 * @since	0.15
	 * @param 	WPT_Production	$production	The Production.
	 * @return	string						The HTML of the production categories.
	 */
	function column_categories( $production ) {
		$args = array(
			'html' => true,
		);
		return $production->categories( $args );
	}

	/**
	 * Gets the HTML for the checkbox in the bulk actions column.
	 *
	 * @since	0.15
	 * @param 	WPT_Production	$production	The Production.
	 * @return 	string						The HTML for the checkbox.
	 */
	function column_cb( $production ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$production->ID
		);
	}
	
	/**
	 * Handles the default column output.
	 *
	 * @since 0.16
	 *
	 * @param WP_Post $post        The current WP_Post object.
	 * @param string  $column_name The current column name.
	 */
	 function column_default( $post, $column_name ) {
		/**
		 * Fires for each custom column of a specific post type in the Posts list table.
		 *
		 * @since 0.16
		 *
		 * @param string $column_name The name of the column to display.
		 * @param int    $post_id     The current post ID.
		 */
		 do_action( 'manage_'.WPT_Production::post_type_name.'_posts_custom_column', $column_name, $post->ID );
	}

	/**
	 * Gets the HTML of the production title and action for use in the title column.
	 *
	 * @since	0.15
	 * @param 	WPT_Production	$production	The Production.
	 * @return	string						The HTML of the production title and actions.
	 */
	function column_title( $production ) {

	    // Define the actions for this production.
		if ( 'trash' == get_post_status( $production->ID ) ) {
	        $restore_url = add_query_arg( 'action', 'untrash', get_edit_post_link( $production->ID ) );
	        $restore_url = wp_nonce_url( $restore_url, 'untrash-post_'.$production->ID );
	        $actions = array(
		        'restore' 	=> '<a href="'.$restore_url.'">'.__( 'Restore' ).'</a>',
				'delete' => '<a href="'.get_delete_post_link( $production->ID, '', true ).'">'.__( 'Delete Permanently' ).'</a>',
			);
		} else {
	        $actions = array(
		        'edit' 	=> '<a href="'.get_edit_post_link( $production->ID ).'">'.__( 'Edit' ).'</a>',
				'delete' => '<a href="'.get_delete_post_link( $production->ID, '', false ).'">'.__( 'Trash' ).'</a>',
				'view' => '<a href="'.get_permalink( $production->ID ).'">'.__( 'View' ).'</a>',
			);
		}

		// Set the production template.
		$template = '{{thumbnail}}{{title}}{{dates}}{{cities}}';

		return sprintf('%1$s %2$s',
			$production->html( $template ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Outputs extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since	0.15.4
	 * @since	0.15.17 		Added two filters to add extra controls.
	 * @since	0.16			Added dates and categories filters.
	 * @param 	string	$which	Location of table nav ('top' or 'bottom').
	 */
	function extra_tablenav( $which ) {
		
		?><div class="alignleft actions"><?php
			
	        if ( 'top' === $which && !is_singular() ) {
		        
	            ob_start();
	            
	            $this->dates_dropdown();
	            $this->categories_dropdown();
	            
	            /**
	             * Fires before the Filter button on the Productions list table.
	             *
	             * Syntax resembles 'restrict_manage_posts' filter in 'wp-admin/includes/class-wp-posts-list-table.php'.
	             *
	             * @since 0.15.17
	             *
	             * @param string $post_type The post type slug.
	             * @param string $which     The location of the extra table nav markup:
	             *                          'top' or 'bottom'.
	             */
	            do_action( 'restrict_manage_productions', $this->screen->post_type, $which );
	 
	            $output = ob_get_clean();
	 
	            if ( ! empty( $output ) ) {
	                echo $output;
	                submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
	            }
	            
	        }
        
        	if ( isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] === 'trash' ) {
				submit_button( __( 'Empty Trash' ), 'apply', 'delete_all', false );
			}
			
		?></div><?php
		
        /**
         * Fires immediately following the closing "actions" div in the tablenav for the productions
         * list table.
         *
         * Syntax resembles 'manage_posts_extra_tablenav' in 'wp-admin/includes/class-wp-posts-list-table.php'.
         *
         * @since 0.15.17
         *
         * @param 	string 	$which 	The location of the extra table nav markup: 'top' or 'bottom'.
         */
        do_action( 'manage_productions_extra_tablenav', $which );		
	}

	/**
	 * Gets the bulk actions for the productions.
	 *
	 * @since	0.15
	 * @return	array	The bulk actions.
	 */
	function get_bulk_actions() {

		if ( ! empty( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] ) {
			$actions = array(
				'restore' => __( 'Restore' ),
				'delete' => __( 'Delete Permanently' ),
			);
		} else {
			$actions = array(
				'publish' => __( 'Publish' ),
				'draft' => __( 'Save as Draft', 'theatre' ),
				'trash' => __( 'Move to Trash' ),
			);
		}

		return $actions;
	}


	/**
	 * Gets the columns for the list table.
	 *
	 * @since	0.15
	 * @return	array	The columns.
	 */
	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Event', 'theatre' ),
			'categories' => __( 'Categories', 'theatre' ),
		);
		
		/**
		 * Filters the columns displayed in the Events list table.
		 *
		 * @since 0.16
		 *
		 * @param string[] $post_columns An associative array of column headings.
		 */
		return apply_filters( 'manage_'.WPT_Production::post_type_name.'_posts_columns', $columns );
	}

	/**
	 * Gets the sortable columns for the list table.
	 *
	 * @since	0.15
	 * @return	array	The sortable columns.
	 */
	function get_sortable_columns() {
		$sortable_columns = array(
			'title' => array( 'title',false ),
		);

		/**
		 * Filters the sortable columns displayed in the Events list table.
		 *
		 * @since 0.16
		 *
		 * @param string[] $post_columns An associative array of column headings.
		 */
		return apply_filters( 'manage_'.WPT_Production::post_type_name.'_sortable_columns', $sortable_columns );
	}
	
	function get_productions_args_from_dates_dropdown() {
		
		if( empty( $_REQUEST['date'] ) ) {
			return false;
		}
		
		switch ( $_REQUEST['date'] ) {
		
			case 'upcoming' :
				$args = array(
					'end_after' => 'now',	
				);
				break;
			case 'past';
				$args = array(
					'end_before' => 'now',	
				);
				break;
			default:
				$args = false;
		}
		
		return $args;
	}

	/**
	 * Gets the views for the list table.
	 *
	 * @since	0.15
	 * @since	0.15.4	Removed the 'paged' query var when switching views.
	 * @return	array	The views.
	 */
	function get_views() {
	    $num_posts = wp_count_posts( WPT_Production::post_type_name, 'readable' );

	    $views = array();

		$view_url = add_query_arg( 'post_status', '' );
		$view_url = remove_query_arg( 'paged', $view_url );

		ob_start();
		?><a href="<?php echo $view_url;?>" <?php if ( empty( $_REQUEST['post_status'] ) ) { ?> class="current"<?php } ?>><?php _e( 'All' ); ?></a><?php
	    $views['all'] = ob_get_clean();

		$views_available = array(
			'publish' => __( 'Published' ),
			'draft' => __( 'Draft' ),
			'trash' => __( 'Trash' ),
		);

	    foreach ( $views_available as $key => $val ) {
		    if ( ! empty( $num_posts->{$key} ) ) {

				$view_url = add_query_arg( 'post_status', $key );
				$view_url = remove_query_arg( 'paged', $view_url );

				ob_start();
				?><a href="<?php echo $view_url;?>" <?php if ( ! empty( $_REQUEST['post_status'] ) && $key == $_REQUEST['post_status'] ) { ?> class="current"<?php } ?>><?php	echo $val;
						?><span class="count"> (<?php echo $num_posts->{$key};?>)</span>
				</a><?php
			    $views[ $key ] = ob_get_clean();
		    }
	    }
	    return $views;
	}

	/**
	 * Outputs the message to be displayed when there are no events.
	 *
	 * @since	0.15
	 * @return void
	 */
	function no_items() {
		ob_start();

		?><p><?php
			_e( 'No events found.', 'theatre' );
		?></p><?php
if ( empty( $_REQUEST['s'] ) ) {
	?><p><?php
		printf(
			__( '<a href="%s">Add an event.</a>', 'theatre' ),
			admin_url( 'post-new.php?post_type='.WPT_Production::post_type_name )
		);
	?></p><?php
}
	}

	/**
	 * Queries and filters the productions, handles sorting, and pagination, and any
	 * other data-manipulation required prior to rendering.
	 *
	 * @since	0.15
	 * @since	0.15.2	Added a context for the productions.
	 * @since	0.15.21	Items per page now uses WP_List_Table::get_items_per_page() from WordPress core.
	 * @since	0.16	Added dates and categories filters.
	 * @return 	void
	 */
	function prepare_items() {
	    global $wp_theatre;

	    $per_page = $this->get_items_per_page( 'edit_' . WPT_Production::post_type_name . '_per_page' );

	    $columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

	    $production_args = array(
		    'context' => 'productions_list_table',
		    'status' => 'any',
	    );

	    if ( ! empty( $_REQUEST['post_status'] ) ) {
		    $production_args['status'] = array( $_REQUEST['post_status'] );
	    }

	    if ( ! empty( $_REQUEST['s'] ) ) {
		    $production_args['s'] = sanitize_text_field( $_REQUEST['s'] );
	    }

	    if ( ! empty( $_REQUEST['cat'] ) ) {
		    $production_args['cat'] = sanitize_text_field( $_REQUEST['cat'] );
	    }

		if ( $date_args = self::get_productions_args_from_dates_dropdown() ) {
			$production_args = array_merge( $production_args, $date_args );
		}

		$productions = $wp_theatre->productions->get( $production_args );

		// Sort the productions.
	    if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) && 'title' == $_REQUEST['orderby'] ) {
		    if ( 'desc' == $_REQUEST['order'] ) {
			    usort( $productions, array( $this, 'sort_productions_desc' ) );
		    } else {
			    usort( $productions, array( $this, 'sort_productions_asc' ) );
		    }
	    }

	    $current_page = $this->get_pagenum();

		$total_items = count( $productions );

		$productions = array_slice( $productions,(($current_page -1) * $per_page),$per_page );

		$this->items = $productions;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

	}

	/**
	 * Sorts two productions alphabetically in ascending order.
	 *
	 * Used as a callback for usort in @see WPT_Productions_List_Table::prepare_items().
	 *
	 * @since	0.15
	 * @param 	WPT_Production	$production_a
	 * @param 	WPT_Production	$production_b
	 * @return 	int
	 */
	private function sort_productions_asc( $production_a, $production_b ) {
		return strcmp( $production_a->title(), $production_b->title() );
	}

	/**
	 * Sorts two productions alphabetically in descending order.
	 *
	 * Used as a callback for usort in @see WPT_Productions_List_Table::prepare_items().
	 *
	 * @since	0.15
	 * @param 	WPT_Production	$production_a
	 * @param 	WPT_Production	$production_b
	 * @return 	int
	 */
	private function sort_productions_desc( $production_a, $production_b ) {
		return strcmp( $production_b->title(), $production_a->title() );
	}
}
