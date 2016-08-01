<?php

/**
 * Handles individual event dates.
 *
 * Each event can have one or more event dates.
 *
 * ##Usage
 *
 * <code>
 * // Output an event date as HTML.
 * $date = new Theater_Event_Date( 123 );
 * echo $date;
 * </code>
 * <code>
 * // Output an event date as HTML with a custom template:
 * $date = new Theater_Event_Date( 123, '{{title}}{{startdate}}{{city}}{{tickets}}' );
 * echo $date;
 * </code>
 * <code>
 * // Get the value of an event date field:
 * $date = new Theater_Event_Date( 123 );
 * $startdate = $date->startdate(); // Eg. '05-06-2017'.
 * $prices = $date->prices(); // An array of all ticket prices for this date.
 * $title = $date->title(); // Eg. 'Sound of Music'.
 * </code>
 * <code>
 * // Output the value of an event date field as HTML:
 * $date = new Theater_Event_Date( 123 );
 * echo $date->startdate;
 * echo $date->prices;
 * echo $date->title;
 * </code>
 *
 * ## Fields
 *
 * Event dates have the following fields:
 * | field | description |
 * |---|---|
 * | `duration` | The duration, based on the starttime and endtime. |
 * | `enddate` | The end date |
 * | `endtime` | The end time |
 * | `enddatetime` | The timestamp of the end |
 * | `location` | The location, based on the venue and the city.
 * | `prices` | The different prices.
 * | `startdate` | The start date |
 * | `starttime` | The start time |
 * | `startdatetime` | The timestamp of the start |
 * | `tickets` | The tickets link.
 * | `venue` | The venue.
 * | `city` | The city.
 * 
 * Additionally, the following fields are inherited from the parent event:
 *
 * | field | description |
 * |---|---|
 * | `categories` | The categories of the event.
 * | `cities` | A summary of all the cities that the event takes place.
 * | `content` | The post content of the event.
 * | `dates` | A summary of all the dates of the event.
 * | `excerpt` | The excerpt of the event.
 * | `summary` | A summary of the event.
 * | `permalink` | The permalink of the event.
 * | `title` | The title of the event.
 * | `thumbnail` | The thumbnail image of the event.
 *
 * ## HTML template
 *
 * The default template for the HTML output of an event date is:
 * `{{thumbnail|permalink}} {{title|permalink}} {{remark}} {{startdatetime}} {{location}} {{tickets}}`
 *
 * @package	Theater/Events
 * @since	0.16
 *
 */

class Theater_Event_Date extends Theater_Item {

	const name = 'date';
	const post_type_name = 'wp_theatre_event';

	const tickets_status_onsale = '_onsale';
	const tickets_status_hidden = '_hidden';
	const tickets_status_cancelled = '_cancelled';
	const tickets_status_soldout = '_soldout';
	const tickets_status_other = '_other';

	function get_fields() {

		$fields = array(
			'duration',
			'enddate',
			'enddatetime',
			'endtime',
			'event',
			'location',
			'prices',
			'prices_summary',
			'startdate',
			'startdatetime',
			'starttime',
			'tickets',
			'tickets_button',
			'tickets_status',
			'tickets_url',
			'venue',
			'city',
		);

		return $fields;
	}

	/**
	 * Gets the duration of an event date.
	 *
	 * @since	0.x?
	 * @internal
	 * @uses	Theater_Event_Date::get_field() to get the start of an event date.
	 * @uses	Theater_Event_Date::get_field() to get the end of an event date.
	 * @return	string	The duration of an event date.
	 */
	function get_duration() {

		$startdate = $this->get_field( 'startdatetime' );
		$enddate = $this->get_field( 'enddatetime' );

		if ( empty( $enddate ) ) {
			return '';
		}

		if ( $enddate < $startdate ) {
			return '';
		}

		$seconds = $enddate - $startdate;
		$minutes = (int) $seconds / 60;
		$value = $minutes.' '._n( 'minute','minutes', $minutes, 'theatre' );

		return $value;

	}

	/**
	 * Gets the event enddate.
	 *
	 * @since	0.12
	 * @since	0.12.7	Now returns <false> is no endate is set.
	 * 					See [#165](https://github.com/slimndap/wp-theatre/issues/165).
	 * @uses	Theater_Event_Date::get_field() to get the end of an event date.
	 * @internal
	 * @return	string 	The event enddate.
	 *					Returns <false> if no endate is set.
	 */
	function get_enddate() {

		$enddate = false;
		if ( $datetime = $this->get_field( 'enddatetime' ) ) {
			$enddate = date_i18n(
				get_option( 'date_format' ),
				$datetime + get_option( 'gmt_offset' ) * 3600
			);
		}
		return $enddate;
	}

	/**
	 * Gets the HTML for the event date enddate.
	 *
	 * @since	0.12
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @uses	Theater_Event_Date::get_field() to get the raw end time of an event date for use with the 'date' filter.
	 * @uses	Theater_Event_Date::get_field() to get the end date of an event date.
	 * @uses	WPT_Template_Placeholder_Filter::apply_to() to apply filters to the field value.
	 * @internal
	 * @param 	WPT_Template_Placeholder_Filter[] 	$filters 	An array of filters to apply to the value if the field.
	 * @return 	string				The HTML for the event date enddate.
	 */
	function get_enddate_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo $this->get_post_type();?>_date <?php echo $this->get_post_type(); ?>_enddate"><?php

if ( $value = $this->get_field( 'enddate' ) ) {

	foreach ( $filters as $filter ) {
		if ( 'date' == $filter->name ) {
			$value = $filter->apply_to( $this->get_field( 'enddatetime' ), $this );
		} else {
			$value = $filter->apply_to( $value, $this );
		}
	}

	echo $value;

}

		?></div><?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Gets the event date endtime.
	 *
	 * @since	0.12
	 * @uses	Theater_Event_Date::get_field() to get the end of an event date.
	 * @internal
	 * @return	string|bool The event endtime.
	 *						Returns <false> if no endate is set.
	 */
	function get_endtime() {

		$endtime = false;
		if ( $datetime = $this->get_field( 'enddatetime' ) ) {
			$endtime = date_i18n(
				get_option( 'time_format' ),
				$datetime + get_option( 'gmt_offset' ) * 3600
			);
		}
		return $endtime;

	}

	/**
	 * Gets the HTML for the event endtime.
	 *
	 * @since	0.12
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @uses	Theater_Event_Date::get_field() to get the raw end time of an event date for use with the 'date' filter.
	 * @uses	Theater_Event_Date::get_field() to get the end time of an event date.
	 * @uses	WPT_Template_Placeholder_Filter::apply_to() to apply filters to the field value.
	 * @internal
	 * @param 	WPT_Template_Placeholder_Filter[] 	$filters 	An array of filters to apply to the value if the field.
	 * @return 	string				The HTML for the event endtime.
	 */
	function get_endtime_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo $this->get_post_type();?>_time <?php echo $this->get_post_type(); ?>_endtime"><?php

if ( $value = $this->get_field( 'endtime' ) ) {

	foreach ( $filters as $filter ) {
		if ( 'date' == $filter->name ) {
			$value = $filter->apply_to( $this->get_field( 'enddatetime' ), $this );
		} else {
			$value = $filter->apply_to( $value, $this );
		}
	}

	echo $value;

}

		?></div><?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Gets the event that this date belongs to.
	 *
	 * @since 	0.4
	 * @since	0.15	Removed local caching of event production.
	 *					Return <false> if no production is set.
	 *
	 * @uses	WPT_Production::post_type_name
	 * @return 	WPT_Production|bool The event or <false> if no event is set.
	 */
	function get_event() {

		$event_id = get_post_meta( $this->ID, WPT_Production::post_type_name, true );

		// Bail if no production ID is set.
		if ( empty( $event_id ) ) {
			return false;
		}

		/*
		 * Bail if production doesn't exist.
		 * See: https://tommcfarlin.com/wordpress-post-exists-by-id/
		 */
		if ( false === get_post_status( $event_id ) ) {
			return false;
		}

		$event = new Theater_Event( $event_id );
		return $event;

	}

	/**
	 * Get the event permalink.
	 *
	 * The permalink is inherited from the parent production.
	 *
	 * @since	0.?
	 * @since	0.13.6	Added a 'wpt/event/permalink' filter.
	 *					Moved HTML version to separate function.
	 * @uses	WPT_Production::permalink() to get the permalink for an event.
	 * @internal
	 * @return 	string	The permalink.
	 */
	function permalink( $deprecated = array() ) {

		if ( ! empty( $deprecated['html'] ) ) {
			return $this->permalink_html( $deprecated );
		}

		$permalink = $this->event()->permalink( $deprecated );

		/**
		 * Filter the event permalink.
		 *
		 * @since	0.13.6
		 * @param	string		$permalink	The event permalink.
		 * @param	WPT_Event	$event		The event.
		 */
		$permalink = apply_filters( 'wpt/event/permalink', $permalink, $this );
		return $permalink;
	}

	/**
	 * Get the HTML for the event permalink.
	 *
	 * The permalink is inherited from the parent production.
	 *
	 * @since	0.13.6
	 * @uses	WPT_Production::permalink() to get the HTML for the permalink for an event.
	 * @internal
	 * @return 	string	The HTML for the event permalink.
	 */
	function permalink_html( $args = array() ) {

		$args['html'] = true;
		$html = $this->event()->permalink( $args );

		/**
		 * Filter the HTML for the event permalink.
		 *
		 * @since	0.13.6
		 * @param	string		$html	The HTML for the event permalink.
		 * @param	WPT_Event	$event	The event.
		 */
		$html = apply_filters( 'wpt/event/permalink/html', $html, $this );
		return $html;
	}

	/**
	 * Gets the event prices.
	 *
	 * @since 	0.4
	 * @internal
	 * @return 	array 	The event prices.
	 */
	function get_prices() {

		$prices = array();

		$prices_named = get_post_meta( $this->ID,'_wpt_event_tickets_price' );

		foreach ( $prices_named as $price_named ) {
			$price_parts = explode( '|',$price_named );
			$prices[] = (float) $price_parts[0];
		}

		return $prices;
	}

	/**
	 * Gets the HTML for the event prices.
	 *
	 * @since 	0.10.14
	 * @uses	Theater_Event_Date::get_field_html() to get a summary of the prices for an event date.
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @internal
	 * @return 	string	The HTML for the event prices.
	 */
	public function get_prices_html() {

		$html = '';

		$prices_summary_html = $this->get_field_html( 'prices_summary' );

		if ( '' != $prices_summary_html ) {
			$html = '<div class="'.$this->get_post_type().'_prices">'.$prices_summary_html.'</div>';
		}

		return $html;

	}

	/**
	 * Gets a summary of event date prices.
	 *
	 * @since 	0.10.14
	 * @uses 	Theater_Event_Date::get_field() to get all prices for an event date.
	 * @internal
	 * @return 	string 	A summary of event date prices.
	 */
	public function get_prices_summary() {

		global $wp_theatre;

		$prices = $this->get_field( 'prices' );

		$prices_summary = '';

		if ( ! empty( $prices ) ) {
			if ( count( $prices ) > 1 ) {
				$prices_summary .= __( 'from','theatre' ).' ';
			}
			if ( ! empty( $wp_theatre->wpt_tickets_options['currencysymbol'] ) ) {
				$prices_summary .= $wp_theatre->wpt_tickets_options['currencysymbol'].' ';
			}

			$prices_summary .= number_format_i18n( (float) min( (array) $prices ), 2 );
		}

		return $prices_summary;

	}

	/**
	 * Gets the HTML for the summary of event date prices.
	 *
	 * @since 	0.10.14
	 * @uses	Theater_Event_Date::get_field() to get the summary of prices for the event date.
	 * @internal
	 * @return 	string	The HTML.
	 */
	public function get_prices_summary_html() {

		$html = $this->get_field( 'prices_summary' );
		$html = esc_html( $html );
		$html = str_replace( ' ', '&nbsp;', $html );

		return $html;
	}

	/**
	 * Gets a valid event date tickets URL.
	 *
	 * Only returns a tickets URL for event dates that are on sale and take
	 * place in the future.
	 *
	 * @since 	0.4
	 * @since 	0.10.14	Deprecated the HTML argument.
	 *					Use @see WPT_Event::tickets_html() instead.
	 * @since	0.13.1	Check for upcoming event now accounts for timezones.
	 *					Fixes #167.
	 *
	 * @uses	Theater_Event_Date::get_field() to check if an event date is on sale.
	 * @uses	Theater_Event_Date::get_field() to check if the event date takes place int he future.
	 * @uses	Theater_Event_Date::get_field() to get the the event date tickets URL.
	 * @internal
	 * @return 	string	The tickets URL or ''.
	 */
	function get_tickets() {
		$tickets = '';

		if (
			self::tickets_status_onsale == $this->get_field( 'tickets_status' ) &&
			$this->get_field( 'startdatetime' ) > current_time( 'timestamp', true )
		) {
			$tickets = $this->get_field( 'tickets_url' );
		}

		return $tickets;
	}

	/**
	 * Gets the text for the event tickets link.
	 *
	 * @since	0.10.14
	 * @internal
	 * @return 	string	The text for the event tickets link.
	 */
	function get_tickets_button() {
		$tickets_button = get_post_meta( $this->ID,'tickets_button',true );

		if ( empty( $tickets_button ) ) {
			$tickets_button = __( 'Tickets', 'theatre' );
		}

		return $tickets_button;
	}

	/**
	 * Gets the HTML for a valid event tickets link.
	 *
	 * @since	0.10.14
	 * @since	0.11.10	Don't return anything for historic events with an 'on sale' status.
	 *					Fixes #118.
	 * @since	0.13.1	Check for upcoming event now accounts for timezones.
	 *					Fixes #167.
	 *
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @uses	Theater_Event_Date::get_field() to check if an event date is on sale.
	 * @uses	Theater_Event_Date::get_field() to check if the event date takes place int he future.
	 * @uses	Theater_Event_Date::get_field_html() to get the HTML output of the tickets URL of an event date.
	 * @uses	Theater_Event_Date::get_field_html() to get the HTML output of the tickets prices of an event date.
	 * @uses	Theater_Event_Date::get_field_html() to get the HTML output of the tickets status of an event date when
	 *			the event date is not on sale.
	 * @internal
	 * @return 	string	The HTML for a valid event tickets link.
	 */
	public function get_tickets_html() {
		$html = '';

		$html .= '<div class="'.$this->get_post_type().'_tickets">';
		if ( self::tickets_status_onsale == $this->get_field( 'tickets_status' ) ) {

			if ( $this->get_field( 'startdatetime' ) > current_time( 'timestamp', true ) ) {
				$html .= $this->get_field_html( 'tickets_url' );

				$prices_html = $this->get_field_html( 'prices' );
				$prices_html = apply_filters( 'wpt_event_tickets_prices_html', $prices_html, $this );
				$html .= $prices_html;
			}
		} else {
			$html .= $this->get_field_html( 'tickets_status' );
		}
		$html .= '</div>'; // .tickets

		return $html;
	}

	/**
	 * Gets the event tickets URL.
	 *
	 * @since 	0.8.3
	 * @since 	0.10.14	Deprecated the HTML argument.
	 *					Use @see WPT_Event::tickets_url_html() instead.
	 * @since	0.12	Moved the iframe url to a new method.
	 *					@see WPT_Event::tickets_url_iframe().
	 * @uses	WP_Theatre::$wpt_tickets_options to check if the tickets URL uses an iframe.
	 * @uses	Theater_Event_Date::tickets_url_iframe() to get the URL of the iframe page for this event date.
	 * @internal
	 * @return 	string 	The event tickets URL.
	 */

	function get_tickets_url() {

		global $wp_theatre;

		$tickets_url = get_post_meta( $this->ID,'tickets_url',true );

		if (
			! empty( $wp_theatre->wpt_tickets_options['integrationtype'] ) &&
			'iframe' == $wp_theatre->wpt_tickets_options['integrationtype']  &&
			! empty( $tickets_url ) &&
			$tickets_url_iframe = $this->tickets_url_iframe()
		) {
			$tickets_url = $tickets_url_iframe;
		}

		return $tickets_url;
	}

	/**
	 * Gets the event tickets iframe URL.
	 *
	 * @since 	0.12
	 * @uses	Theater_Event_Date::$wpt_tickets_options to get the ID of the iframe page.
	 * @uses	Theater_Event_Date::get_post_type	to add the post type to the iframe page URL.
	 * @uses	Theater_Event_Date::$ID to add the post ID to the iframe page URL.
	 * @uses	Theater_Event_Date::get_event() to get the event of the event date.
	 * @internal
	 * @return  string|bool     The event tickets iframe URL or
	 *							<false> if no iframe page is set.
	 */
	public function tickets_url_iframe() {

		global $wp_theatre;

		if ( empty( $wp_theatre->wpt_tickets_options['iframepage'] ) ) {
			return false;
		}

		$tickets_iframe_page = get_post( $wp_theatre->wpt_tickets_options['iframepage'] );

		if ( is_null( $tickets_iframe_page ) ) {
			return false;
		}

		$tickets_url_iframe = get_permalink( $tickets_iframe_page );
		if ( get_option( 'permalink_structure' ) && $event = $this->get_event() ) {
			$tickets_url_iframe = trailingslashit( $tickets_url_iframe ).$this->get_post_type().'/'.$this->ID;
		} else {
			$tickets_url_iframe = add_query_arg( 'wpt_event_tickets', $this->ID, $tickets_url_iframe );
		}

		/**
		 * Filter the event tickets iframe URL.
		 *
		 * @since 	0.12
		 * @param 	string		$tickets_url_iframe		The event tickets iframe URL.
		 * @param	WPT_Event	$this					The event object.
		 */
		$tickets_url_iframe = apply_filters( 'wpt/event/tickets/url/iframe', $tickets_url_iframe, $this );

		return $tickets_url_iframe;

	}

	/**
	 * Get the HTML for the event date tickets URL.
	 *
	 * @since 0.10.14
	 * @uses	Theater_Event_Date::get_field() to get the the event date tickets URL.
	 * @uses	WP_Theatre::$wpt_tickets_options to check if the tickets URL uses an iframe.
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @uses	Theater_Event_Date::get_field() to get the text inside the event date tickets URL.
	 * @internal
	 * @return string	The HTML for the event date tickets URL.
	 */
	public function get_tickets_url_html() {
		global $wp_theatre;

		$html = '';

		$tickets_url = $this->get_field( 'tickets_url' );

		if ( ! empty( $tickets_url ) ) {

			$html .= '<a href="'.$tickets_url.'" rel="nofollow"';

			/**
			 * Add classes to tickets link.
			 */

			$classes = array();
			$classes[] = $this->get_post_type().'_tickets_url';
			if ( ! empty( $wp_theatre->wpt_tickets_options['integrationtype'] ) ) {
				$classes[] = 'wp_theatre_integrationtype_'.$wp_theatre->wpt_tickets_options['integrationtype'];
			}

			/**
			 * Filter the CSS classes of the HTML for the event tickets URL.
			 *
			 * @since	0.10.14
			 * @param 	array 		$classes	The current CSS classes.
			 * @param 	WPT_Event	$event		The event.
			 */
			$classes = apply_filters( 'wpt/event/tickets/url/classes',$classes,$this );

			/**
			 * @deprecated	0.10.14
			 */
			$classes = apply_filters( 'wpt_event_tickets__url_classes',$classes,$this );

			$html .= ' class="'.implode( ' ' ,$classes ).'"';

			$html .= '>';
			$html .= $this->get_field( 'tickets_button' );
			$html .= '</a>';

		}

		return $html;
	}

	/**
	 * Gets the HTML for an event date.
	 *
	 * @since 	0.4
	 * @since 	0.10.8	Added a filter to the default template.
	 * @since	0.14.7	Added the $args parameter.
	 * @since	0.15.2	Removed the $args parameter.
	 *
	 * @uses	Theater_Item::get_post_type() to get the post type for an event date.
	 * @uses	WPT_Event_Template::get_merged() to get the merged HTML for an event date.
	 * @return 	string	The HTML for an event date.
	 */
	function get_html() {

		$classes = array();
		$classes[] = $this->get_post_type();

		$template = new WPT_Event_Template( $this, $this->template );
		$html = $template->get_merged();

		/**
		 * Filter the HTML output for an event.
		 *
		 * @since	0.14.7
		 * @param	string				$html		The HTML output for an event.
		 * @param	WPT_Event_Template	$template	The event template.
		 * @param	array				$args		The listing args (if the event is part of a listing).
		 * @param	WPT_Event			$event		The event.
		 */
		$html = apply_filters( 'wpt/event/html',$html, $template, $this );

		/**
		 * @deprecated	0.14.7
		 */
		$html = apply_filters( 'wpt_event_html',$html, $this );

		/**
		 * Filter the classes for an event.
		 *
		 * @since 	0.?
		 * @param	array		$classes	The classes for an event.
		 * @param	WPT_Event	$event		The event.
		 */
		$classes = apply_filters( 'wpt_event_classes',$classes, $this );

		// Wrapper
		$html = '<div class="'.implode( ' ',$classes ).'">'.$html.'</div>';

		return $html;
	}

	/**
	 * Gets the location of an event date.
	 *
	 * The location is a combination of the event date venue and city.
	 *
	 * @since	0.x
	 * @uses	Theater_Event_Date::get_field() to get the venue of an event date.
	 * @uses	Theater_Event_Date::get_field() to get the city of an event date.
	 * @internal
	 * @return	string The location of an event date.
	 */
	function get_location() {

		$location_parts = array();

		$venue = $this->get_field( 'venue' );
		$city = $this->get_field( 'city' );

		if ( ! empty( $venue ) ) {
			$location_parts[] = $venue;
		}

		if ( ! empty( $city ) ) {
			$location_parts[] = $city;
		}

		$value = implode( ' ', $location_parts );
		return $value;

	}

	/**
	 * Gets the HTML for a location of an event date.
	 *
	 * The location is a combination of the event date venue and city.
	 *
	 * @since	0.16
	 * @uses	Theater_Event_Date::get_field_html() to get the HTML for a venue of an event date.
	 * @uses	Theater_Event_Date::get_field_html() to get the HTML for a city of an event date.
	 * @param 	WPT_Template_Placeholder_Filter[] 	$filters 	An array of filters to apply to the value if the field.
	 * @internal
	 * @return	string The location of an event date.
	 */
	function get_location_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo Theater_Event_Date::post_type_name; ?>_location"><?php
			echo $this->get_field_html( 'venue', $filters );
			echo $this->get_field_html( 'city', $filters );
		?></div><?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Gets the tickets status of an event date.
	 *
	 * @since	0.x
	 * @internal
	 * @return	string
	 */
	function get_tickets_status() {

		$value = get_post_meta( $this->ID,'tickets_status',true );

		if ( empty( $value ) ) {
			$value = self::tickets_status_onsale;
		}

		return $value;

	}

	/**
	 * Get the HTML for the tickets status of an event date.
	 *
	 * @since	0.x
	 * @uses 	Theater_Event_Date::get_field() to get the tickets status of an event.
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @internal
	 * @return 	string	The HTML for the tickets status of an event date.
	 */
	function get_tickets_status_html() {
		$tickets_status = $this->get_field( 'tickets_status' );

		switch ( $tickets_status ) {
			case self::tickets_status_onsale :
				$label = __( 'On sale','theatre' );
				break;
			case self::tickets_status_soldout :
				$label = __( 'Sold out','theatre' );
				break;
			case self::tickets_status_cancelled :
				$label = __( 'Cancelled','theatre' );
				break;
			case self::tickets_status_hidden :
				$label = '';
				break;
			default :
				$label = $tickets_status;
				$tickets_status = self::tickets_status_other;
		}

		$html = '';

		if ( ! empty( $label ) ) {
			$html .= '<span class="'.$this->get_post_type().'_tickets_status '.$this->get_post_type().'_tickets_status'.$tickets_status.'">'.$label.'</span>';
		}

		return $html;
	}

	/**
	 * Gets the end timestamp of an event date.
	 *
	 * @since	0.16
	 * @internal
	 * @return	int	The end timestamp of an event date.
	 */
	function get_enddatetime() {
		$enddate = get_post_meta( $this->ID, 'enddate', true );

		if ( empty( $enddate ) ) {
			return false;
		}

		$value = date_i18n( 'U', strtotime( $enddate, current_time( 'timestamp' ) ) - get_option( 'gmt_offset' ) * 3600 );

		return $value;
	}

	/**
	 * Gets the HTML for the end date and time of an event date.
	 *
	 * @since	0.16
	 * @param 	WPT_Template_Placeholder_Filter[] 	$filters 	An array of filters to apply to the value if the field.
	 * @internal
	 * @return	string	The HTML for the end date and time of an event date.
	 */
	function get_enddatetime_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo Theater_Event_Date::post_type_name; ?>_datetime <?php echo Theater_Event_Date::post_type_name; ?>_startdatetime"><?php

			$value = $this->enddate.$this->endtime;
foreach ( $filters as $filter ) {
	if ( 'date' == $filter->name ) {
		$value = $filter->apply_to( $this->enddatetime(), $this );
	} else {
		$value = $filter->apply_to( $value, $this );
	}
}
			echo $value;

		?></div><?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Gets the event date startdate.
	 *
	 * @since	0.12
	 * @uses	Theater_Event_Date::get_field() to get the start timestamp of an event date.
	 * @internal
	 * @return	string The event date startdate.
	 */
	function get_startdate() {
		$startdate = date_i18n(
			get_option( 'date_format' ),
			$this->get_field( 'startdatetime' ) + get_option( 'gmt_offset' ) * 3600
		);

		return $startdate;
	}

	/**
	 * Gets the HTML for the event date startdate.
	 *
	 * @since	0.12
	 * @since	0.15.1	Fix: No longer compensates for the timezone when applying the 'date'-filter.
	 *					This is already handled by WPT_Template_Placeholder_Filter::callback_date() and
	 *					resulted in double compensations.
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @uses	Theater_Event_Date::get_field() to get the start date of an event date.
	 * @uses	Theater_Event_Date::get_field() to get the raw start time of an event date for use with the 'date' filter.
	 * @uses	WPT_Template_Placeholder_Filter::apply_to() to apply filters to the field value.
	 * @param 	WPT_Template_Placeholder_Filter[] 	$filters 	An array of filters to apply to the value if the field.
	 * @internal
	 * @return 	string				The HTML for the event date startdate.
	 */
	function get_startdate_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo $this->get_post_type();?>_date <?php echo $this->get_post_type(); ?>_startdate"><?php

		$value = $this->get_field( 'startdate' );

foreach ( $filters as $filter ) {
	if ( 'date' == $filter->name ) {
		$value = $filter->apply_to( $this->get_field( 'startdatetime' ), $this );
	} else {
		$value = $filter->apply_to( $value, $this );
	}
}

		echo $value;

		?></div><?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Gets the start timestamp of an event date.
	 *
	 * @since	0.16
	 * @internal
	 * @return	int|bool	The start timestamp of an event date.
	 *						Returns <false> if not startdate is set.
	 */
	function get_startdatetime() {
		$startdate = get_post_meta( $this->ID, 'event_date', true );

		if ( empty( $startdate ) ) {
			return false;
		}

		$value = date_i18n( 'U', strtotime( $startdate, current_time( 'timestamp' ) ) - get_option( 'gmt_offset' ) * 3600 );

		return $value;
	}

	/**
	 * Gets the HTMl for the start date and time of an event date.
	 *
	 * @since	0.16
	 * @param 	WPT_Template_Placeholder_Filter[] 	$filters 	An array of filters to apply to the value if the field.
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @uses	Theater_Event_Date::get_field_html() to get the HTML for the start date of an event date.
	 * @uses	Theater_Event_Date::get_field_html() to get the HTML for the start time of an event date.
	 * @uses	Theater_Event_Date::get_field() to get the raw start time of an event date for use with the 'date' filter.
	 * @internal
	 * @return	string	Tthe HTMl for the start date and time of an event date.
	 */
	function get_startdatetime_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo $this->get_post_type(); ?>_datetime <?php echo $this->get_post_type(); ?>_startdatetime"><?php

			$value = $this->get_field_html( 'startdate' ).$this->get_field_html( 'starttime' );
foreach ( $filters as $filter ) {
	if ( 'date' == $filter->name ) {
		$value = $filter->apply_to( $this->get_field( 'startdatetime' ), $this );
	} else {
		$value = $filter->apply_to( $value, $this );
	}
}
			echo $value;

		?></div><?php

		$html = ob_get_clean();

		return $html;
	}


	/**
	 * Gets the event date starttime.
	 *
	 * @since	0.12
	 * @uses	Theater_Event_Date::get_field() to get the start timestamp of an event date.
	 * @internal
	 * @return	string The event date starttime.
	 */
	function get_starttime() {
		$starttime = date_i18n(
			get_option( 'time_format' ),
			$this->get_field( 'startdatetime' ) + get_option( 'gmt_offset' ) * 3600
		);

		return $starttime;
	}

	/**
	 * Gets the HTML for the event starttime.
	 *
	 * @since	0.12
	 * @param 	array 	$filters	The template filters to apply.
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @uses	Theater_Event_Date::get_field() to get the start time of an event date.
	 * @uses	Theater_Event_Date::get_field() to get the raw start time of an event date for use with the 'date' filter.
	 * @uses	WPT_Template_Placeholder_Filter::apply_to() to apply filters to the field value.
	 * @internal
	 * @return 	string				The HTML for the event starttime.
	 */
	function get_starttime_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo $this->get_post_type();?>_time <?php echo $this->get_post_type(); ?>_starttime"><?php

		$value = $this->starttime();

foreach ( $filters as $filter ) {
	if ( 'date' == $filter->name ) {
		$value = $filter->apply_to( $this->startdatetime(), $this );
	} else {
		$value = $filter->apply_to( $value, $this );
	}
}

		echo $value;

		?></div><?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * @deprecated 0.4
	 * @internal
	 */
	function get_production() {
		_deprecated_function( 'Theater_Event_Date::get_production()', '0.4', 'Theater_Event_Dates::get_event()' );
		return $this->get_event();
	}

	/**
	 * @deprecated 0.16
	 * @internal
	 */
	function production() {
		//_deprecated_function( 'Theater_Event_Date::production()', '0.16', 'Theater_Event_Dates::get_event()' );
		return $this->event();
	}

	/**
	 * @deprecated 0.4 Use $event->prices() instead.
	 * @internal
	 */
	function summary() {
		global $wp_theatre;
		if ( ! isset( $this->summary ) ) {
			$args = array(
			'summary' => true,
			);
			$this->summary = array(
			'prices' => $this->prices( $args ),
			);
		}
		return $this->summary;
	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	function datetime( $enddate = false ) {

		if ( true === $enddate ) {
			$value = $this->enddatetime();
		} else {
			$value = $this->startdatetime( $enddate );
		}

		/**
		 * Filter the event datetime.
		 *
		 * @since	0.12.7
		 * @param	datetime	$datetime	The event timestamp.
		 * @param	WPT_Event	$event		The event.
		 */
		$value = apply_filters( 'wpt/event/datetime', $value, $this );
		$value = apply_filters( 'wpt_event_datetime', $value, $this );

		return $value;
	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	function datetime_html( $filters = array() ) {
		$html = $this->get_field_html( 'startdatetime', $filters );

		/**
		 * Filter the HTML for the event timestamp.
		 *
		 * @since 	0.12.7
		 * @param	string		$html		The HTML for the event timestamp.
		 * @param	array       $filters    The template filters to apply.
		 * @param	WPT_Event	$event		The event.
		 */
		$html = apply_filters( 'wpt/event/datetime/html', $html, $filters, $this );

		return $html;
	}
}

?>
