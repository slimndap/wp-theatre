<?php

/**
 * Extensions Promo class.
 * Adds links to the extensions to the WordPress admin.
 */
class WPT_Extensions_Promo {

	function __construct() {
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );
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
			__( 'Theater Extensions','wp_theatre' ),
			__( 'Extensions', 'wp_theatre' ),
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

		if ( 'theatre/theater.php' != $file ) {
			return $links; }

		$extensions_link = add_query_arg(
			array(
				'utm_source'   => 'plugins-page',
				'utm_medium'   => 'plugin-row',
				'utm_campaign' => 'admin',
			), 'http://theater.slimndap.com/extensions/'
		);

		$links[] = '<a href="' . esc_url( $extensions_link ) . '">' . esc_html__( 'Extensions', 'wp_theatre' ) . '</a>';

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
	 * @access 	private
	 * @return 	array	The extensions.
	 */
	private function get_extensions() {

		if ( false === ( $response = get_transient( 'wpt_extensions_promo_feed' ) ) ) {
			$response = wp_remote_get( 'http://theater.slimndap.com/wp-json/theater/v1/extensions' );
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
	 * @return 	void
	 */
	function get_page() {
		$html = '';
		$html .= '<div class="wrap">';
		$html .= '<h1>'.esc_html__( 'Theater for WordPress extensions','wp_theatre' ).'</h1>';
		$html .= '<p>'.__( 'Extensions are plugins that <strong><em>add functionality</em></strong> to the Theater for WordPress plugin.', 'wp_theatre' ).'</p>';

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

			$html .= '<a href="'.$extension_link.'" class="plugin-icon">';
			$html .= $extension->thumbnail;
			$html .= '</a>';

			$html .= '<div class="name column-name">';
			$html .= '<h4>'.esc_html( $extension->title ).'</h4>';
			$html .= '</div>';

			$html .= '<div class="action-links">';
			$html .= '<ul class="plugin-action-buttons">';

			$html .= '<li>';
			$html .= '<a href="'.$extension_link.'" class="button">'.__( 'Get extension','wp_theatre' ).'</a>';
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
				), 'http://theater.slimndap.com/extensions/'
			);
			$html .= '<p><a href="'.$extensions_link.'" class="button-primary">'.__( 'Browse all extensions','wp_theatre' ).'</a></p>';
		}

		echo $html;
	}



}