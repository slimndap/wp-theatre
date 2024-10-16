<?php
class WPT_Frontend {
	
	public $options;
	
	function __construct() {
		add_action( 'init', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_head', array( $this, 'wp_head' ) );

		add_filter( 'the_content', array( $this, 'the_content' ) );

		add_shortcode( 'wpt_events', array( $this, 'wpt_events' ) );
		add_shortcode( 'wpt_productions', array( $this, 'wpt_productions' ) );
		add_shortcode( 'wpt_seasons', array( $this, 'wpt_productions' ) );

		add_shortcode( 'wp_theatre_iframe', array( $this, 'get_iframe_html' ) );

		add_shortcode( 'wpt_production_events', array( $this, 'wpt_production_events' ) );

		add_shortcode( 'wpt_season_productions', array( $this, 'wpt_season_productions' ) );
		add_shortcode( 'wpt_season_events', array( $this, 'wpt_season_events' ) );

		add_shortcode( 'wpt_event_ticket_button', array( $this, 'wpt_event_ticket_button' ) );

		$this->options = get_option( 'theatre' );

		// Deprecated
		add_shortcode( 'wp_theatre_events', array( $this, 'wpt_events' ) );
		add_action( 'template_redirect', array( $this, 'redirect_deprecated_tickets_page_url' ) );
	}

	/**
	 * Enqueues the Theater javascript and CSS files.
	 *
	 * @since	0.?
	 * @since	0.13	Added thickbox args to manipulate the behaviour of the tickets thickbox.
	 * @since	0.13.5	Moved main.js to footer.
	 * @return 	void
	 */
	function enqueue_scripts() {
		global $wp_theatre;

		// Add built-in Theatre javascript
		wp_enqueue_script( 'wp_theatre_js', plugins_url( '../js/main.js', __FILE__ ), array( 'jquery' ), $wp_theatre->wpt_version, true );

		// Add built-in Theatre stylesheet
		if ( ! empty( $wp_theatre->wpt_style_options['stylesheet'] ) ) {
			wp_enqueue_style( 'wp_theatre', plugins_url( '../css/style.css', __FILE__ ), null, $wp_theatre->wpt_version );
		}

		// Add Thickbox files
		if (
			! empty( $wp_theatre->wpt_tickets_options['integrationtype'] ) &&
			'lightbox' == $wp_theatre->wpt_tickets_options['integrationtype']
		) {
			wp_enqueue_script( 'thickbox' );

			$thickbox_args = array(
				'width' => 800,
				'height' => 600,
				'disable_width' => false,
			);

			/**
			 * Filter the thickbox arguments.
			 *
			 * @since	0.13
			 *
			 * @param 	array	The thickbox arguments.
			 */
			$thickbox_args = apply_filters( 'wpt/frontend/thickbox/args', $thickbox_args );

			wp_localize_script( 'thickbox', 'thickbox_args', $thickbox_args );

			wp_enqueue_style( 'thickbox', includes_url( '/js/thickbox/thickbox.css' ), null, $wp_theatre->wpt_version );
		}
	}


	/**
	 * wp_head function.
	 * 
	 * @since	0.?
	 * @since	0.15.16	Removed custom CSS.
	 * @return 	void
	 */
	function wp_head() {
		global $wp_theatre;
		global $wpt_version;

		$html = array();

		$html[] = '<meta name="generator" content="Theater '.$wpt_version.'" />';

		echo implode( "\n",$html )."\n";
	}

	/**
	 * Adds events listing to the content of a production page.
	 *
	 * @since 	?
	 * @since 	0.11	Event are now only added to the main post content.
	 * 					As explained by Pippin:
	 *					https://pippinsplugins.com/playing-nice-with-the-content-filter/
	 * @param 	string 	$content
	 * @return 	void
	 */
	public function the_content( $content ) {
		global $wp_theatre;

		if ( is_singular( WPT_Production::post_type_name ) && is_main_query() ) {
			if (
				isset( $wp_theatre->options['show_season_events'] ) &&
				in_array( $wp_theatre->options['show_season_events'], array( 'above', 'below' ) )
			) {
				$events_html = '<h3>'.__( 'Events','theatre' ).'</h3>';
				$events_html .= '[wpt_season_events]';

				switch ( $wp_theatre->options['show_season_events'] ) {
					case 'above' :
						$content = $events_html.$content;
						break;
					case 'below' :
						$content .= $events_html;
				}
			}
			if (
				isset( $wp_theatre->options['show_season_productions'] ) &&
				in_array( $wp_theatre->options['show_season_productions'], array( 'above', 'below' ) )
			) {
				$productions_html = '<h3>'.__( 'Productions','theatre' ).'</h3>';
				$productions_html .= '[wpt_season_productions]';

				switch ( $wp_theatre->options['show_season_productions'] ) {
					case 'above' :
						$content = $productions_html.$content;
						break;
					case 'below' :
						$content .= $productions_html;
				}
			}
		}

		if ( is_singular( WPT_Production::post_type_name ) && is_main_query() ) {

			/**
			 * Filter the content of a production page.
			 *
			 * @since 0.9.5
			 *
			 * @param string  $content 	The current content of the production.
			 */
			$content = apply_filters( 'wpt_production_page_content', $content );

			$content_before = '';

			/**
			 * Filter the content before the content of a production page.
			 *
			 * @since 0.9.5
			 *
			 * @param string  $content_before 	The current content before the production content.
			 */
			$content_before = apply_filters( 'wpt_production_page_content_before', $content_before );

			$content_after = '';

			/**
			 * Filter the content after the content of a production page.
			 *
			 * @since 0.9.5
			 *
			 * @param string  $content_after 	The current content after the production content.
			 */
			$content_after = apply_filters( 'wpt_production_page_content_after', $content_after );

			$content = $content_before.$content.$content_after;

		}

		return $content;
	}

	/**
	 * Gets output for the [wpt_events] shortcode.
	 *
	 * @since 	0.?
	 * @since 	0.10.9	Improved the unique key for transients.
	 *					Fixes issue #97.
	 * @since 	0.11.8	Support for 'post__in' and 'post__not_in'.
	 *					Fixes #128.
	 * @since	0.14.4	Support for 'production'.
	 * @since	0.15.24	Now uses the new Theater_Transient object.
	 *
	 * @uses	Theater_Transient::get() to get the transient value of the [wpt_events] shortcode.
	 * @uses	Theater_Transient::set() to set the transient value of the [wpt_events] shortcode.
	 *
	 * @param 	array 	$atts
	 * @param 	string 	$content (default: null)
	 * @return 	string 	The HTML output.
	 */
	function wpt_events( $atts, $content = null ) {
		global $wp_theatre;
		global $wp_query;

		$defaults = array(
			'cat' => false,
			'category' => false, // deprecated since v0.9.
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'tag' => false,
			'day' => false,
			'end' => false,
			'groupby' => false,
			'limit' => false,
			'month' => false,
			'order' => 'asc',
			'paginateby' => array(),
			'post__in' => false,
			'post__not_in' => false,
			'production' => false,
			'season' => false,
			'start' => false,
			'year' => false,
		);

		/**
		 * Filter the defaults for the [wpt_events] shortcode.
		 *
		 * @since 	0.11.9
		 * @param 	array 	$defaults	The current defaults.
		 */
		$defaults = apply_filters( 'wpt/frontend/shortcode/events/defaults', $defaults );

		$atts = shortcode_atts( $defaults, $atts, 'wpt_events' );

		if ( ! empty( $atts['paginateby'] ) ) {
			$fields = explode( ',',$atts['paginateby'] );
			for ( $i = 0;$i < count( $fields );$i++ ) {
				$fields[ $i ] = trim( $fields[ $i ] );
			}
			$atts['paginateby'] = $fields;
		}

		if ( ! empty( $atts['post__in'] ) ) {
			$atts['post__in'] = explode( ',',$atts['post__in'] );
			$atts['post__in'] = array_map( 'trim',$atts['post__in'] );
		}

		if ( ! empty( $atts['post__not_in'] ) ) {
			$atts['post__not_in'] = explode( ',',$atts['post__not_in'] );
			$atts['post__not_in'] = array_map( 'trim',$atts['post__not_in'] );
		}

		if ( ! empty( $atts['production'] ) ) {
			$atts['production'] = explode( ',',$atts['production'] );
			$atts['production'] = array_map( 'trim',$atts['production'] );
		}

		if ( ! empty( $atts['year'] ) ) {
			$atts['start'] = date( 'Y-m-d',strtotime( $atts['year'].'-01-01' ) );
			$atts['end'] = date( 'Y-m-d',strtotime( $atts['year'].'-01-01 + 1 year' ) );
		}

		if ( ! empty( $atts['month'] ) ) {
			$atts['start'] = date( 'Y-m-d',strtotime( $atts['month'] ) );
			$atts['end'] = date( 'Y-m-d',strtotime( $atts['month'].' + 1 month' ) );
		}

		if ( ! empty( $atts['day'] ) ) {
			$atts['start'] = date( 'Y-m-d',strtotime( $atts['day'] ) );
			$atts['end'] = date( 'Y-m-d',strtotime( $atts['day'].' + 1 day' ) );
		}

		if ( ! empty( $atts['category__in'] ) ) {
			$atts['category__in'] = explode( ',',$atts['category__in'] );
		}

		if ( ! empty( $atts['category__not_in'] ) ) {
			$atts['category__not_in'] = explode( ',',$atts['category__not_in'] );
		}

		if (
			empty( $atts['start'] ) &&
			empty( $atts['end'] )
		) {
			$atts['start'] = 'now';
		}

		if ( ! is_null( $content ) && ! empty( $content ) ) {
			$atts['template'] = html_entity_decode( $content );
		}

		/**
		 * Deprecated since v0.9.
		 * Use `cat`, `category_name`, `category__and`, `category__in` or `category__not_in` instead.
		 */

		if ( ! empty( $atts['category'] ) ) {
			$categories = array();
			$fields = explode( ',',$atts['category'] );
			for ( $i = 0;$i < count( $fields );$i++ ) {
				$category_id = trim( $fields[ $i ] );
				if ( is_numeric( $category_id ) ) {
					$categories[] = trim( $fields[ $i ] );
				} else {
					if ( $category = get_category_by_slug( $category_id ) ) {
						$categories[] = $category->term_id;
					}
				}
			}
			$atts['cat'] = implode( ',',$categories );
		}

		/**
		 * Filter the filters for the events listing.
		 *
		 * @since 	0.11.9
		 * @param 	array 	$atts	The current filters, based on the shortcode attributes.
		 */
		$atts = apply_filters( 'wpt/frontend/shortcode/events/filters', $atts );

		/*
		 * Base the $args parameter for the transient on $atts and $wp_query,
		 * to create unique keys for every page of paginated lists.
		 */
		$unique_args = array_merge(
			array( 'atts' => $atts ),
			array( 'wp_query' => $wp_query->query_vars )
		);
		
		$transient = new Theater_Transient( 'e', $unique_args );
		
		if ( ! ( $html = $transient->get() ) ) {

			$html = $wp_theatre->events->get_html( $atts );
			$transient->set( $html );
			
		}
		
		return $html;
	}

	/**
	 * Gets output for the [wpt_productions] shortcode.
	 *
	 * @since 	0.?
	 * @since	0.10.9	Improved the unique key for transients.
	 *					Fixes issue #97.
	 * @since	0.14.7	Added $shortcode to shortcode_atts().
	 * @since	0.15.10	Added 'ignored_sticky_posts' to shortcode atts.
	 * @since	0.15.16	Added 'start_before', 'start_after', 'end_before' and 'end_after' to shortcode atts.
	 *
	 * @param 	array 	$atts
	 * @param 	string 	$content (default: null)
	 * @return 	string 	The HTML output.
	 */
	function wpt_productions( $atts, $content = null ) {
		global $wp_theatre;
		global $wp_query;

		$defaults = array(
			'paginateby' => array(),
			'post__in' => false,
			'post__not_in' => false,
			'upcoming' => false,
			'season' => false,
			'category' => false, // deprecated since v0.9.
			'cat' => false,
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'tag' => false,
			'start' => false,
			'start_before' => false,
			'start_after' => false,
			'end' => false,
			'end_after' => false,
			'end_before' => false,
			'groupby' => false,
			'limit' => false,
			'order' => 'asc',
			'ignore_sticky_posts' => false,
		);

		$atts = shortcode_atts( $defaults,$atts, 'wpt_productions' );

		if ( ! empty( $atts['paginateby'] ) ) {
			$fields = explode( ',',$atts['paginateby'] );
			for ( $i = 0;$i < count( $fields );$i++ ) {
				$fields[ $i ] = trim( $fields[ $i ] );
			}
			$atts['paginateby'] = $fields;
		}

		if ( ! empty( $atts['post__in'] ) ) {
			$atts['post__in'] = explode( ',',$atts['post__in'] );
		}

		if ( ! empty( $atts['post__not_in'] ) ) {
			$atts['post__not_in'] = explode( ',',$atts['post__not_in'] );
		}

		if ( ! empty( $atts['category__in'] ) ) {
			$atts['category__in'] = explode( ',',$atts['category__in'] );
		}

		if ( ! empty( $atts['category__not_in'] ) ) {
			$atts['category__not_in'] = explode( ',',$atts['category__not_in'] );
		}

		if ( ! is_null( $content ) && ! empty( $content ) ) {
			$atts['template'] = html_entity_decode( $content );
		}

		/**
		 * Deprecated since v0.9.
		 * Use `cat`, `category_name`, `category__and`, `category__in` or `category__not_in` instead.
		 */

		if ( ! empty( $atts['category'] ) ) {
			$categories = array();
			$fields = explode( ',',$atts['category'] );
			for ( $i = 0;$i < count( $fields );$i++ ) {
				$category_id = trim( $fields[ $i ] );
				if ( is_numeric( $category_id ) ) {
					$categories[] = trim( $fields[ $i ] );
				} else {
					if ( $category = get_category_by_slug( $category_id ) ) {
						$categories[] = $category->term_id;
					}
				}
			}
			$atts['cat'] = implode( ',',$categories );
		}

		/**
		 * Filter the filters for the productions listing.
		 *
		 * @since 	0.12.7
		 * @param 	array 	$atts	The current filters, based on the shortcode attributes.
		 */
		$atts = apply_filters( 'wpt/frontend/shortcode/productions/filters', $atts );

		/*
		 * Base the $args parameter for the transient on $atts and $wp_query,
		 * to create unique keys for every page of paginated lists.
		 */
		$unique_args = array_merge(
			array( 'atts' => $atts ),
			array( 'wp_query' => $wp_query->query_vars )
		);

		$transient = new Theater_Transient( 'p', $unique_args );

		if ( ! ( $html = $transient->get() ) ) {

			$html = $wp_theatre->productions->get_html( $atts );
			$transient->set( $html );
			
		}

		return $html;
	}

	function wpt_seasons( $atts, $content = null ) {
		global $wp_theatre;

		$atts = shortcode_atts( array(
			'thumbnail' => true,
			'fields' => null,
			'upcoming' => true,
			'paginateby' => null,
		), $atts );

		if ( ! empty( $atts['fields'] ) ) {
			$fields = explode( ',',$atts['fields'] );
			for ( $i = 0;$i < count( $fields );$i++ ) {
				$fields[ $i ] = trim( $fields[ $i ] );
			}
			$atts['fields'] = $fields;
		}

		if ( ! empty( $atts['paginateby'] ) ) {
			$fields = explode( ',',$atts['paginateby'] );
			for ( $i = 0;$i < count( $fields );$i++ ) {
				$fields[ $i ] = trim( $fields[ $i ] );
			}
			$atts['paginateby'] = $fields;
		}

		if ( ! empty( $atts['thumbnail'] ) ) {
			$atts['thumbnail'] = $atts['thumbnail'] == 1;
		}

		$wp_theatre->seasons->filters['upcoming'] = $atts['upcoming'];
		return $wp_theatre->seasons->html( $atts );
	}

	function wpt_season_events( $atts, $content = null ) {
		global $wp_theatre;

		$atts = shortcode_atts( array(
			'upcoming' => true,
			'past' => false,
			'paginateby' => array(),
			'season' => false,
			'groupby' => false,
			'limit' => false,
		), $atts);

		if ( is_singular( WPT_Season::post_type_name ) ) {
			$atts['season'] = get_the_ID();

			if ( ! is_null( $content ) && ! empty( $content ) ) {
				$atts['template'] = html_entity_decode( $content );
			}

			if ( ! empty( $atts['paginateby'] ) ) {
				$fields = explode( ',',$atts['paginateby'] );
				for ( $i = 0;$i < count( $fields );$i++ ) {
					$fields[ $i ] = trim( $fields[ $i ] );
				}
				$atts['paginateby'] = $fields;
			}

			return $wp_theatre->events->get_html( $atts );
		}
	}

	function wpt_season_productions( $atts, $content = null ) {
		global $wp_theatre;

		$atts = shortcode_atts( array(
			'paginateby' => array(),
			'upcoming' => false,
			'season' => false,
			'groupby' => false,
			'limit' => false,
		), $atts);

		if ( is_singular( WPT_Season::post_type_name ) ) {
			$atts['season'] = get_the_ID();

			if ( ! is_null( $content ) && ! empty( $content ) ) {
				$atts['template'] = html_entity_decode( $content );
			}

			if ( ! empty( $atts['paginateby'] ) ) {
				$fields = explode( ',',$atts['paginateby'] );
				for ( $i = 0;$i < count( $fields );$i++ ) {
					$fields[ $i ] = trim( $fields[ $i ] );
				}
				$atts['paginateby'] = $fields;
			}

			return $wp_theatre->productions->get_html( $atts );
		}
	}

	/**
	 * Gets the HTML for the [wp_theatre_iframe] shortcode.
	 *
	 * @since  	?.?
	 * @since	0.12	Work with the 'wpt_event_tickets' query var,
	 * 					instead of $_GET vars.
	 * @since	0.13.3	Added the 'wpt/frontend/iframe/html' filter.
	 * @since	0.14	Fixed a PHP notice when the 'wpt_event_tickets' is not set.
	 *					Eg. when the iframe page is called directly.
	 * @return 	string	The HTML for the [wpt_event_tickets] shortcode.
	 */
	function get_iframe_html() {
		$html = '';

		$event_id = (int) get_query_var( 'wpt_event_tickets' );

		$tickets_url = '';

		if ( ! empty( $event_id ) ) {
			$tickets_url = get_post_meta( $event_id,'tickets_url',true );
			if ( ! empty( $tickets_url ) ) {
				$html .= '<iframe src="'.$tickets_url.'" class="wp_theatre_iframe"></iframe>';
			}
		}

		/**
		 * Filter the HTML for the [wp_theatre_iframe] shortcode.
		 *
		 * @since	0.13.3
		 * @param 	string	$html			The HTML for the [wp_theatre_iframe] shortcode.
		 * @param	string	$tickets_url	The event tickets url.
		 * @pararm	int		$event_id		The event ID.
		 */
		$html = apply_filters( 'wpt/frontend/iframe/html', $html, $tickets_url, $event_id );

		/**
		 * @deprecated	0.13.3
		 * Use 'wpt/frontend/iframe/html' filter instead.
		 */
		do_action( 'wp_theatre_iframe' );

		return $html;
	}

	/*
	 * Gets the HTML output for the [wpt_production_events] shortcode.
	 *
     * Examples:
	 *     [wpt_production_events production=123]
	 *     [wpt_production_events production=123,456]
	 *     [wpt_production_events production=123]{{title|permalink}}{{datetime}}{{tickets}}[/wpt_production_events]
	 *
	 * On the page of a single production you can leave out the production:
	 *
	 *     [wpt_production_events]
	 *
	 * @since 	0.?
	 * @since	0.14.4	Use the [wpt_events] shortcode to render the output.
	 * @since	0.14.7	Added support for filtered shortcode atts.
	 *
	 * @param 	array	$atts		The shortcode attributes.
	 * @param 	string	$template 	The template. Default <null>.
	 * @return 	string				The HTML output for the [wpt_production_events] shortcode.
	 */
	function wpt_production_events( $atts, $template = null ) {
		global $wp_theatre;

		$atts = shortcode_atts( array(
			'production' => false,
		), $atts, 'wpt_production_events' );

		// Fallback to ID of current production.
		if ( empty( $atts['production'] ) && is_singular( WPT_Production::post_type_name ) ) {
			$atts['production'] = get_the_ID();
		}

		// Bail if no production is defined.
		if ( empty( $atts['production'] ) ) {
			return;
		}

		if ( empty( $template ) ) {
			$template = '{{remark}}{{datetime}}{{location}}{{tickets}}';
		}

		$shortcode_atts = '';
		foreach ( $atts as $key => $value ) {
			$shortcode_atts .= $key.'="'.$value.'" ';
		}

		$shortcode = '[wpt_events '.$shortcode_atts.']'.$template.'[/wpt_events]';
		return do_shortcode( $shortcode );
	}

	function wpt_event_ticket_button( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'id' => false,
		), $atts );
		extract( $atts );

		if ( $id ) {
			$event = new WPT_Event( $id );
			$args = array(
				'html' => true,
			);
			return $event->tickets( $args );
		}
	}

	/**
	 * Redirects any visits to old style tickets page URLs to the new (0.12) pretty tickets page URLs.
	 *
	 * Old: http://example.com/tickets-page/?Event=123
	 * New: http://example.com/tickets-page/my-event/123
	 *
	 * @since	0.12.3
	 * @return	void
	 */
	public function redirect_deprecated_tickets_page_url() {
		global $wp_theatre;

		$theatre_options = $wp_theatre->wpt_tickets_options;
		if (
			isset( $theatre_options['iframepage'] ) &&
			$theatre_options['iframepage'] == get_the_id() &&
			isset( $_GET[ __( 'Event','theatre' ) ] )
		) {
			// We are on the tickets page, using the old style URL, let's redirect
			$event = new WPT_Event( $_GET[ __( 'Event','theatre' ) ] );
			if ( ! empty( $event ) ) {
				$tickets_url_iframe = $event->tickets_url_iframe();
				if ( ! empty( $tickets_url_iframe ) ) {
					// Redirect, Moved Permanently
					wp_redirect( $tickets_url_iframe, 301 );
					exit();
				}
			}
		}
	}
}

?>
