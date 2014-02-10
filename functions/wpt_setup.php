<?php
class WPT_Setup {
	function __construct() {
		$this->options = get_option( 'wp_theatre' );

		add_action( 'init', array($this,'init'));
		
		register_activation_hook( __FILE__, array($this, 'activate' ));		

		add_action( 'widgets_init', function(){
		     register_widget( 'WPT_Events_Widget' );
		     register_widget( 'WPT_Productions_Widget' );
		     register_widget( 'WPT_Cart_Widget' );
		});
		
		add_action( 'plugins_loaded', array($this,'plugins_loaded'));
	}

	function init() {
		register_post_type( WPT_Production::post_type_name,
			array(
				'labels' => array(
					'name' => __( 'Productions','wp_theatre'),
					'singular_name' => __( 'Production','wp_theatre'),
					'add_new' =>  _x('Add New', 'production','wp_theatre'),
					'new_item' => __('New production','wp_theatre'),
					'add_new_item' => __('Add new').' '.__('production','wp_theatre'),
					'edit_item' => __('Edit production','wp_theatre')
				),
				'public' => true,
				'has_archive' => true,
				'show_in_menu'  => 'theatre',
				'show_in_admin_bar' => true,
	  			'supports' => array('title', 'editor', 'excerpt', 'thumbnail','comments'),
	  			'taxonomies' => array('category','post_tag'),
	  			'rewrite' => array(
	  				'slug' => sanitize_title(__('production','wp_theatre'))
	  			)
	  			
			)
		);
		register_post_type( WPT_Event::post_type_name,
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
				'show_in_menu'  => false,
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
		wp_enqueue_style( 'wp_theatre_css', plugins_url( '../css/style.css', __FILE__ ) );
	}	

	function plugins_loaded(){
		load_plugin_textdomain('wp_theatre', false, dirname( plugin_basename( __FILE__ ) ) . '/../lang/' );
	}
	
	function activate() {
		$this->init();
		flush_rewrite_rules();
	}


}

$WPT_Setup = new WPT_Setup();


?>