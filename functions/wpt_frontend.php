<?php
class WPT_Frontend {
	function __construct() {
		add_action('init', array($this,'init'));
		add_action('wp_head', array($this,'wp_head'));

		add_action('the_content', array($this, 'the_content'));

		add_shortcode('wpt_events', array($this,'wpt_events'));
		add_shortcode('wpt_productions', array($this,'wpt_productions'));
		add_shortcode('wpt_seasons', array($this,'wpt_productions'));
		add_shortcode('wp_theatre_iframe', array($this,'wp_theatre_iframe'));
		add_shortcode('wpt_production_events', array($this,'wpt_production_events'));
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
		if (!empty($wp_theatre->options['stylesheet'])) {
			wp_enqueue_style( 'wp_theatre', plugins_url( '../css/style.css', __FILE__ ), null, $wpt_version );
		}

		// Add Thickbox files
		if (!empty($wp_theatre->options['integrationtype']) && $wp_theatre->options['integrationtype']=='lightbox') {
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox', includes_url('/js/thickbox/thickbox.css'), null, $wpt_version);			
		}
	}

	function wp_head() {
		global $wp_theatre;
		global $wpt_version;
		
		$html = array();
		
		$html[] = '<meta name="generator" content="Theatre '.$wpt_version.'" />';

		if (!empty($wp_theatre->options['custom_css'])) {
			$html[].= '<!-- Custom Theatre CSS -->';
			$html[].= '<style>';
			$html[].= $wp_theatre->options['custom_css'];
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
				isset( $wp_theatre->options['show_events'] ) &&
				in_array($wp_theatre->options['show_events'], array('above','below'))
			) {
				$production = new WPT_Production();			
				$events_html = '<h3>'.WPT_Event::post_type()->labels->name.'</h3>';
				$events_html.= '[wpt_production_events]{{remark}} {{datetime}} {{location}} {{tickets}}[/wpt_production_events]';
				
				switch ($wp_theatre->options['show_events']) {
					case 'above' :
						$content = $events_html.$content;
						break;
					case 'below' :
						$content.= $events_html;
				}
			}
		}
		return $content;
	}

	function wpt_events($atts, $content=null) {
		global $wp_theatre;
		
		$atts = shortcode_atts( array(
			'upcoming' => true,
			'past' => false,
			'paginateby'=>array(),
			'season'=> false,
			'groupby'=>false,
			'limit'=>false
		), $atts );
				
		if (!empty($atts['paginateby'])) {
			$fields = explode(',',$atts['paginateby']);
			for ($i=0;$i<count($fields);$i++) {
				$fields[$i] = trim($fields[$i]);
			}
			$atts['paginateby'] = $fields;
		}
		
		if (!is_null($content) && !empty($content)) {
			$atts['template'] = html_entity_decode($content);
		}

		$wp_theatre->events->filters['upcoming'] = true;
		
		$transient_key = 'wpt_events_'.md5(serialize(array_merge($atts, $_GET)));
		if ( ! ( $html = get_transient($transient_key) ) ) {
			$html = $wp_theatre->events->html($atts);
			set_transient($transient_key, $html, 4 * MINUTE_IN_SECONDS );
		}

		return $html;
	}

	function wpt_productions($atts, $content=null) {
		global $wp_theatre;
		
		$atts = shortcode_atts( array(
			'paginateby' => array(),
			'upcoming' => false,
			'season'=> false,
			'groupby'=>false,
			'limit'=>false
		), $atts );
				
		if (!empty($atts['paginateby'])) {
			$fields = explode(',',$atts['paginateby']);
			for ($i=0;$i<count($fields);$i++) {
				$fields[$i] = trim($fields[$i]);
			}
			$atts['paginateby'] = $fields;
		}

		if (!is_null($content) && !empty($content)) {
			$atts['template'] = html_entity_decode($content);
		}

		$transient_key = 'wpt_prod_'.md5(serialize(array_merge($atts, $_GET)));
		if ( ! ( $html = get_transient($transient_key) ) ) {
			$html = $wp_theatre->productions->html($atts);
			set_transient($transient_key, $html, 4 * MINUTE_IN_SECONDS );
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
	
	function wpt_production_events($atts, $content=null) {
		global $wp_theatre;

		if (is_singular(WPT_Production::post_type_name)) {
			$args = array(
				'production' => get_the_ID(),
				'status' => get_post_status()
			);
		
			if (!is_null($content) && !empty($content)) {
				$args['template'] = html_entity_decode($content);
			} else {
				$args['template'] = '{{remark}} {{datetime}} {{location}} {{tickets}}';
			}
			
			return $wp_theatre->events->html($args);
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