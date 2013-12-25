<?php

class WPT_Setup {
	function __construct() {
		add_action( 'init', array($this,'init'));
		add_filter('template_include', array($this, 'template_include')); 
		add_action('the_content', array($this, 'the_content'));
		add_action('wp', array($this, 'wp'));
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
	
	function template_include($template){	
		if ( is_singular(WPT_Production::post_type_name) ) {
			$template_name = 'single-'.WPT_Production::post_type()->name.'.php';
			$theme_template = locate_template(array($template_name), true);
			
			if(empty($theme_template)) {
				return plugin_dir_path(__FILE__).'../templates/'.$template_name;
			} else {
				return '';
			}
		}
		return $template;
	}
	
	function the_content($content) {
		if (is_singular(WPT_Production::post_type_name)) {
			$content .= $this->production->render_events();
		}
		return $content;
	}

}

new WPT_Setup();

?>