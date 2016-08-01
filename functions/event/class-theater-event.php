<?php
/**
 * Handles individual events.
 *
 * An event can have one or more event dates.
 *
 * ##Usage
 *
 * <code>
 * // Output an event as HTML.
 * $event = new Theater_Event( 123 );
 * echo $event;
 * </code>
 * <code>
 * // Output an event as HTML with a custom template:
 * $event = new Theater_Event( 123, '{{title}}{{dates}}{{location}}' );
 * echo $event;
 * </code>
 * <code>
 * // Get the value of an event field:
 * $event = new Theater_Event( 123 );
 * $dates = $event->dates(); // Eg. '05-06-2017'.
 * $prices = $event->prices(); // An array of all ticket prices for this date.
 * $title = $event->title(); // Eg. 'Sound of Music'.
 * </code>
 * <code>
 * // Output the value of an event date field as HTML:
 * $event = new Theater_Event( 123 );
 * echo $event->dates;
 * echo $event->prices;
 * echo $event->title;
 * </code>
 *
 * ## Fields
 *
 * Events have the following fields:
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
 * `{{thumbnail|permalink}} {{title|permalink}} {{dates}} {{cities}}`
 *
 * @package	Theater/Events
 * @since	0.16
 *
 */
class Theater_Event extends Theater_Item {

	const name = 'event';	
	const post_type_name = 'wp_theatre_prod';

	function get_fields() {
		
		$fields = array(
			'categories',
			'cities',
			'content',
			'dates',
			'dates_summary',
			'event_dates',
			'excerpt',
			'prices',
			'summary',
		);
		
		return $fields;
	}

	/**
	 * Gets the categories of an event.
	 * 
	 * @since	0.x
	 * @todo	The results from wp_get_post_categories() aren’t cached which will result in a db call beign made 
	 *			every time this function is called. Use this function with care. For performance, functions like 
	 *			get_the_category() should be used to return categories attached to a post.
	 * @internal
	 * @return	WP_Category[] 	The categories of an event
	 */
	function get_categories() {
		$categories = wp_get_post_categories( $this->ID );
		return $categories;
	}
	
	/**
	 * Gets the HTML for the categories of an event.
	 * 
	 * @since	0.16
	 * @uses	Theater_Item::get_field() to get the list of categories for an event.
	 * @internal
	 * @return 	string	The HTML for the categories of an event.
	 */
	function get_categories_html() {
		
		$categories = $this->get_field('categories');
		
		if (empty($categories)) {
			return '';
		}
		
		ob_start();
		?><ul class="wpt_production_categories"><?php
			foreach ( $categories as $category_id ) {
				$category = get_category( $category_id );
				?><li class="wpt_production_category wpt_production_category_<?php echo $category->slug; ?>"><?php
					echo $category->name;
				?></li><?php
			}
		?></ul><?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Gets the cities of an event.
	 *
	 * @since 0.4
	 * @uses	Theater_Event::get_event_dates() to get a list of upcoming dates for an event.
	 * @uses	Theater_Item::get_field() to get the city of an event date.
	 * @internal
	 * @return	string	The cities of an event.
	 */
	function get_cities() {
		
		$cities = array();

		foreach( $this->get_event_dates(array( 'start' => 'now' ) ) as $date) {
			$city = $date->get_field('city');
			$city = trim( ucwords( $city ) );
			if (!empty($city)) {
				$cities[] = $city;
			}
		}
		
		$cities = array_unique( array_values($cities) );

		$cities_text = '';

		switch ( count( array_slice( $cities,0,3 ) ) ) {
			case 1:
				$cities_text .= $cities[0];
				break;
			case 2:
				$cities_text .= $cities[0].' '.__( 'and','theatre' ).' '.$cities[1];
				break;
			case 3:
				$cities_text .= $cities[0].', '.$cities[1].' '.__( 'and','theatre' ).' '.$cities[2];
				break;
		}

		if ( count( $cities ) > 3 ) {
			$cities_text = __( 'ao','theatre' ).' '.$cities_text;
		}
		
		return $cities_text;
	}
	
	/**
	 * Gets the post content of an event.
	 * 
	 * @since	0.x
	 * @uses	Theater_Item::get_post() to get the post object of an event.
	 * @internal
	 * @return	string	The post content of an event.
	 */
	function get_content() {		
		$content = $this->get_post()->post_content;
		return $content;
	}
	
	/**
	 * Gets the HTML for the post content of an event.
	 * 
	 * @since	0.16
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @internal
	 * @return	string	The HTML for the post content of an event.
	 */
	function get_content_html() {
		global $wp_theatre;
		/*
		 * Temporarily unhook other Theater filters that hook into `the_content`
		 * to avoid loops.
		 */

		remove_filter( 'the_content', array( $wp_theatre->frontend, 'the_content' ) );
		remove_filter( 'the_content', array( $wp_theatre->listing_page, 'the_content' ) );
		
		ob_start();
		?><div class="<?php echo $this->get_post_type(); ?>_content"><?php
		echo apply_filters( 'the_content',$this->get_field('content'));
		?></div><?php
			
		$html = ob_get_clean();

		add_filter( 'the_content', array( $wp_theatre->frontend, 'the_content' ) );
		add_filter( 'the_content', array( $wp_theatre->listing_page, 'the_content' ) );
		
		return $html;
		
	}

	/**
	 * Gets the upcoming event dates.
	 *
	 * @since	0.4
	 * @since	0.15.3	Moved HTML output to seperate method.
	 *					@see WPT_Production::dates_html();
	 *					Now returns an array instead of a summary (string).
	 * @since	0.15.7	Make sure that the keys are reset of the returned array.
	 *					Fixes #199.
	 * @uses	Theater_Event::get_event_dates() to get a list of upcoming dates for an event.
	 * @uses	Theater_Item::get_field() to get the startdate of an event date.
	 * @internal
	 * @return	array	The upcoming production dates.
	 */
	function get_dates() {

		$dates = array();

		foreach ( $this->get_event_dates( array( 'start' => 'now' ) ) as $date ) {
			$dates[] = $date->get_field('startdate');
		}

		// Remove duplicate dates _without_ preserving keys.
		$dates = array_values( array_unique( $dates ) );

		return $dates;
	}

	/**
	 * Gets the HTML for the upcoming event dates.
	 *
	 * @since	0.15.3
	 * @uses	Theater_Item::get_post_type() to add the post type to the classes of the HTML output.
	 * @uses	Theater_Item::get_field() to get the dates summary of an event date.
	 * @param 	WPT_Template_Placeholder_Filter[] 	$filters 	An array of filters to apply to the value if the field.
	 * @internal
	 * @return	string				The HTML for the upcoming event dates.
	 */
	function get_dates_html( $filters = array() ) {

		ob_start();

		?><div class="<?php echo $this->get_post_type(); ?>_dates"><?php echo $this->apply_template_filters( $this->get_field('dates_summary'), $filters ); ?></div><?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Gets the summary of the upcoming event dates of an event.
	 *
	 * @since	0.15.3
	 * @since	0.15.7	Fix: A production with both historic and multiple upcoming events 
	 *					showed the first upcoming event as the enddate.
	 *					Fixes #200.
	 * @uses	Theater_Item::get_field() to get all upcoming event dates of an event.
	 * @uses	Theater_Event::get_event_dates() to get all past event dates of an event.
	 * @internal
	 * @return	string	The summary for the upcoming event dates.
	 */
	function get_dates_summary() {

		$dates = $this->get_field('dates');

		if ( empty( $dates ) ) {
			return '';
		}

		$old_dates_args = array(
			'end' => 'now',
			'production' => $this->ID,
		);
		$old_events = $this->get_event_dates($old_dates_args);

		if ( empty( $old_events ) ) {
			if ( 1 == count( $dates ) ) {
				$dates_summary = $dates[0];
			} else {
				/* translators: a date range, eg. April 10, 2016 to April 12, 2016 */
				$dates_summary = sprintf( _x( '%s to %s', 'production dates', 'theatre' ), $dates[0], $dates[ count( $dates ) -1 ] );
			}
		} else {
			/* translators: enddate of a running event, eg. until April 12, 2016 */
			$dates_summary = sprintf( _x( 'until %s', 'production dates', 'theatre' ), $dates[count( $dates ) -1] );
		}

		return $dates_summary;
	}

	/**
	 * Gets the event dates of an event.
	 * 
	 * @since	0.16
	 * @uses	Theater_Event_Date_List::get() to get the event dates of an event.
	 * @param 	array 	$filters An array of filter arguments. Optional.
	 * 					See	[Theater_Event_Date_List::get()](class-Theater_Event_Date_List.html#_get) for possible filter arguments.
	 * @return	Theater_Event_Date[]	The event dates of an event.
	 */
	function get_event_dates( $filters = array() ) {

		$defaults = array(
			'event' => $this->ID,
			'status' => $this->post()->post_status,
		);

		$filters = wp_parse_args( $filters, $defaults );
		
		$dates = new Theater_Event_Date_List($filters);
		return $dates->get();
		
	}

	/**
	 * Gets the event excerpt.
	 *
	 * Returns an excerpt of the production page as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @internal
	 * @return 	string 	The event excerpt.
	 */
	function excerpt( $args = array() ) {

		$defaults = array(
			'words' => 15,
		);

		$args = wp_parse_args( $args, $defaults );

		$excerpt = $this->post()->post_excerpt;
		if ( empty( $excerpt ) ) {
			 $excerpt = wp_trim_words( strip_shortcodes( $this->post()->post_content ), $args['words'] );
		}
		$excerpt = apply_filters( 'wpt_production_excerpt',$excerpt, $this );

		return $excerpt;
	}
	
	/**
	 * Gets the HTML for the excerpt of an event.
	 * 
	 * @since	0.16
	 * @param 	array $filters (default: array())
	 * @internal
	 * @return 	void
	 */
	function get_excerpt_html( $filters = array() ) {
		$value = $this->excerpt();
		ob_start();
		?><p class="<?php echo $this->get_post_type(); ?>_excerpt"><?php
			echo $this->apply_template_filters( $this->excerpt(), $filters );
		?></p><?php
		$html= ob_get_clean();
		
		return $html;
	}

	/**
	 * Production permalink.
	 *
	 * Returns a link to the production page as a URL or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type string $text Display text for HTML version. Defaults to the title of the production.
	 *     @type bool $inside Try to place de link inside the surrounding div. Default <false>.
	 * }
	 * @internal
	 * @return string URL or HTML.
	 */
	function permalink( $args = array() ) {
		$defaults = array(
			'html' => false,
			'text' => $this->title(),
			'inside' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->permalink ) ) {
			$this->permalink = apply_filters( 'wpt_production_permalink',get_permalink( $this->ID ), $this );
		}

		if ( $args['html'] ) {
			$html = '';

			if ( $args['inside'] ) {
				$text_sanitized = trim( $args['text'] );

				$before = '';
				$after = '';
				$text = $args['text'];

				$elements = array( 'div','figure' );
				foreach ( $elements as $element ) {
					if (
						$args['inside'] &&
						strpos( $text_sanitized, '<'.$element ) === 0 &&
						strrpos( $text_sanitized, '</'.$element ) === strlen( $text_sanitized ) - strlen( $element ) - 3
					) {
						$before = substr( $args['text'], 0, strpos( $args['text'], '>' ) + 1 );
						$after = '</'.$element.'>';
						$text = substr( $args['text'], strpos( $args['text'], '>' ) + 1, strrpos( $args['text'],'<' ) - strpos( $args['text'], '>' ) - 1 );
						continue;
					}
				}
				$inside_args = array(
					'html' => true,
					'text' => $text,
				);
				return $before.$this->permalink( $inside_args ).$after;
			} else {
				$html .= '<a href="'.get_permalink( $this->ID ).'">';
				$html .= $args['text'];
				$html .= '</a>';
			}
			return apply_filters( 'wpt_production_permalink_html', $html, $this );
		} else {
			return $this->permalink;
		}
	}

	/**
	 * Gets the prices for the production.
	 *
	 * @since	0.15.3
	 * @internal
	 * @return	array	The prices for the production.
	 */
	function get_prices() {

		$prices = array();

		foreach ( $this->event_dates() as $date ) {
			$date_prices = $date->get_field('prices');
			foreach ( $date_prices as $price ) {
				$prices[] = $price;
			}
		}
		$prices = array_unique( $prices );
		sort( $prices );

		/**
		 * Filter the prices of the production.
		 *
		 * @since	0.15.3
		 * @param 	array	 		$prices		The current prices.
		 * @param 	WPT_Production	$production	The production.
		 */
		$prices = apply_filters( 'wpt/production/prices', $prices, $this );

		return $prices;
	}

	/**
	 * Gets the HTML of the prices for the production.
	 *
	 * @since	0.15.3
	 * @internal
	 * @param   array	$filters	The template filters to apply.
	 * @return	array				The HTML of the prices for the production.
	 */
	function get_prices_html( $filters = array() ) {
		$html = '';

		$prices_summary_html = $this->get_field_html('prices_summary');

		if ( ! empty( $prices_summary_html ) ) {
			ob_start();
			?><div class="<?php echo $this->get_post_type(); ?>_prices"><?php echo $this->apply_template_filters( $prices_summary_html, $filters ); ?></div><?php
			$html = ob_get_clean();
		}

		/**
		 * Filter the HTML of the prices for the production.
		 *
		 * @since	0.15.3
		 * @param 	string	 		$html		The current html.
		 * @param 	WPT_Production	$production	The production.
		 */
		$html = apply_filters( 'wpt/production/prices/html', $html, $this );

		return $html;
	}

	/**
	 * Gets a summary of the prices for the production.
	 *
	 * @since 	0.15.3
	 * @internal
	 * @see 	WPT_Production::prices()
	 * @return 	string 	A summary of the prices for the production.
	 */
	function get_prices_summary() {

		global $wp_theatre;

		$prices = $this->get_field('prices');

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

		return $prices_summary;
	}

	/**
	 * Gets the HTML for the summary of the prices for the production.
	 *
	 * @since 	0.15.3
	 * @internal
	 * @see		WPT_Production::prices_summary()
	 * @return 	string	The HTML for the summary of the prices for the production.
	 */
	public function get_prices_summary_html() {

		$html = $this->get_field('prices_summary');
		$html = esc_html( $html );
		$html = str_replace( ' ', '&nbsp;', $html );

		/**
		 * Filter the HTML for the summary of the prices for the production.
		 *
		 * @since	0.15.3
		 * @param 	string	 		$html		The current html.
		 * @param 	WPT_Production	$production	The production.
		 */
		$html = apply_filters( 'wpt/production/prices/summary/html', $html, $this );

		return $html;
	}

	/**
	 * Production season.
	 *
	 * @since 0.4
	 * @internal
	 *
	 * @return object WPT_Season.
	 */
	function get_season() {
		$season_id = get_post_meta( $this->ID,'wp_theatre_season',true );
		if ( ! empty( $season_id ) ) {
			$season = new WPT_Season( $season_id );
		} else {
			$season = false;
		}

		return $season;
	}

	/**
	 * Production summary.
	 *
	 * Returns a summary of the production page containing dates, cities and excerpt as plain text or as an HTML element.
	 *
	 * @todo Add prices.
	 *
	 * @since 0.4
	 * @internal
	 *
	 * @return string URL or HTML.
	 */
	function get_summary() {

		$summary = '';
		
		$dates = $this->get_field('dates');

		if (!empty($dates))	{
			$short = $this->get_field('dates_summary');
			
			$cities = $this->get_field('cities');
			if ( !empty($cities) ) {
				$short .= ' '.sprintf(__( 'in %s ','theatre' ), $cities);
			}
			$short .= '. ';
			$summary .= ucfirst( $short );
		}
		$summary .= $this->get_field('excerpt');

		return $summary;
	}

	/**
	 * Gets the production thumbnail ID.
	 *
	 * @since 	0.4
	 * @since	0.12.5	Deprecated the HTML output.
	 *					Use @see WPT_Production::thumbnail_html() instead.
	 * @internal
	 *
	 * @return 	int	ID of the thumbnail.
	 */
	function thumbnail( $deprecated = array() ) {

		if ( ! empty( $deprecated['html'] ) ) {
			$defaults = array(
				'size' => 'thumbnail',
				'filters' => array(),
			);
			$deprecated = wp_parse_args( $deprecated, $defaults );
			return $this->thumbnail_html( $deprecated['size'], $deprecated['filters'] );
		}

		$thumbnail = get_post_thumbnail_id( $this->ID );

		/**
		 * Filter the production thumbnail ID.
		 *
		 * @since	0.12.5
		 * @param	int				ID			The production thumbnail ID.
		 * @param	WPT_Production	$production	The production.
		 */
		$thumbnail = apply_filters( 'wpt/production/thumbnail', $thumbnail, $this );

		return $thumbnail;
	}

	/**
	 * Get the production thumbnail HTML.
	 *
	 * @since	0.12.5
	 * @internal
	 * @param 	string 	$size 		The thumbnail size. Default: 'thumbnail'.
	 * @param 	array 	$filters 	The template filters to apply.
	 * @return 	string				The production thumbnail HTML.
	 */
	function thumbnail_html( $size = 'thumbnail', $filters = array() ) {

		$html = '';
		$thumbnail = get_the_post_thumbnail( $this->ID,$size );
		if ( ! empty( $thumbnail ) ) {
			$html .= '<figure>';
			$html .= $this->apply_template_filters( $thumbnail, $filters );
			$html .= '</figure>';
		}

		/**
		 * Filter the production thumbnail HTML.
		 *
		 * @since	0.12.5
		 * @param	string			$html		The production thumbnail HTML.
		 * @param	string			$size		The thumbnail size.
		 * @param	array			$filters	The template filters to apply.
		 * @param	WPT_Production	$production	The production.
		 */
		$html = apply_filters( 'wpt/production/thumbnail/html/size='.$size, $html, $filters, $this );
		$html = apply_filters( 'wpt/production/thumbnail/html', $html, $size, $filters, $this );

		/**
		 * @deprecated	0.12.5
		 */
		$html = apply_filters( 'wpt_production_thumbnail_html', $html, $this );

		return $html;
	}

	/**
	 * Production title.
	 *
	 * Returns the production title as plain text or as an HTML element.
	 *
	 * @since 0.4
	 * @internal
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function get_title( $args = array() ) {
		$title = get_the_title( $this->ID );
		return $title;
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
	 * @return 	string				The HTML for an event.
	 */
	function get_html( $template = '' ) {
		$classes = array();
		$classes[] = $this->get_post_type();

		$template = new WPT_Production_Template( $this, $this->template );
		$html = $template->get_merged();

		/**
		 * Filter the HTML output for a production.
		 *
		 * @since	0.14.7
		 * @param	string				$html		The HTML output for a production.
		 * @param	WPT_Event_Template	$template	The production template.
		 * @param	array				$args		The listing args (if the production is part of a listing).
		 * @param	WPT_Production		$production	The production.
		 */
		$html = apply_filters( 'wpt/production/html',$html, $template, $this );

		/**
		 * @deprecated	0.14.7
		 */
		$html = apply_filters( 'wpt_production_html',$html, $this );

		/**
		 * Filter the classes for a production.
		 *
		 * @since 	0.?
		 * @param	array		$classes	The classes for a production.
		 * @param	WPT_Event	$event		The production.
		 */
		$classes = apply_filters( 'wpt_production_classes',$classes, $this );

		// Wrapper
		$html = '<div class="'.implode( ' ',$classes ).'">'.$html.'</div>';

		return $html;
	}

	/**
	 * @deprecated 	0.x
	 * @internal
	 */
	function render() {
		return $this->html();
	}

	/**
	 * @deprecated 	0.16
	 * @internal
	 */
	function events( $filters = array() ) {
		return $this->get_event_dates( $filters );
	}

	/**
	 * @deprecated 	0.16
	 * @internal
	 */
	function past() {
		global $wp_theatre;
		if ( ! isset( $this->past ) ) {
			$filters = array(
				'production' => $this->ID,
				'past' => true,
			);
			$this->past = $wp_theatre->events->get( $filters );
		}
		return $this->past;
	}

	/**
	 * @deprecated 	0.16
	 * @internal
	 */
	function upcoming() {
		global $wp_theatre;
		if ( ! isset( $this->upcoming ) ) {
			$filters = array(
				'upcoming' => true,
			);
			$this->upcoming = $this->events( $filters );
		}
		return $this->upcoming;
	}

}

?>
