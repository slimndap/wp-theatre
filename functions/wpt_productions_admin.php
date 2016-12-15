<?php

/**
 * The Productions Admin Page.
 * @since	0.15
 */
class WPT_Productions_Admin {

	function __construct() {

		// Priority 6 places the submenu item at the top of the theater menu.
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 6 );

		add_filter( 'wpt_production_title_html', array( $this, 'add_production_title_edit_link' ), 10, 2 );
		add_filter( 'wpt/production/thumbnail/html', array( $this, 'add_production_thumbnail_placeholder' ), 10, 4 );
		
		add_action ('current_screen', array($this, 'process_bulk_actions'));
	}


	/**
	 * Adds a thumbnail placeholder to productions without a thumnbail.
	 *
	 * @since	0.15
	 * @param	string			$html		The production thumbnail HTML.
	 * @param	string			$size		The thumbnail size.
	 * @param	array			$filters	The template filters to apply.
	 * @param	WPT_Production	$production	The production.
	 * @return	string						The new production thumbnail HTML.
	 */
	function add_production_thumbnail_placeholder( $html, $size, $filters, $production ) {

		// Bail if not in admin.
		if ( ! is_admin() ) {
			return $html;
		}

		// Bail if not on Productions Admin Page.
		if ( empty( $_GET['page'] ) || 'theater-events' != $_GET['page'] ) {
			return $html;
		}

		// Add placeholder if thumbnail is empty.
		if ( empty( $html ) ) {
			$html = '<figure class="placeholder"><span class="dashicons dashicons-tickets-alt"></span></figure>';
		}

		return $html;
	}

	/**
	 * Adds a link to the production edit screen to the production title.
	 *
	 * @since	0.15
	 * @param 	string			$html		The production title HTML.
	 * @param 	WPT_Production	$production	The production.
	 * @return	string                      The production title HTML with a link to
	 *										the production edit screen.
	 */
	function add_production_title_edit_link( $html, $production ) {

		// Bail if not in admin.
		if ( ! is_admin() ) {
			return $html;
		}

		// Bail if not on Productions Admin Page.
		if ( empty( $_GET['page'] ) || 'theater-events' != $_GET['page'] ) {
			return $html;
		}

		// Bail if Productions Admin Page is showing trashed productions.
		if ( 'trash' == get_post_status( $production->ID ) ) {
			return $html;
		}

		ob_start();

		?><div class="wp_theatre_prod_title">
			<a href="<?php echo get_edit_post_link( $production->ID ); ?>">
				<?php echo $production->title(); ?>
			</a><?php
			_post_states( $production->post() );
		?></div><?php

		return ob_get_clean();
	}

	/**
	 * Add a submenu for the Productions Admin Screen to the Theater menu.
	 *
	 * @since	0.15
	 * @return 	void
	 */
	function add_submenu() {
		add_submenu_page(
			'theater-events',
			__( 'Events', 'theatre' ),
			__( 'Events', 'theatre' ),
			'edit_posts',
			'theater-events',
			array( $this,'page_html' )
		);
	}

	/**
	 * Outputs the HTML for the Productions Admin Page.
	 *
	 * @since	0.15
	 * @since	0.15.9	No longer triggers WPT_Productions_Admin::process_bulk_actions().
	 *					Changed form method from 'post' to 'get'.
	 *					Fixes #206.
	 *
	 * @see		WPT_Productions_Admin::add_submenu()
	 * @return 	void
	 */
	public function page_html() {

		$list_table = new WPT_Productions_List_Table();

		$this->empty_trash();

		ob_start();

		?><div class="wrap">
			<h1><?php _e( 'Events','theatre' ); ?>
				<a href="<?php echo admin_url( 'post-new.php?post_type='.WPT_Production::post_type_name );?>" class="page-title-action">
					<?php _e( 'Add new event', 'theatre' ); ?>
				</a>
				<?php echo $this->get_search_request_summary_html(); ?>
			</h1><?php

			$list_table->views();

			?><form method="get">
				<input type="hidden" name="page" value="theater-events" /><?php

				$list_table->prepare_items();
				$list_table->search_box( __( 'Search events', 'theatre' ), WPT_Production::post_type_name );
				$list_table->display();

			?></form>
		</div><?php

		ob_end_flush();
	}

	/**
	 * Processes bulk actions.
	 *
	 * @since	0.15
	 * @since	0.15.4	Added the $action param.
	 *					@see WP_List_Table::current_action()
	 * @since	0.15.5	Publish now uses wp_update_post() instead of wp_publish_post().
	 *					Fixes #197.
	 * @since	0.15.9	Removed the $action param again because this method is now
	 *					triggered by the 'current_screen'-hook.
	 *					
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
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-productions' ) ) {
			return;
		}

		$list_table = new WPT_Productions_List_Table();
		$action = $list_table->current_action();

		// Get a clean URL to redirect to after actions are done.
		$sendback_url = remove_query_arg( array('production', '_wpnonce', '_wp_http_referer'), wp_get_referer() );

		/* Start processing...
		 *
		 * @todo	Add information to $sendback_url so we can display a status message after the redirect. 
		 * @see		https://github.com/WordPress/WordPress/blob/703d5bdc8deb17781e9c6d8f0dd7e2c6b6353885/wp-admin/edit.php#L100
		 */
	
		foreach ( $_REQUEST['production'] as $production_id ) {
			switch ( $action ) {
				case 'publish':
	            	$production_post = array(
		            	'ID' => $production_id,
		            	'post_status' => 'publish',
	            	);
	            	wp_update_post( $production_post );
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
		
		/* 
		 * Redirect back to admin screen.
		 * Don't redirect if headers are already sent (eg. in unit tests).
		 */
		if (! headers_sent() ) {
			wp_redirect($sendback_url);
			exit;
		}
		
	}

	/**
	 * Empties the trash.
	 *
	 * @since	0.15.4
	 */
	function empty_trash() {
		global $wp_theatre;

		// Bail if this is not a delete all request.
		if ( ! isset( $_REQUEST['delete_all'] ) ) {
			return;
		}

		// Bail if nonce is missing.
		if ( ! isset( $_REQUEST['_wpnonce'] ) || empty( $_REQUEST['_wpnonce'] ) ) {
	        return;
	    }

		// Bail if nonce is invalid.
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-productions' ) ) {
			return;
		}

		// Start processing...
		$productions_args = array(
			'status' => 'trash',
		);
		foreach ( $wp_theatre->productions->get( $productions_args ) as $production ) {
			wp_delete_post( $production->ID );
		}
	}

	/**
	 * Gets a summary of the search request.
	 *
	 * @since	0.15
	 * @return 	string	A summary of the search request.
	 */
	private function get_search_request_summary_html() {

		// Bail if there is no search request.
		if ( empty( $_REQUEST['s'] ) ) {
			return '';
		}

		ob_start();
		?> <span class="subtitle"><?php
			printf( __( 'Search results for &#8220;%s&#8221;' ),sanitize_text_field( $_REQUEST['s'] ) );
		?></span><?php
		return ob_get_clean();
	}
}
