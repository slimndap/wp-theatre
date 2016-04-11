<?php

/**
 * Manage the status of productions and events.
 * @since 0.15.4
 */
class WPT_Status {

	function __construct() {
		add_action( 'save_post', array( $this, 'update_events_stati' ), 20, 3 );
	}

	/**
	 * Updates the status of events when the parent production is saved.
	 *
	 * @since	0.15.4
	 * @param 	int		$post_id	The ID of the production.
	 * @param 	WP_Post	$post		The post of the production.
	 * @param 	bool 	$update
	 */
	function update_events_stati( $post_id, $post, $update ) {

		global $wp_theatre;

		if ( WPT_Production::post_type_name != $post->post_type ) {
			return;
		}

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

			$event_post = array(
				'ID' => $event->ID,
				'post_status' => $post->post_status,
				'edit_date' => true,
				'post_date' => $post->post_date,
				'post_date_gmt' => get_gmt_from_date( $post->post_date ),
			);

			wp_update_post( $event_post );
		}
	}
}
