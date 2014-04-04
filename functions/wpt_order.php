<?php
	class WPT_Order {
	
		function __construct() {
			add_action('wpt_cron', array($this,'update_post_order'));
			add_action('save_post', array( $this, 'save_post' ) );
			add_filter('pre_get_posts', array($this,'pre_get_posts') );
		}
	
		function pre_get_posts($query) {
			$query->set('meta_key','wpt_order');
			$query->set('orderby','meta_value');
		}

		function save_post( $post_id ) {
			$post = get_post($post_id);
			$this->set_post_order($post);
		}
		
		function set_post_order($post) {
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
		
		function update_post_order() {
			// unhook
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

			// rehook
			add_filter( 'pre_get_posts', array($this, 'pre_get_posts') );

		}		
	}
?>