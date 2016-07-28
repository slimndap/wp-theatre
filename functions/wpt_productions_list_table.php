<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * List Table for the Productions Admin Page (default view).
 *
 * @since	0.15
 *
 * @see		WPT_Productions_Admin
 * @see		https://codex.wordpress.org/Class_Reference/WP_List_Table
 * @see		https://pippinsplugins.com/creating-wp-list-tables-by-hand/
 *
 * @package	Theater/Productions
 * @group	Admin
 * @internal
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
	 * @param 	string	$which	 Location of table nav ('top' or 'bottom').
	 */
	function extra_tablenav( $which ) {
		if ( isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] === 'trash' ) {
			submit_button( __( 'Empty Trash' ), 'apply', 'delete_all', false );
		}
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
		return $columns;
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
		return $sortable_columns;
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
	 * @return 	void
	 */
	function prepare_items() {
	    global $wp_theatre;

	    $per_page = 20;

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
