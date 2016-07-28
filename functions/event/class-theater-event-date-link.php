<?php
/**
 * Maintains the link between events and event dates.
 *
 * @since	0.16
 * @package	Theater/Events
 */
class Theater_Event_Date_Link {

	/**
	 * Adds the action hooks that maintain the link between events and event dates.
	 *
	 * @since	0.16
	 * @static
	 * @return void
	 */
	static function init() {

		add_action( 'updated_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );
		add_action( 'added_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );
		add_action( 'set_object_terms', array( __CLASS__, 'sync_categories' ),20, 6 );

		add_action( 'before_delete_post',array( __CLASS__, 'delete_dates' ) );
		add_action( 'wp_trash_post',array( __CLASS__, 'trash_dates' ) );
		add_action( 'untrash_post',array( __CLASS__, 'untrash_dates' ) );
		
		add_filter( 'theater/date/field', array(__CLASS__, 'get_production_field_values'), 10, 3);

	}

	/**
	 * Delete all connected dates of an event.
	 *
	 * Whenever an event is deleted (not just trashed), make sure that all connected dates are deleted as well.
	 * Dates that are already in the trash are left alone.
	 *
	 * @since 	0.7
	 * @since	0.12	Added support for events with an 'auto-draft' post_status.
	 *
	 * @access public
	 * @static
	 * @param 	int		$post_id	The ID of the trashed event.
	 * @return void
	 */
	static function delete_dates( $post_id ) {
		$post = get_post( $post_id );
		if ( ! empty( $post ) && $post->post_type == WPT_Production::post_type_name ) {
			$args = array(
				'post_type' => WPT_Event::post_type_name,
				'post_status' => array( 'any', 'auto-draft' ),
				'meta_query' => array(
					array(
						'key' => WPT_Production::post_type_name,
						'value' => $post_id,
					),
				),
			);
			$events = get_posts( $args );
			foreach ( $events as $event ) {
				wp_delete_post( $event->ID );
			}
		}
	}

	static function get_production_field_values( $value, $field, $date) {
		
		if (!empty($value)) {
			return $value;
		}
		
		if ( $date->has_field($field) ) {
			return $value;			
		}
		
		if ( $event = $date->get_event() ) {
			$value = $event->get_field($field);       
		}
		return $value;
	}

	/**
	 * Overwrites the categories of an event date with the categories of the event.
	 *
	 * Triggered if the categories of an event are set. Walks through all connected dates and
	 * overwrites the categories of the dates with the categories of the event.
	 *
	 * @static
	 * @since	0.?
	 *
	 * @param 	int    $object_id  Object ID.
	 * @param 	array  $terms      An array of object terms.
	 * @param 	array  $tt_ids     An array of term taxonomy IDs.
	 * @param 	string $taxonomy   Taxonomy slug.
	 * @param 	bool   $append     Whether to append new terms to the old terms.
	 * @param 	array  $old_tt_ids Old array of term taxonomy IDs.
	 * @return 	void
	 */
	static function sync_categories( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( 'category' == $taxonomy && WPT_Production::post_type_name == get_post_type( $object_id ) ) {
			$production = new WPT_Production( $object_id );
			foreach ( $production->events() as $event ) {
				wp_set_post_categories( $event->ID, $terms );
			}
		}
	}

	/**
	 * Updates the season of event dates to the season of the parent event.
	 *
	 * Triggered by the updated_post_meta action.
	 *
	 * Used when:
	 * - an event is saved through the admin screen or
	 * - an event date is attached to an event.
	 *
	 * @since 0.7
	 *
	 * @access public
	 * @static
	 *
	 * @param 	null|bool $check      	Whether to allow updating metadata for the given type.
	 * @param 	int       $object_id  	Object ID.
	 * @param 	string    $meta_key   	Meta key.
	 * @param 	mixed     $meta_value 	Meta value. Must be serializable if non-scalar.
	 * @param 	mixed     $prev_value 	Optional. If specified, only update existing
	 *                              	metadata entries with the specified value.
	 *                              	Otherwise, update all entries.
	 * @return 	void
	 */
	static function sync_season( $meta_id, $object_id, $meta_key, $meta_value ) {
		// An event is saved through the admin screen.
		if ( $meta_key == WPT_Season::post_type_name ) {
			$post = get_post( $object_id );
			if ( $post->post_type == WPT_Production::post_type_name ) {

				// avoid loops
				remove_action( 'updated_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );
				remove_action( 'added_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );

				$args = array(
					'production' => $post->ID,
				);
				$events = Theater()->events->get( $args );
				foreach ( $events as $event ) {
					update_post_meta( $event->ID, WPT_Season::post_type_name, $meta_value );
				}

				add_action( 'updated_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );
				add_action( 'added_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );
			}
		}

		// An event date is attached to an event.
		if ( $meta_key == WPT_Production::post_type_name ) {
			$event = new WPT_Event( $object_id );

			// avoid loops
			remove_action( 'updated_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );
			remove_action( 'added_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );

			// inherit season from production
			if ( $season = $event->production()->season() ) {
				update_post_meta( $event->ID, WPT_Season::post_type_name, $season->ID );
			}

			// inherit categories from production
			$categories = wp_get_post_categories( $meta_value );
			wp_set_post_categories( $event->ID, $categories );

			add_action( 'updated_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );
			add_action( 'added_post_meta', array( __CLASS__, 'sync_season' ), 20 ,4 );
		}

	}

	/**
	 * Trash all connected dates of an event.
	 *
	 * Whenever an event is trashed (not deleted), make sure that all connected dates are trashed as well.
	 *
	 * @since 0.7
	 *
	 * @static
	 * @param 	int		$post_id	The ID of the trashed event.
	 * @return 	void
	 */
	static function trash_dates( $post_id ) {
		$post = get_post( $post_id );
		if ( ! empty( $post ) && $post->post_type == WPT_Production::post_type_name ) {
			$args = array(
				'status' => 'any',
				'production' => $post_id,
			);
			$events = Theater()->events->get( $args );
			foreach ( $events as $event ) {
				wp_trash_post( $event->ID );
			}
		}
	}

	/**
	 * Untrash all connected dates of an event.
	 *
	 * Whenever an event is untrashed, make sure that all connected dates are untrashed as well.
	 *
	 * @since 0.7
	 *
	 * @access public
	 * @static
	 * @param 	int		$post_id	The ID of the trashed event.
	 * @return void
	 */
	static function untrash_dates( $post_id ) {
		$post = get_post( $post_id );
		if ( ! empty( $post ) && $post->post_type == WPT_Production::post_type_name ) {
			$args = array(
				'post_type' => WPT_Event::post_type_name,
				'post_status' => 'trash',
				'meta_query' => array(
					array(
						'key' => WPT_Production::post_type_name,
						'value' => $post_id,
					),
				),
			);
			$events = get_posts( $args );
			foreach ( $events as $event ) {
				wp_untrash_post( $event->ID );
			}
		}
	}
}
