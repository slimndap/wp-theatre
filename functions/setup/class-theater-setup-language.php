<?php

/**
 * Setup language translations.
 *
 * @package	Theater/Setup
 * @since	0.16
 */
class Theater_Setup_Language {
	
	/**
	 * Adds the action hooks that handle the translations.
	 *
	 * @since	0.16
	 * @static
	 * @return void
	 */
	static function init() {

		add_action( 'plugins_loaded', 'Theater_Setup_Language::load_textdomain' );
		add_filter( 'gettext', 'Theater_Setup_Language::apply_translations_from_settings', 20, 3 );
		
	}
	
	/**
	 * Applies translation from the Theater Settings page.
	 *
	 * @since	0.?
	 * @since	0.15.2	Removed the translation for 'Events'.
	 *					This is now handled directly by WPT_Listing_Page::wpt_production_page_events_content().
	 *
	 * @param 	string 	$translated_text
	 * @param	string 	$text
	 * @param	string 	$domain
	 * @return	string
	 */
	static function apply_translations_from_settings( $translated_text, $text, $domain ) {
		global $wp_theatre;
		if ( $domain == 'theatre' ) {
			switch ( $text ) {
				case 'Tickets' :
					if ( ! empty( $wp_theatre->wpt_language_options['language_tickets'] ) ) {
						$translated_text = $wp_theatre->wpt_language_options['language_tickets'];
					}
					break;
				case 'categories' :
					if ( ! empty( $wp_theatre->wpt_language_options['language_categories'] ) ) {
						$translated_text = strtolower( $wp_theatre->wpt_language_options['language_categories'] );
					}
					break;
			}
		}
		return $translated_text;
	}

	/**
	 * Loads the plugin's translated strings.
	 * 
	 * @static
	 * @since	0.?
	 * @return 	void
	 */
	static function load_textdomain() {
		load_plugin_textdomain( 'theatre', false, dirname( plugin_basename( __FILE__ ) ) . '/../lang/' );
	}

}