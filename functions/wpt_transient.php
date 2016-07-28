<?php
	/**
	 * Handle all transients.
	 *
	 * WPT_Transient handles all setting and getting of transients.
	 * All Theatre transients are reset everytime a (custom) post or post_meta is saved.
	 *
	 * @since 0.7
	 * @internal
	 *
	 */	
	class WPT_Transient {

		function __construct() {
			add_action('save_post', array($this,'save_post'));
			add_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
			add_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
		}
		
		function get($name, $args) {
			if ( is_user_logged_in() ) {
				return false;
			}
			$key = 'wpt'.$name.md5(serialize($args));
			return get_transient($key);
		}
		
		/**
		 * Empty all Theatre transients.
		 *
		 * Empty all Theatre transients by removing them from the DB.
		 * Major flaw: this is useless if transients are not stored in the DB (eg. with memcached).
		 * See: http://wordpress.org/ideas/topic/transient-api-should-allow-delete-by-prefix
		 *
		 * @since 0.7
		 *
		 */	
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
			$key = 'wpt'.$name.md5(serialize($args));
			set_transient($key, $value, 10 * MINUTE_IN_SECONDS );
		}
		
		function updated_post_meta($meta_id, $object_id, $meta_key, $meta_value) {
			$this->reset();
		}
	}
?>