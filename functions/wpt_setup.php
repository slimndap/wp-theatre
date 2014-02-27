<?php
class WPT_Setup {
	function __construct() {

		$this->options = get_option( 'wp_theatre' );
		// Installation
		register_activation_hook( __FILE__, array($this, 'activate' ));		

		// Hooks
		add_action( 'init', array($this,'init'));
		add_filter( 'gettext', array($this,'gettext'), 20, 3 );
		
		add_action( 'widgets_init', function(){
		     register_widget( 'WPT_Events_Widget' );
		     register_widget( 'WPT_Productions_Widget' );
		     register_widget( 'WPT_Cart_Widget' );
		});
		
		add_action( 'plugins_loaded', array($this,'plugins_loaded'));
	}

	/**
	 * action_links function.
	 *
	 * @access public
	 * @param mixed $links
	 * @return void
	 */
	public function plugin_action_links( $links ) {

		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wpt_admin' ) . '">' . __( 'Settings') . '</a>',
			'<a href="https://github.com/slimndap/wp-theatre/wiki">' . __( 'Docs', 'wp_theatre' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
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
	}	

	function plugins_loaded(){
		load_plugin_textdomain('wp_theatre', false, dirname( plugin_basename( __FILE__ ) ) . '/../lang/' );
	}
	
	function activate() {
		$this->init();
		flush_rewrite_rules();
	}

	function gettext($translated_text, $text, $domain) {
		global $wp_theatre;
		if ($domain=='wp_theatre') {
			switch ( $text ) {
				case 'Tickets' :
					if (!empty($wp_theatre->wpt_language_options['language_tickets'])) {
						$translated_text = $wp_theatre->wpt_language_options['language_tickets'];
					}
					break;
				case 'Events' :
					if (!empty($wp_theatre->wpt_language_options['language_events'])) {
						$translated_text = $wp_theatre->wpt_language_options['language_events'];
					}
					break;
				case 'categories' :				
					if (!empty($wp_theatre->wpt_language_options['language_categories'])) {
						$translated_text = strtolower($wp_theatre->wpt_language_options['language_categories']);
					}
					break;
			}
			
		}
		return $translated_text;
	}
}

add_action( 'wp_ajax_save_bulk_edit_'.WPT_Production::post_type_name, 'wp_ajax_save_bulk_edit_production' );
function wp_ajax_save_bulk_edit_production() {
	$wpt_admin = new WPT_Admin();

	// TODO perform nonce checking
	remove_action( 'save_post', array( $this, 'save_post' ) );

	$post_ids = ( ! empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
	if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
		foreach( $post_ids as $post_id ) {
			// Update status of connected Events
			$events = $wpt_admin->get_events($post_id);
			foreach($events as $event) {
				$post = array(
					'ID'=>$event->ID,
					'post_status'=>$_POST[ 'post_status' ]
				);
				wp_update_post($post);
			}
		}
	}

	add_action( 'save_post', array( $this, 'save_post' ) );

	die();					
}



?>