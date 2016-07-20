<?php

/** 
 * The Theater Date class handles individual events dates.
 *
 * ##Usage
 *
 * <code>
 * $event = new WPT_Event();
 * $event = new WPT_Event($post_id);
 * $event = new WPT_Event($post);
 *
 * echo $event->html(); // output the details of an event as HTML
 *
 * echo $event->prices( array('summary'=>true) ) // // a summary of all available ticketprices
 * echo $event->datetime() // timestamp of the event
 * echo $event->startdate() // localized and formatted date of the event
 * echo $event->starttime() // localized and formatted time of the event
 *
 * $date = new Theater_Date();
 * echo $date->title;
 * $title = $date->title;
 * </code>
 *
 * @package	Theater/Date
 * @api
 *
 */

class Theater_Date {

	const name = 'date';
	
	const post_type_name = 'wp_theatre_event';

	const tickets_status_onsale = '_onsale';
	const tickets_status_hidden = '_hidden';
	const tickets_status_cancelled = '_cancelled';
	const tickets_status_soldout = '_soldout';
	const tickets_status_other = '_other';
	
	protected $fields = array();
	
	function __construct( $ID = false ) {
		
		if ( $ID instanceof WP_Post ) {
			$this->post = $ID;
			$ID = $ID->ID;
		}

		$this->ID = $ID;
		
	}

	function __get( $name ) {
		$value = $this->get_field_html($name);
		return $value;	
	}
	
	function __call( $name, $args ) {

		if ( !empty ($args[0]['html']) ) {		
			if (!empty($args[0]['filters'])) {
				return $this->get_field_html( $name, $args[0]['filters'] );
			}
			return $this->get_field_html( $name );
		}
		
		$name_parts = explode('_', $name);
		if ('html' == $name_parts[count($name_parts) - 1]) {
			array_pop($name_parts);
			$name = implode('_', $name_parts);
			if (!empty($args[0])) {
				return $this->get_field_html( $name, $args[0] );
			}
			return $this->get_field_html( $name );
		}
		
		
		return $this->get_field( $name );
	}
	
	function get_field( $name, $filters = array() ) {
		$field = new Theater_Date_Field($name, $filters, $this);
		return $field();		
	}
	
	function get_field_html( $name, $filters = array() ) {
		$field = new Theater_Date_Field($name, $filters, $this);
		return $field;	
	}
	
	function get_fields() {
		$fields = apply_filters('theater/date/fields', $this->fields, $this);
		return $fields;
		
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

	function get_duration() {
		
		$startdate = $this->datetime();
		$enddate = $this->datetime(true);

		if (empty( $enddate )) {
			return '';
		}
		
		if ($enddate < $startdate) {
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
	 * 					See: https://github.com/slimndap/wp-theatre/issues/165
	 * @return	string 	The event enddate.
	 *					Returns <false> if no endate is set.
	 */
	function get_enddate() {
		
		$enddate = false;
		if ( $datetime = $this->enddatetime() ) {
			$enddate = date_i18n(
				get_option( 'date_format' ),
				$datetime + get_option( 'gmt_offset' ) * 3600
			);
		}

		/**
		 * @deprecated	0.16
		 */
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
	function get_enddate_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo self::post_type_name;?>_date <?php echo self::post_type_name; ?>_enddate"><?php
				
		if ($value = $this->enddate()) {

			foreach ( $filters as $filter ) {
				if ( 'date' == $filter->name ) {
					$value = $filter->apply_to( $this->enddatetime(), $this );
				} else {
					$value = $filter->apply_to( $value, $this );
				}
			}
			
			echo $value;
			
		}

		?></div><?php

		$html = ob_get_clean();


		/**
		 * @deprecated	0.16
		 */
		$html = apply_filters( 'wpt/event/enddate/html', $html, $filters, $this );

		return $html;
	}

	/**
	 * Gets the event endtime.
	 *
	 * @since	0.12
	 * @since	0.12.7		Now returns <false> is no endate is set.
	 * 						See: https://github.com/slimndap/wp-theatre/issues/165
	 * @return	string|bool The event endtime.
	 *						Returns <false> if no endate is set.
	 */
	function get_endtime() {
		
		$endtime = false;
		if ( $datetime = $this->enddatetime() ) {
			$endtime = date_i18n(
				get_option( 'time_format' ),
				$datetime + get_option( 'gmt_offset' ) * 3600
			);
		}
		
		/**
		 * @deprecated	0.16
		 */
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
	function get_endtime_html( $filters = array() ) {
		
		ob_start();
		?><div class="<?php echo self::post_type_name;?>_time <?php echo self::post_type_name; ?>_endtime"><?php
				
		if ($value = $this->endtime()) {

			foreach ( $filters as $filter ) {
				if ( 'date' == $filter->name ) {
					$value = $filter->apply_to( $this->enddatetime(), $this );
				} else {
					$value = $filter->apply_to( $value, $this );
				}
			}
			
			echo $value;
			
		}

		?></div><?php

		$html = ob_get_clean();

		/**
		 * @deprecated	0.16
		 */
		$html = apply_filters( 'wpt/event/endtime/html', $html, $filters, $this );

		return $html;
	}
	
	function get_event() {
		
		$event_id = get_post_meta( $this->ID, WPT_Production::post_type_name, true );

		// Bail if no production ID is set.
		if (empty($event_id)) {
			return false;
		}

		/*
		 * Bail if production doesn't exist.
		 * See: https://tommcfarlin.com/wordpress-post-exists-by-id/
		 */
		if (FALSE === get_post_status( $event_id )) {
			return false;
		}
		
		$event = new WPT_Production( $event_id );
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
	 * @since 	0.10.14	Deprecated the HTML argument.
	 *					Use @see WPT_Event::prices_html() instead.
	 *
	 * @return 	array 	The event prices.
	 */
	function get_prices() {

		$prices= array();

		$prices_named = get_post_meta( $this->ID,'_wpt_event_tickets_price' );

		foreach ($prices_named as $price_named) {
			$price_parts = explode( '|',$price_named );
			$prices[] = (float) $price_parts[0];		
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

		if ( !empty( $prices ) ) {
			if ( count( $prices ) > 1 ) {
				$prices_summary .= __( 'from','theatre' ).' ';
			}
			if ( ! empty( $wp_theatre->wpt_tickets_options['currencysymbol'] ) ) {
				$prices_summary .= $wp_theatre->wpt_tickets_options['currencysymbol'].' ';
			}

			$prices_summary .= number_format_i18n( (float) min( (array) $prices ), 2 );
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
		return $this->event();
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
			$html .= $this->tickets_status;
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
	
	public function get_post_type() {
		return self::post_type_name;
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

	function get_location() {
		
		$location_parts = array();
		
		$venue = $this->venue();
		$city = $this->city();
		
		if (!empty($venue)) {
			$location_parts[] = $venue;
		}
		
		if (!empty($city)) {
			$location_parts[] = $city;
		}
		
		$value = implode(' ', $location_parts);
		return $value;
		
	}

	function get_location_html( $filters = array() ) {
		$value = $this->location;
		
		ob_start();
		?><div class="<?php echo Theater_Date::post_type_name; ?>_location"><?php 
			echo $this->get_field_html('venue', $filters);
			echo $this->get_field_html('city', $filters);
		?></div><?php
		$html = ob_get_clean();
		
		return $html;
	}

	function get_name() {
		return self::name;
	}

	function get_title() {
		
		if (!($event = $this->production()) ) {
			return '';	
		}
		
		return $event->title();		
	}
	
	function get_tickets_status() {
		
		$value = get_post_meta( $this->ID,'tickets_status',true );

		if ( empty( $value ) ) {
			$value = self::tickets_status_onsale;
		}

		/**
		 * @deprecated	0.16
		 */
		$value = apply_filters( 'wpt/event/tickets/status', $value, $this );

		/**
		 * @deprecated	0.10.14
		 */
		$value = apply_filters( 'wpt_event_tickets_status', $value, $this );

		return $value;
		
	}

	function get_tickets_status_html( $filters = array() ) {
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
		 * @deprecated	0.16
		 */
		$html = apply_filters( 'wpt/event/tickets/status/html', $html, $this );

		return $html;
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

	function get_enddatetime() {
		$enddate = get_post_meta( $this->ID, 'enddate', true );

		if ( empty( $enddate ) ) {
			return false;
		}

		$value = date_i18n( 'U', strtotime( $enddate, current_time( 'timestamp' ) ) - get_option( 'gmt_offset' ) * 3600 );

		return $value;
	}
	
	function get_enddatetime_html( $filters = array() ) {
		
		ob_start();
		?><div class="<?php echo Theater_Date::post_type_name; ?>_datetime <?php echo Theater_Date::post_type_name; ?>_startdatetime"><?php
			
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
	 * Gets the event startdate.
	 *
	 * @since	0.12
	 * @return	string The even startdate.
	 */
	function get_startdate() {
		$startdate = date_i18n(
			get_option( 'date_format' ),
			$this->startdatetime() + get_option( 'gmt_offset' ) * 3600
		);

		/**
		 * @deprecated	0.16
		 */
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
	function get_startdate_html( $filters = array() ) {
		
		ob_start();
		?><div class="<?php echo self::post_type_name;?>_date <?php echo self::post_type_name; ?>_startdate"><?php
				
		$value = $this->startdate();

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

		/**
		 * @deprecated	0.16
		 */
		$html = apply_filters( 'wpt/event/startdate/html', $html, $filters, $this );

		return $html;
	}

	function get_startdatetime() {
		$startdate = get_post_meta( $this->ID, 'event_date', true );

		if ( empty( $startdate ) ) {
			return false;
		}

		$value = date_i18n( 'U', strtotime( $startdate, current_time( 'timestamp' ) ) - get_option( 'gmt_offset' ) * 3600 );

		return $value;
	}
	
	function get_startdatetime_html( $filters = array() ) {
		
		ob_start();
		?><div class="<?php echo Theater_Date::post_type_name; ?>_datetime <?php echo Theater_Date::post_type_name; ?>_startdatetime"><?php
			
			$value = $this->startdate.$this->starttime;		
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
	 * Gets the event starttime.
	 *
	 * @since	0.12
	 * @return	string The event starttime.
	 */
	function get_starttime() {
		$starttime = date_i18n(
			get_option( 'time_format' ),
			$this->get_startdatetime() + get_option( 'gmt_offset' ) * 3600
		);
		
		/**
		 * @deprecated	0.16
		 */
		$starttime = apply_filters( 'wpt/event/starttime', $starttime, $this );
		
		return $starttime;
	}

	/**
	 * Gets the HTML for the event starttime.
	 *
	 * @since	0.12
	 * @param 	array 	$filters	The template filters to apply.
	 * @return 	sring				The HTML for the event starttime.
	 */
	function get_starttime_html( $filters = array() ) {

		ob_start();
		?><div class="<?php echo self::post_type_name;?>_time <?php echo self::post_type_name; ?>_starttime"><?php
				
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
		
		/**
		 * @deprecated	0.16
		 */
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
	 * @deprecated	0.16
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
	 */
	function datetime_html( $filters = array() ) {
		$html = $this-get_field_html('startdatetime', $filters);

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
