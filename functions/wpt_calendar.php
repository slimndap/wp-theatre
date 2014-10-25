<?php

	/**
	 * A calendar with upcoming events.
	 * @since 0.8
	 */
	class WPT_Calendar {
	
		/**
		 * Add hooks to init the [wpt_calendar] shortcode and the Theater Calendar widget.
		 */
	
		function __construct() {
			add_shortcode('wpt_calendar', array($this,'shortcode'));
			add_action( 'widgets_init', array($this,'widgets_init'));
		}
		
		/**
		 * Get the HTML version of the calendar.
		 * @see WPT_Calendar::check_dependencies()	To check if all dependencies are set.
		 * @see WPT_Events::months()				To retrieve all months with upcoming events.
		 * @see WPT_Events::load()					To retrieve all upcoming events.
		 * @see WPT_Listing_Page::url()				To retrieve the URL of the listing page.
		 * @see WPT_Event::datetime()				To collect the dates for upcoming events.
		 * @see WPT_Production::permalink()			To get the permalink for an event.
		 * @since 0.8
		 * @return void
		 */
		function html() {
			if (!$this->check_dependencies()) {
				return '';
			}
		
			global $wp_theatre;
			
			/**
			 * If no months are set, show all months between now and the month of the last event.
			 */
			 
			if (empty($args['month'])) {
				$months = $wp_theatre->events->months();
				$months = array_keys($months);			
			}
			
			
			$start_of_week = get_option('start_of_week');
	
			$thead_html = '<thead><tr>';
			$sunday = strtotime('next Sunday');
			for($i=0;$i<7;$i++) {
				$thead_html.= '<th>';
				$thead_html.= substr(date_i18n('D',$sunday + ($start_of_week * 60 * 60 * 24) + ($i * 60 * 60 * 24)) , 0 , 1);
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

				$events_filters = array();

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

				$events = $wp_theatre->events->load($events_filters);
				
				foreach ($events as $event) {
					$date = date('Y-m-d',$event->datetime());
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
							$url = htmlentities($events[0]->production()->permalink());
						} else {
							$url = htmlentities($wp_theatre->listing_page->url(array('wpt_day'=>$day)));
						}
	
						$day_html.= '<a href="'.$url.'">';
						$day_html.= $day_label;				
						$day_html.= '</a>';
					}
	
					if (date('Y-m',strtotime($day)) != $month) {
						$classes[] = 'trailing';
					}
	
					if (!empty($classes)) {
						$day_html = '<td class="'.implode(' ',$classes).'">'.$day_html.'</td>';
					} else {
						$day_html = '<td>'.$day_html.'</td>';
					}
					
					$month_html.= $day_html;
	
					if (($day_index % 7) == 6) {
						$month_html.= '</tr>';
					}
	
	
	
					$day_index++;
				}
	
				$month_html.= '</tbody>';
				
				$html.= '<table class="wpt_month">'.$month_html.'</table>';
	
			}
			$html = '<div class="wpt_calendar">'.$html.'</div>';
			
			return $html;
		}
		
		function check_dependencies() {
			global $wp_theatre;

			$everything_ok = $wp_theatre->listing_page->page() instanceof WP_Post;
			
			if (!$everything_ok) {
				
			}
			return $everything_ok;
		}
		
		/**
		 * Handle the [wpt_calendar] shortcode.
		 * @see WPT_Calendar::check_dependencies()	To check if all dependencies are set.
		 * @see WPT_Calendar::html()				To generate the HTML output.
		 * @see WPT_Transients::get()				To retrieve a cached version of the output.
		 * @see WPT_Transients::set()				To store a cached version of the output.
		 * @since 0.8
		 */
		
		function shortcode() {
			$html = '';
			
			if ($this->check_dependencies()) {
				global $wp_theatre;
	
				if ( ! ( $html = $wp_theatre->transient->get('c', array()) ) ) {
					$html = $wp_theatre->calendar->html();
					$wp_theatre->transient->set('c', array(), $html);
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
				__('Theater Calendar','wp_theatre'), // Name
				array( 'description' => __( 'Calendar of upcoming events', 'wp_theatre' ), ) // Args
			);
		}
		
		public function widget($args,$instance) {
			global $wp_theatre;
			
			$title = apply_filters( 'widget_title', $instance['title'] );
			
			echo $args['before_widget'];
			if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
				
			if ( ! ( $html = $wp_theatre->transient->get('c', array()) ) ) {
				$html = $wp_theatre->calendar->html();
				$wp_theatre->transient->set('c', array(), $html);
			}

			echo $html;

			echo $args['after_widget'];
		}
		
		public function form($instance) {
			$defaults = array(
				'title' => __( 'Upcoming events', 'wp_theatre' )
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
