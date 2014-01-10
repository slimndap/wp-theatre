<?php

class WPT_Setup {
	function __construct() {
		$this->options = get_option( 'wp_theatre' );

		add_action( 'init', array($this,'init'));
		add_action('the_content', array($this, 'the_content'));
		add_action('wp', array($this, 'wp'));
		
		add_shortcode('wp_theatre_events', array($this,'shortcode_events'));

		register_activation_hook( __FILE__, array($this, 'activate' ));		

		add_action( 'plugins_loaded', array($this,'plugins_loaded'));
	}

	function init() {
		register_post_type( WPT_Production::post_type_name,
			array(
				'labels' => array(
					'name' => __( 'Productions','wp_theatre'),
					'singular_name' => __( 'Production','wp_theatre'),
					'add_new' =>  _x('Add New', 'production','wp_theatre'),
					'new_item' => __('New'),' '.__('production','wp_theatre'),
					'add_new_item' => __('Add new').' '.__('production','wp_theatre'),
					'edit_item' => __('Edit production','wp_theatre')
				),
				'public' => true,
				'has_archive' => true,
				'show_in_menu'  => 'theatre',
				'show_in_admin_bar' => true,
	  			'supports' => array('title', 'editor', 'excerpt', 'thumbnail'),
	  			'rewrite' => array(
	  				'slug' => 'production'
	  			)
	  			
			)
		);
		register_post_type( 'wp_theatre_event',
			array(
				'labels' => array(
					'name' => __( 'Events','wp_theatre'),
					'singular_name' => __( 'Event','wp_theatre'),
					'new_item' => __('New event','wp_theatre'),
					'add_new_item' => __('Add new event','wp_theatre'),
					'edit_item' => __('Edit event','wp_theatre')

				),
			'public' => true,
			'has_archive' => true,
			'show_in_menu' => false,
			'supports' => array(''),
			'show_in_nav_menus'=> false
			)
		);
		register_post_type( 'wp_theatre_season',
			array(
				'labels' => array(
					'name' => __( 'Seasons','wp_theatre'),
					'singular_name' => __( 'Season','wp_theatre')
				),
			'public' => true,
			'has_archive' => true,
			'supports' => array('title'),
			'show_in_menu'  => 'theatre',
			)
		);
	}	

	function plugins_loaded(){
		load_plugin_textdomain('wp_theatre', false, dirname( plugin_basename( __FILE__ ) ) . '/../lang/' );
	}
	
	function wp() {
		$this->production = new WPT_Production();			
	}
	
	function the_content($content) {
		if (is_singular(WPT_Production::post_type_name)) {
			if (isset( $this->options['show_events'] ) && (esc_attr( $this->options['show_events'])=='yes')) {
				$content .= $this->production->render_events();
			}
		}
		return $content;
	}
	
	function shortcode_events() {
		global $wp_theatre;
		return $wp_theatre->render_events();
	}

	function activate() {
		$this->init();
		flush_rewrite_rules();
	}

}

$WPT_Setup = new WPT_Setup();


?>