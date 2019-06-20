<?php
/**
 * Manages the use of transients in Theater.
 *
 * All Theater transients are reset every time a (custom) post or post_meta is saved.
 *
 * @since 	0.7
 * @since	0.15.24	Renamed from WPT_Transients to Theater_Transients.
 *
 */
class Theater_Transients {

	/**
	 * Inits all transient hooks.
	 *
	 * @since	0.7
	 * @since	0.15.24	Renamed from __construct() to init().
	 *
	 * @uses	Theater_Transients::enable_reset_hooks() to enable all hooks that should reset all Theate transients.
	 * @return 	void
	 */
	static function init() {

		if ( ! defined( 'THEATER_TRANSIENTS_OPTION' ) ) {
			define( 'THEATER_TRANSIENTS_OPTION', 'theater_transient_keys' );
		}

		self::enable_reset_hooks();

		// Disable transient resets during imports.
		add_action( 'wpt/importer/execute/before', array( __CLASS__, 'disable_reset_hooks' ) );
		add_action( 'wpt/importer/execute/after', array( __CLASS__, 'enable_reset_hooks' ) );
		add_action( 'wpt/importer/execute/after', array( __CLASS__, 'reset' ) );
	}

	/**
	 * Enables all hooks that should reset all Theater transients.
	 *
	 * All Theater transients should be reset every time a (custom) post or post_meta is saved.
	 *
	 * @since	0.15.24
	 *
	 * @todo	Make this a bit smarter.
	 *			Eg. only reset if an event or event date is saved.
	 *
	 * @return 	void
	 */
	static function enable_reset_hooks() {
		add_action( 'save_post', array( __CLASS__, 'reset' ), 10 );
		add_action( 'added_post_meta', array( __CLASS__, 'reset' ), 20 );
		add_action( 'updated_post_meta', array( __CLASS__, 'reset' ), 20 );
	}

	/**
	 * Disables all hooks that should reset all Theater transients.
	 *
	 * @since	0.15.24
	 * @return 	void
	 */
	static function disable_reset_hooks() {
		remove_action( 'save_post', array( __CLASS__, 'reset' ), 10 );
		remove_action( 'added_post_meta', array( __CLASS__, 'reset' ), 20 );
		remove_action( 'updated_post_meta', array( __CLASS__, 'reset' ), 20 );
	}

	/**
	 * Gets a list of all Theater transients that are in use.
	 *
	 * @since	0.15.24
	 * @since	0.16.1	Reset list if transients is list is corrupted.
	 *
	 * @return	array	A list of all Theater transients that are in use.
	 */
	static function get_transient_keys() {
		$transient_keys = get_option( THEATER_TRANSIENTS_OPTION );

		if ( ! $transient_keys ) {
			$transient_keys = array();
		}
		
		// The list of transients is corrupted. Throw them away.
		if ( !is_array( $transient_keys ) ) {
			delete_option( THEATER_TRANSIENTS_OPTION );
			$transient_keys = array();			
		}

		return $transient_keys;
	}

	/**
	 * Resets all Theater transients in use.
	 *
	 * @since 	0.7
	 * @since	0.15.24	Now uses the Theater_Transient object.
	 *
	 * @uses	Theater_Transients::get_transient_keys() to get a list of all Theater transients that are in use.
	 * @uses	Theater_Transient::load_by_key() to load transients by their keys.
	 * @uses	Theater_Transient::reset() to reset transients.
	 */
	static function reset() {
		$transients_keys = self::get_transient_keys();
		foreach ( $transients_keys as $transient_key ) {
			$transient = new Theater_Transient();
			$transient->load_by_key( $transient_key );
			$transient->reset();
		}
	}

}

