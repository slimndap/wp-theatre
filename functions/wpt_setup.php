<?php

class WPT_Setup {
	function __construct() {
		add_action( 'init', array($this,'init'));
		add_action('the_content', array($this, 'the_content'));
		add_action('wp', array($this, 'wp'));
		
		add_shortcode('wp_theatre_events', array($this,'shortcode_events'));
	}

	function init() {
		register_post_type( WPT_Production::post_type_name,
			array(
				'labels' => array(
					'name' => __( 'Productions' ),
					'singular_name' => __( 'Production' )
				),
			'public' => true,
			'has_archive' => true,
			'supports' => array('title', 'editor', 'excerpt', 'thumbnail')
			)
		);
		register_post_type( 'wp_theatre_event',
			array(
				'labels' => array(
					'name' => __( 'Events' ),
					'singular_name' => __( 'Event' ),
					'new_item' => __('New event'),
					'add_new_item' => __('Add new event'),
					'edit_item' => __('Edit event')

				),
			'public' => true,
			'has_archive' => true,
			'show_in_menu' => false,
			'supports' => array('')
			)
		);
		register_post_type( 'wp_theatre_season',
			array(
				'labels' => array(
					'name' => __( 'Seasons' ),
					'singular_name' => __( 'Season' )
				),
			'public' => true,
			'has_archive' => true,
			'supports' => array('title')
			)
		);
	}	

	function wp() {
		$this->production = new WPT_Production();			
	}
	
	function the_content($content) {
		if (is_singular(WPT_Production::post_type_name)) {
			$content .= $this->production->render_events();
		}
		return $content;
	}
	
	function shortcode_events() {
		global $wp_theatre;
		return $wp_theatre->render_events();
	}

}

new WPT_Setup();

?>