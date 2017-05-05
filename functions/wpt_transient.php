<?php
	/**
	 * Handle all transients.
	 *
	 * WPT_Transient handles all setting and getting of transients.
	 * All Theater transients are reset everytime a (custom) post or post_meta is saved.
	 *
	 * @since 0.7
	 *
	 */	
	class WPT_Transient {

		function __construct() {
				$this->enable_reset_hooks();
			
			// Hooks to disable transient reset hooks during imports.
			add_action('wpt/importer/execute/before', array( $this, 'disable_reset_hooks') );
			add_action('wpt/importer/execute/after', array( $this, 'enable_reset_hooks') );
		}
		
		function enable_reset_hooks() {
			add_action('save_post', array($this,'reset'), 10 );
			add_action('added_post_meta', array($this,'reset'), 20 );
			add_action('updated_post_meta', array($this,'reset'), 20 );			
		}
		
		function disable_reset_hooks() {
			remove_action('save_post', array($this,'reset'), 10 );
			remove_action('added_post_meta', array($this,'reset'), 20);
			remove_action('updated_post_meta', array($this,'reset'), 20);			
		}
		
		function get($name, $args) {
			if ( is_user_logged_in() ) {
				return false;
			}			
			$key = $this->get_key( $name, $args );
			return get_transient( $key );
		}
		
		function get_prefix() {
			
			$prefix = 'wpt';
			
			return $prefix;
			
		}
		
		function get_key( $name, $args ) {
			
			$key = $this->get_prefix().$name.md5( serialize( $args ) );
			
			return $key;
			
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
		
		function set($name, $args, $value) {
			$key = $this->get_key( $name, $args );
			set_transient( $key, $value, 10 * MINUTE_IN_SECONDS );
		}

	}
?>