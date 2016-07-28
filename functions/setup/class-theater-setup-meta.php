<?php
/**
 * Setup meta fields for event dates.
 *
 * @since	0.16
 * @package	Theater/Setup
 * @internal
 */
class Theater_Setup_Meta {

	/**
	 * Adds the action hook that registers the meta fields for event dates.
	 *
	 * @since	0.16
	 * @static
	 * @return void
	 */
	static function init() {
		add_action( 'init', array( __CLASS__, 'register_date_meta' ) );
	}

	/**
	 * Registers all event date meta fields and their sanitization callbacks.
	 *
	 * By defining this globally it is no longer necessary to manually sanitize data when
	 * saving it to the database (eg. in the admin or during the import).
	 *
	 * Registers the following meta fields:
	 *
	 * - Start date (`event_date`)
	 * - End date (`enddate`)
	 * - Venue (`venue`)
	 * - City (`city`)
	 * - Remark (`remark`)
	 * - Tickets url (`tickets_url`)
	 * - Text for tickets link (`tickets_button`)
	 * - Tickets status (`tickets_status`)
	 * - Prices (`_wpt_event_tickets_price`)
	 *
	 * @since 	0.11
	 * @since	0.15.8	Changed Tickets URL sanitiziation to `esc_url_raw()` because
	 *					`sanitize_text_field()` was breaking valid urls.
	 * @since	0.16	Now uses the enhanced `register_meta()` that was introduced in WordPress 4.6.
	 */
	static function register_date_meta() {

		register_meta(
			'post',
			'event_date',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'Theater_Setup_Meta::sanitize_start_date',
			    'type' => 'string',
			    'description' => __( 'Start date', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		register_meta(
			'post',
			'enddate',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'Theater_Setup_Meta::sanitize_end_date',
			    'type' => 'string',
			    'description' => __( 'End date', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		register_meta(
			'post',
			'venue',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'sanitize_text_field',
			    'type' => 'string',
			    'description' => __( 'Venue', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		register_meta(
			'post',
			'city',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'sanitize_text_field',
			    'type' => 'string',
			    'description' => __( 'City', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		register_meta(
			'post',
			'remark',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'sanitize_text_field',
			    'type' => 'string',
			    'description' => __( 'Remark', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		register_meta(
			'post',
			'tickets_url',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'esc_url_raw',
			    'type' => 'string',
			    'description' => __( 'Tickets url', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		register_meta(
			'post',
			'tickets_button',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'sanitize_text_field',
			    'type' => 'string',
			    'description' => __( 'Text for tickets link', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		register_meta(
			'post',
			'tickets_status',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'sanitize_text_field',
			    'type' => 'string',
			    'description' => __( 'Tickets status', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		register_meta(
			'post',
			'_wpt_event_tickets_price',
			array(
			    'object_subtype' => WPT_Event::post_type_name,
			    'sanitize_callback' => 'Theater_Setup_Meta::sanitize_tickets_prices',
			    'type' => 'string',
			    'description' => __( 'Prices', 'theatre' ),
			    'single' => true,
			    'show_in_rest' => true,
			)
		);

		/**
		 * Pre-WordPress 4.6 compatibility.
		 * @see https://make.wordpress.org/core/2016/07/08/enhancing-register_meta-in-4-6/
		 */

		if ( ! has_filter( 'sanitize_post_meta_event_date' ) ) {
		    add_filter( 'sanitize_post_meta_event_date', 'Theater_Setup_Meta::sanitize_start_date', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta_enddate' ) ) {
		    add_filter( 'sanitize_post_meta_enddate', 'Theater_Setup_Meta::sanitize_end_date', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta_venue' ) ) {
		    add_filter( 'sanitize_post_meta_venue', 'sanitize_text_field', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta_venue' ) ) {
		    add_filter( 'sanitize_post_meta_venue', 'sanitize_text_field', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta_city' ) ) {
		    add_filter( 'sanitize_post_meta_city', 'sanitize_text_field', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta_remark' ) ) {
		    add_filter( 'sanitize_post_meta_remark', 'sanitize_text_field', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta_tickets_url' ) ) {
		    add_filter( 'sanitize_post_meta_tickets_url', 'esc_url_raw', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta_tickets_button' ) ) {
		    add_filter( 'sanitize_post_meta_tickets_button', 'sanitize_text_field', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta_tickets_status' ) ) {
		    add_filter( 'sanitize_post_meta_tickets_status', 'sanitize_text_field', 10, 4 );
		}

		if ( ! has_filter( 'sanitize_post_meta__wpt_event_tickets_price' ) ) {
		    add_filter( 'sanitize_post_meta__wpt_event_tickets_price', 'Theater_Setup_Meta::sanitize_tickets_prices', 10, 4 );
		}

	}

	/**
	 * Sanitizes the start date value.
	 *
	 * Makes sure the start date is always stored as 'Y-m-d H:i'.
	 *
	 * @since 	0.11
	 * @param 	string $value  	The start date value.
	 * @return 	string			The sanitized start date.
	 */
	static function sanitize_start_date( $value ) {
		return date( 'Y-m-d H:i', strtotime( $value ) );
	}

	/**
	 * Sanitizes the end date value.
	 *
	 * Makes sure the end date is always stored as 'Y-m-d H:i'.
	 *
	 * @since 0.11
	 * @param 	string $value  	The end date value.
	 * @return 	string			The sanitized end date.
	 */
	static function sanitize_end_date( $value ) {
		return date( 'Y-m-d H:i', strtotime( $value ) );
	}

	/**
	 * Sanitizes ticket price values.
	 *
	 * @since 	0.11
	 * @since	0.16	Sanitization of price names was not working.
	 *
	 * @param 	string 	$value  The ticket price values.
	 * @return 	string			The sanitized ticket price values.
	 */
	static function sanitize_tickets_prices( $value ) {

		$price_parts = explode( '|', $value );

		// Sanitize the amount.
		$price_parts[0] = (float) $price_parts[0];

		// Sanitize the name.
		if ( ! empty( $price_parts[1] ) ) {
			$price_parts[1] = sanitize_text_field( $price_parts[1] );
		}

		return implode( '|',$price_parts );

	}
}
