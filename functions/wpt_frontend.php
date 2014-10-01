<?php
class WPT_Frontend {
	function __construct() {
		add_action('init', array($this,'init'));
		add_action('wp_head', array($this,'wp_head'));

		add_action('the_content', array($this, 'the_content'));
		add_filter('pre_get_posts', array($this,'pre_get_posts') );

		add_shortcode('wpt_events', array($this,'wpt_events'));
		add_shortcode('wpt_productions', array($this,'wpt_productions'));
		add_shortcode('wpt_seasons', array($this,'wpt_productions'));
		add_shortcode('wp_theatre_iframe', array($this,'wp_theatre_iframe'));

		add_shortcode('wpt_production_events', array($this,'wpt_production_events'));

		add_shortcode('wpt_season_productions', array($this,'wpt_season_productions'));
		add_shortcode('wpt_season_events', array($this,'wpt_season_events'));

		add_shortcode('wpt_event_ticket_button', array($this,'wpt_event_ticket_button'));

		$this->options = get_option( 'wp_theatre' );
		
		// Deprecated
		add_shortcode('wp_theatre_events', array($this,'wpt_events'));
	}
	
	function init() {
		global $wp_theatre;
		global $wpt_version;

		// Add built-in Theatre javascript
		wp_enqueue_script( 'wp_theatre_js', plugins_url( '../js/main.js', __FILE__ ), array('jquery'), $wpt_version );

		// Add built-in Theatre stylesheet
		if (!empty($wp_theatre->wpt_style_options['stylesheet'])) {
			wp_enqueue_style( 'wp_theatre', plugins_url( '../css/style.css', __FILE__ ), null, $wpt_version );
		}

		// Add Thickbox files
		if (
			!empty($wp_theatre->wpt_tickets_options['integrationtype']) && 
			$wp_theatre->wpt_tickets_options['integrationtype']=='lightbox'
		) {
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox', includes_url('/js/thickbox/thickbox.css'), null, $wpt_version);			
		}
	}

	function wp_head() {
		global $wp_theatre;
		global $wpt_version;
		
		$html = array();
		
		$html[] = '<meta name="generator" content="Theater '.$wpt_version.'" />';

		if (!empty($wp_theatre->wpt_style_options['custom_css'])) {
			$html[].= '<!-- Custom Theater CSS -->';
			$html[].= '<style>';
			$html[].= $wp_theatre->wpt_style_options['custom_css'];
			$html[].= '</style>';
		}
		
		echo implode("\n",$html)."\n";
	}
	
	function pre_get_posts($query) {
				
		// add productions to tag and category archives
		if( is_category() || is_tag() && empty( $query->query_vars['suppress_filters'] ) ) {
			$post_types = $query->get( 'post_type');
			if (empty($post_types)) {
				$post_types = array('post');
			}
			if (is_array($post_types)) {
				$post_types[] = WPT_Production::post_type_name;
			}
			$query->set('post_type',$post_types);
		}
		return $query;
	}

	function the_content($content) {
		global $wp_theatre;
		
		if (is_singular(WPT_Production::post_type_name)) {
			if (
				isset( $wp_theatre->options['show_season_events'] ) &&
				in_array($wp_theatre->options['show_season_events'], array('above','below'))
			) {
				$events_html = '<h3>'.__('Events','wp_theatre').'</h3>';
				$events_html.= '[wpt_season_events]';
				
				switch ($wp_theatre->options['show_season_events']) {
					case 'above' :
						$content = $events_html.$content;
						break;
					case 'below' :
						$content.= $events_html;
				}
			}
			if (
				isset( $wp_theatre->options['show_season_productions'] ) &&
				in_array($wp_theatre->options['show_season_productions'], array('above','below'))
			) {
				$productions_html = '<h3>'.__('Productions','wp_theatre').'</h3>';
				$productions_html.= '[wpt_season_productions]';
				
				switch ($wp_theatre->options['show_season_productions']) {
					case 'above' :
						$content = $productions_html.$content;
						break;
					case 'below' :
						$content.= $productions_html;
				}
			}
		}
		
		return $content;
	}

	function wpt_events($atts, $content=null) {
		global $wp_theatre;
		global $wp_query;
		
		$defaults = array(
			'upcoming' => 'true',
			'past' => false,
			'paginateby'=>array(),
			'category'=> false,
			'day' => false,
			'month' => false,
			'season'=> false,
			'start' => false,
			'end' => false,
			'groupby'=>false,
			'limit'=>false,
		);
		
		if (!empty($wp_query->query_vars['wpt_category'])) {
			$defaults['category']=$wp_query->query_vars['wpt_category'];
		} else {
			/*
			 * For backward compatibility purposes.
			 * Before v0.8 $_GET[__('category','wp_theatre')] was used for the category filter.
			 */
			if(!empty($_GET[__('category','wp_theatre')])) {
				$defaults['category']=$_GET[__('category','wp_theatre')];
			}
		}

		if (!empty($wp_query->query_vars['wpt_day'])) {
			$defaults['day']=$wp_query->query_vars['wpt_day'];
		}
		
		if (!empty($wp_query->query_vars['wpt_month'])) {
			$defaults['month']=$wp_query->query_vars['wpt_month'];
		} else {
			/*
			 * For backward compatibility purposes.
			 * Before v0.8 $_GET[__('month','wp_theatre')] was used for the category filter.
			 */
			if(!empty($_GET[__('month','wp_theatre')])) {
				$defaults['month']=$_GET[__('month','wp_theatre')];
			}
		}
		
		$atts = shortcode_atts( $defaults, $atts );
				
		if (!empty($atts['paginateby'])) {
			$fields = explode(',',$atts['paginateby']);
			for ($i=0;$i<count($fields);$i++) {
				$fields[$i] = trim($fields[$i]);
			}
			$atts['paginateby'] = $fields;
		}
		
		/**
		 * Sanitize the categories.
		 * Translate slugs to IDs.
		 */
		
		if (!empty($atts['category'])) {
			$categories = array();
			$fields = explode(',',$atts['category']);
			for ($i=0;$i<count($fields);$i++) {
				$category_id = trim($fields[$i]);
				if (is_numeric($category_id)) {
					$categories[] = trim($fields[$i]);
				} else {
					if ($category = get_category_by_slug($category_id)) {
						$categories[] = $category->term_id;
					}
				}
			}
			$atts['category'] = implode(',',$categories);
		}
		
		$atts['upcoming'] = 'true' === $atts['upcoming'];
		
		if (!is_null($content) && !empty($content)) {
			$atts['template'] = html_entity_decode($content);
		}

		if ( ! ( $html = $wp_theatre->transient->get('e', array_merge($atts, $_GET)) ) ) {
			$html = $wp_theatre->events->html($atts);
			$wp_theatre->transient->set('e', array_merge($atts, $_GET), $html);
		}
		return $html;
	}

	function wpt_productions($atts, $content=null) {
		global $wp_theatre;
		global $wp_query;
		
		$defaults = array(
			'paginateby' => array(),
			'post__in' => false,
			'post__not_in' => false,
			'upcoming' => false,
			'season'=> false,
			'category'=> false,
			'groupby'=>false,
			'limit'=>false
		);
				
		if (!empty($wp_query->query_vars['wpt_category'])) {
			$defaults['category']=$wp_query->query_vars['wpt_category'];
		} else {
			/*
			 * For backward compatibility purposes.
			 * Before v0.8 $_GET[__('category','wp_theatre')] was used for the category filter.
			 */
			if(!empty($_GET[__('category','wp_theatre')])) {
				$defaults['category']=$_GET[__('category','wp_theatre')];
			}
		}

		$atts = shortcode_atts($defaults,$atts);
				
		if (!empty($atts['paginateby'])) {
			$fields = explode(',',$atts['paginateby']);
			for ($i=0;$i<count($fields);$i++) {
				$fields[$i] = trim($fields[$i]);
			}
			$atts['paginateby'] = $fields;
		}

		if (!empty($atts['category'])) {
			$categories = array();
			$fields = explode(',',$atts['category']);
			for ($i=0;$i<count($fields);$i++) {
				$category_id = trim($fields[$i]);
				if (is_numeric($category_id)) {
					$categories[] = trim($fields[$i]);
				} else {
					if ($category = get_category_by_slug($category_id)) {
						$categories[] = $category->term_id;
					}
				}
			}
			$atts['category'] = implode(',',$categories);
		}

		if (!empty($atts['post__in'])) {
			$atts['post__in'] = explode(',',$atts['post__in']);
		}
		
		if (!empty($atts['post__not_in'])) {
			$atts['post__not_in'] = explode(',',$atts['post__not_in']);
		}
		
		if (!is_null($content) && !empty($content)) {
			$atts['template'] = html_entity_decode($content);
		}

		if ( ! ( $html = $wp_theatre->transient->get('p', array_merge($atts, $_GET)) ) ) {
			$html = $wp_theatre->productions->html($atts);
			$wp_theatre->transient->set('p', array_merge($atts, $_GET), $html);
		}

		return $html;
	}

	function wpt_seasons($atts, $content=null) {
		global $wp_theatre;
		
		$atts = shortcode_atts( array(
			'thumbnail' => true,
			'fields' => null,
			'upcoming' => true,
			'paginateby'=>null
		), $atts );
				
		if (!empty($atts['fields'])) {
			$fields = explode(',',$atts['fields']);
			for ($i=0;$i<count($fields);$i++) {
				$fields[$i] = trim($fields[$i]);
			}
			$atts['fields'] = $fields;
		}

		if (!empty($atts['paginateby'])) {
			$fields = explode(',',$atts['paginateby']);
			for ($i=0;$i<count($fields);$i++) {
				$fields[$i] = trim($fields[$i]);
			}
			$atts['paginateby'] = $fields;
		}
		
		if (!empty($atts['thumbnail'])) {
			$atts['thumbnail'] = $atts['thumbnail'] == 1;
		}
		
		$wp_theatre->seasons->filters['upcoming'] = $atts['upcoming'];
		return $wp_theatre->seasons->html($atts);
	}
	
	function wpt_season_events($atts, $content=null) {
		global $wp_theatre;

		$atts = shortcode_atts( array(
			'upcoming' => true,
			'past' => false,
			'paginateby'=>array(),
			'season'=> false,
			'groupby'=>false,
			'limit'=>false
		), $atts);
		
		if (is_singular(WPT_Season::post_type_name)) {
			$atts['season'] = get_the_ID();
		
			if (!is_null($content) && !empty($content)) {
				$atts['template'] = html_entity_decode($content);
			}

			if (!empty($atts['paginateby'])) {
				$fields = explode(',',$atts['paginateby']);
				for ($i=0;$i<count($fields);$i++) {
					$fields[$i] = trim($fields[$i]);
				}
				$atts['paginateby'] = $fields;
			}

			return $wp_theatre->events->html($atts);
		}
	}
	
	function wpt_season_productions($atts, $content=null) {
		global $wp_theatre;

		$atts = shortcode_atts( array(
			'paginateby' => array(),
			'upcoming' => false,
			'season'=> false,
			'groupby'=>false,
			'limit'=>false
		), $atts);
		
		if (is_singular(WPT_Season::post_type_name)) {
			$atts['season'] = get_the_ID();
		
			if (!is_null($content) && !empty($content)) {
				$atts['template'] = html_entity_decode($content);
			}

			if (!empty($atts['paginateby'])) {
				$fields = explode(',',$atts['paginateby']);
				for ($i=0;$i<count($fields);$i++) {
					$fields[$i] = trim($fields[$i]);
				}
				$atts['paginateby'] = $fields;
			}

			return $wp_theatre->productions->html($atts);
		}
	}
	
	function wp_theatre_iframe($atts, $content=null) {
		$html = '';
		if (isset($_GET[__('Event','wp_theatre')])) {
			$tickets_url = get_post_meta($_GET[__('Event','wp_theatre')],'tickets_url',true);
			if ($tickets_url!='') {
				$html = '<iframe src="'.$tickets_url.'" class="wp_theatre_iframe"></iframe>';
			}
		}
		do_action('wp_theatre_iframe', $atts, $content=null);
		return $html;
	}
	
	/* 
	 * Shortcode to display the upcoming events of a production.
	 *
	 * Examples: 
	 *     [wpt_production_events production=123]
	 *     [wpt_production_events production=123]{{title|permalink}}{{datetime}}{{tickets}}[/wpt_production_events]
	 *
	 * On the page of a single production you can leave out the production:
	 *
	 *     [wpt_production_events]
	 *
	 */
	
	function wpt_production_events($atts, $content=null) {
		global $wp_theatre;

		$atts = shortcode_atts( array(
			'production' => false
		), $atts );
		extract($atts);

		if (!$production && is_singular(WPT_Production::post_type_name)) {
			$production = get_the_ID();
		}

		if ($production) {			
			$args = array(
				'production' => $production
			);
		
			if (!is_null($content) && !empty($content)) {
				$args['template'] = html_entity_decode($content);
			} else {
				$args['template'] = '{{remark}} {{datetime}} {{location}} {{tickets}}';
			}
			
			if ( ! ( $html = $wp_theatre->transient->get('e', array_merge($args, $_GET)) ) ) {
				$html = $wp_theatre->events->html($args);
				$wp_theatre->transient->set('e', array_merge($args, $_GET), $html);
			}

			return $html;
		}
	}
	
	function wpt_event_ticket_button($atts, $content=null) {
		$atts = shortcode_atts( array(
			'id' => false
		), $atts );
		extract($atts);
		
		if ($id) {
			$event = new WPT_Event($id);
			$args = array(
				'html'=>true
			);
			return $event->tickets($args);
		}
	}
}

?>