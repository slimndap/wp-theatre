<?php

/** Usage:
 *
 *  $event = new WPT_Event();
 *  $event = new WPT_Event($post_id);
 *  $event = new WPT_Event($post);
 *
 *	echo $event->html(); // output the details of an event as HTML
 *
 *	echo $event->prices( array('summary'=>true) ) // // a summary of all available ticketprices
 *	echo $event->datetime() // timestamp of the event
 *	echo $event->date() // localized and formatted date of the event
 *	echo $event->time() // localized and formatted time of the event
 *
 */

class WPT_Event {

	const post_type_name = 'wp_theatre_event';

	const tickets_status_onsale = '_onsale';
	const tickets_status_hidden = '_hidden';
	const tickets_status_cancelled = '_cancelled';
	const tickets_status_soldout = '_soldout';
	const tickets_status_other = '_other';

	function __construct($ID = false, $PostClass = false) {
		$this->PostClass = $PostClass;

		if ( $ID instanceof WP_Post ) {
			// $ID is a WP_Post object
			if ( ! $PostClass ) {
				$this->post = $ID;
			}
			$ID = $ID->ID;
		}

		$this->ID = $ID;

		$this->format = 'full';
	}

	function post_type() {
		return get_post_type_object( self::post_type_name );
	}

	function post_class() {
		$classes = array();
		$classes[] = self::post_type_name;
		return implode( ' ',$classes );
	}

	/**
	 * Event city.
	 *
	 * @since 0.4
	 *
	 * @return string City.
	 */
	function city($args = array()) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset($this->city) ) {
			$this->city = apply_filters( 'wpt_event_venue',get_post_meta( $this->ID,'city',true ),$this );
		}

		if ( $args['html'] ) {
			$html = '<div class="'.self::post_type_name.'_city">';
			$html .= $wp_theatre->filter->apply( $this->city, $args['filters'], $this );
			$html .= '</div>';
			return apply_filters( 'wpt_event_city_html', $html, $this );
		} else {
			return $this->city;
		}
	}

	/**
	 * Returns value of a custom field.
	 * Fallback to production is custom field doesn't exist for event.
	 *
	 * @since 0.8.3
	 *
	 * @param string $field
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @param bool $fallback_to_production
	 * @return string.
	 */
	function custom($field, $args = array(), $fallback_to_production = true) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset($this->{$field}) ) {
			$custom_value = get_post_meta( $this->ID, $field, true );
	        if ( empty($custom_value) ) {
	            $custom_value = $this->production()->custom( $field );
	        }

			$this->{$field} = apply_filters(
				'wpt_event_'.$field,
				$custom_value,
				$field,
				$this
			);
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_'.$field.'">';
			$html .= $wp_theatre->filter->apply( $this->{$field}, $args['filters'], $this );
			$html .= '</div>';

			return apply_filters( 'wpt_event_'.$field.'_html', $html, $field, $this );
		} else {
			return $this->{$field};
		}
	}

	/**
	 * Event date.
	 *
	 * Returns the event date as plain text or as an HTML element.
	 *
	 * @since 0.4
	 * @since 0.10.15	Now accounts for timezones.
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function date($args = array()) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'start' => true,
			'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( $args['start'] ) {
			$field = 'event_date';
		} else {
			$field = 'enddate';
		}

		if ( ! isset($this->date[ $field ]) ) {
			$datetime_args = array( 'start' => $args['start'] );
			$this->date[ $field ] = apply_filters(
				'wpt_event_date',
				date_i18n( 
					get_option( 'date_format' ),
					$this->datetime( $datetime_args ) + get_option('gmt_offset') * 3600
				),
				$this
			);
		}

		if ( $args['html'] ) {
			$html = '<div class="'.self::post_type_name.'_date">';

			/**
			 * Apply WPT_Filters
			 * Use the raw datetime when the date filter is active.
			 */
			$filters_functions = $wp_theatre->filter->get_functions( $args['filters'] );
			if ( in_array( 'date', $filters_functions ) ) {
				$html .= $wp_theatre->filter->apply( $this->datetime[ $field ], $args['filters'], $this );
			} else {
				$html .= $wp_theatre->filter->apply( $this->date[ $field ], $args['filters'], $this );
			}

			$html .= '</div>';
			return apply_filters( 'wpt_event_date_html', $html, $this );
		} else {
			return $this->date[ $field ];
		}
	}

	/**
	 * Event date and time.
	 *
	 * Returns the event date and time combined as plain text or as an HTML element.
	 *
	 * @since 0.4
	 * @since 0.10.15	Always return the datetime in UTC.
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 *
	 * @see WPT_Event::date().
	 * @see WPT_Event::time().
	 *
	 * @return string text or HTML.
	 */
	function datetime($args = array()) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'start' => true,
			'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( $args['start'] ) {
			$field = 'event_date';
		} else {
			$field = 'enddate';
		}

		if ( ! isset($this->datetime[ $field ]) ) {
			$this->datetime[ $field ] = apply_filters(
				'wpt_event_datetime',
				date_i18n( 
					'U',
					strtotime(
						$this->post()->{$field},
						current_time( 'timestamp' )
					) - get_option('gmt_offset') * 3600
				), 
				$this 
			);
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_datetime">';

			/**
			 * Apply WPT_Filters
			 * Use the raw datetime when the date filter is active.
			 */
			$filters_functions = $wp_theatre->filter->get_functions( $args['filters'] );
			if ( in_array( 'date', $filters_functions ) ) {
				$html .= $wp_theatre->filter->apply( $this->datetime[ $field ], $args['filters'], $this );
			} else {
				$html .= $this->date( $args );
				$html .= $this->time( $args );
			}
			$html .= '</div>';
			return $html;
		} else {
			return $this->datetime[ $field ];
		}
	}

	function duration($args = array()) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );
		if (
			! isset($this->duration) &&
			! empty($this->post()->enddate) &&
			$this->post()->enddate > $this->post()->event_date
		) {

			// Don't use human_time_diff until filters are added.
			// See: https://core.trac.wordpress.org/ticket/27271
			// $this->duration = apply_filters('wpt_event_duration',human_time_diff(strtotime($this->post()->enddate), strtotime($this->post()->event_date)),$this);
			$seconds = abs( strtotime( $this->post()->enddate ) - strtotime( $this->post()->event_date ) );
			$minutes = (int) $seconds / 60;
			$text = $minutes.' '._n( 'minute','minutes', $minutes, 'wp_theatre' );
			$this->duration = apply_filters( 'wpt_event_duration',$text,$this );
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_duration">';
			$html .= $wp_theatre->filter->apply( $this->duration, $args['filters'], $this );
			$html .= '</div>';
			return $html;
		} else {
			return $this->duration;
		}
	}

	/**
	 * Event endtime.
	 *
	 * Returns the event endtime as plain text of as an HTML element.
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function endtime($args = array()) {
		global $wp_theatre;

		$defaults = array(
		'html' => false,
		'start' => false,
		'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( $args['start'] ) {
			$field = 'event_date';
		} else {
			$field = 'enddate';
		}

		if ( ! isset($this->endtime[ $field ]) ) {
			$datetime_args = array( 'start' => $args['start'] );
			$this->endtime[ $field ] = apply_filters(
				'wpt_event_endtime',
				date_i18n( 
					get_option( 'time_format' ),
					$this->datetime( $datetime_args ) + get_option('gmt_offset') * 3600
				),
				$this 
			);
		}

		if ( $args['html'] ) {
			$html = '<div class="'.self::post_type_name.'_endtime">';

			/**
		 * Apply WPT_Filters
		 * Use the raw datetime when the date filter is active.
		 */
			$filters_functions = $wp_theatre->filter->get_functions( $args['filters'] );
			if ( in_array( 'date', $filters_functions ) ) {
				$html .= $wp_theatre->filter->apply( $this->datetime[ $field ], $args['filters'], $this );
			} else {
				$html .= $wp_theatre->filter->apply( $this->endtime[ $field ], $args['filters'], $this );
			}
			$html .= '</div>';
			return apply_filters( 'wpt_event_endtime_html', $html, $this );
		} else {
			return $this->endtime[ $field ];
		}
	}


	/**
	 * Event location.
	 *
	 * Returns the event venue and city combined as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 *
	 * @see WPT_Event::venue().
	 * @see WPT_Event::city().
	 *
	 * @return string text or HTML.
	 */
	function location($args = array()) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset($this->location) ) {
			$location = '';
			$venue = $this->venue();
			$city = $this->city();
			if ( ! empty($venue) ) {
				$location .= $this->venue();
			}
			if ( ! empty($city) ) {
				if ( ! empty($venue) ) {
					$location .= ' ';
				}
				$location .= $this->city();
			}
			$this->location = apply_filters( 'wpt_event_location',$location,$this );
		}

		if ( $args['html'] ) {
			$venue = $this->venue();
			$city = $this->city();
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_location">';
			$html .= $this->venue( $args );
			$html .= $this->city( $args );
			$html .= '</div>'; // .location
			return apply_filters( 'wpt_event_location_html', $html, $this );
		} else {
			return $this->location;
		}
	}

	function permalink($args = array()) {
		return $this->production()->permalink( $args );
	}

	/**
	 * Gets the event prices.
	 *
	 * @since 	0.4
	 * @since 	0.10.14	Deprecated the HTML argument.
	 *					Use @see WPT_Event::prices_html() instead.
	 *
	 * @return 	array 	The event prices.
	 */
	function prices($deprecated = array()) {

		if ( ! empty($deprecated['html']) ) {
			return $this->prices_html();
		}

		if ( ! empty($deprecated['summary']) ) {
			return $this->prices_summary();
		}

		$prices = get_post_meta( $this->ID,'_wpt_event_tickets_price' );

		for ( $p = 0;$p < count( $prices );$p++ ) {
			$price_parts = explode( '|',$prices[ $p ] );
			$prices[ $p ] = (float) $price_parts[0];
		}

		/**
		 * Filter the event prices.
		 *
		 * @since	0.10.14
		 * @param 	array 	$prices	The current prices.
		 * @param 	WPT_Event	$event	The event.
		 */
		$prices = apply_filters( 'wpt/event/prices',$prices, $this );

		/**
		 * @deprecated	0.10.14
		 */
		$prices = apply_filters( 'wpt_event_prices',$prices, $this );

		return $prices;
	}

	/**
	 * Gets the HTML for the event prices.
	 *
	 * @since 	0.10.14
	 * @see		WPT_Event::prices_summary_html()
	 * @return 	string	The HTML.
	 */
	public function prices_html() {

		$html = '';

		$prices_summary_html = $this->prices_summary_html();

		if ( ! empty($prices_summary_html) ) {
			$html = '<div class="'.self::post_type_name.'_prices">'.$prices_summary_html.'</div>';
		}

		/**
		 * Filter the HTML for the event prices.
		 *
		 * @since	0.10.14
		 * @param 	string	 	$html	The current html.
		 * @param 	WPT_Event	$event	The event.
		 */
		$html = apply_filters( 'wpt/event/prices/html', $html, $this );

		/**
		 * @deprecated	0.10.14
		 */
		$html = apply_filters( 'wpt_event_prices_html', $html, $this );

		return $html;

	}

	/**
	 * Gets a summary of event prices.
	 *
	 * @since 	0.10.14
	 * @see 	WPT_Event::prices()
	 * @return 	array 	A summary of event prices.
	 */
	public function prices_summary() {

		global $wp_theatre;

		$prices = $this->prices();

		$prices_summary = '';

		if ( count( $prices ) ) {
			if ( count( $prices ) > 1 ) {
				$prices_summary .= __( 'from','wp_theatre' ).' ';
			}
			if ( ! empty($wp_theatre->wpt_tickets_options['currencysymbol']) ) {
				$prices_summary .= $wp_theatre->wpt_tickets_options['currencysymbol'].' ';
			}
			$prices_summary .= number_format_i18n( (float) min( $prices ), 2 );
		}

		/**
		 * Filter the summary of event prices.
		 *
		 * @since	0.10.14
		 * @param 	string	 	$prices_summary	The current summary.
		 * @param 	WPT_Event	$event			The event.
		 */
		$prices_summary = apply_filters( 'wpt/event/prices/summary',$prices_summary, $this );

		return $prices_summary;
	}

	/**
	 * Gets the HTML for the summary of event prices.
	 *
	 * @since 	0.10.14
	 * @see		WPT_Event::prices_summary()
	 * @return 	string	The HTML.
	 */
	public function prices_summary_html() {

		$html = $this->prices_summary();
		$html = esc_html( $html );
		$html = str_replace( ' ', '&nbsp;', $html );

		/**
		 * Filter the HTML for the summary of event prices.
		 *
		 * @since	0.10.14
		 * @param 	string	 	$html	The current html.
		 * @param 	WPT_Event	$event	The event.
		 */
		$html = apply_filters( 'wpt/event/prices/summary/html', $html, $this );

		return $html;
	}

	/**
	 * Event production.
	 *
	 * Returns the production of the event as a WPT_Production object.
	 *
	 * @since 0.4
	 *
	 * @return WPT_Production Production.
	 */
	function production() {
		if ( ! isset($this->production) ) {
			$this->production = new WPT_Production( get_post_meta( $this->ID,WPT_Production::post_type_name, true ), $this->PostClass );
		}
		return $this->production;
	}

	/**
	 * Event remark.
	 *
	 * Returns the event remark as plain text of as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function remark($args = array()) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'text' => false,
			'filters' => array()
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset($this->remark) ) {
			$this->remark = apply_filters( 'wpt_event_remark',get_post_meta( $this->ID,'remark',true ), $this );
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_remark">';
			$html .= $wp_theatre->filter->apply( $this->remark, $args['filters'], $this );
			$html .= '</div>';
			return apply_filters( 'wpt_event_remark_html', $html, $this );
		} else {
			return $this->remark;
		}
	}

	/**
	 * Gets a valid event tickets link.
	 *
	 * Returns a valid event tickets URL for events that are on sales and take
	 * place in the future.
	 *
	 * @since 	0.4
	 * @since 	0.10.14	Deprecated the HTML argument.
	 *					Use @see WPT_Event::tickets_html() instead.
	 *
	 * @return 	string	The tickets URL or ''.
	 */
	function tickets($deprecated = array()) {

		if ( ! empty($deprecated['html'] ) ) {
			return $this->tickets_html();
		}

		$tickets = '';

		if (
			self::tickets_status_onsale == $this->tickets_status() &&
			$this->datetime() > current_time( 'timestamp' )
		) {
			$tickets = $this->tickets_url();
		}

		/**
		 * Filter the valid event tickets link.
		 *
		 * @since	0.10.14
		 * @param 	array 		$prices	The current valid event tickets link.
		 * @param 	WPT_Event	$event	The event.
		 */
		$tickets = apply_filters( 'wpt/event/tickets',$tickets,$this );

		/**
		 * @deprecated	0.10.14
		 */
		$tickets = apply_filters( 'wpt_event_tickets',$tickets,$this );

		return $tickets;
	}

	/**
	 * Gets the text for the event tickets link.
	 *
	 * @since	0.10.14
	 * @return 	string	The text for the event tickets link.
	 */
	public function tickets_button() {
		$tickets_button = get_post_meta( $this->ID,'tickets_button',true );

		if ( empty($tickets_button) ) {
			$tickets_button = __( 'Tickets', 'wp_theatre' );
		}

		/**
		 * Filter the text for the event tickets link.
		 *
		 * @since	0.10.14
		 * @param 	string 		$tickets_button	The current text for the event tickets link.
		 * @param 	WPT_Event	$event			The event.
		 */
		$tickets_button = apply_filters( 'wpt/event/tickets/button', $tickets_button, $this );

		return ($tickets_button);
	}

	/**
	 * Gets the HTML for a valid event tickets link.
	 *
	 * @since	0.10.14
	 * @return 	string	The HTML for a valid event tickets link.
	 */
	public function tickets_html() {

		$html = '';

		$tickets_status = $this->tickets_status();

		$html .= '<div class="'.self::post_type_name.'_tickets">';

		if (
			self::tickets_status_onsale == $this->tickets_status() &&
			$this->datetime() > current_time( 'timestamp' )
		) {

			$html .= $this->tickets_url_html();

			$prices_html = $this->prices_html();
			$prices_html = apply_filters( 'wpt_event_tickets_prices_html', $prices_html, $this );
			$html .= $prices_html;

		} else {
			$html .= $this->tickets_status_html();
		}

		$html .= '</div>'; // .tickets

		/**
		 * Filter the HTML for the valid event tickets link.
		 *
		 * @since	0.10.14
		 * @param 	string 		$html	The current HTML.
		 * @param 	WPT_Event	$event	The event.
		 */
		$html = apply_filters( 'wpt/event/tickets/html', $html, $this );

		/**
		 * @deprecated	0.10.14
		 */
		$html = apply_filters( 'wpt_event_tickets_html', $html, $this );

		return $html;
	}

	/**
	 * Gets the event tickets status.
	 *
	 * @since 	0.10.14
	 * @return 	string	The event tickets status.
	 */
	public function tickets_status() {
		$tickets_status = get_post_meta( $this->ID,'tickets_status',true );

		if ( empty($tickets_status) ) {
			$tickets_status = self::tickets_status_onsale;
		}

		/**
		 * Filter the tickets status value for an event.
		 *
		 * @since 0.10.14	Renamed filter.
		 *
		 * @param	string 		$status	 The current value of the tickets status.
		 * @param	WPT_Event	$this	 The event object.
		 */
		$tickets_status = apply_filters( 'wpt/event/tickets/status', $tickets_status, $this );

		/**
		 * @since 0.10.9
		 * @deprecated	0.10.14
		 */
		$tickets_status = apply_filters( 'wpt_event_tickets_status', $tickets_status, $this );

		return $tickets_status;
	}

	/**
	 * Get the HTML for the event tickets status.
	 *
	 * @since 0.10.14
	 * @return string	The HTML for the event tickets status.
	 */
	public function tickets_status_html() {
		$tickets_status = $this->tickets_status();

		switch ( $tickets_status ) {
			case self::tickets_status_onsale :
				$label = __( 'On sale','wp_theatre' );
				break;
			case self::tickets_status_soldout :
				$label = __( 'Sold out','wp_theatre' );
				break;
			case self::tickets_status_cancelled :
				$label = __( 'Cancelled','wp_theatre' );
				break;
			case self::tickets_status_hidden :
				$label = '';
				break;
			default :
				$label = $tickets_status;
				$tickets_status = self::tickets_status_other;
		}

		$html = '';

		if ( ! empty($label) ) {
			$html .= '<span class="'.self::post_type_name.'_tickets_status '.self::post_type_name.'_tickets_status'.$tickets_status.'">'.$label.'</span>';
		}

		/**
		 * Filter the HTML for the event tickets status.
		 *
		 * @since	0.10.14
		 * @param 	string 		$html	The current HTML.
		 * @param 	WPT_Event	$event	The event.
		 */
		$html = apply_filters( 'wpt/event/tickets/status/html', $html, $this );

		return $html;
	}

	/**
	 * Get the event tickets URL.
	 *
	 * Returns the event tickets URL as plain text of as an HTML link element.
	 *
	 * @since 	0.8.3
	 * @since 	0.10.14	Deprecated the HTML argument.
	 *					Use @see WPT_Event::tickets_url_html() instead.
	 * @return 	string 	The event tickets URL.
	 */

	function tickets_url($deprecated = array()) {

		global $wp_theatre;

		if ( ! empty($deprecated['html']) ) {
			return $this->tickets_url_html();
		}

		$tickets_url = get_post_meta( $this->ID,'tickets_url',true );

		if (
			! empty($wp_theatre->wpt_tickets_options['integrationtype']) &&
			'iframe' == $wp_theatre->wpt_tickets_options['integrationtype']  &&
			! empty($tickets_url)
		) {
			$tickets_url = get_permalink( $wp_theatre->wpt_tickets_options['iframepage'] );
			$tickets_url = add_query_arg(
				array(
					__( 'Event','wp_theatre' ) => $this->ID,
				),
				$tickets_url
			);
		}

		/**
		 * Filter the event tickets URL.
		 *
		 * @since 0.10.14
		 *
		 * @param	string 		$status	 The current value of the event tickets URL.
		 * @param	WPT_Event	$this	 The event object.
		 */
		$tickets_url = apply_filters( 'wpt/event/tickets/url',$tickets_url,$this );

		/**
		 * @deprecated	0.10.14
		 */
		$tickets_url = apply_filters( 'wpt_event_tickets_url',$tickets_url,$this );

		return $tickets_url;
	}

	/**
	 * Get the HTML for the event tickets URL.
	 *
	 * @since 0.10.14
	 * @return string	The HTML for the event tickets URL.
	 */
	public function tickets_url_html() {
		global $wp_theatre;

		$html = '';

		$tickets_url = $this->tickets_url();

		if ( ! empty($tickets_url) ) {

			$html .= '<a href="'.$tickets_url.'" rel="nofollow"';

			/**
			 * Add classes to tickets link.
			 */

			$classes = array();
			$classes[] = self::post_type_name.'_tickets_url';
			if ( ! empty($wp_theatre->wpt_tickets_options['integrationtype']) ) {
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
			$html .= $this->tickets_button();
			$html .= '</a>';

		}

		/**
		 * Filter the HTML for the event tickets URL.
		 *
		 * @since	0.10.14
		 * @param 	string 		$html	The current URL.
		 * @param 	WPT_Event	$event	The event.
		 */
		$html = apply_filters( 'wpt/event/tickets/url/html', $html, $this );

		/**
		 * @deprecated	0.10.14
		 */
		$html = apply_filters( 'wpt_event_tickets_url_html', $html, $this );

		return $html;
	}

	/**
	 * Gets the title of the event.
	 *
	 * The title is taken from the parent production, since event don't have titles.
	 *
	 * @since ?.?
	 * @since 0.10.10	Fixed the name of the 'wpt_event_title'-filter.
	 *					Closes #114.
	 *
	 * @param array $args {
	 * 		@type bool 	$html 		Return HTML? Default <false>.
	 *		@type array	$filters
	 * }
	 * @return string text or HTML.
	 */
	function title($args = array()) {
		global $wp_theatre;

		$defaults = array(
		'html' => false,
		'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset($this->title) ) {
			$this->title = apply_filters( 'wpt_event_title',$this->production()->title(),$this );
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_title">';
			$html .= $wp_theatre->filter->apply( $this->title, $args['filters'], $this );
			$html .= '</div>';
			return apply_filters( 'wpt_event_title_html', $html, $this );
		} else {
			return $this->title;
		}

	}

	/**
	 * Event time.
	 *
	 * Returns the event time as plain text of as an HTML element.
	 *
	 * @since 0.4
	 * @since 0.10.15	Now accounts for timezones.
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function time($args = array()) {
		global $wp_theatre;

		$defaults = array(
		'html' => false,
		'start' => true,
		'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( $args['start'] ) {
			$field = 'event_date';
		} else {
			$field = 'enddate';
		}

		if ( ! isset($this->time[ $field ]) ) {
			$datetime_args = array( 'start' => $args['start'] );
			$this->time[ $field ] = apply_filters(
				'wpt_event_time',
				date_i18n( 
					get_option( 'time_format' ),
					$this->datetime( $datetime_args ) + get_option('gmt_offset') * 3600
				),
				$this 
			);
		}

		if ( $args['html'] ) {
			$html = '<div class="'.self::post_type_name.'_time">';

			/**
		 * Apply WPT_Filters
		 * Use the raw datetime when the date filter is active.
		 */
			$filters_functions = $wp_theatre->filter->get_functions( $args['filters'] );
			if ( in_array( 'date', $filters_functions ) ) {
				$html .= $wp_theatre->filter->apply( $this->datetime[ $field ], $args['filters'], $this );
			} else {
				$html .= $wp_theatre->filter->apply( $this->time[ $field ], $args['filters'], $this );
			}
			$html .= '</div>';
			return apply_filters( 'wpt_event_time_html', $html, $this );
		} else {
			return $this->time[ $field ];
		}
	}

	/**
	 * Event venue.
	 *
	 * @since 0.4
	 *
	 * @return string Venue.
	 */
	function venue($args = array()) {
		global $wp_theatre;

		$defaults = array(
		'html' => false,
		'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset($this->venue) ) {
			$this->venue = apply_filters( 'wpt_event_venue',get_post_meta( $this->ID,'venue',true ),$this );
		}

		if ( $args['html'] ) {
			$html = '<div class="'.self::post_type_name.'_venue">';
			$html .= $wp_theatre->filter->apply( $this->venue, $args['filters'], $this );
			$html .= '</div>';
			return apply_filters( 'wpt_event_venue_html', $html, $this );
		} else {
			return $this->venue;
		}
	}

	/**
	 * HTML version of the event.
	 *
	 * @since 0.4
	 * @since 0.10.8	Added a filter to the default template.
	 *
	 * @param array $args {
	 *
	 *	   @type array $fields Fields to include. Default <array('title','remark', 'datetime','location')>.
	 *     @type bool $thumbnail Include thumbnail? Default <true>.
	 *     @type bool $tickets Include tickets button? Default <true>.
	 * }
	 * @return string HTML.
	 */
	function html($args = array()) {
		$defaults = array(
		'template' => apply_filters(
			'wpt_event_template_default',
			'{{thumbnail|permalink}} {{title|permalink}} {{remark}} {{datetime}} {{location}} {{tickets}}'
		),
		);
		$args = wp_parse_args( $args, $defaults );

		$classes = array();
		$classes[] = self::post_type_name;

		$html = $args['template'];

		// Parse template
		$placeholders = array();
		preg_match_all( '~{{(.*?)}}~', $html, $placeholders );
		foreach ( $placeholders[1] as $placeholder ) {

			$field = '';
			$filters = array();

			$placeholder_parts = explode( '|',$placeholder );

			if ( ! empty($placeholder_parts[0]) ) {
				$field = $placeholder_parts[0];
			}
			if ( ! empty($placeholder_parts[1]) ) {
				$filters = $placeholder_parts;
				array_shift( $filters );
			}

			switch ( $field ) {
				case 'date':
				case 'datetime':
				case 'duration':
				case 'endtime':
				case 'location':
				case 'remark':
				case 'time':
				case 'tickets':
				case 'tickets_url':
				case 'title':
				case 'prices':
					$replacement = $this->{$field}(
					array(
						'html' => true,
						'filters' => $filters,
					)
					);
					break;
				case 'categories':
				case 'content':
				case 'excerpt':
				case 'thumbnail':
					$replacement = $this->production()->{$field}(
					array(
						'html' => true,
						'filters' => $filters,
					)
					);
					break;
				default:
					$replacement = $this->custom(
						$field,
						array(
						'html' => true,
						'filters' => $filters,
						)
					);
			}
			$html = str_replace( '{{'.$placeholder.'}}', $replacement, $html );
		}

		// Tickets
		if ( false !== strpos( $html,'{{tickets}}' ) ) {
			$tickets_args = array(
			'html' => true,
			);
			$tickets = $this->tickets( $tickets_args );
			if ( empty($tickets) ) {
				$classes[] = self::post_type_name.'_without_tickets';
			}
			$html = str_replace( '{{tickets}}', $tickets, $html );
		}

		// Filters
		$html = apply_filters( 'wpt_event_html',$html, $this );
		$classes = apply_filters( 'wpt_event_classes',$classes, $this );

		// Wrapper
		$html = '<div class="'.implode( ' ',$classes ).'">'.$html.'</div>';

		return $html;
	}

	/**
	 * The custom post as a WP_Post object.
	 *
	 * It can be used to access all properties and methods of the corresponding WP_Post object.
	 *
	 * Example:
	 *
	 * $event = new WPT_Event();
	 * echo WPT_Event->post()->post_title();
	 *
	 * @since 0.3.5
	 *
	 * @return mixed A WP_Post object.
	 */
	public function post() {
		return $this->get_post();
	}

	private function get_post() {
		if ( ! isset($this->post) ) {
			if ( $this->PostClass ) {
				$this->post = new $this->PostClass( $this->ID );
			} else {
				$this->post = get_post( $this->ID );
			}
		}
		return $this->post;
	}

	/**
	 * HTML version of the event.
	 *
	 * @deprecated 0.4 Use $event->html() instead.
	 * @see $event->html()
	 *
	 * @return string HTML.
	 */
	function compile() {
		return $this->html();
	}

	/**
	 * Event production.
	 *
	 * Returns the production of the event as a WPT_Production object.
	 *
	 * @deprecated 0.4 Use $event->production() instead.
	 * @see $event->production()
	 *
	 * @return WPT_Production Production.
	 */
	function get_production() {
		return $this->production();
	}

	/**
	 * Echoes an HTML version of the event.
	 *
	 * @deprecated 0.4 Use echo $event->html() instead.
	 * @see $event->html()
	 *
	 * @return void.
	 */
	function render() {
		echo $this->html();
	}

	/**
	 * Summary of the event.
	 *
	 * An array of strings that can be used to summerize the event.
	 * Currently only returns a summary of the event prices.
	 *
	 * @deprecated 0.4 Use $event->prices() instead.
	 * @see $event->prices()
	 *
	 * @return array Summary.
	 */
	function summary() {
		global $wp_theatre;
		if ( ! isset($this->summary) ) {
			$args = array(
			'summary' => true,
			);
			$this->summary = array(
			'prices' => $this->prices( $args ),
			);
		}
		return $this->summary;
	}

}

?>