<?php
/**
 * Handles all ordering of events and event dates.
 *
 * Adds a `_wpt_order` custom field to all posts (of an event or event date post type) and uses this to chronologically
 * order WP_Query listings that hold events or event dates.
 *
 * For events the `_wpt_order` value is based on the `event_date` value of the first upcoming event date.
 * For event dates the `_wpt_order` value is based on the `event_date`  value.
 *
 * @since 	0.6.2
 * @since	0.15.13	Renamed class from `WPT_Order` to `Theater_Event_Order`.
 *					No longer alters the ordering of non-event post types.
 *
 * @package	Theater/Events
 */
class Theater_Event_Order {

	/**
	 * Inits all action hooks and filters.
	 *
	 * @since	0.6.2
	 * @since	0.15.13	Increased the priority of the `save_post`-hook to make sure that
	 *					the order index is set _after_ the event dates post stati are set of
	 *					a production.
	 *					@see WPT_Status::update_events_stati()
	 *					Fixes #198.
	 * @return void
	 */
	static function init() {

		if ( ! defined( 'THEATER_ORDER_INDEX_KEY' ) ) {
			define( 'THEATER_ORDER_INDEX_KEY', '_wpt_order' );
		}

		add_action( 'save_post', array( __CLASS__, 'set_order_index' ), 90 );
		add_action( 'updated_post_meta', array( __CLASS__, 'update_order_index_when_event_date_is_updated' ), 20 ,4 );
		add_action( 'added_post_meta', array( __CLASS__, 'update_order_index_when_event_date_is_updated' ), 20 ,4 );
		add_filter( 'pre_get_posts', array( __CLASS__, 'sort_events' ) );
		add_action( 'wpt_cron', array( __CLASS__, 'update_order_indexes' ) );
	}

	/**
	 * Calculates the order index for an event.
	 *
	 * The `_wpt_order` value is based on the `event_date` value of the first upcoming event date.
	 *
	 * @since	0.6.2	As part of Theater_Event_Order::set_order_index().
	 * @since	0.15.13	Renamed to `calculate_event_order_index`.
	 *					Events with only past event dates now use the last event for the
	 *					`_wpt_order` value.
	 * @param 	int		$event_id
	 * @return	int					The order index for an event.
	 */
	static function calculate_event_order_index( $event_id ) {
		global $wp_theatre;

		$event = new WPT_Production( $event_id );
		$event_dates = $wp_theatre->events->get( array( 'start' => 'now', 'production' => $event_id ) );

		// Use the last event if no upcoming events are found.
		if ( empty( $event_dates ) ) {
			$event_dates = $wp_theatre->events->get( array( 'production' => $event_id ) );
			$event_dates = array_reverse( $event_dates );
		}

		// Bail if event doesn't have any event dates at all.
		if ( empty( $event_dates ) ) {
			$order_index = -1;
		} else {
			$order_index = $event_dates[0]->datetime();
		}

		/**
		 * Filters the calculated order index of an event.
		 * 
		 * @since	0.15.14
		 * @param	int	$order_index	The current calculated order index.
		 * @param	WPT_Production		The event.
		 * @param	WPT_Event[]			The event dates of the event.
		 */
		$order_index = apply_filters('theater/event/order/index/calculate', $order_index, $event, $event_dates);

		return $order_index;
	}

	/**
	 * Calculates the order index for an event date.
	 *
	 * The `_wpt_order` value is based on the `event_date` value.
	 *
	 * @since	0.6.2	As part of Theater_Event_Order::set_order_index().
	 * @since	0.15.13	Renamed to `calculate_event_date_order_index`.
	 * @param 	int		$event_date_id
	 * @return	int						The order index for an event date.
	 */
	static function calculate_event_date_order_index( $event_date_id ) {

		$event_date = new WPT_Event( $event_date_id );
		return $event_date->datetime();

	}

	/**
	 * Gets the current event order index of an event.
	 *
	 * @since	0.15.13
	 * @param	int	$event_id
	 * @return	int				The event order index of an event
	 */
	static function get_event_order_index( $event_id ) {

		return get_post_meta( $event_id, '_wpt_order', true );

	}

	/**
	 * Gets the post types for events and event dates.
	 *
	 * @since	0.15.13
	 * @return	array	The post types for events and event dates.
	 */
	static function get_event_post_types() {
		return array( WPT_Production::post_type_name, WPT_Event::post_type_name );
	}

	/**
	 * Sets the order index of events and events dates.
	 *
	 * @since 	0.6.2
	 * @since 	0.10.15	Always set _wpt_order in UTC.
	 *					Fixes #117.
	 * @since	0.15.13	Moved the calculation of order indexes to seperate methods.
	 *					No longer adds order indexes to non-event post types.
	 *
	 * @uses	Theater_Event_Order::get_event_post_types() to get the post types for events and event dates.
	 * @uses	Theater_Event_Order::calculate_event_order_index() to calculate the order index of events.
	 * @uses	Theater_Event_Order::calculate_event_date_order_index() to calculate the order index of event dates.
	 *
	 * @param	int		$post_id	The ID of an event or an event date.
	 * @return	void
	 */
	static function set_order_index( $post_id ) {

		$post_type = get_post_type( $post_id );

		if ( ! in_array( $post_type, self::get_event_post_types() ) ) {
			return;
		}

		if ( WPT_Production::post_type_name == $post_type ) {
			update_post_meta( $post_id, '_wpt_order', self::calculate_event_order_index( $post_id ) );
		}

		if ( WPT_Event::post_type_name == $post_type ) {
			update_post_meta( $post_id, '_wpt_order', self::calculate_event_date_order_index( $post_id ) );
		}

	}

	/**
	 * Sorts WP_Query objects that query events or event dates.
	 *
	 * @since	0.6.2
	 * @since	0.15.13	No longer sort queries that only query non-event post types.
	 * @since	0.15.15	No longer sort queries that also query non-event post types.
	 * @uses	Theater_Event_Order::get_event_post_types() to get the post types for events and event dates.
	 *
	 * @param 	WP_Query	$query
	 * @return	void
	 */
	static function sort_events( $query ) {

		$post_types = (array) $query->get( 'post_type' );

		// Don't interfere with custom sorting of post columns.
		if ( is_admin() && ! empty( $_GET['orderby'] ) ) {
			return;
		}

		$event_post_types_in_query = array_intersect( $post_types, self::get_event_post_types() );

		if ( empty( $event_post_types_in_query ) ) {
			return;
		}

		$other_post_types_in_query = array_diff( $post_types, self::get_event_post_types() );

		if ( ! empty( $other_post_types_in_query ) ) {
			return;
		}

		// This query is for event post types and event post types only, sort query

		$query->set( 'meta_key','_wpt_order' );
		$query->set( 'orderby','meta_value' );
	}

	/**
	 * Updates the order index of a both the event dates and the parent event whenever
	 * the `event_date` field of an event date is updated.
	 *
	 * @since 	0.7
	 * @uses	Theater_Event_Order::set_order_index() to set the order index of events and event dates.
	 * @param 	int 	$meta_id	ID of updated metadata entry.
	 * @param 	int 	$object_id	Object ID.
	 * @param 	string 	$meta_key	Meta key.
	 * @param 	mixed 	$meta_value	Meta value.
	 * @return 	void
	 */
	static function update_order_index_when_event_date_is_updated( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'event_date' == $meta_key ) {
			$event_date = new WPT_Event( $object_id );
			self::set_order_index( $event_date->ID );

			$event = $event_date->production();

			if ( ! empty( $event ) ) {
				self::set_order_index( $event->ID );
			}
		}
	}

	/**
	 * Updates the order index of all events and event dates.
	 *
	 * Triggered by `wpt_cron` action hook or by directly calling the function (eg. after an import).
	 *
	 * @since 	0.6.2
	 * @since	0.15.13	No longer updates the order index of non-event post types.
	 *
	 * @uses	Theater_Event_Order::get_event_post_types() to get the post types for events and event dates.
	 * @uses	Theater_Event_Order::set_order_index() to set the order index for events and event dates.
	 *
	 */
	static function update_order_indexes() {
		/**
		 * Unhook pre_get_posts filter.
		 * Remove order by wpt_order to include any productions and events that don't have a wpt_order set yet.
		 */
		remove_filter( 'pre_get_posts', array( __CLASS__, 'sort_events' ) );

		$args = array(
			'post_type' => self::get_event_post_types(),
			'post_status' => 'any',
			'nopaging' => true,
		);
		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			self::set_order_index( $post->ID );
		}

		/**
		 * Re-activate pre_get_posts filter.
		 */
		add_filter( 'pre_get_posts', array( __CLASS__, 'sort_events' ) );
	}


}
