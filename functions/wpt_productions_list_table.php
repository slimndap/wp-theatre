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
		$production_args = array(
	    	'template' => '{{thumbnail}}{{title}}{{dates}}{{cities}}',
		);

		return sprintf('%1$s %2$s',
			$production->html( $production_args ),
			$this->row_actions( $actions )
		);
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
	 * @return	array	The views.
	 */
	function get_views() {
	    $num_posts = wp_count_posts( WPT_Production::post_type_name, 'readable' );

	    $views = array();

		ob_start();
		?><a href="<?php echo add_query_arg( 'post_status', '' );?>" <?php
if ( empty( $_REQUEST['post_status'] ) ) {
	?> class="current"<?php
}
		?>><?php _e( 'All' ); ?></a><?php
	    $views['all'] = ob_get_clean();

		$views_available = array(
			'publish' => __( 'Published' ),
			'draft' => __( 'Draft' ),
			'trash' => __( 'Trash' ),
		);

	    foreach ( $views_available as $key => $val ) {
		    if ( ! empty( $num_posts->{$key} ) ) {
				ob_start();
				?><a href="<?php echo add_query_arg( 'post_status', $key );?>" <?php
if ( ! empty( $_REQUEST['post_status'] ) && $key == $_REQUEST['post_status'] ) {
	?> class="current"<?php
}
				?>><?php echo $val; ?></a><?php
			    $views[ $key ] = ob_get_clean();
		    }
	    }
	    return $views;
	}

	/**
	 * Queries and filters the productions, handles sorting, and pagination, and any
	 * other data-manipulation required prior to rendering.
	 *
	 * @since	0.15
	 * @return 	void
	 */
	function prepare_items() {
	    global $wp_theatre;

	    $per_page = 10;

	    $columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Process bulk actions.
		$this->process_bulk_actions();

	    $production_args = array();

	    $production_args['status'] = 'any';

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
			    usort( $productions, function( $a, $b ) {
				    return strcmp( $b->title(), $a->title() );
			    });
		    } else {
			    usort( $productions, function( $a, $b ) {
				    return strcmp( $a->title(), $b->title() );
			    });
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
	 * Processes bulk actions .
	 *
	 * @since	0.15
	 * @return void
	 */
	function process_bulk_actions() {

		//Bail if no productions are selected.
		if ( empty( $_REQUEST['production'] ) || ! is_array( $_REQUEST['production'] ) ) {
			return;
		}

		// Bail if nonce is missing.
		if ( ! isset( $_REQUEST['_wpnonce'] ) || empty( $_REQUEST['_wpnonce'] ) ) {
	        return;
	    }

		// Bail if nonce is invalid.
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}

		// Start processing...
		if ( $action = $this->current_action() ) {
	        foreach ( $_POST['production'] as $production_id ) {
				switch ( $action ) {
					case 'publish':
						wp_publish_post( $production_id );
		                break;

		            case 'draft':
		            	$production_post = array(
			            	'ID' => $production_id,
			            	'post_status' => 'draft',
		            	);
		            	wp_update_post( $production_post );
		                break;

		            case 'trash':
			            wp_trash_post( $production_id );
		                break;

		            case 'restore':
			            wp_untrash_post( $production_id );
		                break;

		            case 'delete':
			            wp_delete_post( $production_id );
		                break;

		            default:
		                return;
		                break;
				}
	        }
		}
	}
}
