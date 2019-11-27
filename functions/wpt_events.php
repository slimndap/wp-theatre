<?php

/*
 * Manages event listings.
 *
 * Uses this class to compile lists of events or fully formatted HTML listings of events.
 *
 * @since 0.5
 * @since 0.10	Complete rewrite, while maintaining backwards compatibility.
 */

class WPT_Events extends WPT_Listing {

	/**
	 * Adds the page selectors for seasons and categories to the public query vars.
	 *
	 * This is needed to make `$wp_query->query_vars['wpt_category']` work.
	 *
	 * @since 	0.10
	 * @since	0.16	Added 'wpt_tag' to the query args.
	 *
	 * @param array $vars	The current public query vars.
	 * @return array		The new public query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wpt_day';
		$vars[] = 'wpt_month';
		$vars[] = 'wpt_year';
		$vars[] = 'wpt_category';
		$vars[] = 'wpt_tag';
		return $vars;
	}

	/**
	 * Gets an array of all categories events.
	 *
	 * @since 0.5
	 * @since 0.10		Renamed method from `categories()` to `get_categories()`.
		 * @since 0.10.2	Now returns the slug instead of the term_id as the array keys.
		 * @since 0.10.14	Significally decreased the number of queries used.
	 *
	 * @param 	array $filters	See WPT_Events::get() for possible values.
	 * @return 	array 			Categories.
	 */
	function get_categories( $filters = array() ) {
		$filters['category'] = false;
		$events = $this->get( $filters );
		$event_ids = wp_list_pluck( $events, 'ID' );
		$terms = wp_get_object_terms( $event_ids, 'category' );
		$categories = array();

		foreach ( $terms as $term ) {
			$categories[ $term->slug ] = $term->name;
		}

		asort( $categories );
		return $categories;
	}

	/**
	 * Gets the CSS classes for an event listing.
	 *
	 * @see WPT_Listing::get_classes_for_html()
	 *
	 * @since 	0.10
	 * @since	0.14.7	Added $args to parent::get_classes_for_html().
	 *
	 * @access 	protected
	 * @param 	array $args 	See WPT_Events::get_html() for possible values. Default: array().
	 * @return 	array 			The CSS classes.
	 */
	protected function get_classes_for_html( $args = array() ) {

		// Start with the default classes for listings.
		$classes = parent::get_classes_for_html( $args );

		$classes[] = 'wpt_events';

		// Thumbnail
		if ( ! empty( $args['template'] ) && false === strpos( $args['template'],'{{thumbnail}}' ) ) {
			$classes[] = 'wpt_events_without_thumbnail';
		}

		/**
		 * Filter the CSS classes.
		 *
		 * @since 0.10
		 *
		 * @param 	array $classes 	The CSS classesSee WPT_Events::get_html() for possible values.
		 * @param 	array $args 	The $args that are being used for the listing.
		 */
		$classes = apply_filters( 'wpt_events_classes', $classes, $args );

		return $classes;
	}

	/**
	 * Gets an array of all days with events.
	 *
	 * @since 	0.8
	 * @since 	0.10	No longer limits the output to days with upcoming events.
	 *					See: https://github.com/slimndap/wp-theatre/issues/75
	 * 					Renamed method from `days()` to `get_days()`.
	 * @since 	0.10.1	Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 	0.10.6	Added custom sorting again.
	 *					Can't rely on sorting order of events, because historic events have no
	 *					`_wpt_order` set.
	 * @since	0.15.11	Added support for next day start time offset.
	 *
	 * @uses	Theater_Helpers_Time::get_next_day_start_time_offset() to get the next day start time offset.
	 * @param 	array 	$filters	See WPT_Events::get() for possible values.
	 * @return 	array 				All days with events
	 */
	function get_days( $filters = array() ) {
		$events = $this->get( $filters );
		$days = array();
		foreach ( $events as $event ) {
			
			$day_datetime = $event->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			$day_datetime -= Theater_Helpers_Time::get_next_day_start_time_offset();
			
			$days[ date( 'Y-m-d', $day_datetime) ] = date_i18n( 'D j M', $day_datetime );
			
		}

		if ( ! empty( $filters['order'] ) && 'desc' == $filters['order'] ) {
			krsort( $days );
		} else {
			ksort( $days );
		}

		return $days;
	}

	/**
	 * Gets a fully formatted listing of events in HTML.
	 *
	 * The list of events is compiled using filter-arguments that are part of $args.
	 * See WPT_Events::get() for possible values.
	 *
	 * The events can be shown on a single page or be cut up into multiple pages by setting
	 * $paginateby. If $paginateby is set then a page navigation is added to the top of
	 * the listing.
	 *
	 * The events can be grouped inside the pages by setting $groupby.
	 *
	 * @since 0.5
	 * @since 0.10	Moved parts of this method to seperate reusable methods.
	 *				Renamed method from `html()` to `get_html()`.
	 *				Rewrote documentation.
	 *
	 * @see WPT_Listing::get_html()
	 * @see WPT_Events::get_html_pagination()
	 * @see WPT_Events::get_html_for_page()
	 *
	 * @param array $args {
	 * 		An array of arguments. Optional.
	 *
	 *		These can be any of the arguments used in the $filters of WPT_Events::get(), plus:
	 *
	 *		@type array		$paginateby	Fields to paginate the listing by.
	 *									@see WPT_Events::get_html_pagination() for possible values.
	 *									Default <[]>.
	 *		@type string    $groupby    Field to group the listing by.
	 *									@see WPT_Events::get_html_grouped() for possible values.
	 *									Default <false>.
	 * 		@type string	$template	Template to use for the individual events.
	 *									Default <NULL>.
	 * }
		 * @return string HTML.
	 */
	public function get_html( $args = array() ) {

		$html = parent::get_html( $args );

		/**
		 * Filter the formatted listing of events in HTML.
		 *
		 * @since 0.10
		 *
		 * @param 	string $html 	The HTML.
		 * @param 	array $args 	The $args that are being used for the listing.
		 */
		$html = apply_filters( 'wpt_events_html', $html, $args );

		return  $html;
	}

	/**
	 * Gets a list of events in HTML for a single category.
	 *
	 * @since 0.10
		 * @since 0.10.2	Category now uses slug instead of term_id.
	 *
	 * @see WPT_Events::get_html_grouped();
	 *
	 * @access private
	 * @param 	string $category_slug		Slug of the category.
	 * @param 	array $args 				See WPT_Events::get_html() for possible values.
	 * @return 	string						The HTML.
	 */
	private function get_html_for_category( $category_slug, $args = array() ) {
		if ( $category = get_category_by_slug( $category_slug ) ) {
				$args['cat'] = $category->term_id;
		}

		return $this->get_html_grouped( $args );
	}

	/**
	 * Gets a list of events in HTML for a page.
	 *
	 * @since	0.10
	 * @since	0.15.16		Replaced $wp_query->query_vars['wpt_day'] with $wp_query->query['wpt_day'].
	 *						Fixes #217.
	 *						Maybe this is caused by wp_resolve_numeric_slug_conflicts(), which was
	 *						added in WP 4.3.
	 * @since	0.16		Added support for tags.
	 *
	 * @see WPT_Events::get_html_grouped()
	 * @see WPT_Events::get_html_for_year()
	 * @see WPT_Events::get_html_for_month()
	 * @see WPT_Events::get_html_for_day()
	 * @see WPT_Events::get_html_for_category()
	 *
	 * @access protected
	 * @param 	array $args 	See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_for_page( $args = array() ) {
		global $wp_query;

		$html = '';

		/*
		 * Check if the user used the page navigation to select a particular page.
		 * Then revert to the corresponding WPT_Events::get_html_for_* method.
		 * @see WPT_Events::get_html_page_navigation().
		 */

		if ( ! empty( $wp_query->query_vars['wpt_year'] ) ) {
			$html = $this->get_html_for_year( $wp_query->query_vars['wpt_year'], $args );
		} elseif ( ! empty( $wp_query->query_vars['wpt_month'] ) ) {
			$html = $this->get_html_for_month( $wp_query->query_vars['wpt_month'], $args );
		} elseif ( ! empty( $wp_query->query['wpt_day'] ) ) {
			$html = $this->get_html_for_day( $wp_query->query['wpt_day'], $args );
		} elseif ( ! empty( $wp_query->query_vars['wpt_category'] ) ) {
			$html = $this->get_html_for_category( $wp_query->query_vars['wpt_category'], $args );
		} elseif ( ! empty( $wp_query->query_vars['wpt_tag'] ) ) {
			$html = $this->get_html_for_tag( $wp_query->query_vars['wpt_tag'], $args );
		} else {
			/*
			 * The user didn't select a page.
			 * Show the full listing.
			 */
			$html = $this->get_html_grouped( $args );
		}

		/**
		 * Filter the HTML for a page in a listing.
		 *
		 * @since	0.12.2
		 * @param	string	$html_group	The HTML for this page.
		 * @param	array	$args		The arguments for the HTML of this listing.
		 */
		$html = apply_filters( 'wpt/events/html/page', $html, $args );

		return $html;
	}

	/**
	 * Gets a list of events in HTML for a single tag.
	 *
	 * @since 0.16
	 *
	 * @see WPT_Events::get_html_grouped();
	 *
	 * @access	private
	 * @param	string	$tag_slug		Slug of the category.
	 * @param	array	$args 			See WPT_Events::get_html() for possible values.
	 * @return	string					The HTML.
	 */
	private function get_html_for_tag( $tag_slug, $args = array() ) {
		if ( $tag = get_term_by( 'slug', $tag_slug, 'post_tag' ) ) {
			$args['tag'] = $tag->slug;
		}
		return $this->get_html_grouped( $args );
	}

	/**
	 * Gets a list of events in HTML.
	 *
	 * The events can be grouped inside a page by setting $groupby.
	 * If $groupby is not set then all events are show in a single, ungrouped list.
	 *
	 * @since 	0.10
	 * @since	0.14.7	Added $args to $event->html().
	 * @since	0.15.29	Added $args to all header filters.
	 * @since	0.16	Added support for tags.
	 *
	 * @see WPT_Event::html();
	 * @see WPT_Events::get_html_for_month();
	 * @see WPT_Events::get_html_for_day();
	 * @see WPT_Events::get_html_for_category();
	 *
	 * @access protected
	 * @param 	array $args 	See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_grouped( $args = array() ) {

		$args = wp_parse_args( $args, $this->default_args_for_html );

		/*
		 * Get the `groupby` setting and remove it from $args.
		 * $args can now be passed on to any of the other `get_html_*`-methods safely
		 * without the risk of creating grouped listings within grouped listings.
		 */
		$groupby = $args['groupby'];
		$args['groupby'] = false;

		$html = '';
		switch ( $groupby ) {
			case 'day':
				$days = $this->get_days( $args );
				foreach ( $days as $day => $name ) {
					if ( $day_html = $this->get_html_for_day( $day, $args ) ) {
						$html .= '<h3 class="wpt_listing_group day">';

						/**
						 * Filter the day header in an events list.
						 * 
						 * @since 	0.?
						 * @since	0.15.29	Added the $args param.
						 *
						 * @param	string	$header	The header.
						 * @param	string	$day	The day.
						 * @param	array	$args	The arguments for the HTML of this list.
						 */
						$html .= apply_filters( 'wpt_listing_group_day', date_i18n( 'l d F',strtotime( $day ) ), $day, $args );
						
						$html .= '</h3>';
						$html .= $day_html;
					}
				}
				break;
			case 'month':
				$months = $this->get_months( $args );
				foreach ( $months as $month => $name ) {
					if ( $month_html = $this->get_html_for_month( $month, $args ) ) {
						$html .= '<h3 class="wpt_listing_group month">';
						
						/**
						 * Filter the month header in an events list.
						 * 
						 * @since 	0.?
						 * @since	0.15.29	Added the $args param.
						 *
						 * @param	string	$header	The header.
						 * @param	string	$day	The month.
						 * @param	array	$args	The arguments for the HTML of this list.
						 */
						$html .= apply_filters( 'wpt_listing_group_month', date_i18n( 'F',strtotime( $month ) ), $month, $args );
						
						$html .= '</h3>';
						$html .= $month_html;
					}
				}
				break;
			case 'year':
				$years = $this->get_years( $args );
				foreach ( $years as $year => $name ) {
					if ( $year_html = $this->get_html_for_year( $year, $args ) ) {
						$html .= '<h3 class="wpt_listing_group year">';
						
						/**
						 * Filter the year header in an events list.
						 * 
						 * @since 	0.?
						 * @since	0.15.29	Added the $args param.
						 *
						 * @param	string	$header	The header.
						 * @param	string	$day	The year.
						 * @param	array	$args	The arguments for the HTML of this list.
						 */
						$html .= apply_filters( 'wpt_listing_group_year', date_i18n( 'Y',strtotime( $year.'-01-01' ) ), $year, $args );
						
						$html .= '</h3>';
						$html .= $year_html;
					}
				}
				break;
			case 'category':
				$categories = $this->get_categories( $args );
				foreach ( $categories as $cat_id => $name ) {
					if ( $cat_html = $this->get_html_for_category( $cat_id, $args ) ) {
						$html .= '<h3 class="wpt_listing_group category">';
						
						/**
						 * Filter the category header in an events list.
						 * 
						 * @since 	0.?
						 * @since	0.15.29	Added the $args param.
						 *
						 * @param	string	$header	The header.
						 * @param	string	$day	The category.
						 * @param	array	$args	The arguments for the HTML of this list.
						 */
						$html .= apply_filters( 'wpt_listing_group_category', $name, $cat_id, $args );
						
						$html .= '</h3>';
						$html .= $cat_html;
					}
				}
				break;			
			case 'tag':
				$tags = $this->get_tags( $args );
				foreach ( $tags as $slug => $name ) {
					if ( $tag_html = $this->get_html_for_tag( $slug, $args ) ) {
						$html .= '<h3 class="wpt_listing_group tag">';
						
						/**
						 * Filter the tag header in an events list.
						 * 
						 * @since 	0.16
						 *
						 * @param	string	$header	The header.
						 * @param	string	$slug	The tag slug.
						 * @param	array	$args	The arguments for the HTML of this list.
						 */
						$html .= apply_filters( 'wpt_listing_group_tag', $name, $slug, $args );
						
						$html .= '</h3>';
						$html .= $tag_html;
					}
				}
				break;
			default:
				$events = $this->get( $args );
				$events = $this->preload_events_with_productions( $events );
				$html_group = '';
				foreach ( $events as $event ) {

					/*
					 * Use the general default template for events.
					 * @see WPT_Event_Template::get_default();
					 */
					$template = false;

					/**
					 * Filter the default template for events in lists.
					 *
					 * @since	0.14.6
					 * @param	string	$template	The default template.
					 */
					$template = apply_filters( 'wpt/events/event/template/default', $template );

					if ( ! empty( $args['template'] ) ) {
						$template = $args['template'];
					}

					$html_event = $event->html( $template, $args );

					/**
					 * Filters the event HTML in lists.
					 *
					 * @since	0.14.6
					 *
					 * @param	string		$html_event	The event HTML.
					 * @param	WPT_Event	$event		The event.
					 */
					$html_event = apply_filters( 'wpt/events/event/html', $html_event, $event );

					$html_group .= $html_event;
				}

				/**
				 * Filter the HTML for a group in a listing.
				 *
				 * @since	0.12.2
				 * @param	string	$html_group	The HTML for this group.
				 * @param	array	$args		The arguments for the HTML of this listing.
				 */
				$html_group = apply_filters( 'wpt/events/html/grouped/group', $html_group, $args );

				$html .= $html_group;
		}
		return $html;
	}

	/**
	 * Gets the pagination filters for an event listing.
	 *
	 * @since	0.13.4
	 * @since	0.16	Added the tag filter.
	 * @return 	array	The pagination filters for an event listing.
	 */
	public function get_pagination_filters() {

		$filters = parent::get_pagination_filters();

		$filters['day'] = array(
			'title' => __( 'Days', 'theatre' ),
			'query_arg' => 'wpt_day',
			'callback' => array( $this, 'get_days' ),
		);

		$filters['month'] = array(
			'title' => __( 'Months', 'theatre' ),
			'query_arg' => 'wpt_month',
			'callback' => array( $this, 'get_months' ),
		);

		$filters['year'] = array(
			'title' => __( 'Years', 'theatre' ),
			'query_arg' => 'wpt_year',
			'callback' => array( $this, 'get_years' ),
		);

		$filters['category'] = array(
			'title' => __( 'Categories', 'theatre' ),
			'query_arg' => 'wpt_category',
			'callback' => array( $this, 'get_categories' ),
		);

		$filters['tag'] = array(
			'title' => __( 'Tags', 'theatre' ),
			'query_arg' => 'wpt_tag',
			'callback' => array( $this, 'get_tags' ),
		);

		/**
		 * Filter the pagination filters for an event listing.
		 *
		 * @since 	0.13.4
		 * @param	array	$filters	The current pagination filters for an event listing..
		 */
		$filters = apply_filters( 'wpt/events/pagination/filters', $filters );

		return $filters;
	}

	/**
	 * Gets all tags for events.
	 *
	 * @since 0.16
	 *
	 * @param 	array 	$filters	See WPT_Events::get() for possible values.
	 * @return 	array 				Tags.
	 */
	 function get_tags( $filters = array() ) {
		
		$filters['tag'] = false;
		$events = $this->get( $filters );
		$event_ids = wp_list_pluck( $events, 'ID' );
		$terms = wp_get_object_terms( $event_ids, 'post_tag' );
		$tags = array();

		foreach ( $terms as $term ) {
			$tags[ $term->slug ] = $term->name;
		}

		asort( $tags );
		return $tags;
	}

	/**
	 * Gets the page navigation for an event listing in HTML.
	 *
	 * @see WPT_Listing::filter_pagination()
	 * @see WPT_Events::get_days()
	 * @see WPT_Events::get_months()
	 * @see WPT_Events::get_categories()
	 *
	 * @since 	0.10
	 * @since	0.13.4	Show the pagination filters in the same order as the
	 *					the 'paginateby' argument.
	 *
	 * @access 	protected
	 * @param 	array $args     The arguments being used for the event listing.
	 *							See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML for the page navigation.
	 */
	protected function get_html_page_navigation( $args = array() ) {
		global $wp_query;

		$html = '';

		$paginateby = empty( $args['paginateby'] ) ? array() : $args['paginateby'];

		$filters = $this->get_pagination_filters();

		foreach ( $filters as $filter_name => $filter_options ) {
			if ( ! empty( $wp_query->query_vars[ $filter_options['query_arg'] ] ) ) {
				$paginateby[] = $filter_name;
			}
		}

		$paginateby = array_unique( $paginateby );

		foreach ( $paginateby as $paginateby_filter ) {
			$options = call_user_func_array(
				$filters[ $paginateby_filter ]['callback'],
				array( $args )
			);
			$html .= $this->filter_pagination(
				$paginateby_filter,
				$options,
				$args
			);
		}

		/**
		 * Filter the HTML of the page navigation for an event listing.
		 *
		 * @since	0.13.3
		 * @param 	string 	$html	The HTML of the page navigation for an event listing.
		 * @param 	array 	$args	The arguments being used for the event listing.
		 */
		$html = apply_filters( 'wpt/events/html/page/navigation', $html, $args );

		return $html;
	}

	/**
	 * Gets all months that have events.
	 *
	 * @since 	0.5
	 * @since	0.10	No longer limits the output to months with upcoming events.
	 *					See: https://github.com/slimndap/wp-theatre/issues/75
	 * 					Renamed method from `months()` to `get_months()`.
	 * @since 	0.10.1	Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 	0.10.6 	Added custom sorting again.
	 *					Can't rely on sorting order of events, because historic events have no
	 *					`_wpt_order` set.
	 * @since	0.15.11	Added support for next day start time offset.
	 *
	 * @uses	Theater_Helpers_Time::get_next_day_start_time_offset() to get the next day start time offset.
	 *
	 * @param 	array 	$filters	See WPT_Events::get() for possible values.
	 * @return 	array 				All months that have events.
	 */
	function get_months( $filters = array() ) {
		$events = $this->get( $filters );
		$months = array();
		foreach ( $events as $event ) {
			$month_datetime = $event->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			$month_datetime -= Theater_Helpers_Time::get_next_day_start_time_offset();

			$months[ date( 'Y-m',$month_datetime ) ] = date_i18n( 'M Y',$month_datetime );
		}

		if ( ! empty( $filters['order'] ) && 'desc' == $filters['order'] ) {
			krsort( $months );
		} else {
			ksort( $months );
		}

		return $months;
	}

	/**
	 * Gets all years that have events.
	 *
	 * @since 	0.10
	 * @since 	0.10.1	Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 	0.10.6	Added custom sorting again.
	 *					Can't rely on sorting order of events, because historic events have no
	 *					`_wpt_order` set.
	 * @since	0.15.11	Added support for next day start time offset.
	 *
	 * @uses	Theater_Helpers_Time::get_next_day_start_time_offset() to get the next day start time offset.
	 *
	 * @param 	array 	$filters	See WPT_Events::get() for possible values.
	 * @return 	array 				All years that have events.
	 */
	function get_years( $filters = array() ) {
		$events = $this->get( $filters );
		$years = array();
		foreach ( $events as $event ) {

			$year_datetime = $event->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			$year_datetime -= Theater_Helpers_Time::get_next_day_start_time_offset();


			$years[ date( 'Y',$year_datetime) ] = date_i18n( 'Y',$year_datetime );
		}

		if ( ! empty( $filters['order'] ) && 'desc' == $filters['order'] ) {
			krsort( $years );
		} else {
			ksort( $years );
		}

		return $years;
	}

	/*
	 * Get the last event.
	 *
	 * @since 0.8
	 */

	function last() {
		$args = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => 'publish',
			'order' => 'desc',
			'posts_per_page' => 1,
		);

		$events = get_posts( $args );

		if ( empty( $events ) ) {
			return false;
		} else {
			return new WPT_Event( $events[0] );
		}
	}

	/**
	 * Gets a list of events.
	 *
	 * @since 	0.5
	 * @since 	0.10	Renamed method from `load()` to `get()`.
	 * 					Added 'order' to $args.
	 * @since 	0.10.14	Preload events with their productions.
	 *					This dramatically decreases the number of queries needed to show a listing of events.
	 * @since 	0.10.15	'Start' and 'end' $args now account for timezones.
	 *					Fixes #117.
	 * @since 	0.11.8	Support for 'post__in' and 'post__not_in'.
	 *					Fixes #128.
	 * @since	0.13	Added support for multiple productions.
	 * @since	0.13.1	'Start' and 'end' filter explicitly set to 'NUMERIC'.
	 *					Fixes #168.
	 * @since	0.15	Added support for 's' (keyword search).
	 * @since	0.15.32 's' now actually works.
	 *					Fixes #272.
	 *
	 * @return array Events.
	 */

	public function get( $filters = array() ) {
		global $wp_theatre;

		$defaults = array(
			'cat' => false,
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'tag' => false,
			'end' => false,
			'limit' => false,
			'order' => 'asc',
			'past' => false,
			'post__in' => false,
			'post__not_in' => false,
			'production' => false,
			'season' => false,
			's' => false,
			'start' => false,
			'status' => array( 'publish' ),
			'upcoming' => false,
		);

		/**
		 * Filter the defaults for the list of events.
		 *
		 * @since 	0.11.9
		 * @param 	array 	$defaults	The current defaults.
		 */
		$defaults = apply_filters( 'wpt/events/get/defaults', $defaults );

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => $filters['status'],
			'meta_query' => array(),
			'order' => $filters['order'],
		);

		/**
		 * Apply upcoming filter.
		 * Ignore when one of the other time related filters are set.
		 * Maybe deprecate in favor of `start="now"`.
		 */

		if (
			$filters['upcoming'] &&
			! $filters['start'] &&
			! $filters['end']
		) {
			$filters['start'] = 'now';
		}

		if ( $filters['production'] ) {
			$args['meta_query'][] = array(
				'key' => WPT_Production::post_type_name,
				'value' => (array) $filters['production'],
				'compare' => 'IN',
			);
		}
		/**
		 * Apply start filter.
		 * Only show events that start after the `start` value.
		 * Can be any value that is supported by strtotime().
		 * @since 0.9
		 */

		if ( $filters['start'] ) {
			$args['meta_query'][] = array(
				'key' => THEATER_ORDER_INDEX_KEY,
				'value' => strtotime( $filters['start'], current_time( 'timestamp' ) ) - get_option( 'gmt_offset' ) * 3600,
				'compare' => '>=',
				'type' => 'NUMERIC',
			);
		}

		/**
		 * Apply end filter.
		 * Only show events that start before the `end` value.
		 * Can be any value that is supported by strtotime().
		 * @since 0.9
		 */

		if ( $filters['end'] ) {
			$args['meta_query'][] = array(
				'key' => THEATER_ORDER_INDEX_KEY,
				'value' => strtotime( $filters['end'], current_time( 'timestamp' ) ) - get_option( 'gmt_offset' ) * 3600,
				'compare' => '<=',
				'type' => 'NUMERIC',
			);
		}

		if ( $filters['post__in'] ) {
			$args['post__in'] = $filters['post__in'];
		}

		if ( $filters['post__not_in'] ) {
			$args['post__not_in'] = $filters['post__not_in'];
		}

		if($filters['s']) {
			$productions_by_keyword_args = array(
				's' => $filters['s'],
				'status' => $filters['status'],
				'ignore_sticky_posts' => true,
			);
			$productions_by_keyword = $wp_theatre->productions->get($productions_by_keyword_args);
						
			$args['meta_query'][] = array(
				'key' => WPT_Production::post_type_name,
				'value' => wp_list_pluck( $productions_by_keyword, 'ID' ),
				'compare' => 'IN',				
			);
		}

		if ( $filters['season'] ) {
			$args['meta_query'][] = array(
				'key' => WPT_Season::post_type_name,
				'value' => $filters['season'],
				'compare' => '=',
			);
		}

		if ( $filters['cat'] ) {
			$args['cat'] = $filters['cat'];
		}

		if ( $filters['category_name'] ) {
			$args['category_name'] = $filters['category_name'];
		}

		if ( $filters['category__and'] ) {
			$args['category__and'] = $filters['category__and'];
		}

		if ( $filters['category__in'] ) {
			$args['category__in'] = $filters['category__in'];
		}

		if ( $filters['category__not_in'] ) {
			$args['category__not_in'] = $filters['category__not_in'];
		}

		if ( $filters['tag'] ) {
			$args['tag'] = $filters['tag'];
		}

		if ( $filters['limit'] ) {
			$args['posts_per_page'] = $filters['limit'];
			$args['numberposts'] = $filters['limit'];
		} else {
			$args['posts_per_page'] = -1;
			$args['numberposts'] = -1;
		}

		/**
		 * Filter the $args before doing get_posts().
		 *
		 * @since 0.9.2
		 * @since 0.11.9	New filter added, with an extra 'filter' param.
		 *
		 * @param array 	$args 		The arguments to use in get_posts to retrieve events.
		 * @param array 	$filters 	The filters for the list of events.
		 */
		$args = apply_filters( 'wpt_events_load_args',$args );
		$args = apply_filters( 'wpt_events_get_args',$args );
		$args = apply_filters( 'wpt/events/get/args', $args, $filters );

		$posts = array();

		/*
		 * Don't try to retrieve events if the 's' argument (keyword search) is used, 
		 * but no productions match the search.
		 */
		if (
			empty($filters['s']) ||				// True when the 's' filter is not used.
			! empty($productions_by_keyword)	// True when the 's' filter resulted in matching productions.
		) {
			$posts = get_posts( $args );
		}

		$events = array();
		for ( $i = 0;$i < count( $posts );$i++ ) {
			$event = new WPT_Event( $posts[ $i ] );
			$events[] = $event;
		}

		return $events;
	}

	/**
	 * Preloads events with their productions.
	 *
	 * Sets the production of a each event in a list of events with a single query.
	 * This dramatically decreases the number of queries needed to show a listing of events.
	 *
	 * @since 	0.10.14
	 * @access 	private
	 * @param 	array	$events		An array of WPT_Event objects.
	 * @return 	array				An array of WPT_Event objects, with the production preloaded.
	 */
	private function preload_events_with_productions( $events ) {

		$production_ids = array();

		foreach ( $events as $event ) {
			$production_ids[] = get_post_meta( $event->ID, WPT_Production::post_type_name, true );
		}

		$production_ids = array_unique( $production_ids );

		$productions = get_posts(
			array(
				'post_type' => WPT_Production::post_type_name,
				'post__in' => array_unique( $production_ids ),
				'posts_per_page' => -1,
			)
		);

		$productions_with_keys = array();

		foreach ( $productions as $production ) {
			$productions_with_keys[ $production->ID ] = $production;
		}

		for ( $i = 0; $i < count( $events );$i++ ) {
			$production_id = get_post_meta( $events[ $i ]->ID, WPT_Production::post_type_name, true );
			if ( in_array( $production_id, array_keys( $productions_with_keys ) ) ) {
				$events[ $i ]->production = new WPT_Production( $productions_with_keys[ $production_id ] );
			}
		}
		return $events;
	}

	/**
	 * @deprecated 0.10
	 * @see WPT_Events::get_categories()
	 */
	public function categories( $filters = array() ) {
		_deprecated_function( 'WPT_Events::categories()', '0.10', 'WPT_Events::get_categories()' );
		return $this->get_categories( $filters );
	}

	/**
	 * @deprecated 0.10
	 * @see WPT_Events::get_days()
	 */
	public function days( $filters = array() ) {
		_deprecated_function( 'WPT_Events::days()', '0.10', 'WPT_Events::get_days()' );
		return $this->get_days( $filters );
	}

	/**
	 * @deprecated 0.10
	 * @see WPT_Events::get_months()
	 */
	public function months( $filters = array() ) {
		_deprecated_function( 'WPT_Events::months()', '0.10', 'WPT_Events::get_months()' );
		return $this->get_months( $filters );
	}
}
?>
