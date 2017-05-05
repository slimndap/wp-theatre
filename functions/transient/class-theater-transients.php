<?php
/**
 * Handle all transients.
 *
 * WPT_Transient handles all setting and getting of transients.
 * All Theater transients are reset everytime a (custom) post or post_meta is saved.
 *
 * @since 	0.7
 *
 */	
class Theater_Transients {

	static function init() {
		
		if (! defined('THEATER_TRANSIENTS_OPTION') ) {
			define( 'THEATER_TRANSIENTS_OPTION', 'theater_transient_keys');		
		}
		
		self::enable_reset_hooks();
		
		// Hooks to disable transient reset hooks during imports.
		add_action('wpt/importer/execute/before', array( __CLASS__, 'disable_reset_hooks') );
		add_action('wpt/importer/execute/after', array( __CLASS__, 'enable_reset_hooks') );
	}
	
	static function enable_reset_hooks() {
		add_action('save_post', array(__CLASS__,'reset'), 10 );
		add_action('added_post_meta', array(__CLASS__,'reset'), 20 );
		add_action('updated_post_meta', array(__CLASS__,'reset'), 20 );			
	}
	
	static function disable_reset_hooks() {
		remove_action('save_post', array(__CLASS__,'reset'), 10 );
		remove_action('added_post_meta', array(__CLASS__,'reset'), 20);
		remove_action('updated_post_meta', array(__CLASS__,'reset'), 20);			
	}
	
	
	static function get_transient_keys() {
		$transient_keys = get_option( THEATER_TRANSIENTS_OPTION );
		
		if (! $transient_keys ) {
			$transient_keys = array();
		}
		
		return $transient_keys;
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
	static function reset() {
		$transients_keys = self::get_transient_keys();
		foreach ($transients_keys as $transient_key ) {
			$transient = new Theater_Transient();
			$transient->load_by_key( $transient_key);
			$transient->delete();
		}
		return;
	}
	
}
?>