<?php

/**
 * Production bulk editing.
 *
 * Makes sure that all events inherit the post_status of their parent production,
 * while bulk editing productions.
 *
 * @since	0.12
 */
class WPT_Bulk_Editor {

	function __construct() {
		add_action( 'admin_init', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wpt_bulk_editor', array( $this, 'update_event_post_status' ) );
	}

	/**
	 * Adds the bulk editor nonce to the javascript variables.
	 * 
	 * @since	0.12
	 * @access 	public
	 * @return  void
	 */
	public function enqueue_scripts() {
		wp_localize_script(
			'wp_theatre_admin',
			'wpt_bulk_editor_security',
			array(
				'nonce' => wp_create_nonce( 'wpt_bulk_editor_ajax_nonce' ),
			)
		);
	}

	/**
	 * Updates the status of events when bulk editing productions.
	 * 
	 * Triggered through AJAX when the user clicks the 'update' button.
	 * Finished before the production data is submitted to the server.
	 * See wpt_bulk_editor.coffee.
	 *
	 * @since 	0.12
	 * @access 	public
	 * @return 	void
	 */
	function update_event_post_status() {
		global $wp_theatre;

		check_ajax_referer( 'wpt_bulk_editor_ajax_nonce', 'wpt_bulk_editor_ajax_nonce' , true );

		if (
			! empty( $_POST['post_ids'] ) &&
			is_array( $_POST['post_ids'] ) &&
			1 != $_POST['post_status']
		) {

			// Status of production is updated
			foreach ( $_POST['post_ids'] as $post_id ) {

				// Update status of connected Events
				$args = array(
					'status' => array( 'any', 'auto-draft' ),
					'production' => $post_id,
				);
				$events = $wp_theatre->events->get( $args );

				foreach ( $events as $event ) {
					// Keep trashed events in the trash.
					if ( 'trash' == get_post_status( $event->ID ) ) {
						continue;
					}

					$post = array(
						'ID' => $event->ID,
						'post_status' => $_POST['post_status'],
					);

					wp_update_post( $post );
				}
			}
		}

		echo 'ok';

		wp_die();
	}
}