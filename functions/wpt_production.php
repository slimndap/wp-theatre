<?php
class WPT_Production {

	const post_type_name = 'wp_theatre_prod';
	
	public $ID;
	public $post;
	public $title;
	public $events;
	public $upcoming;
	public $cities;
	public $categories;

	// @codingStandardsIgnoreStart
	function __construct( $ID = false ) {
		if ( $ID instanceof WP_Post ) {
			// $ID is a WP_Post object
			$this->post = $ID;
			$ID = $ID->ID;
		}

		if ( ! $ID ) {
			$post = get_post();
			if ( $post ) {
				$ID = $post->ID;
			}
		}

		$this->ID = $ID;
	}
	// @codingStandardsIgnoreEnd

	function post_type() {
		return get_post_type_object( self::post_type_name );
	}

	protected function apply_template_filters( $value, $filters ) {
		foreach ( $filters as $filter ) {
			$value = $filter->apply_to( $value, $this );
		}
		return $value;
	}

	function categories( $args = array() ) {
		$defaults = array(
			'html' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->categories ) ) {
			$this->categories = apply_filters( 'wpt_production_categories',wp_get_post_categories( $this->ID ),$this );
		}

		if ( $args['html'] ) {
			if ( ! empty( $this->categories ) ) {
				$html = '';
				$html .= '<ul class="wpt_production_categories">';
				foreach ( $this->categories as $category_id ) {
					$category = get_category( $category_id );
					$html .= '<li class="wpt_production_category wpt_production_category_'.$category->slug.'">'.$category->name.'</li>';
				}
				$html .= '</ul>';
				return apply_filters( 'wpt_production_categories_html', $html, $this );
			}
		} else {
			return $this->categories;
		}
	}

	/**
	 * Production cites.
	 *
	 * Returns a summary of the cities of the production events as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string URL or HTML.
	 */
	function cities( $args = array() ) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->cities ) ) {
			$cities = array();

			$events = $this->upcoming();
			if ( is_array( $events ) && (count( $events ) > 0) ) {
				foreach ( $events as $event ) {
					$city = trim( ucwords( get_post_meta( $event->ID,'city',true ) ) );
					if ( ! empty( $city ) && ! in_array( $city, $cities ) ) {
						$cities[] = $city;
					}
				}
			}

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
			$this->cities = apply_filters( 'wpt_production_cities',$cities_text, $this );
		}
		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_cities">';
			$html .= $this->apply_template_filters( $this->cities(), $args['filters'] );
			$html .= '</div>';
			return apply_filters( 'wpt_production_cities_html', $html, $this );
		} else {
			return $this->cities;
		}
	}

	function content( $args = array() ) {
		global $wp_theatre;
		$defaults = array(
			'html' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->content ) ) {
			$content = $this->post()->post_content;
			$this->content = apply_filters( 'wpt_production_content',$content, $this );

		}

		if ( $args['html'] ) {

			/*
			 * Temporarily unhook other Theater filters that hook into `the_content`
			 * to avoid loops.
			 */

			remove_filter( 'the_content', array( $wp_theatre->frontend, 'the_content' ) );
			remove_filter( 'the_content', array( $wp_theatre->listing_page, 'the_content' ) );

			$html = '';
			$html .= '<div class="'.self::post_type_name.'_content">';
			$html .= apply_filters( 'the_content',$this->content );
			$html .= '</div>';

			/*
			 * Re-hook other Theater filters that hook into `the_content`.
			 */

			add_filter( 'the_content', array( $wp_theatre->frontend, 'the_content' ) );
			add_filter( 'the_content', array( $wp_theatre->listing_page, 'the_content' ) );

			return apply_filters( 'wpt_production_content_html', $html, $this );
		} else {
			return $this->content;
		}

	}

	/**
	 * Gets the upcoming production dates.
	 *
	 * @since	0.4
	 * @since	0.15.3	Moved HTML output to seperate method.
	 *					@see WPT_Production::dates_html();
	 *					Now returns an array instead of a summary (string).
	 * @since	0.15.7	Make sure that the keys are reset of the returned array.
	 *					Fixes #199.
	 * @return	array	The upcoming production dates.
	 */
	function dates( $deprecated = array() ) {

		if ( ! empty( $deprecated['html'] ) ) {
			$defaults = array(
				'filters' => array(),
			);
			$deprecated = wp_parse_args( $deprecated, $defaults );
			return $this->dates_html( $deprecated['filters'] );
		}

		$dates = array();

		foreach ( $this->events( array( 'start' => 'now' ) ) as $event ) {
			$dates[] = $event->startdate();
		}

		// Remove duplicate dates _without_ preserving keys.
		$dates = array_values(array_unique( $dates ));

		/**
		 * Filter the upcoming production dates.
		 * @since	0.15.3
		 * @param	array			$dates		The upcoming production dates.
		 * @param	WPT_Production	$production	The production.
		 */
		$dates = apply_filters( 'wpt/production/dates', $dates, $this );

		/**
		 * @deprecated	0.15.3
		 */
		$dates = apply_filters( 'wpt_production_dates', $dates, $this );

		return $dates;
	}

	/**
	 * Gets the HTML for the upcoming production dates.
	 *
	 * @since	0.15.3
	 * @param	array	$filters	The template filters to apply.
	 * @return	string				The HTML for the upcoming production dates.
	 */
	function dates_html( $filters = array() ) {

		ob_start();

		?><div class="<?php echo self::post_type_name; ?>_dates"><?php echo $this->apply_template_filters( $this->dates_summary(), $filters ); ?></div><?php

		$html = ob_get_clean();

		/**
		 * Filter the HTML for the upcoming production dates.
		 * @since	0.15.3
		 * @param	string			$html		The HTML for the upcoming production dates.
		 * @param	WPT_Production	$production	The production.
		 */
		$html = apply_filters( 'wpt/production/dates/html', $html, $this );

		/**
		 * @deprecated	0.15.3
		 */
		$html = apply_filters( 'wpt_production_dates_html', $html, $this );

		return $html;
	}

	/**
	 * Gets the summary for the upcoming production dates.
	 *
	 * @since	0.15.3
	 * @since	0.15.7	Fix: A production with both historic and multiple upcoming events 
	 *					showed the first upcoming event as the enddate.
	 *					Fixes #200.
	 * @return	string	The summary for the upcoming production dates.
	 */
	function dates_summary() {
		global $wp_theatre;

		$dates = $this->dates();

		if ( empty( $dates ) ) {
			return '';
		}

		$old_events_args = array(
			'end' => 'now',
			'production' => $this->ID,
		);
		$old_events = $wp_theatre->events->get( $old_events_args );

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

		/**
		 * Filter the summary for the upcoming production dates.
		 * @since	0.15.3
		 * @param	string			$dates_summary	The summary for the upcoming production dates.
		 * @param	WPT_Production	$production		The production.
		 */
		$dates_summary = apply_filters( 'wpt/production/dates/summary', $dates_summary, $this );

		return $dates_summary;
	}

	/**
	 * Gets the events of a production.
	 * 
	 * @since	0.?
	 * @since	0.15.15	Use get_post_status() instead of $this->post()->post_status to
	 *					make sure that we're not using a locally cached value.
	 * @param array $filters (default: array())
	 * @return void
	 */
	function events( $filters = array() ) {
		global $wp_theatre;

		$defaults = array(
			'production' => $this->ID,
			'status' => get_post_status( $this->ID ),
		);
		
		$filters = wp_parse_args( $filters, $defaults );

		if ( ! isset( $this->events ) ) {
			$this->events = $wp_theatre->events->get( $filters );
		}

		return $this->events;
	}

	/**
	 * Production excerpt.
	 *
	 * Returns an excerpt of the production page as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string URL or HTML.
	 */
	function excerpt( $args = array() ) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'words' => 15,
			'filters' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->excerpt ) ) {
			$excerpt = $this->post()->post_excerpt;
			if ( empty( $excerpt ) ) {
				 $excerpt = wp_trim_words( strip_shortcodes( $this->post()->post_content ), $args['words'] );
			}
			$this->excerpt = apply_filters( 'wpt_production_excerpt',$excerpt, $this );

		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<p class="'.self::post_type_name.'_excerpt">';
			$html .= $this->apply_template_filters( $this->excerpt(), $args['filters'] );
			$html .= '</p>';
			return apply_filters( 'wpt_production_excerpt_html', $html, $this );
		} else {
			return $this->excerpt;
		}
	}

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
	 * @return	array	The prices for the production.
	 */
	function prices() {

		$prices = array();

		foreach ( $this->events() as $event ) {
			foreach ( $event->prices() as $price ) {
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
	 * @param   array	$filters	The template filters to apply.
	 * @return	array				The HTML of the prices for the production.
	 */
	function prices_html( $filters = array() ) {
		$html = '';

		$prices_summary_html = $this->prices_summary_html();

		if ( ! empty( $prices_summary_html ) ) {
			ob_start();
			?><div class="<?php echo self::post_type_name; ?>_prices"><?php echo $this->apply_template_filters( $prices_summary_html, $filters ); ?></div><?php
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
	 * @see 	WPT_Production::prices()
	 * @return 	string 	A summary of the prices for the production.
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
		 * Filter the summary of the prices for the production.
		 *
		 * @since	0.15.3
		 * @param 	string	 		$prices_summary	The current summary.
		 * @param 	WPT_Production	$production		The production.
		 */
		$prices_summary = apply_filters( 'wpt/production/prices/summary',$prices_summary, $this );

		return $prices_summary;
	}

	/**
	 * Gets the HTML for the summary of the prices for the production.
	 *
	 * @since 	0.15.3
	 * @see		WPT_Production::prices_summary()
	 * @return 	string	The HTML for the summary of the prices for the production.
	 */
	public function prices_summary_html() {

		$html = $this->prices_summary();
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
	 *
	 * @return object WPT_Season.
	 */
	function season() {
		if ( ! isset( $this->season ) ) {
			$season = get_post_meta( $this->ID,'wp_theatre_season',true );
			if ( ! empty( $season ) ) {
				$this->season = new WPT_Season( $season );
			} else {
				$this->season = false;
			}
		}
		return $this->season;
	}

	/**
	 * Production summary.
	 *
	 * Returns a summary of the production page containing dates, cities and excerpt as plain text or as an HTML element.
	 *
	 * @todo Add prices.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string URL or HTML.
	 */
	function summary( $args = array() ) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->summary ) ) {
			$this->summary = '';
			if ( $this->dates() != '' ) {
				$short = $this->dates_summary();
				if ( $this->cities() != '' ) {
					$short .= ' '.__( 'in','theatre' ).' '.$this->cities();
				}
				$short .= '. ';
				$this->summary .= ucfirst( $short );
			}
			$this->summary .= $this->excerpt();
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<p class="'.self::post_type_name.'_summary">';
			$html .= $this->apply_template_filters( $this->summary(), $args['filters'] );
			$html .= '</p>';

			return apply_filters( 'wpt_production_summary_html', $html, $this );
		} else {
			return $this->summary;
		}
	}

	/**
	 * Gets the production tags.
	 * 
	 * @since	0.15.27
	 * @return	WP_Term[]	The production tags.
	 */
	function tags( ) {
		
		$tags = wp_get_post_tags( $this->ID );
				
		/**
		 * Filter the production tags.
		 * 
		 * @since	0.15.27
		 * @param	WP_Term[]		$tags		The production tags.
		 * @param	WPT_Production	$production	The production.
		 */
		$tags = apply_filters( 'wpt/production/tags', $tags, $this );
		
		return $tags;
	}
	
	/**
	 * Gets the HTML for the production tags.
	 * 
	 * @since	0.15.27
	 * @return	string	The HTML for the production tags.
	 */
	function tags_html( ) {
		
		ob_start();

		?><ul class="<?php echo self::post_type_name; ?>_tags"><?php
			
			$tags = $this->tags();
			foreach( $tags as $tag ) {
				?><li class="<?php echo self::post_type_name; ?>_tag <?php echo self::post_type_name; ?>_tag_<?php echo $tag->slug; ?>"><?php 
					echo $tag->name; 
				?></li><?php
			}
		?></ul><?php

		$html = ob_get_clean();

		/**
		 * Filter the HTML for the production tags.
		 * @since	0.15.27
		 * @param	string			$html		The HTML for the upcoming production dates.
		 * @param	WPT_Production	$production	The production.
		 */
		$html = apply_filters( 'wpt/production/tags/html', $html, $this );

		return $html;
		
	}

	/**
	 * Gets the production thumbnail ID.
	 *
	 * @since 	0.4
	 * @since	0.12.5	Deprecated the HTML output.
	 *					Use @see WPT_Production::thumbnail_html() instead.
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
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
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

		if ( ! isset( $this->title ) ) {
			$post = $this->post();
			if ( empty( $post ) ) {
				$title = '';
			} else {
				$title = $this->post()->post_title;
			}
			$this->title = apply_filters( 'wpt_production_title', $title, $this );
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_title">';
			$html .= $this->apply_template_filters( $this->title(), $args['filters'] );
			$html .= '</div>';
			return apply_filters( 'wpt_production_title_html', $html, $this );
		} else {
			return $this->title;
		}
	}

	/**
	 * Returns value of a custom field.
	 *
	 * @since 0.8
	 *
	 * @param array $args {
	 *     @type string $field custom field name.
	 * }
	 * @return string.
	 */
	function custom( $field, $args = array() ) {
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'filters' => array(),
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! isset( $this->{$field} ) ) {
			$this->{$field} = apply_filters(
				'wpt_production_'.$field,
				get_post_meta( $this->ID, $field, true ),
				$field,
				$this
			);
		}

		if ( $args['html'] ) {
			$html = '';
			$html .= '<div class="'.self::post_type_name.'_'.$field.'">';
			$html .= $this->apply_template_filters( $this->{$field}, $args['filters'] );
			$html .= '</div>';

			return apply_filters( 'wpt_production_'.$field.'_html', $html, $this );
		} else {
			return $this->{$field};
		}
	}

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

	/**
	 * Gets the HTML for a production.
	 *
	 * @since 	0.4
	 * @since 	0.10.8	Added a filter to the default template.
	 * @since	0.14.7	Added the $args parameter.
	 * @since	0.15.2	Removed the $args parameter.
	 *
	 * @param	string	$template	The template for the production HTML.
	 * @param 	array	$args		The listing args (if the production is part of a listing).
	 * @return 	string				The HTML for a production.
	 */
	function html( $template = '' ) {
		global $wp_theatre;

		if ( is_array( $template ) ) {
			$defaults = array(
				'template' => '',
			);
			$args = wp_parse_args( $template, $defaults );
			$template = $args['template'];
		}

		$classes = array();
		$classes[] = self::post_type_name;

		$template = new WPT_Production_Template( $this, $template );
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
			$this->post = get_post( $this->ID );
		}
		return $this->post;
	}

	function render() {
		return $this->html();
	}

	function get_events() {
		return $this->events();
	}
}

?>
