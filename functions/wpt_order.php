<?php
	/**
	 * Handle all ordering of events and productions.
	 *
	 * WPT_Order adds a `_wpt_order` custom field to all posts (of any post_type) and uses this to chronologically 
	 * order WP_Query listings that hold productions or events.
	 *
	 * For productions the _wpt_order value is based on the event_date of the first upcoming event.
	 * For events the _wpt_order value is based on the event_date.
	 * The _wpt_order value is based on the post_date for all other post_types.
	 *
	 * @since 	0.6.2
	 * @package	Theater
	 * @internal
	 *
	 */
	 
 	class WPT_Order {

		function __construct() {
			$this->meta_key = '_wpt_order';
		
			add_action('wpt_cron', array($this,'update_post_order'));
			add_filter('pre_get_posts', array($this,'pre_get_posts') );

			add_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
			add_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
			add_action('save_post', array( $this, 'save_post' ) );
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
			
			// Don't interfere with custom sorting of post columns.
			if (is_admin() && !empty($_GET['orderby'])) {
				return;
			}
			
			$wpt_post_types = array(WPT_Production::post_type_name,WPT_Event::post_type_name,'any');
			foreach ($wpt_post_types as $wpt_post_type) {
				if (in_array($wpt_post_type, $post_types)) {
					$query->set('meta_key',$this->meta_key);
					$query->set('orderby','meta_value');
					continue;					
				}
			}
		}

		/**
		 * Update the _wpt_order of a post (of any post_type) whenever it is saved.
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
		 * Update the _wpt_order of a post (of any post_type).
		 *
		 * For productions the _wpt_order value is based on the event_date of the first upcoming event.
		 * For events the _wpt_order value is based on the event_date.
		 * The _wpt_order value is based on the post_date for all other post_types.
		 *
		 * @since 0.6.2
		 * @since 0.10.15	Always set _wpt_order in UTC.
		 *					Fixes #117.
		 *
		 */
		function set_post_order($post) {
			global $wp_theatre;
			
			if (is_numeric($post)) {
				$post = get_post($post);
			}
		
			switch ($post->post_type) {
				case WPT_Production::post_type_name:
					$production = new WPT_Production($post->ID);
					$events = $production->upcoming();
					
					if (!empty($events[0])) {
						$wpt_order = strtotime(
							get_post_meta(
								$events[0]->ID, 
								'event_date',
								TRUE
							),
							current_time( 'timestamp' )
						) - 3600 * get_option('gmt_offset');
						break;
					}
				case WPT_Event::post_type_name:
					$wpt_order = strtotime(
						get_post_meta(
							$post->ID, 
							'event_date', 
							TRUE
						),
						current_time( 'timestamp' )
					) - 3600 * get_option('gmt_offset');
					break;
				default:
					$wpt_order = strtotime($post->post_date) + 3600 * get_option('gmt_offset');
			}			
			update_post_meta($post->ID, $this->meta_key, $wpt_order);			
		}
		
		/**
		 * Update the wpt_order of all posts (of any post_type).
		 *
		 * Triggered by wpt_cron or by directly calling the function (eg. after an import).
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

		/**
		 * Update the _wpt_order of a both the event and the parent production whenever the event_date of an event is updated.
		 *
		 * Triggered by the updated_post_meta action.
		 *
		 * @since 0.7
		 *
		 * @see set_post_order
 		 *
		 */

		function updated_post_meta($meta_id, $object_id, $meta_key, $meta_value) {
			if ($meta_key=='event_date') {
				$this->set_post_order($object_id);					
				$production_id = get_post_meta($object_id, WPT_Production:: post_type_name, TRUE);
				if (!empty($production_id)) {
					$this->set_post_order($production_id);					
				}
			}
		}
	
	}
?>