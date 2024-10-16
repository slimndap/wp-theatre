<?php
	class WPT_Setup {
		
		public $options;
		
		function __construct() {
	
			$this->options = get_option( 'wp_theatre' );
	
			// Hooks
			add_action( 'init', array($this,'init'));

			add_action( 'init', array($this,'register_post_types'));
			add_action( 'init', array($this,'register_event_meta'));
			
			add_filter( 'gettext', array($this,'gettext'), 20, 3 );
			
			add_action( 'widgets_init', array($this,'widgets_init'));
			
			add_action( 'plugins_loaded', array($this,'plugins_loaded'));
			
			add_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
			add_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
			add_action( 'set_object_terms', array($this,'set_object_terms'),20, 6);
			
			add_action('before_delete_post',array( $this,'before_delete_post'));
			add_action('wp_trash_post',array( $this,'wp_trash_post'));
			add_action('untrash_post',array( $this,'untrash_post'));
			
			add_filter( 'cron_schedules', array($this,'cron_schedules'));

			add_filter( 'query_vars', array($this,'add_tickets_url_iframe_query_vars'));	 
			add_action('init', array($this, 'add_tickets_url_iframe_rewrites'));
		}
	
		/**
		 * Adds event tickets URL iframe rewrites.
		 * 
		 * Rewrite pretty iframed ticket screens URLs from:
		 *
		 * http://example.com/tickets/my-event/123
		 *
		 * to:
		 *
		 * http://example.com/?pagename=tickets&wpt_event_tickets=123
		 *
		 * @see		WPT_Setup::add_tickets_url_iframe_query_vars()
		 * @since	0.12
		 * @since	0.15.33		Added support for tickets pages with a parent page.
		 *						Fixes #262.
		 * @since	0.16.2		Added support for multisite with subfolders.
		 * @return	void
		 */
		public function add_tickets_url_iframe_rewrites() {
			global $wp_theatre;
			
			if (empty($wp_theatre->wpt_tickets_options['iframepage'])) {
				return;
			}
			
			$iframe_page = get_post( $wp_theatre->wpt_tickets_options[ 'iframepage' ] );
			
			if ( is_null( $iframe_page ) ) {
				return;
			}

			if ( $path = get_page_uri( $wp_theatre->wpt_tickets_options[ 'iframepage' ] ) ) {

				add_rewrite_tag('%wpt_event_tickets%', '.*');
	
				add_rewrite_rule(
					$path.'/([a-z0-9-]+)/([0-9]+)$', 
					'index.php?pagename='.$path.'&wpt_event_tickets=$matches[2]',
					'top'
				);
				
			}
			
		}
		
		/**
		 * Adds 'wpt_event_tickets' to the query vars.
		 *
		 * Makes it possible to access iframed ticket screens like:
		 * http://example.com/?pagename=tickets&wpt_event_tickets=123
		 *
		 * @see WPT_Setup::add_tickets_url_iframe_rewrites()
		 * @access public
		 * @param mixed $query_vars
		 * @return void
		 */
		public function add_tickets_url_iframe_query_vars($query_vars) {
			$query_vars[] = 'wpt_event_tickets';
			return $query_vars;
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
				'<a href="https://github.com/slimndap/wp-theatre/wiki">' . __( 'Docs', 'theatre' ) . '</a>',
			);
	
			return array_merge( $plugin_links, $links );
		}
	
		/**
		 * Why is this here?.
		 */
		function init() {
			do_action('wpt_rewrite_rules');
		}
	
		/**
		 * Registers all event meta fields and their sanitization callbacks.
		 * 
		 * By defining this globally it is no longer necessary to manually sanitize data when
		 * saving it to the database (eg. in the admin or during the import).
		 *
		 * @since 	0.11
		 * @since	0.15.8	Changed Tickets URL sanitiziation to esc_url_raw().
		 *					sanitize_event_date() was breaking valid urls.
		 */
		public function register_event_meta() {
			register_meta(
				'post',
				'event_date',
				array($this, 'sanitize_event_date')
			);
			register_meta(
				'post',
				'enddate',
				array($this, 'sanitize_enddate')
			);
			register_meta(
				'post',
				'venue',
				'sanitize_text_field'
			);
			register_meta(
				'post',
				'city',
				'sanitize_text_field'
			);
			register_meta(
				'post',
				'remark',
				'sanitize_text_field'
			);
			register_meta(
				'post',
				'tickets_url',
				'esc_url_raw'
			);
			register_meta(
				'post',
				'tickets_button',
				'sanitize_text_field'
			);
			register_meta(
				'post',
				'tickets_status',
				'sanitize_text_field'
			);
			register_meta(
				'post',
				'_wpt_event_tickets_price',
				array($this, 'sanitize_event_tickets_price')
			);
		}
	
		/**
		 * Registers the custom post types for productions, events and seasons.
		 * 
		 * @since 	?.?
		 * @since 	0.12	Slug is dynamic.
		 *					Archive is disabled.
		 * @since	0.15	Renamed post type title from 'Production' to 'Event'.
		 * @since	0.15.6	Fixed the text-domain used in the 'Production' post type.
		 * @since	0.15.9	Added a filter to the post type args.
		 * @since	0.15.10	Productions now have an archive ('has_archive' == true).
		 *
		 * @see		WPT_Production_Permalink::get_permalink()	The production permalink.
		 *				
		 * @return void
		 */
		public function register_post_types() {
			
			global $wp_theatre;

			$post_type_args = array(
				'labels' => array(
					'name' => __( 'Events','theatre'),
					'singular_name' => __( 'Event','theatre'),
					'add_new' =>  _x('Add New', 'production','theatre'),
					'new_item' => __('New event','theatre'),
					'add_new_item' => __('Add new event','theatre'),
					'edit_item' => __('Edit event','theatre')
				),
				'public' => true,
				'has_archive' => true,
				'show_in_menu'  => false,
				'show_in_admin_bar' => true,
				'supports' => array('title', 'editor', 'excerpt', 'thumbnail','comments'),
				'taxonomies' => array('category','post_tag'),
				'rewrite' => array( 
					'slug' => $wp_theatre->production_permalink->get_base(), 
					'with_front' => false, 
					'feeds' => true 
				),
			);

			/**
			 * Filter the post type args for productions.
			 * 
			 * @since	0.15.9
			 * @param	$post_type_args	The post type args.
			 */
			$post_type_args = apply_filters('wpt/setup/post_type/args/?post_type='.WPT_Production::post_type_name, $post_type_args);

			register_post_type( WPT_Production::post_type_name, $post_type_args );
			
			$post_type_args = array(
				'labels' => array(
					'name' => __( 'Events','theatre'),
					'singular_name' => __( 'Event','theatre'),
					'new_item' => __('New event','theatre'),
					'add_new_item' => __('Add new event','theatre'),
					'edit_item' => __('Edit event','theatre')

				),
				'public' => true,
				'has_archive' => true,
				'show_in_menu'  => false,
				'supports' => array(''),
	  			'taxonomies' => array('category','post_tag'),
				'show_in_nav_menus'=> false,
			);

			/**
			 * Filter the post type args for events.
			 * 
			 * @since	0.15.9
			 * @param	$post_type_args	The post type args.
			 */
			$post_type_args = apply_filters('wpt/setup/post_type/args/?post_type='.WPT_Event::post_type_name, $post_type_args);

			register_post_type( WPT_Event::post_type_name, $post_type_args );
			
			$post_type_args = array(
					'labels' => array(
						'name' => __( 'Seasons','theatre'),
						'singular_name' => __( 'Season','theatre')
					),
					'public' => true,
					'has_archive' => true,
					'supports' => array('title','editor'),
					'show_in_menu'  => 'theater-events',
					'exclude_from_search' => true,
			);

			/**
			 * Filter the post type args for seasons.
			 * 
			 * @since	0.15.9
			 * @param	$post_type_args	The post type args.
			 */
			$post_type_args = apply_filters('wpt/setup/post_type/args/?post_type=wp_theatre_season', $post_type_args);

			register_post_type( 'wp_theatre_season', $post_type_args );

		}	
		
		/**
		 * Sanitizes the event_date meta value.
		 *
		 * Makes sure the event_date is always stored as 'Y-m-d H:i'.
		 * 
		 * @since 0.11
		 * @param 	string 	$value	The event_date value.
		 * @return 	string			The sanitized event_date.
		 */
		public function sanitize_event_date( $value ) {
			return date( 'Y-m-d H:i', strtotime($value) );
		}
		
		/**
		 * Sanitizes the enddate meta value.
		 *
		 * Makes sure the enddate is always stored as 'Y-m-d H:i'.
		 * 
		 * @since 0.11
		 * @param 	string 	$value	The enddate value.
		 * @return 	string			The sanitized enddate.
		 */
		public function sanitize_enddate( $value ) {
			return date( 'Y-m-d H:i', strtotime($value) );
		}
		
		/**
		 * Sanitizes the event_tickets_price value.
		 * 
		 * @since 0.11
		 * @param 	string	$value	The event_tickets_price value.
		 * @return 	string			The sanitized event_tickets_price.
		 */
		public function sanitize_event_tickets_price( $value) {
			
			$price_parts = explode( '|', $value );
			
			// Sanitize the amount.
			$price_parts[0] = (float) $price_parts[0];
			
			// Sanitize the name.
			if (!empty($prices_parts[1])) {
				$price_parts[1] = trim($price_parts[1]);
			}
			
			return implode('|',$price_parts);
			
		}
	
		function cron_schedules( $schedules ) {
			// Adds once weekly to the existing schedules.
			$schedules['wpt_schedule'] = array(
				'interval' => 5*60,
				'display' => __( 'Every 5 minutes', 'theatre' )
			);
			return $schedules;
		}
			
		/**
		 * Delete all connected events of a production.
		 *
		 * Whenever a production is deleted (not just trashed), make sure that all connected events are deleted as well.
		 * Events that al already in the trash are left alone.
		 *
		 * @since 	0.7
		 * @since	0.12	Added support for events with an 'auto-draft' post_status.
		 */
		function before_delete_post($post_id) {
			$post = get_post($post_id);
			if (!empty($post) && $post->post_type==WPT_Production::post_type_name) {
				$args = array (
					'post_type' => WPT_Event::post_type_name,
					'post_status' => array('any', 'auto-draft'),
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
		
		/**
		 * Applies translation from the Theater Settings page.
		 * 
		 * @since	0.?
		 * @since	0.15.2	Removed the translation for 'Events'.
		 *					This is now handled directly by WPT_Listing_Page::wpt_production_page_events_content().
		 *
		 * @param 	string	$translated_text
		 * @param	string 	$text
		 * @param	string	$domain
		 * @return	string
		 */
		function gettext($translated_text, $text, $domain) {
			global $wp_theatre;
			if ($domain=='theatre') {
				switch ( $text ) {
					case 'Tickets' :
						if (!empty($wp_theatre->wpt_language_options['language_tickets'])) {
							$translated_text = $wp_theatre->wpt_language_options['language_tickets'];
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
			load_plugin_textdomain('theatre', false, dirname( plugin_basename( __FILE__ ) ) . '/../lang/' );
		}
		
		/*
		 * Give child events the same category as the parent production.
		 * If the category of a production is set, walk through all connected events and
		 * overwrite the categories of the events with the categories of the production.
		 */
		
		function set_object_terms($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
			if ('category'==$taxonomy && WPT_Production::post_type_name==get_post_type($object_id)) {
				$production = new WPT_Production($object_id);
				$events = $production->events();
				$categories = wp_get_post_categories($object_id);
				foreach ($events as $event) {
					wp_set_post_categories($event->ID, $categories);
				}
			}
		}

		function widgets_init() {
		     register_widget( 'WPT_Events_Widget' );
		     register_widget( 'WPT_Production_Widget' );
		     register_widget( 'WPT_Production_Events_Widget' );
		     register_widget( 'WPT_Productions_Widget' );
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
				$events = $wp_theatre->events->get($args);
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

		/**
		 * Update the season of events to the season of the parent production.
		 *
		 * Triggered by the updated_post_meta action.
		 *
		 * Used when:
		 * - a production is saved through the admin screen or
		 * - an event is attached to a production.
		 *
		 * @since	0.7
		 * @since	0.18.5	Fixed a PHP error when manually inserting an Event through wp_insert_post().
		 *					@see: https://wpsupport.zendesk.com/agent/tickets/3481
		 *
		 */

		function updated_post_meta($meta_id, $object_id, $meta_key, $meta_value) {
			global $wp_theatre;
			
			// A production is saved through the admin screen.
			if ($meta_key==WPT_Season::post_type_name) {
				$post = get_post($object_id);
				if ($post->post_type==WPT_Production::post_type_name) {

					// avoid loops
					remove_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
					remove_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
				
					$args = array(
						'production'=>$post->ID
					);
					$events = $wp_theatre->events->get($args);
					foreach($events as $event) {
						update_post_meta($event->ID, WPT_Season::post_type_name, $meta_value);
					}

					add_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
					add_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
				}
			}

			// An event is attached to a production.
			if ($meta_key==WPT_Production::post_type_name) {

				$event = new WPT_Event( $object_id );
				$production = new WPT_Production( $meta_value );
				
				// avoid loops
				remove_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
				remove_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
				
				// inherit season from production
				if ( $season = $production->season() ) {
					update_post_meta( $event->ID, WPT_Season::post_type_name, $season->ID );				
				}
				
				// inherit categories from production
				$categories = wp_get_post_categories( $meta_value );
				wp_set_post_categories( $event->ID, $categories );

				add_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
				add_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);

			}

		}
	}
	


?>
