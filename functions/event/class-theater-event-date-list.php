<?php

/**
 * Handles event dates lists.
 *
 * Use this class to retrieve a list of event dates or a fully formatted HTML list of event dates.
 *
 * ## Basic usage
 * <code>
 * // Retrieve a list of all dates.
 * $dates = new Theater_Dates;
 * foreach ( $dates() as $date ) {
 *		// $date is a Theater_Date object.	 
 *		echo $date->title();
 * }
 * </code>
 *
 * <code>
 * // Output a formatted list of all dates.
 * $dates = new Theater_Dates;
 * echo $dates;
 * </code>
 *
 * ### Filtered lists
 * You can pass extra filter arguments to customize the events that are in the list:
 *
 * <code>
 * // Retrieve a list of upcoming dates.
 * $dates = new Theater_Dates( array( 'start' => 'now' ) );
 * </code>
 * <code>
 * // Retrieve a list of dates for a single event.
 * $dates = new Theater_Dates( array( 'event' => 123 ) );
 * </code>
 * <code>
 * // Retrieve a list of upcoming dates for a single event.
 * $dates = new Theater_Dates( array( 'event' => 123, 'start' => 'now' ) );
 * </code>
 *
 * See [Theater_Dates::get()](#_get) for a full list of accepted arguments.
 *
 * ### Customized output
 * You can also formatting arguments to customize the HTML output of the list:
 *
 * <code>
 * // Retrieve a list of upcoming dates.
 * $dates = new Theater_Dates( array( 'start' => 'now' ) );
 * </code>
 *
 * See [Theater_Dates::get_html()](#_get_html) for a full list of accepted arguments. 
 *
 * @since 	0.5
 * @since 	0.10	Complete rewrite, while maintaining backwards compatibility.
 * @since	0.16	Another rewrite. 
 * @package Theater/Events
 */

class Theater_Event_Date_List extends Theater_List {

	/**
	 * The default filter arguments for event dates lists.
	 * 
	 * @var 	array[string]
	 * @access 	protected
	 * @static
	 */
	protected $default_args = array(
		'cat' => false,
		'category_name' => false,
		'category__and' => false,
		'category__in' => false,
		'category__not_in' => false,
		'tag' => false,
		'end' => false,
		'event' => false,
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
		'template' => '',
		'upcoming' => false,
	);

	/**
	 * Gets a list of event dates.
	 *
	 * ## Usage
	 * <code>
	 * // Retrieve a list of all dates.
	 * Theater_Dates::get();
	 * </code>
	 *
	 * <code>
	 * // Retrieve a list of upcoming dates for a single event.
	 * Theater_Dates::get( array( 'event' => 123, 'start' => 'now' ) );
	 * </code>
	 *
	 * ### Shorthand
	 * This method is also used if you call the `Theater_Dates` class as a function:
	 * <code>
	 * // Retrieve a list of all dates.
	 * $dates = new Theater_Dates;
	 * foreach ( $dates() as $date ) {
	 *		// $date is a Theater_Date object.	 
	 *		echo $date->title();
	 * }
	 * </code>
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
	 * @since	0.16	Replaced the `production` filter with the `event` filter.
	 *
	 * @uses	Theater_Dates::$default_args
	 * @uses	WPT_Order::meta_key to get the key to filter when the 'start' or 'end' filter is used.
	 * @uses	Theater_Date::post_type_name to get the event date post type.
	 * @uses	WPT_Production::post_type_name to get the events post type if the 'event' or 
	 *			's' (keyword search) filter is used.
	 * @uses	WPT_Season::post_type_name to get the seasons post type if the 'season' filter is used.
	 * @uses	WPT_Productions::get() to find productions if the 's' (keyword search) filter is used.
	 * @uses	Theater_Date to create a new event date object for each event in the list.
	 *
	 * @param 	array[string] 	$filters 	An array of filter arguments. Optional.
	 *
	 * Possible argument values:
	 * | argument           | type            | description                                                               |
	 * | ------------------ | --------------- | ------------------------------------------------------------------------- |
	 * | `cat`              | int&#124;string | Category ID or comma-separated list of IDs.                               |
	 * | `category_name`    | string          | Category slug.                                                            |
	 * | `category__and`    | string          | An array of category IDs (AND in).                                        |
	 * | `category__in`     | int[]           | An array of category IDs (OR in, no children).                            |
	 * | `category__not_in` | int[]           | An array of category IDs (NOT in).                                        |
	 * | `tag`              | string          | Tag slug. Comma-separated (either), Plus-separated (all).                 |
	 * | `end`              | string          | A date/time string. Only show event dates that start before this date.    |
	 * |                    |                 | Valid formats are explained in                                            |
	 * |                    |                 | [Date and Time Formats](http://php.net/manual/en/datetime.formats.php).   |
	 * | `event`            | int&#124;array  | Event ID of an array of event IDs. Only show dates of one or more events. |
	 * | `limit`            | int             | Number of dates to return. Use `-1` to show all event dates.              |
	 * | `order`            | string          | The order in which to return the event dates. Either `ASC` or `DESC`.     |
	 * | `post__in`         | int[]           | Array of event date IDs.                                                  |
	 * | `post__not_in`     | int[]           | Array of event date IDs.                                                  |
	 * | `season`           | int             | Season ID. Only show event dates for this season                          |
	 * | `s`                | string          | Search keyword(s). Prepending a term with a hyphen will                   |
	 * |                    |                 | exclude events matching that term. Eg, 'pillow -sofa' will                |
	 * |                    |                 | return events containing 'pillow' but not 'sofa'.                         |
	 * | `start`            | string          | A date/time string. Only show event dates that start after this date.     |
	 * |                    |                 | Valid formats are explained in                                            |
	 * |                    |                 | [Date and Time Formats](http://php.net/manual/en/datetime.formats.php).   |
	 * | `status`           | array           | Array of post statusses. Only show event dates of events with these       |
	 * |                    |                 | statuses.                                                                 |  
	 *
	 * @return 	Theater_Date[] 	An array of events.
	 */
	function get( $filters = array() ) {
		global $wp_theatre;

		/**
		 * Filter the defaults for the list of events.
		 *
		 * @since 	0.11.9
		 * @param 	array 	$defaults	The current defaults.
		 */
		$defaults = apply_filters( 'wpt/events/get/defaults', $this->default_args );

		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type' => Theater_Event_Date::post_type_name,
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

		if ( $filters['production'] && !$filters['event']) {
			$filters['event'] = $filters['production'];
			unset($filters['production']);
		}

		if ( $filters['event'] ) {
			$args['meta_query'][] = array(
				'key' => Theater_Event::post_type_name,
				'value' => (array) $filters['event'],
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
				'key' => $wp_theatre->order->meta_key,
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
				'key' => $wp_theatre->order->meta_key,
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
			);
			$productions_by_keyword = $wp_theatre->productions->get($productions_by_keyword_args);
						
			$args['meta_query'][] = array(
				'key' => WPT_Production::post_type_name,
				'value' => $productions_by_keyword,
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
		 * @param array 	$filters 	The filters for the list of event dates.
		 */
		$args = apply_filters( 'wpt_events_load_args',$args );
		$args = apply_filters( 'wpt_events_get_args',$args );
		$args = apply_filters( 'wpt/events/get/args', $args, $filters );

		$posts = array();

		/**
		 * Don't try to retrieve event dates if the 's' argument (keyword search) is used, 
		 * but no events match the search.
		 */
		if (
			empty($filters['s']) ||				// True when the 's' filter is not used.
			! empty($productions_by_keyword)	// True when the 's' filter resulted in matching productions.
		) {
			$posts = get_posts( $args );
		}

		/*
		 * Use the general default template for events.
		 * @see WPT_Event_Template::get_default();
		 */
		$template = false;

		/**
		 * Filter the default template for event dates in lists.
		 *
		 * @since	0.14.6
		 * @param	string	$template	The default template.
		 */
		$template = apply_filters( 'theater/dates/template/default', $template );

		/**
		 * @deprecated 0.16
		 */
		$template = apply_filters( 'wpt/events/event/template/default', $template );

		if ( ! empty( $filters['template'] ) ) {
			$template = $filters['template'];
		}

		$dates = array();
		for ( $i = 0;$i < count( $posts );$i++ ) {
			$date = new Theater_Event_Date( $posts[ $i ], $template );
			$dates[] = $date;
		}

		return $dates;
	}

	/**
	 * Gets an array of all categories for an event dates list.
	 *
	 * @since 0.5
	 * @since 0.10		Renamed method from `categories()` to `get_categories()`.
	 * @since 0.10.2	Now returns the slug instead of the term_id as the array keys.
	 * @since 0.10.14	Significally decreased the number of queries used.
	 *
	 * @uses	Theater_Dates::get() to get a list of all event dates for a list.
	 *
	 * @param 	array 				$filters	See Theater_Dates::get() for possible values.
	 * @return 	array[string]string				An array of category slug => name pairs.
	 */
	function get_categories( $filters = array() ) {
		$filters['category'] = false;
		$dates = $this->get( $filters );
		$date_ids = wp_list_pluck( $dates, 'ID' );
		$terms = wp_get_object_terms( $date_ids, 'category' );
		$categories = array();

		foreach ( $terms as $term ) {
			$categories[ $term->slug ] = $term->name;
		}

		asort( $categories );
		return $categories;
	}

	/**
	 * Gets the CSS classes for an event dates list.
	 *
	 * @uses 	Theater_Lists::get_classes_for_html() to retrieve the default classes for a list.
	 *
	 * @since 	0.10
	 * @since	0.14.7	Added `$args` to `parent::get_classes_for_html()`.
	 *
	 * @access 	protected
	 * @param 	array $args 	See `Theater_Dates::get_html()` for possible values. Default: array().
	 * @return 	array 			The CSS classes.
	 */
	protected function get_classes_for_html( $args = array() ) {

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
		 * @param 	array $classes 	The CSS classes.
		 * @param 	array $args 	The $args that are being used for the listing. 
		 *							See Theater_Dates::get_html() for possible values.
		 */
		$classes = apply_filters( 'wpt_events_classes', $classes, $args );

		return $classes;
	}

	/**
	 * Gets an array of all days with event dates.
	 *
	 * @since 0.8
	 * @since 0.10				No longer limits the output to days with upcoming events.
	 *							See: https://github.com/slimndap/wp-theatre/issues/75
	 * 							Renamed method from `days()` to `get_days()`.
	 * @since 0.10.1			Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 0.10.6            Added custom sorting again.
	 *							Can't rely on sorting order of events, because historic events have no
	 *							`_wpt_order` set.
	 *
	 * @uses	Theater_Dates::get() to get a list of all event dates for a list.
	 *
	 * @param 	array $filters	See `Theater_Dates::get()` for possible values.
	 * @return 	array 			Days.
	 */
	function get_days( $filters = array() ) {
		$dates = $this->get( $filters );
		$days = array();
		foreach ( $dates as $date ) {
			$days[ date( 'Y-m-d',$date->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ] = date_i18n( 'D j M',$date->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		}

		if ( ! empty( $filters['order'] ) && 'desc' == $filters['order'] ) {
			krsort( $days );
		} else {
			ksort( $days );
		}

		return $days;
	}

	/**
	 * Gets a fully formatted list of event dates in HTML.
	 *
	 * The list of dates is compiled using filter arguments that are part of `$args`.
	 *	
	 * ##Basic usage
	 * 
	 * <code>
	 * // Output an HTML list with all dates.
	 * echo Theater_Dates::get_html();
	 * </code>
	 * 
	 * <code>
	 * // Output an HTML list with upcoming dates.
	 * echo Theater_Dates::get_html( array( 'start' => 'now' ) );
	 * </code>
	 *
	 * See [Theater_Dates::get()](#_get) for possible values for `$args`.
	 *
	 * ### Customized output
	 * Additionally, it is possible to customize the output using formatting argument:
	 *
	 * <code>
	 * // Output an HTML list with a 'category' filter at the top.
	 * echo Theater_Dates::get_html( array( 'paginateby' => 'category' ) );
	 * </code>
	 * 
	 * <code>
	 * // Output an HTML list with upcoming dates.
	 * echo Theater_Dates::get_html( array( 'start' => 'now' ) );
	 * </code>
	 *
	 
	 * The event dates can be shown on a single page or be cut up into multiple pages by setting
	 * `$paginateby`. If `$paginateby` is set then a page navigation is added to the top of
	 * the listing.
	 *
	 * The dates can be grouped inside the pages by setting `$groupby`.
	 *
	 * @since 0.5
	 * @since 0.10	Moved parts of this method to seperate reusable methods.
	 *				Renamed method from `html()` to `get_html()`.
	 *				Rewrote documentation.
	 *
	 * @uses Theater_List::get_html() to generate the HTML for an event dates list.
	 *
	 * @param array[string] $args 	An array of arguments. Optional.
	 *
	 * The possible argument are identical to `Theater_Dates::get()`, plus the following formatting arguments:
	 *
	 *
	 * Possible argument values:
	 * | argument           | type            | description                                                               |
	 * | ------------------ | --------------- | ------------------------------------------------------------------------- |
	 * | `paginateby`       | string[]        | Array of field names to paginate the list by.                             |
	 * |                    |                 | Possible values: `day`, `month`, `year` and `category`.                   |
	 * | `groupby`          | string          | Field name to group the list by.                                          |
	 * |                    |                 | Possible values: `day`, `month`, `year` and `category`.                   |
	 * | `template`         | string          | Template to use for the individual event dates.                           |
	 *
	 * @return 	string 	A fully formatted list of event dates in HTML.
	 */
	function get_html( $args = array() ) {

		$html = parent::get_html( $args );

		/**
		 * Filter the formatted listof event dates in HTML.
		 *
		 * @since 0.10
		 *
		 * @param 	string $html 	The HTML.
		 * @param 	array $args 	The $args that are being used for the listing.
		 */
		$html = apply_filters( 'theater/dates/html', $html, $args );
		
		/**
		 * @deprecated 0.16
		 */
		$html = apply_filters( 'wpt_events_html', $html, $args );

		return  $html;
	}

	/**
	 * Gets a list of event dates in HTML for a single category.
	 *
	 * @since 0.10
	 * @since 0.10.2	Category now uses slug instead of term_id.
	 *
	 * @uses 	Theater_Dates::get_html_grouped();
	 *
	 * @access 	protected
	 * @param 	string $category_slug		Slug of the category.
	 * @param 	array $args 				See Theater_Dates::get_html() for possible values.
	 * @return 	string						The HTML.
	 */
	protected function get_html_for_category( $category_slug, $args = array() ) {
		if ( $category = get_category_by_slug( $category_slug ) ) {
				$args['cat'] = $category->term_id;
		}

		return $this->get_html_grouped( $args );
	}

	/**
	 * Gets a list of event dates in HTML for a single day.
	 *
	 * @since 0.10
	 *
	 * @uses	Theater_Dates::get_html_grouped();
	 *
	 * @access 	protected
	 * @param 	string $day		The day in `YYYY-MM-DD` format.
	 * @param 	array $args 	See Theater_Dates::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_for_day( $day, $args = array() ) {

		/*
		 * Set the `start`-filter to today.
		 * Except when the active `start`-filter is set to a later date.
		 */
		if (
			empty( $args['start'] ) ||
			(strtotime( $args['start'] ) < strtotime( $day ))
		) {
			$args['start'] = $day;
		}

		/*
		 * Set the `end`-filter to the next day.
		 * Except when the active `end`-filter is set to an earlier date.
		 */
		if (
			empty( $args['end'] ) ||
			(strtotime( $args['end'] ) > strtotime( $day.' +1 day' ))
		) {
			$args['end'] = $day.' +1 day';
		}

		return $this->get_html_grouped( $args );
	}

	/**
	 * Gets a list of event dates in HTML for a single month.
	 *
	 * @since 0.10
	 *
	 * @uses 	Theater_Dates::get_html_grouped();
	 *
	 * @access 	protected
	 * @param 	string $day		The month in `YYYY-MM` format.
	 * @param 	array $args 	See Theater_Dates::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_for_month( $month, $args = array() ) {

		/*
		 * Set the `start`-filter to the first day of the month.
		 * Except when the active `start`-filter is set to a later date.
		 */
		if (
			empty( $args['start'] ) ||
			(strtotime( $args['start'] ) < strtotime( $month ))
		) {
			$args['start'] = $month;
		}

		/*
		 * Set the `end`-filter to the first day of the next month.
		 * Except when the active `end`-filter is set to an earlier date.
		 */
		if (
			empty( $args['end'] ) ||
			(strtotime( $args['end'] ) > strtotime( $month.' +1 month' ))
		) {
			$args['end'] = $month.' +1 month';
		}

		return $this->get_html_grouped( $args );
	}

	/**
	 * Gets a list of event dates in HTML for a single year.
	 *
	 * @since 0.10
	 *
	 * @uses 	Theater_Dates::get_html_grouped();
	 *
	 * @access 	protected
	 * @param 	string $day		The year in `YYYY` format.
	 * @param 	array $args 	See Theater_Dates::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_for_year( $year, $args = array() ) {

		/*
		 * Set the `start`-filter to the first day of the year.
		 * Except when the active `start`-filter is set to a later date.
		 */
		if (
			empty( $args['start'] ) ||
			(strtotime( $args['start'] ) < strtotime( $year.'-01-01' ))
		) {
			$args['start'] = $year.'-01-01';
		}

		/*
		 * Set the `end`-filter to the first day of the next year.
		 * Except when the active `end`-filter is set to an earlier date.
		 */
		if (
			empty( $args['end'] ) ||
			(strtotime( $args['end'] ) > strtotime( $year.'-01-01 +1 year' ))
		) {
			$args['end'] = $year.'-01-01 +1 year';
		}

		return $this->get_html_grouped( $args );
	}

	/**
	 * Gets a list of event dates in HTML for a page.
	 *
	 * @since 0.10
	 *
	 * @uses Theater_Dates::get_html_grouped()
	 * @uses Theater_Dates::get_html_for_year()
	 * @uses Theater_Dates::get_html_for_month()
	 * @uses Theater_Dates::get_html_for_day()
	 * @uses Theater_Dates::get_html_for_category()
	 *
	 * @access 	protected
	 * @param 	array $args 	See Theater_Dates::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_for_page( $args = array() ) {
		global $wp_query;

		$html = '';

		/*
		 * Check if the user used the page navigation to select a particular page.
		 * Then revert to the corresponding Theater_Dates::get_html_for_* method.
		 * @see Theater_Dates::get_html_page_navigation().
		 */

		if ( ! empty( $wp_query->query_vars['wpt_year'] ) ) {
			$html = $this->get_html_for_year( $wp_query->query_vars['wpt_year'], $args );
		} elseif ( ! empty( $wp_query->query_vars['wpt_month'] ) ) {
			$html = $this->get_html_for_month( $wp_query->query_vars['wpt_month'], $args );
		} elseif ( ! empty( $wp_query->query_vars['wpt_day'] ) ) {
			$html = $this->get_html_for_day( $wp_query->query_vars['wpt_day'], $args );
		} elseif ( ! empty( $wp_query->query_vars['wpt_category'] ) ) {
			$html = $this->get_html_for_category( $wp_query->query_vars['wpt_category'], $args );
		} else {
			/*
			 * The user didn't select a page.
			 * Show the full listing.
			 */
			$html = $this->get_html_grouped( $args );
		}

		/**
		 * Filter the HTML for a page in an event dates list.
		 *
		 * @since	0.12.2
		 * @param	string	$html_group	The HTML for this page.
		 * @param	array	$args		The arguments for the HTML for a page in this event dates list.
		 */
		$html = apply_filters( 'theater/dates/html/page', $html, $args );

		/**
		 * @deprecated 0.16
		 */
		$html = apply_filters( 'wpt/events/html/page', $html, $args );

		return $html;
	}

	/**
	 * Gets a grouped list of event dates in HTML.
	 *
	 * The event dates can be grouped inside a page by setting $groupby.
	 * If $groupby is not set then all events are show in a single, ungrouped list.
	 *
	 * @since 	0.10
	 * @since	0.14.7	Added $args to $event->html().
	 *
	 * @uses Theater_Dates::$default_args_for_html
	 * @uses Theater_Dates::html()
	 * @uses Theater_Dates::get_html_for_month()
	 * @uses Theater_Dates::get_html_for_day()
	 * @uses Theater_Dates::get_html_for_category()
	 * @uses Theater_Dates::get()
	 * @uses Theater_Dates::preload_dates_with_events()
	 * @uses Theater_Date::get_html() to get the HTML output of an event date.
	 *
	 * @access 	protected
	 * @param 	array $args 	See Theater_Dates::get_html() for possible values.
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
						
						$day_header = date_i18n( 'l d F',strtotime( $day ) );
						$day_header = apply_filters( 'theater/dates/group/day', $day_header, $day );

						/**
						 * @deprecated 0.16
						 */
						$day_header = apply_filters( 'wpt_listing_group_day', $day_header, $day );
						
						ob_start();
						?><h3 class="wpt_listing_group day"><?php echo $day_header; ?></h3><?php
						echo $day_html;
							
						$html .= ob_get_clean();
						
					}
				}
				break;
			case 'month':
				$months = $this->get_months( $args );
				foreach ( $months as $month => $name ) {
					if ( $month_html = $this->get_html_for_month( $month, $args ) ) {
						
						$month_header = date_i18n( 'F',strtotime( $month ) );
						$month_header = apply_filters( 'theater/dates/group/month',$month_header,$month );

						/**
						 * @deprecated 0.16
						 */
						$month_header = apply_filters( 'wpt_listing_group_month',$month_header,$month );
						
						ob_start();
						?><h3 class="wpt_listing_group month"><?php echo $month_header; ?></h3><?php
						echo $month_html;
						
						$html .= ob_get_clean();
					}
				}
				break;
			case 'year':
				$years = $this->get_years( $args );
				foreach ( $years as $year => $name ) {
					if ( $year_html = $this->get_html_for_year( $year, $args ) ) {
						
						$year_header = date_i18n( 'Y',strtotime( $year.'-01-01' ) );
						
						$year_header = apply_filters( 'theater/dates/group/year',$year_header,$year );

						/**
						 * @deprecated 0.16
						 */
						$year_header = apply_filters( 'wpt_listing_group_year',$year_header,$year );

						ob_start();
						?><h3 class="wpt_listing_group year"><?php echo $year_header; ?></h3><?php
						echo $year_html;
						
						$html .= ob_get_clean();
					}
				}
				break;
			case 'category':
				$categories = $this->get_categories( $args );
				foreach ( $categories as $cat_id => $name ) {
					if ( $cat_html = $this->get_html_for_category( $cat_id, $args ) ) {
						
						$cat_header = $name;
						$cat_header = apply_filters( 'theater/dates/group/category',$cat_header,$cat_id );

						/**
						 * @deprecated 0.16
						 */
						$cat_header = apply_filters( 'wpt_listing_group_category',$cat_header,$cat_id );
						
						ob_start();
						?><h3 class="wpt_listing_group category"><?php echo $cat_header; ?></h3><?php
						echo $cat_html;
							
						$html .= ob_get_clean();

					}
				}
				break;
			default:
				$events = $this->get( $args );
				$events = $this->preload_dates_with_events( $events );
				$html_group = '';

				foreach ( $events as $event ) {

					$html_event = $event->get_html();

					/**
					 * Filters the event HTML in lists.
					 *
					 * @since	0.14.6
					 *
					 * @param	string			$html_event	The event HTML.
					 * @param	Theater_Date	$event		The event.
					 */
					$html_event = apply_filters( 'theater/dates/date/html', $html_event, $event );

					/**
					 * @deprecated 0.16
					 */
					$html_event = apply_filters( 'wpt/events/event/html', $html_event, $event );

					$html_group .= $html_event;
				}

				/**
				 * Filter the HTML for a group in a list.
				 *
				 * @since	0.12.2
				 * @param	string	$html_group	The HTML for this group.
				 * @param	array	$args		The arguments for the HTML of this listing.
				 */
				$html_group = apply_filters( 'theater/dates/group/html', $html_group, $args );

				/**
				 * @deprecated 0.16
				 */
				$html_group = apply_filters( 'wpt/events/html/grouped/group', $html_group, $args );

				$html .= $html_group;
		}
		return $html;
	}

	/**
	 * Gets the page navigation for an event dates list in HTML.
	 *
	 * @since 	0.10
	 * @since	0.13.4	Show the pagination filters in the same order as the
	 *					the 'paginateby' argument.
	 *
	 * @access 	protected
	 * @uses	Theater_Dates::get_pagination_filters()
	 * @uses	Theater_List::filter_pagination()
	 * @param 	array $args     The arguments being used for the event listing.
	 *							See Theater_Dates::get_html() for possible values.
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
		 * Filter the HTML of the page navigation for an event dates list.
		 *
		 * @since	0.13.3
		 * @param 	string 	$html	The HTML of the page navigation for an event listing.
		 * @param 	array 	$args	The arguments being used for the event listing.
		 */
		$html = apply_filters( 'theater/dates/navigation/html', $html, $args );

		/**
		 * @deprecated 0.16
		 */
		$html = apply_filters( 'wpt/events/html/page/navigation', $html, $args );

		return $html;
	}

	/**
	 * Gets all months that have events.
	 *
	 * @since 0.5
	 * @since 0.10				No longer limits the output to months with upcoming events.
	 *							See: https://github.com/slimndap/wp-theatre/issues/75
	 * 							Renamed method from `months()` to `get_months()`.
	 * @since 0.10.1			Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 0.10.6            Added custom sorting again.
	 *							Can't rely on sorting order of events, because historic events have no
	 *							`_wpt_order` set.
	 *
	 * @uses	Theater_Dates::get() to get a list of all event dates for a list.
	 * @uses	Theater_Date::datetime()
	 *
	 * @param 	array $filters	See Theater_Dates::get() for possible values.
	 * @return 	array 			Months.
	 */
	function get_months( $filters = array() ) {
		$events = $this->get( $filters );
		$months = array();
		foreach ( $events as $event ) {
			$months[ date( 'Y-m',$event->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ] = date_i18n( 'M Y',$event->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		}

		if ( ! empty( $filters['order'] ) && 'desc' == $filters['order'] ) {
			krsort( $months );
		} else {
			ksort( $months );
		}

		return $months;
	}

	/**
	 * Gets the pagination filters for an event listing.
	 *
	 * @since	0.13.4
	 * @uses	Theater_Dates::get_pagination_filters()
	 * @return 	array	The pagination filters for an event listing.
	 */
	protected function get_pagination_filters() {

		$filters = parent::get_pagination_filters();

		$filters['day'] = array(
			'title' => __( 'Days', 'theatre' ),
			'query_arg' => 'wpt_day',
			'callback' => array( __CLASS__, 'get_days' ),
		);

		$filters['month'] = array(
			'title' => __( 'Months', 'theatre' ),
			'query_arg' => 'wpt_month',
			'callback' => array( __CLASS__, 'get_months' ),
		);

		$filters['year'] = array(
			'title' => __( 'Years', 'theatre' ),
			'query_arg' => 'wpt_year',
			'callback' => array( __CLASS__, 'get_years' ),
		);

		$filters['category'] = array(
			'title' => __( 'Categories', 'theatre' ),
			'query_arg' => 'wpt_category',
			'callback' => array( __CLASS__, 'get_categories' ),
		);

		/**
		 * Filter the pagination filters for an event listing.
		 *
		 * @since 	0.13.4
		 * @param	array	$filters	The current pagination filters for an event listing..
		 */
		$filters = apply_filters( 'theater/dates/pagination/filters', $filters );

		/**
		 * @deprecated 0.16
		 */
		$filters = apply_filters( 'wpt/events/pagination/filters', $filters );

		return $filters;
	}

	/**
	 * Gets all distincs years for an event dates list.
	 *
	 * @since 0.10
	 * @since 0.10.1			Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 0.10.6            Added custom sorting again.
	 *							Can't rely on sorting order of events, because historic events have no
	 *							`_wpt_order` set.
	 *
	 * @uses	Theater_Dates::get() to get a list of all event dates for a list.
	 * @uses	Theater_Date::datetime()
	 *
	 * @param 	array $filters	See Theater_Dates::get() for possible values.
	 * @return 	array 			Years.
	 */
	function get_years( $filters = array() ) {
		$events = $this->get( $filters );
		$years = array();
		foreach ( $events as $event ) {
			$years[ date( 'Y',$event->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ] = date_i18n( 'Y',$event->datetime() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		}

		if ( ! empty( $filters['order'] ) && 'desc' == $filters['order'] ) {
			krsort( $years );
		} else {
			ksort( $years );
		}

		return $years;
	}

	/**
	 * Preloads dates with their events.
	 *
	 * Sets the event of a each date in a list of event dates with a single query.
	 * This dramatically decreases the number of queries needed to show a list of event dates.
	 *
	 * @since 	0.10.14
	 * @access 	private
	 * @uses	WPT_Production::post_type_name
	 * @uses	Theater_Date::$ID
	 * @uses	Theater_Date::$production
	 * @uses	WPT_Production
	 * @param 	Theater_Date[]	$events	An array of Theater_Date objects.
	 * @return 	Theater_Date[]			An array of Theater_Date objects, with the event preloaded.
	 */
	protected function preload_dates_with_events( $dates ) {

		$event_ids = array();

		foreach ( $dates as $date ) {
			$event_ids[] = get_post_meta( $date->ID, WPT_Production::post_type_name, true );
		}

		$event_ids = array_unique( $event_ids );

		$events = get_posts(
			array(
				'post_type' => WPT_Production::post_type_name,
				'post__in' => array_unique( $event_ids ),
				'posts_per_page' => -1,
			)
		);

		$events_with_keys = array();

		foreach ( $events as $event ) {
			$events_with_keys[ $event->ID ] = $event;
		}

		for ( $i = 0; $i < count( $dates );$i++ ) {
			$event_id = get_post_meta( $dates[ $i ]->ID, WPT_Production::post_type_name, true );
			if ( in_array( $event_id, array_keys( $events_with_keys ) ) ) {
				$dates[ $i ]->event = new WPT_Production( $events_with_keys[ $event_id ] );
			}
		}
		return $dates;
	}

	/**
	 * @deprecated 0.10
	 * @see Theater_Dates::get_categories()
	 */
	function categories( $filters = array() ) {
		_deprecated_function( 'Theater_Dates::categories()', '0.10', 'Theater_Dates::get_categories()' );
		return selfget_categories( $filters );
	}

	/**
	 * @deprecated 0.10
	 * @see Theater_Dates::get_days()
	 */
	function days( $filters = array() ) {
		_deprecated_function( 'Theater_Dates::days()', '0.10', 'Theater_Dates::get_days()' );
		return $this->get_days( $filters );
	}

	/**
	 * Gets the last event date.
	 *
	 * @since 		0.8
	 * @deprecated	0.16
	 * @return		Theater_Date|bool	The last event date or <false> if no event date is found.
	 */
	function last() {
		_deprecated_function( 'Theater_Dates::months()', '0.16', 'Theater_Dates( array(\'limit\' => 0, \'order\' => \'DESC\') )' );

		$args = array(
			'post_type' => Theater_Date::post_type_name,
			'post_status' => 'publish',
			'order' => 'desc',
			'posts_per_page' => 1,
		);

		$events = get_posts( $args );

		if ( empty( $events ) ) {
			return false;
		} else {
			return new Theater_Date( $events[0] );
		}
	}

	/**
	 * @deprecated 0.10
	 * @see Theater_Dates::get_months()
	 */
	function months( $filters = array() ) {
		_deprecated_function( 'Theater_Dates::months()', '0.10', 'Theater_Dates::get_months()' );
		return $this->get_months( $filters );
	}
}
?>
