<?php
	/**
	 * Handle all ordering of events and productions.
	 *
	 * WPT_Order adds a wpt_order custom field to all posts (of any post_type) and uses this to chronologically 
	 * order WP_Query listings that hold productions or events.
	 *
	 * For productions the wpt_order value is based on the event_date of the first upcoming event.
	 * For events the wpt_order value is based on the event_date.
	 * The wpt_order value is based on the post_date for all other post_types.
	 *
	 * @since 0.6.2
	 *
	 */
	 
 	class WPT_Order {
	
		function __construct() {
			add_action('wpt_cron', array($this,'update_post_order'));
			add_action('save_post', array( $this, 'save_post' ) );
			add_filter('pre_get_posts', array($this,'pre_get_posts') );
		}
	
		/**
		 * Order a WP_Query by wpt_order.
		 *
		 * Alter any WP_Query that lists productions or events to order by wpt_order.
		 * Triggered by the pre_get_posts action.
		 *
		 * @since 0.6.2
 		 *
		 */
 
 		function pre_get_posts($query) {
			$post_types = (array) $query->get('post_type');
			
			$wpt_post_types = array(WPT_Production::post_type_name,WPT_Event::post_type_name,'any');
			foreach ($wpt_post_types as $wpt_post_type) {
				if (in_array($wpt_post_type, $post_types)) {
					$query->set('meta_key','wpt_order');
					$query->set('orderby','meta_value');
					continue;					
				}
			}
		}

		/**
		 * Update the wpt_order of a post (of any post_type) whenever it is saved.
		 *
		 * Triggered by the save_post action.
		 *
		 * @since 0.6.2
		 *
		 * @see set_post_order
 		 *
		 */

		function save_post( $post_id ) {
			$this->set_post_order($post_id);
		}
		
		/**
		 * Update the wpt_order of a post (of any post_type).
		 *
		 * For productions the wpt_order value is based on the event_date of the first upcoming event.
		 * For events the wpt_order value is based on the event_date.
		 * The wpt_order value is based on the post_date for all other post_types.
		 *
		 * @since 0.6.2
		 *
		 */
		function set_post_order($post) {
			if (is_numeric($post)) {
				$post = get_post($post);
			}
		
			delete_post_meta($post->ID, 'wpt_order');
			
			switch ($post->post_type) {
				case WPT_Production::post_type_name:
					$production = new WPT_Production($post->ID);
					$events = $production->events();
					if (!empty($events[0])) {
						$wpt_order = strtotime(get_post_meta($events[0]->ID, 'event_date',TRUE));
						break;
					}
				case WPT_Event::post_type_name:
					$wpt_order = strtotime(get_post_meta($post->ID, 'event_date',TRUE));
					break;
				default:
					$wpt_order = strtotime($post->post_date);
			}
			
			add_post_meta($post->ID, 'wpt_order', $wpt_order);			
		}
		
		/**
		 * Update the wpt_order of all posts (of any post_type).
		 *
		 * Trigger by wpt_cron or by directly calling the function (eg. after an import).
		 *
		 * @since 0.6.2
		 *
		 * @see set_post_order
		 * @see pre_get_posts
 		 *
		 */
		function update_post_order() {
			/**
			 * Unhook pre_get_posts filter.
			 * Remove order by wpt_order to include any productions and events that don't have a wpt_order set yet.
			 */
			remove_filter( 'pre_get_posts', array($this, 'pre_get_posts') );
			
			$args = array(
				'post_type'=>'any',
				'post_status'=>'any',
				'posts_per_page' => -1
			);
			$posts = get_posts($args);
			foreach ($posts as $post) {
				$this->set_post_order($post);
			}

			/**
			 * Re-activate pre_get_posts filter.
			 */
			add_filter( 'pre_get_posts', array($this, 'pre_get_posts') );

		}		
	}
?>