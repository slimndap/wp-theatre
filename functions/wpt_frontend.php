<?php
class WPT_Frontend {
	function __construct() {
		add_action('init', array($this,'init'));
		add_action('wp_head', array($this,'wp_head'));

		add_filter('pre_get_posts', array($this,'pre_get_posts') );
		add_action('the_content', array($this, 'the_content'));

		add_shortcode('wp_theatre_events', array($this,'wp_theatre_events'));
		add_shortcode('wp_theatre_iframe', array($this,'wp_theatre_iframe'));
		add_shortcode('wpt_production_events', array($this,'wpt_production_events'));

		$this->options = get_option( 'wp_theatre' );
	}
	
	function init() {
		global $wp_theatre;

		// Add built-in Theatre javascript
		wp_enqueue_script( 'wp_theatre_js', plugins_url( '../js/main.js', __FILE__ ), array('jquery') );

		// Add built-in Theatre stylesheet
		if (!empty($wp_theatre->options['stylesheet'])) {
			wp_enqueue_style( 'wp_theatre', plugins_url( '../css/style.css', __FILE__ ) );
		}

		// Add Thickbox files
		if ($wp_theatre->options['integrationtype']=='lightbox') {
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox', includes_url('/js/thickbox/thickbox.css'), null, '1.0');			
		}
	}

	function wp_head() {
		global $wp_theatre;
		
		$html = array();
		
		$html[] = '<meta name="generator" content="Theatre '.$wp_theatre->version.'" />';

		if (!empty($wp_theatre->options['custom_css'])) {
			$html[].= '<!-- Custom Theatre CSS -->';
			$html[].= '<style>';
			$html[].= $wp_theatre->options['custom_css'];
			$html[].= '</style>';
		
		}		
		if (is_singular(WPT_Production::post_type_name)) {
			$production = new WPT_Production();			
			$html[].= $production->social_meta_tags();
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
				$events_html.= '[wpt_production_events]';
				
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

	function wp_theatre_events($atts, $content=null) {
		$atts = shortcode_atts( array(
			'paged' => 0,
			'grouped' => 0,
		), $atts );
		extract($atts);
				
		return WP_Theatre::compile_events($atts);
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
		if (is_singular(WPT_Production::post_type_name)) {
			$production = new WPT_Production();			
			return $production->compile_events();
		}
	}
}

?>