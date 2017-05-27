<?php

	/**
	 * A calendar with upcoming events.
	 * @since 0.8
	 */
	class WPT_Calendar {
	
		/**
		 * Months for this calendar.
		 * 
		 * @access	private
		 * @since	0.15.23
		 */
		private $months = false;
	
		/**
		 * Add hooks to init the [wpt_calendar] shortcode and the Theater Calendar widget.
		 */
	
		function __construct() {
			add_shortcode('wpt_calendar', array($this,'shortcode'));
			add_action( 'widgets_init', array($this,'widgets_init'));
		}
		
		/**
		 * Gets the active month, based on the current main query.
		 * 
		 * @since	0.15.23
		 * @param	$filters		The filters used for the calendar.
		 * @return 	string|bool		The active month or <false> if there are no months at all.
		 */
		function get_active_month( $filters ) {
			
			global $wp_query;
				
			if ( !empty( $wp_query->query_vars['wpt_month'] ) ) {
				return $wp_query->query_vars['wpt_month'];
			}
				
			if ( !empty( $wp_query->query_vars['wpt_day'] ) ) {
				return substr( $wp_query->query_vars['wpt_day'], 0, 7 );
			}
			
			$months = $this->get_months( $filters );
			
			if (empty($months)) {
				return false;
			}
			
			return $months[0];
			
		}
		
		/**
		 * Get the months for this calendar.
		 * 
		 * @since	0.15.23
		 * @param 	array	$filters	The filters used for the calendar.
		 * @return	array				The months for this calendar.
		 */
		function get_months( $filters ) {
			
			global $wp_theatre;

			$defaults = array(
				'start' => 'now',
			);
			$filters = wp_parse_args( $filters, $defaults );

			$months = $wp_theatre->events->get_months( $filters );				
			$months = array_keys($months);			
			return $months;
			
		}
		
		/**
		 * Gets the HTML version for the calendar.
		 *
		 * @see WPT_Calendar::check_dependencies()	To check if all dependencies are set.
		 * @see WPT_Events::get_months()			To retrieve all months with upcoming events.
		 * @see WPT_Events::get()					To retrieve all upcoming events.
		 * @see WPT_Listing_Page::url()				To retrieve the URL of the listing page.
		 * @see WPT_Event::datetime()				To collect the dates for upcoming events.
		 * @see WPT_Production::permalink()			To get the permalink for an event.
		 *
		 * @since	0.8
		 * @since 	0.10.6	Bugfix: calendar was showing months that have historic events.
		 *					See https://wordpress.org/support/topic/calendar-wrong-month-shown-again.
		 * @since 	0.10.15	Bugfix: now accounts for timezones.
		 *					Fixes #117.
		 * @since	0.13.3	Bugfix: weekdays were showing up as question marks when using 
		 *					a multibyte language (eg. Russian).
		 * 					Fixes #174.
		 * @since	0.15.16	Added support for custom $filters.
		 * @since	0.15.23	Sets the active month class.
		 *
		 * @param 	array	$filters	The filters used for the calendar.
		 * @return 	string 				The HTML for the calendar.
		 */
		function html( $filters = array() ) {
			
			global $wp_locale;
			
			if (!$this->check_dependencies()) {
				return '';
			}
		
			global $wp_theatre;
			
			$months = $this->get_months( $filters );						
			$start_of_week = get_option('start_of_week');
	
			$thead_html = '<thead><tr>';
			$sunday = strtotime('next Sunday');
			for($i=0;$i<7;$i++) {
				$thead_html.= '<th>';
				$thead_html.= $wp_locale->get_weekday_initial( date_i18n('l',$sunday + ($start_of_week * 60 * 60 * 24) + ($i * 60 * 60 * 24)) );
				$thead_html.= '</th>';
			}
			$thead_html.= '</tr></thead>';
	
			$html = '';
			
			for ($m=0;$m<count($months);$m++) {
				$month = $months[$m];
	
				$month_html = '';
	
				$first_day = strtotime($month.'-01');
				$no_of_days = date('t',$first_day);
				$last_day = strtotime($month.'-'.$no_of_days);
	
				// Month header
				$month_url = htmlentities($wp_theatre->listing_page->url(array('wpt_month'=>$month)));
				$month_html.= '<caption><h3><a href="'.$month_url.'">'.date_i18n('F Y',$first_day).'</a></h3></caption>';
				
				// Month footer
				$month_html.= '<tfoot>';
				$month_html.= '<td class="prev" colspan="3">';
				if (!empty($months[$m-1])) {
					$month_url = htmlentities($wp_theatre->listing_page->url(array('wpt_month'=>$months[$m-1])));
					$month_html.= '<a href="'.$month_url.'">&laquo; '.date_i18n('M',strtotime($months[$m-1].'-01')).'</a>';
				}
				$month_html.= '</td>';
				$month_html.= '<td class="pad"></td>';
				$month_html.= '<td class="next" colspan="3">';
				if (!empty($months[$m+1])) {
					$month_url = htmlentities($wp_theatre->listing_page->url(array('wpt_month'=>$months[$m+1])));
					$month_html.= '<a href="'.$month_url.'">'.date_i18n('M',strtotime($months[$m+1].'-01')).' &raquo;</a>';
				}
				$month_html.= '</td>';
				$month_html.= '</tfoot>';
				
				// Calculate leading days (of previous month)
				$first_day_pos = date('w',$first_day) - $start_of_week;
				if ($first_day_pos < 0) {
					$leading_days = 7 + $first_day_pos;
				} else {
					$leading_days = $first_day_pos;
				}
				
				// Calculate trailing days (of next month)
				$last_day_pos = date('w',$last_day) - $start_of_week;
				if ($last_day_pos < 0) {
					$trailing_days = -1 - $last_day_pos;
				} else {
					$trailing_days = 6 - $last_day_pos;
				}
				
				$first_day -= $leading_days * 60 * 60 * 24;
				$no_of_days += $leading_days + $trailing_days;
			
				$days = array();
				for($i=0;$i<$no_of_days;$i++) {
					$date = date('Y-m-d', $first_day + ($i * 60*60*24));
					$days[$date] = array();
				}

				
				$default_events_filters = array();
				$events_filters = wp_parse_args( $filters, $default_events_filters );

				/**
				 * Set the start-filter for the events.
				 * Start a first day of `$month` if `$month` is not the current month.
				 * Start today if `$month` is the current month.
				 */
				 
				$start_time = strtotime($month);
				if ($start_time < time()) {
					$events_filters['start'] = 'now';
				} else {
					$events_filters['start'] = date('Y-m-d', $start_time);					
				}

				/**
				 * Set the end-filter for the events.
				 * Use the first day of the next month for the end-filter.
				 */

				$events_filters['end'] = date('Y-m-d',strtotime($month.' + 1 month'));

				$events = $wp_theatre->events->get($events_filters);
				
				foreach ($events as $event) {
					$date = date('Y-m-d',$event->datetime() + get_option('gmt_offset') * HOUR_IN_SECONDS);
					$days[$date][] = $event;
				}
	
				$month_html.= '<tbody>';
				$month_html.= $thead_html;
				
				$day_index = 0;
				foreach($days as $day=>$events) {
					$day_html = '';
					
					if ($day_index % 7 == 0) {
						$month_html.= '<tr>';
					}
					
					$classes = array();
	
					$day_label = (int) substr($day,8,2);
	
					if (empty($events)) {
						$day_html.= $day_label;				
					} else {
						
						if (count($events)==1) {
							$day_url = htmlentities($events[0]->production()->permalink());
						} else {
							$day_url = htmlentities($wp_theatre->listing_page->url(array('wpt_day'=>$day)));
						}
	
						$day_link = '<a href="'.$day_url.'">';
						$day_link.= $day_label;				
						$day_link.= '</a>';
						
						/**
						 * Filter the HTML link for a day.
						 *
						 * @since 0.9.4
						 *
						 * @param string  $day_link 	The HTML for the link for the day.
						 * @param string  $day 			The day of the month being displayed in `yyyy-mm-dd` format.
						 * @param string  $day_url 		The URL to the production page or the listing page.
						 * @param string  $day_label 	The text being shown inside the link for the day.
						 * @param array   $events		An array of WTP_Event objects. 
						 * 								The events that take place on the day of the month being displayed.
						 */
						$day_html.= apply_filters('wpt_calendar_html_day_link',$day_link, $day, $day_url, $day_label, $events);
					}
	
					if (date('Y-m',strtotime($day)) != $month) {
						$classes[] = 'trailing';
					}
	
					if (!empty($classes)) {
						$day_html = '<td class="'.implode(' ',$classes).'">'.$day_html.'</td>';
					} else {
						$day_html = '<td>'.$day_html.'</td>';
					}
					
					/**
					 * Filter the HTML output for a day.
					 *
					 * @since 0.9.4
					 *
					 * @param string  $day_html The HTML for the day.
					 * @param string  $day 		The day of the month being displayed in `yyyy-mm-dd` format.
					 * @param array   $events	An array of WTP_Event objects. 
					 * 							The events that take place on the day of the month being displayed.
					 */
					$month_html.= apply_filters('wpt_calendar_html_day', $day_html, $day, $events);
	
					if (($day_index % 7) == 6) {
						$month_html.= '</tr>';
					}
		
					$day_index++;
				}
	
				$month_html.= '</tbody>';
				
				// Set the month classes.
				$month_classes = array( 'wpt_month' );				
				if ($this->is_active_month( $month, $filters )) {
					// Add an 'active' class to the active month.
					$month_classes[] = 'active';
				}
				
				$month_html = '<table class="'.implode(' ', $month_classes).'">'.$month_html.'</table>';

				/**
				 * Filter the HTML output for the full month.
				 *
				 * @since 0.9.4
				 *
				 * @param string  $month_html The HTML for the full month.
				 * @param string  $month The month being displayed in yyyy-mm format.
				 */
 				$html.= apply_filters('wpt_calendar_html_month', $month_html, $month);
	
			}
			$html = '<div class="wpt_calendar">'.$html.'</div>';

			/**
			 * Filter the HTML output for entire calendar.
			 *
			 * @since 0.9.4
			 *
			 * @param string  $html The HTML for the calendar.
			 */
			$html = apply_filters('wpt_calendar_html', $html);
			
			return $html;
		}
		
		/**
		 * Checks if a months is the active month.
		 * 
		 * @since	0.15.23
		 * @param 	string	$month	The month.
		 * @param	$filters		The filters used for the calendar.
		 * @return 	bool
		 */
		function is_active_month( $month, $filters ) {
			
			return $month == $this->get_active_month( $filters );
			
		}
		
		/**
		 * check_dependencies function.
		 *
		 * @since	0.? 
	     * @since	0.14.3	Bugfix: Avoid PHP errors when no listing page is set.
	     *					Fixes #181.
	     *
		 * @access public
		 * @return void
		 */
		function check_dependencies() {
			global $wp_theatre;

			$everything_ok = ($listing_page = $wp_theatre->listing_page->page()) && $listing_page instanceof WP_Post;
			
			if (!$everything_ok) {
				
			}
			return $everything_ok;
		}
		
		/**
		 * Gets the output for the [wpt_calendar] shortcode.
		 *
		 * @uses 	WPT_Calendar::check_dependencies()	To check if all dependencies are set.
		 * @uses 	WPT_Calendar::html()				To generate the HTML output.
		 * @uses 	Theater_Transient::get()			To retrieve a cached version of the output.
		 * @uses 	Theater_Transient::set()			To store a cached version of the output.
		 *
		 * @since 	0.8
		 * @since	0.15.24		Now uses the new Theater_Transient object.
		 *
		 * @return	string
		 */
		function shortcode() {
			$html = '';
			
			if ($this->check_dependencies()) {
				global $wp_theatre;
	
				$transient = new Theater_Transient( 'c' );
	
				if ( ! ( $html = $transient->get() ) ) {
					$html = $wp_theatre->calendar->html();
					$transient->set( $html );
				}
			}
			
			return $html;
		}

		/**
		 * Register the Theater Calendar widget.
		 * @see WPT_Calendar::check_dependencies()	To check if all dependencies are set.
		 * @see WPT_Calendar_Widget					The Theater Calendar widget.
		 * @since 0.8
		 */
		
		function widgets_init() {
			if ($this->check_dependencies()) {
				register_widget( 'WPT_Calendar_Widget' );			
			}	
		}
		
	}

	/*
	 * Theater Calendar widget.
	 * @since 0.8
	 */

	class WPT_Calendar_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_calendar_widget',
				__('Theater Calendar','theatre'), // Name
				array( 'description' => __( 'Calendar of upcoming events', 'theatre' ), ) // Args
			);
		}
		
		/**
		 * Outputs the calendar widget HTML.
		 * 
		 * @since	0.8
		 * @since	0.15.25	Now uses the new Theater_Transient object.
		 *
		 * @param 	array	$args
		 * @param	array	$instance
		 * @return 	void
		 */
		public function widget($args,$instance) {
			global $wp_theatre;
			
			echo $args['before_widget'];

			if ( ! empty( $instance['title'] ) ) {			
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base  );
				echo $args['before_title'] . $title . $args['after_title'];
			}
							
			$transient = new Theater_Transient( 'c' );
			
			if ( ! ( $html = $transient->get() ) ) {
	
				$html = $wp_theatre->calendar->html();
				$transient->set( $html );
				
			}

			echo $html;

			echo $args['after_widget'];
		}
		
		public function form($instance) {
			$defaults = array(
				'title' => __( 'Upcoming events', 'theatre' )
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>
			<?php 	
		}
	}
