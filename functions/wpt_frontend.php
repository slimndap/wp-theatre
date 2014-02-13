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
		wp_enqueue_script( 'wp_theatre_js', plugins_url( '../js/main.js', __FILE__ ), array('jquery') );
		if ($this->options['integrationtype']=='lightbox') {
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox.css', includes_url('/js/thickbox/thickbox.css'), null, '1.0');			
		}
	}

	function wp_head() {
		global $wp_theatre;
		echo '<meta name="generator" content="Theatre '.$wp_theatre->version.'" />'."\n";
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
		if (is_singular(WPT_Production::post_type_name)) {
			if (isset( $this->options['show_events'] ) && (esc_attr( $this->options['show_events'])=='yes')) {
				$production = new WPT_Production();			
				$content .= '<h3>'.WPT_Event::post_type()->labels->name.'</h3>';
				$content .= $production->compile_events();
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
				
		return WP_Theatre::render_events($atts);
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