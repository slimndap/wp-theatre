<?php
	class WPT_Setup {
		function __construct() {
	
			$this->options = get_option( 'wp_theatre' );
	
			// Installation
	
			// Hooks
			add_action( 'init', array($this,'init'));
			add_filter( 'gettext', array($this,'gettext'), 20, 3 );
			
			add_action( 'widgets_init', function(){
			     register_widget( 'WPT_Events_Widget' );
			     register_widget( 'WPT_Productions_Widget' );
			     register_widget( 'WPT_Cart_Widget' );
			});
			
			add_action( 'plugins_loaded', array($this,'plugins_loaded'));
			
			add_action('save_post_'.WPT_Production::post_type_name,array( $this,'save_production'));
			add_action('before_delete_post',array( $this,'before_delete_post'));
			add_action('wp_trash_post',array( $this,'wp_trash_post'));
			add_action('untrash_post',array( $this,'untrash_post'));
			
			add_filter( 'cron_schedules', array($this,'cron_schedules'));
	 
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
		  			'taxonomies' => array('category','post_tag'),
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
	
		function activate() {
			$this->init();
			flush_rewrite_rules();
		}
	
		function cron_schedules( $schedules ) {
			// Adds once weekly to the existing schedules.
			$schedules['wpt_schedule'] = array(
				'interval' => 5*60,
				'display' => __( 'Every 5 minutes', 'wp_theatre' )
			);
			return $schedules;
		}
			
		/**
		 * Delete all connected events of a production.
		 *
		 * Whenever a production is deleted (not just trashed), make sure that all connected events are deleted as well.
		 * Events that al already in the trash are left alone.
		 *
		 * @since 0.7
 		 *
		 */
		function before_delete_post($post_id) {
			$post = get_post($post_id);
			if (!empty($post) && $post->post_type==WPT_Production::post_type_name) {
				$args = array (
					'post_type' => WPT_Event::post_type_name,
					'post_status' => array('any'),
					'meta_query' => array(
						array(
							'key' => WPT_Production::post_type_name,
							'value' => $post_id
						)
					)
				);
				$events = get_posts($args);
				foreach ($events as $event) {
					wp_delete_post($event->ID);
				}							
			}
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
		
		function plugins_loaded(){
			load_plugin_textdomain('wp_theatre', false, dirname( plugin_basename( __FILE__ ) ) . '/../lang/' );
		}
		
		function save_production($post_id) {
			$production = new WPT_Production($post_id);
			$categories = wp_get_post_categories($post_id);
			$events = $production->events();
			foreach ($events as $event) {
				wp_set_post_categories($event->ID, $categories);
			}
			
		}
		
		/**
		 * Trash all connected events of a production.
		 *
		 * Whenever a production is trashed (not deleted), make sure that all connected events are trashed as well.
		 *
		 * @since 0.7
 		 *
		 */
		function wp_trash_post($post_id) {
			global $wp_theatre;
			$post = get_post($post_id);
			if (!empty($post) && $post->post_type==WPT_Production::post_type_name) {
				$args = array(
					'status' => 'any',
					'production' => $post_id
				);
				$events = $wp_theatre->events($args);
				foreach ($events as $event) {
					wp_trash_post($event->ID);
				}							
			}			
		}
		
		/**
		 * Untrash all connected events of a production.
		 *
		 * Whenever a production is untrashed, make sure that all connected events are untrashed as well.
		 *
		 * @since 0.7
 		 *
		 */
		function untrash_post($post_id) {
			$post = get_post($post_id);
			if (!empty($post) && $post->post_type==WPT_Production::post_type_name) {
				$args = array (
					'post_type' => WPT_Event::post_type_name,
					'post_status' => 'trash',
					'meta_query' => array(
						array(
							'key' => WPT_Production::post_type_name,
							'value' => $post_id
						)
					)
				);
				$events = get_posts($args);
				foreach ($events as $event) {
					wp_untrash_post($event->ID);
				}							
			}
		}
	}
	
	add_action( 'wp_ajax_save_bulk_edit_'.WPT_Production::post_type_name, 'wp_ajax_save_bulk_edit_production' );
	function wp_ajax_save_bulk_edit_production() {
		$wpt_admin = new WPT_Admin();
	
		// TODO perform nonce checking
		remove_action( 'save_post', array( $this, 'save_post' ) );
	
		$post_ids = ( ! empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
		if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
			if ($_POST['post_status']!=-1) {
				// Status of production is updated			
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
		}
	
		add_action( 'save_post', array( $this, 'save_post' ) );
	
		die();					
	}



?>