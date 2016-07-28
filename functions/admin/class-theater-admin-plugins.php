<?php
/**
 * Customizes the Theater for WordPress plugin entry on the plugins page.
 *
 * @package	Theater/Admin
 * @since	0.16
 * @internal
 */
class Theater_Admin_Plugins {

	/**
	 * Adds the hooks that customize the plugin entry.
	 *
	 * @since	0.16
	 * @return 	void
	 */
	static function init() {
		add_filter( 'plugin_row_meta', array( __CLASS__, 'add_plugin_links' ), 10, 2 );
	}

	/**
	 * Adds extra links below the plugin description on the plugins page.
	 *
	 * Adds two links below the Theater for WordPress plugin description on the plugins page:
	 *
	 * - Settings.
	 * - Developer documentation.
	 *
	 * @static
	 * @since	0.16
	 * @param	array	$links	The current links for the plugin.
	 * @param	string 	$file	The filename of the plugin.
	 * @return	array			The new links for the plugin.
	 */
	static function add_plugin_links( $links, $file ) {

		if ( THEATER_PLUGIN_BASENAME != $file ) {
			return $links; }

		$settings_link = admin_url( 'admin.php?page=wpt_admin' );
		$links[] = '<a href="' . esc_url( $settings_link ) . '">' . esc_html__( 'Settings', 'theatre' ) . '</a>';

		$docs_link = 'http://wp.theater/apidocs/index.html';
		$links[] = '<a href="' . esc_url( $docs_link ) . '">' . esc_html__( 'Docs', 'theatre' ) . '</a>';

		return $links;

	}
}



?>
