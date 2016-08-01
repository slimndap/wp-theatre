<?php

/**
 * Extensions Promo class.
 * Adds links to the extensions to the WordPress admin.
 * @package	Theater/Extensions
 */
class WPT_Extensions_Promo {

	function __construct() {
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );
		
		// Add the 'extensions' submenu with priority 40 to move it below the 'settings' submenu.
		add_action( 'admin_menu', array( $this, 'add_menu' ), 40 );
	}

	/**
	 * Add the extensions page to the admin menu.
	 *
	 * @since	0.13.2
	 * @return 	void
	 */
	function add_menu() {
		add_submenu_page(
			'theater-events',
			__( 'Theater Extensions','theatre' ),
			__( 'Extensions', 'theatre' ),
			'manage_options',
			'wpt_extensions',
			array( $this, 'get_page' )
		);
	}

	/**
	 * Adds an extensions link to the plugin row in the plugins page.
	 *
	 * @since	0.12.7
	 * @param 	array	$links	The current links.
	 * @param 	string	$file	The plugin filename.
	 * @return	array			The new links.
	 */
	function add_plugin_row_meta($links, $file) {

		if ( THEATER_PLUGIN_BASENAME != $file ) {
			return $links; }

		$extensions_link = add_query_arg(
			array(
				'utm_source'   => 'plugins-page',
				'utm_medium'   => 'plugin-row',
				'utm_campaign' => 'admin',
			), 'https://wp.theater/extensions/'
		);

		$links[] = '<a href="' . esc_url( $extensions_link ) . '">' . esc_html__( 'Extensions', 'theatre' ) . '</a>';

		return $links;
	}

	/**
	 * Gets the extensions.
	 *
	 * Downloads the available extensions through the REST API of the
	 * Theater for WordPress website.
	 * The response is stored in a transient for 24 hours.
	 *
	 * @since	0.13.2
	 * @since	0.14.1	Increased timeout of extensions feed retrieval.
	 * @access 	private
	 * @return 	array	The extensions.
	 */
	private function get_extensions() {

		if ( false === ( $response = get_transient( 'wpt_extensions_promo_feed' ) ) ) {
			$args = array(
				'timeout' => 30,	
			);
			$response = wp_remote_get( 'https://wp.theater/wp-json/theater/v1/extensions', $args );
			set_transient( 'wpt_extensions_promo_feed', $response, DAY_IN_SECONDS );
		}

		$extensions = array();
		if (
			! is_wp_error( $response )
			&& isset( $response['response']['code'] )
			&& 200 === $response['response']['code'] ) {
			$body = wp_remote_retrieve_body( $response );
			$extensions = json_decode( $body );
		}
		return $extensions;
	}

	/**
	 * Outputs the HTML for the Extensions page.
	 *
	 * @since	0.13.2
	 * @since	0.14.1	Extensions page layout was broken in WP 4.4.
	 * @return 	void
	 */
	function get_page() {
		$html = '';
		$html .= '<div class="wrap">';
		$html .= '<h1>'.esc_html__( 'Theater for WordPress extensions','theatre' ).'</h1>';
		$html .= '<p>'.__( 'Extensions are plugins that <strong><em>add functionality</em></strong> to the Theater for WordPress plugin.', 'theatre' ).'</p>';

		$html .= '<div class="widefat">';

		$extensions = $this->get_extensions();
		foreach ( $extensions as $extension ) {
			$extension_link = add_query_arg(
				array(
					'utm_source'   => 'plugin-extensions-page',
					'utm_medium'   => 'plugin-card',
					'utm_campaign' => 'admin',
					'utm_content'  => urlencode( $extension->title ),
				), $extension->permalink
			);

			$html .= '<div class="plugin-card">';

			$html .= '<div class="plugin-card-top">';

			$html .= '<div class="name column-name">';
			$html .= '<h3>';
			$html .= '<a href="'.$extension_link.'" class="">';
			$html .= esc_html( $extension->title );
			$html .= '<span class="plugin-icon">'.$extension->thumbnail.'</span>';
			$html .= '</a>';
			$html .= '</h3>';
			$html .= '</div>';

			$html .= '<div class="action-links">';
			$html .= '<ul class="plugin-action-buttons">';

			$html .= '<li>';
			$html .= '<a href="'.$extension_link.'" class="button">'.__( 'Get extension','theatre' ).'</a>';
			$html .= '</li>';

			$html .= '</ul>';
			$html .= '</div>';

			$html .= '<div class="desc column-description">';
			$html .= $extension->excerpt;
			$html .= '</div>';

			$html .= '</div>';

			$html .= '</div>';
		}
		$html .= '</div>';

		if ( empty($extensions) ) {
			$extensions_link = add_query_arg(
				array(
					'utm_source'   => 'plugin-extensions-page',
					'utm_medium'   => 'browse-button',
					'utm_campaign' => 'admin',
				), 'https://wp.theater/extensions/'
			);
			$html .= '<p><a href="'.$extensions_link.'" class="button-primary">'.__( 'Browse all extensions','theatre' ).'</a></p>';
		}

		echo $html;
	}



}