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
 *	echo $event->startdate() // localized and formatted date of the event
 *	echo $event->starttime() // localized and formatted time of the event
 *
 */

class WPT_Event {

	const post_type_name = 'wp_theatre_event';

	const tickets_status_onsale = '_onsale';
	const tickets_status_hidden = '_hidden';
	const tickets_status_cancelled = '_cancelled';
	const tickets_status_soldout = '_soldout';
	const tickets_status_other = '_other';

	public $ID;
	public $PostClass;
	public $post;
	public $format;

	function __construct( $ID = false, $PostClass = false ) {
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

	protected function apply_template_filters( $value, $filters ) {
		foreach ( $filters as $filter ) {
			$value = $filter->apply_to( $value, $this );
		}
		return $value;
	}

	/**
	 * Event city.
	 *
	 * @since 	0.4
	 * @since	0.15.29	Added a filter for the event city value.
	 *					See: https://github.com/slimndap/wp-theatre/issues/254
	 *
	 * @return 	string 	City.
	 */
	function city( $args = array() ) {

		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array(),
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->city ) ) {
			
			/**
			 * Filter the value of the event city.
			 * 
			 * @since	0.15.29
			 * @param	string		$city	The value of the event city.
			 * @param	WPT_Event	$event	The event.
			 */
			$this->city = apply_filters( 'wpt_event_city', get_post_meta( $this->ID, 'city', true ), $this );
		}

		if ( $args['html'] ) {
			$html = '<div class="'.self::post_type_name.'_city">';
			$html .= $this->apply_template_filters( $this->city, $args['filters'] );
			$html .= '</div>';
			return apply_filters( 'wpt_event_city_html', $html, $this );
		} else {
			return $this->city;
		}
	}

	/**
	 * Returns value of a custom field.
	 *
	 * @since 	0.8.3
	 * @since	0.15	Fixed an error when no production is set for the event.
	 * @since	0.15.27	Fix: $fallback_to_production was not doing anything.
	 *					Deprecated the $args argument.
	 *					Moved HTML output to WPT_Event::custom_html().
	 *
	 * @uses 	WPT_Event::custom_html() to get the HTM for a custom field.
	 * @uses	WPT_Event::production() to get the production of an event.
	 *
	 * @param 	string 	$field					The custom field.
	 * @param	bool	$fallback_to_production	Use the value of the production if the value of the event is empty?
	 *											Defaults to <true>.
	 * @return  string							The value of a custom field.
	 */
	function custom( $field, $fallback_to_production = true ) {
		
		// Add backwards compatibility for the deprecated $args argument.
		if ( is_array( $fallback_to_production ) && !empty( $fallback_to_production['html'] ) ) {
			$filters = array();
			if ( !empty( $fallback_to_production['filters'] ) ) {
				$filters = $fallback_to_production['filters'];
			}
			return $this->custom_html( $field, $filters );
		}
		
		$value = get_post_meta( $this->ID, $field, true );
		
		/**
		 * Filter the value of a custom field.
		 * 
		 * @since	0.8.3
		 * @param	string		$value	The value of a custom field.
		 * @param	string		$field	The custom field.
		 * @param	WPT_Event	$event	The event.
		 */
		$value = apply_filters( 'wpt_event_'.$field, $value, $field, $this );
		
		if ( empty($value) && $fallback_to_production && $production = $this->production() ) {
			$value = $production->custom( $field );
		}

		return $value;
	}
	
	/**
	 * Gets the HTML for a custom field.
	 * 
	 * @since	0.15.27
	 *
	 * @uses	WPT_Event::custom() to get the value of a custom field.
	 * @uses	WPT_Event::apply_template_filters() to apply template filters to the custom field value.
	 *
	 * @param 	string 	$field					The custom field.
	 * @param 	array 	$filters				The template filters to apply.
	 * @param	bool	$fallback_to_production	Use the value of the production if the value of the event is empty?
	 * @return 	string	The HTML for a custom field.
	 */
	function custom_html( $field, $filters = array(), $fallback_to_production = true ) {
		
		$value = $this->custom( $field, $fallback_to_production );
		
		ob_start();
		
		?><div class="<?php echo self::post_type_name; ?>_<?php echo $field; ?>"><?php
			echo $this->apply_template_filters( $value, $filters );
		?></div><?php
		
		$html = ob_get_clean();
		
		/**
		 * Filter the HTML for a custom field.
		 * 
		 * @since	0.8.3
		 *
		 * @param	string		$html	The HTML for a custom field.
		 * @param	string		$field	The custom field.
		 * @param	WPT_Event	$event	The event.
		 */
		$html = apply_filters( 'wpt_event_'.$field.'_html', $html, $field, $this );
		
		return $html;
	}

	/**
	 * Gets the event timestamp.
	 *
	 * @since 	0.4
	 * @since 	0.10.15		Always return the datetime in UTC.
	 * @since	0.12.7		Moved HTML output to WPT_Event::datetime_html().
	 *
	 * @param 	bool        $enddate    Wheter to return the end datetime instead of the start endtime.
	 * @return 	datetime				The event timestamp.
	 *									Returns false if no date is set.
	 */
	function datetime( $enddate = false ) {

		if ( ! empty( $enddate['html'] ) ) {
			$filters = array();
			if ( ! empty( $enddate['filters'] ) ) {
				$filters = $enddate['filters'];
			}
			return $this->datetime_html( $filters );
		}

		if ( ! empty( $enddate['start'] ) ) {
			$enddate = false;
		}

		if ( false === $enddate ) {
			$date = get_post_meta( $this->ID, 'event_date', true );
		} else {
			$date = get_post_meta( $this->ID, 'enddate', true );
		}

		if ( empty( $date ) ) {
			return false;
		}

		$datetime = date_i18n( 'U', strtotime( $date, current_time( 'timestamp' ) ) - get_option( 'gmt_offset' ) * 3600 );

		/**
		 * Filter the event datetime.
		 *
		 * @since	0.12.7
		 * @param	datetime	$datetime	The event timestamp.
		 * @param	WPT_Event	$event		The event.
		 */
		$datetime = apply_filters( 'wpt/event/datetime', $datetime, $this );
		$datetime = apply_filters( 'wpt_event_datetime', $datetime, $this );

		return $datetime;
	}

	/**
	 * Gets the HTML for the event timestamp.
	 *
	 * @since	0.12.7
	 * @param 	array 	$filters	The template filters to apply.
	 * @return 	sring				The HTML for the event timestamp.
	 */
	function datetime_html( $filters = array() ) {
		$html = '<div class="'.self::post_type_name.'_datetime">';

		$datetime_html = $this->startdate_html().$this->starttime_html();
		foreach ( $filters as $filter ) {
			if ( 'date' == $filter->name ) {
				$datetime_html = $filter->apply_to( $this->datetime(), $this );
			} else {
				$datetime_html = $filter->apply_to( $datetime_html, $this );
			}
		}
		$html .= $datetime_html;

		$html .= '</div>';

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

	/**
	 * Gets the duration of an event.
	 * 
	 * @since	0.?
	 * @since	0.15.16		Fixed call to apply_template_filters(). '$this->' was missing.
	 * 						See: https://github.com/slimndap/wp-theatre/pull/231
	 * @param 	array $args (default: array())
	 * @return 	string
	 */
	function duration( $args = array() ) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array(),
		);
		$args = wp_parse_args( $args, $defaults );
		if (
			! isset( $this->duration ) &&
			! empty( $this->post()->enddate ) &&
			$this->post()->enddate > $this->post()->event_date
		) {

			// Don't use human_time_diff until filters are added.
			// See: https://core.trac.wordpress.org/ticket/27271
			// $this->duration = apply_filters('wpt_event_duration',human_time_diff(strtotime($this->post()->enddate), strtotime($this->post()->event_date)),$this);
			$seconds = abs( strtotime( $this->post()->enddate ) - strtotime( $this->post()->event_date ) );
			$minutes = (int) $seconds / 60;
			$text = $minutes.' '._n( 'minute','minutes', $minutes, 'theatre' );
			$this->duration = apply_filters( 'wpt_event_duration',$text,$this );
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_duration">';
			$html .= $this->apply_template_filters( $this->duration, $args['filters'] );
			$html .= '</div>';
			return $html;
		} else {
			return $this->duration;
		}
	}

	/**
	 * Gets the event enddate.
	 *
	 * @since	0.12
	 * @since	0.12.7	Now returns <false> is no endate is set.
	 * 					See: https://github.com/slimndap/wp-theatre/issues/165
	 * @return	string 	The event enddate.
	 *					Returns <false> if no endate is set.
	 */
	function enddate() {
		$enddate = false;
		if ( $datetime = $this->datetime( true ) ) {
			$enddate = date_i18n(
				get_option( 'date_format' ),
				$datetime + get_option( 'gmt_offset' ) * 3600
			);
		}
		$enddate = apply_filters( 'wpt/event/enddate', $enddate, $this );
		return $enddate;
	}

	/**
	 * Gets the HTML for the event enddate.
	 *
	 * @since	0.12
	 * @since	0.12.7	No longer returns a date when no enddate is set.
	 * 					See: https://github.com/slimndap/wp-theatre/issues/165
	 * @param 	array 	$filters	The template filters to apply.
	 * @return 	sring				The HTML for the event enddate.
	 */
	function enddate_html( $filters = array() ) {
		$html = '<div class="'.self::post_type_name.'_date '.self::post_type_name.'_enddate">';

		if ( $enddate_html = $this->enddate() ) {
			foreach ( $filters as $filter ) {
				if ( 'date' == $filter->name ) {
					$enddate_html = $filter->apply_to(
						$this->datetime( true ) + get_option( 'gmt_offset' ) * 3600,
						$this
					);
				} else {
					$enddate_html = $filter->apply_to( $enddate_html, $this );
				}
			}
			$html .= $enddate_html;

		}

		$html .= '</div>';

		$html = apply_filters( 'wpt/event/enddate/html', $html, $filters, $this );

		return $html;
	}

	/**
	 * Gets the event endtime.
	 *
	 * @since	0.12
	 * @since	0.12.7	Now returns <false> is no endate is set.
	 * 					See: https://github.com/slimndap/wp-theatre/issues/165
	 * @return	string 	The event endtime.
	 *					Returns <false> if no endate is set.
	 */
	function endtime() {
		$endtime = false;
		if ( $datetime = $this->datetime( true ) ) {
			$endtime = date_i18n(
				get_option( 'time_format' ),
				$datetime + get_option( 'gmt_offset' ) * 3600
			);
		}
		$endtime = apply_filters( 'wpt/event/endtime', $endtime, $this );
		return $endtime;
	}

	/**
	 * Gets the HTML for the event endtime.
	 *
	 * @since	0.12
	 * @since	0.12.7	No longer returns a time when no enddate is set.
	 * 					See: https://github.com/slimndap/wp-theatre/issues/165
	 * @param 	array 	$filters	The template filters to apply.
	 * @return 	sring				The HTML for the event endtime.
	 */
	function endtime_html( $filters = array() ) {
		global $wp_theatre;

		$html = '<div class="'.self::post_type_name.'_time '.self::post_type_name.'_endtime">';

		if ( $endtime_html = $this->endtime() ) {
			foreach ( $filters as $filter ) {
				if ( 'date' == $filter->name ) {
					$endtime_html = $filter->apply_to( $this->datetime( true ) + get_option( 'gmt_offset' ) * 3600, $this );
				} else {
					$endtime_html = $filter->apply_to( $endtime_html, $this );
				}
			}
			$html .= $endtime_html;
		}

		$html .= '</div>';

		$html = apply_filters( 'wpt/event/endtime/html', $html, $filters, $this );

		return $html;
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
	function location( $args = array() ) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array(),
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->location ) ) {
			$location = '';
			$venue = $this->venue();
			$city = $this->city();
			if ( ! empty( $venue ) ) {
				$location .= $this->venue();
			}
			if ( ! empty( $city ) ) {
				if ( ! empty( $venue ) ) {
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

	/**
	 * Get the event permalink.
	 *
	 * The permalink is inherited from the parent production.
	 *
	 * @since	0.?
	 * @since	0.13.6	Added a 'wpt/event/permalink' filter.
	 *					Moved HTML version to separate function.
	 * @return 	string	The permalink.
	 */
	function permalink( $deprecated = array() ) {

		if ( ! empty( $deprecated['html'] ) ) {
			return $this->permalink_html( $deprecated );
		}

		$permalink = $this->production()->permalink( $deprecated );

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
	 * @return 	string	The HTML for the event permalink.
	 */
	function permalink_html( $args = array() ) {
		$args['html'] = true;
		$html = $this->production()->permalink( $args );

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
	 * @since 	0.10.14	Deprecated the HTML argument.
	 *					Use @see WPT_Event::prices_html() instead.
	 *
	 * @return 	array 	The event prices.
	 */
	function prices( $deprecated = array() ) {

		if ( ! empty( $deprecated['html'] ) ) {
			return $this->prices_html();
		}

		if ( ! empty( $deprecated['summary'] ) ) {
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
		 * @param 	array 		$prices	The current prices.
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

		if ( ! empty( $prices_summary_html ) ) {
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
				$prices_summary .= __( 'from','theatre' ).' ';
			}
			if ( ! empty( $wp_theatre->wpt_tickets_options['currencysymbol'] ) ) {
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
	 * @since 	0.4
	 * @since	0.15	Removed local caching of event production.
	 *					Return <false> if no production is set.
	 *
	 * @return 	WPT_Production 	The production.
	 *							Returns <false> if no production is set.
	 */
	function production() {
		$production_id = get_post_meta( $this->ID,WPT_Production::post_type_name, true );

		// Bail if no production ID is set.
		if (empty($production_id)) {
			return false;
		}

		/*
		 * Bail if production doesn't exist.
		 * See: https://tommcfarlin.com/wordpress-post-exists-by-id/
		 */
		if (FALSE === get_post_status( $production_id )) {
			return false;
		}
		
		$production = new WPT_Production( $production_id, $this->PostClass );
		return $production;
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
	function remark( $args = array() ) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'text' => false,
			'filters' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->remark ) ) {
			$this->remark = apply_filters( 'wpt_event_remark',get_post_meta( $this->ID,'remark',true ), $this );
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_remark">';
			$html .= $this->apply_template_filters( $this->remark, $args['filters'] );
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
	 * @since	0.13.1	Check for upcoming event now accounts for timezones.
	 *					Fixes #167.
	 *
	 * @return 	string	The tickets URL or ''.
	 */
	function tickets( $deprecated = array() ) {

		if ( ! empty( $deprecated['html'] ) ) {
			return $this->tickets_html();
		}

		$tickets = '';

		if (
			self::tickets_status_onsale == $this->tickets_status() &&
			$this->datetime() > current_time( 'timestamp', true )
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

		if ( empty( $tickets_button ) ) {
			$tickets_button = __( 'Tickets', 'theatre' );
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
	 * @since	0.11.10	Don't return anything for historic events with an 'on sale' status.
	 *					Fixes #118.
	 * @since	0.13.1	Check for upcoming event now accounts for timezones.
	 *					Fixes #167.
	 *
	 * @return 	string	The HTML for a valid event tickets link.
	 */
	public function tickets_html() {
		$html = '';

		$tickets_status = $this->tickets_status();

		$html .= '<div class="'.self::post_type_name.'_tickets">';

		if ( self::tickets_status_onsale == $this->tickets_status() ) {

			if ( $this->datetime() > current_time( 'timestamp', true ) ) {
				$html .= $this->tickets_url_html();

				$prices_html = $this->prices_html();
				$prices_html = apply_filters( 'wpt_event_tickets_prices_html', $prices_html, $this );
				$html .= $prices_html;
			}
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

		if ( empty( $tickets_status ) ) {
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
	 * Gets the event tickets URL.
	 *
	 * @since 	0.8.3
	 * @since 	0.10.14	Deprecated the HTML argument.
	 *					Use @see WPT_Event::tickets_url_html() instead.
	 * @since	0.12	Moved the iframe url to a new method.
	 *					@see WPT_Event::tickets_url_iframe().
	 * @return 	string 	The event tickets URL.
	 */

	function tickets_url( $deprecated = array() ) {

		global $wp_theatre;

		if ( ! empty( $deprecated['html'] ) ) {
			return $this->tickets_url_html();
		}

		$tickets_url = get_post_meta( $this->ID,'tickets_url',true );
	
		if (
			! empty( $wp_theatre->wpt_tickets_options['integrationtype'] ) &&
			'iframe' == $wp_theatre->wpt_tickets_options['integrationtype']  &&
			! empty( $tickets_url ) &&
			$tickets_url_iframe = $this->tickets_url_iframe()
		) {
			$tickets_url = $tickets_url_iframe;
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
	 * Gets the event tickets iframe URL.
	 *
	 * @since 	0.12
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
		if (get_option('permalink_structure') && $production = $this->production()) {
			$tickets_url_iframe = trailingslashit($tickets_url_iframe).$production->post()->post_name.'/'.$this->ID;
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
	 * Get the HTML for the event tickets URL.
	 *
	 * @since 0.10.14
	 * @return string	The HTML for the event tickets URL.
	 */
	public function tickets_url_html() {
		global $wp_theatre;

		$html = '';

		$tickets_url = $this->tickets_url();

		if ( ! empty( $tickets_url ) ) {

			$html .= '<a href="'.$tickets_url.'" rel="nofollow"';

			/**
			 * Add classes to tickets link.
			 */

			$classes = array();
			$classes[] = self::post_type_name.'_tickets_url';
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
	 * @since 	?.?
	 * @since 	0.10.10	Fixed the name of the 'wpt_event_title'-filter.
	 *					Closes #114.
	 * @since	0.15	Fixed an error when no production is set for the event.
	 *
	 * @param array $args {
	 * 		@type bool 	$html 		Return HTML? Default <false>.
	 *		@type array	$filters
	 * }
	 * @return string text or HTML.
	 */
	function title( $args = array() ) {
		global $wp_theatre;

		$defaults = array(
		'html' => false,
		'filters' => array(),
		);
		$args = wp_parse_args( $args, $defaults );
		if ( ! isset($this->title) ) {
			$title = '';
			if ($production = $this->production() ) {
				$title = $production->title();
			}
			$this->title = apply_filters( 'wpt_event_title',$title,$this );
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_title">';
			$html .= $this->apply_template_filters( $this->title(), $args['filters'] );
			$html .= '</div>';
			return apply_filters( 'wpt_event_title_html', $html, $this );
		} else {
			return $this->title;
		}

	}

	/**
	 * Event venue.
	 *
	 * @since 0.4
	 *
	 * @return string Venue.
	 */
	function venue( $args = array() ) {
		global $wp_theatre;

		$defaults = array(
		'html' => false,
		'filters' => array(),
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->venue ) ) {
			$this->venue = apply_filters( 'wpt_event_venue',get_post_meta( $this->ID,'venue',true ),$this );
		}

		if ( $args['html'] ) {
			$html = '<div class="'.self::post_type_name.'_venue">';
			$html .= $this->apply_template_filters( $this->venue(), $args['filters'] );
			$html .= '</div>';
			return apply_filters( 'wpt_event_venue_html', $html, $this );
		} else {
			return $this->venue;
		}
	}

	/**
	 * Gets the HTML for an event.
	 *
	 * @since 	0.4
	 * @since 	0.10.8	Added a filter to the default template.
	 * @since	0.14.7	Added the $args parameter.
	 * @since	0.15.2	Removed the $args parameter.
	 *
	 * @param	string	$template	The template for the event HTML.
	 * @param 	array	$args		The listing args (if the event is part of a listing).
	 * @return 	string				The HTML for an event.
	 */
	function html( $template = '' ) {
		if ( is_array( $template ) ) {
			$defaults = array(
				'template' => '',
			);
			$args = wp_parse_args( $template, $defaults );
			$template = $args['template'];
		}

		$classes = array();
		$classes[] = self::post_type_name;

		$template = new WPT_Event_Template( $this, $template );
		$html = $template->get_merged();

		// Tickets
		if ( false !== strpos( $html,'{{tickets}}' ) ) {
			$tickets_args = array(
			'html' => true,
			);
			$tickets = $this->tickets( $tickets_args );
			if ( empty( $tickets ) ) {
				$classes[] = self::post_type_name.'_without_tickets';
			}
			$html = str_replace( '{{tickets}}', $tickets, $html );
		}

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
		if ( ! isset( $this->post ) ) {
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
	 * Gets the event startdate.
	 *
	 * @since	0.12
	 * @since	0.15.11	Added support for next day start time offset.
	 *
	 * @uses	Theater_Helpers_Time::get_next_day_start_time_offset() to get the next day start time offset.
	 * @return	string The event startdate.
	 */
	function startdate() {
		$startdate_datetime = $this->datetime();
		$startdate_datetime += ( get_option( 'gmt_offset' ) * 3600 );
		$startdate_datetime -= Theater_Helpers_Time::get_next_day_start_time_offset();

		$startdate = date_i18n(	get_option( 'date_format' ), $startdate_datetime);

		$startdate = apply_filters( 'wpt/event/startdate', $startdate, $this );

		return $startdate;
	}

	/**
	 * Gets the HTML for the event startdate.
	 *
	 * @since	0.12
	 * @sine	0.15.1	Fix: No longer compensates for the timezone when applying the 'date'-filter.
	 *					This is already handled by WPT_Template_Placeholder_Filter::callback_date() and
	 *					resulted in double compensations.
	 * @param 	array 	$filters	The template filters to apply.
	 * @return 	sring				The HTML for the event startdate.
	 */
	function startdate_html( $filters = array() ) {
		global $wp_theatre;

		$html = '<div class="'.self::post_type_name.'_date '.self::post_type_name.'_startdate">';

		$startdate_html = $this->startdate();
		foreach ( $filters as $filter ) {
			if ( 'date' == $filter->name ) {
				$startdate_html = $filter->apply_to( $this->datetime(), $this );
			} else {
				$startdate_html = $filter->apply_to( $startdate_html, $this );
			}
		}
		$html .= $startdate_html;

		$html .= '</div>';

		$html = apply_filters( 'wpt/event/startdate/html', $html, $filters, $this );

		return $html;
	}

	/**
	 * Gets the event starttime.
	 *
	 * @since	0.12
	 * @return	string The event starttime.
	 */
	function starttime() {
		$starttime = date_i18n(
			get_option( 'time_format' ),
			$this->datetime() + get_option( 'gmt_offset' ) * 3600
		);
		$starttime = apply_filters( 'wpt/event/starttime', $starttime, $this );
		return $starttime;
	}

	/**
	 * Gets the HTML for the event starttime.
	 *
	 * @since	0.12
	 * @since	0.15.18	No longer compensates for timezones when applying the 'date' filter,
	 *					because this already handled by the date filter by itself.
	 *					Fixes https://github.com/slimndap/wp-theatre/issues/219.
	 *
	 * @param 	array 	$filters	The template filters to apply.
	 * @return 	sring				The HTML for the event starttime.
	 */
	function starttime_html( $filters = array() ) {
		global $wp_theatre;

		$html = '<div class="'.self::post_type_name.'_time '.self::post_type_name.'_starttime">';

		$starttime_html = $this->starttime();
		foreach ( $filters as $filter ) {
			if ( 'date' == $filter->name ) {
				$starttime_html = $filter->apply_to( $this->datetime(), $this );
			} else {
				$starttime_html = $filter->apply_to( $starttime_html, $this );
			}
		}
		$html .= $starttime_html;

		$html .= '</div>';

		$html = apply_filters( 'wpt/event/starttime/html', $html, $filters, $this );

		return $html;
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
	 * @deprecated 0.12
	 * @see WPT_Event::startdate()
	 * @see WPT_Event::enddate()
	 */
	function date( $deprecated = array() ) {
		if ( empty( $deprecated['html'] ) ) {
			if ( isset( $deprecated['start'] ) && false === $deprecated['start'] ) {
				_deprecated_function( 'WPT_Event::date()', '0.12', 'WPT_Event::enddate()' );
				return $this->enddate( $deprecated );
			} else {
				_deprecated_function( 'WPT_Event::date()', '0.12', 'WPT_Event::startdate()' );
				return $this->startdate( $deprecated );
			}
		} else {
			$filters = array();
			if ( ! empty( $deprecated['filters'] ) ) {
				$filters = $deprecated['filters'];
			}

			if ( isset( $deprecated['start'] ) && false === $deprecated['start'] ) {
				_deprecated_function( 'WPT_Event::date_html()', '0.12', 'WPT_Event::enddate_html()' );
				return $this->enddate_html( $filters );
			} else {
				_deprecated_function( 'WPT_Event::date_html()', '0.12', 'WPT_Event::startdate_html()' );
				return $this->startdate_html( $filters );
			}
		}
	}

	/**
	 * @deprecated 0.12
	 * @see WPT_Event::starttime()
	 * @see WPT_Event::endtime()
	 */
	function time( $deprecated = array() ) {
		if ( empty( $deprecated['html'] ) ) {
			if ( isset( $deprecated['start'] ) && false === $deprecated['start'] ) {
				_deprecated_function( 'WPT_Event::time()', '0.12', 'WPT_Event::endtime()' );
				return $this->endtime( $deprecated );
			} else {
				_deprecated_function( 'WPT_Event::time()', '0.12', 'WPT_Event::starttime()' );
				return $this->starttime( $deprecated );
			}
		} else {
			$filters = array();
			if ( ! empty( $deprecated['filters'] ) ) {
				$filters = $deprecated['filters'];
			}

			if ( isset( $deprecated['start'] ) && false === $deprecated['start'] ) {
				_deprecated_function( 'WPT_Event::time()', '0.12', 'WPT_Event::endtime_html()' );
				return $this->endtime_html( $filters );
			} else {
				_deprecated_function( 'WPT_Event::time()', '0.12', 'WPT_Event::starttime_html()' );
				return $this->starttime_html( $filters );
			}
		}
	}
}

?>
