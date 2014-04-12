<?php
	/**
	 * Handle all transients.
	 *
	 * WPT_Transient handles all setting and getting of transients.
	 * All Theatre transients are reset everytime a (custom) post or post_meta is saved.
	 *
	 * @since 0.7
	 *
	 */	
	 class WPT_Transient {

		function __construct() {
			add_action('save_post', array($this,'save_post'));
			add_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
			add_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
		}
		
		public function __invoke($name, $args, $value=false) {
			if ($value) {
				return $this->set($name, $args, $value);
			} else {
				return $this->get($name, $args);
			}
		}

		function get($name, $args) {
			$key = 'wpt_'.$name.'_'.md5(serialize($args));
			return get_transient($key);
		}
		
		function reset() {
			global $wpdb;
			$query = "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_wpt\_%'";
			$wpdb->query($query);
			return;
		}
		
		function save_post($post_id) {
			$this->reset();
		}

		function set($name, $args, $value) {
			$key = 'wpt_'.$name.'_'.md5(serialize($args));
			set_transient($key, $value, 10 * MINUTE_IN_SECONDS );
		}
		
		function updated_post_meta($meta_id, $object_id, $meta_key, $meta_value) {
			$this->reset();
		}
	}
?>