<?php

/**
 * Extensions Promo class.
 * Adds links to the extensions to the WordPress admin.
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

		if ( 'theatre/theater.php' != $file ) {
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
	 * Gets the extensions for a category.
	 * 
	 * @access 	protected
	 * @since	0.15.12
	 * @param	$category_slug	The category slug.
	 * @return	array			The extensions for the category.
	 */
	protected function get_extensions_for_category($category) {
		
		$extensions_for_category = array();

		foreach ( $this->get_extensions() as $extension) {
			$categories = wp_list_pluck( $extension->categories, 'slug');
			if (in_array($category, $categories)) {
				$extensions_for_category[]	 = $extension;
			}
		}
		
		return $extensions_for_category;
		
	}

	/**
	 * Outputs the HTML for the Extensions page.
	 *
	 * @since	0.13.2
	 * @since	0.14.1	Extensions page layout was broken in WP 4.4.
	 * @since	0.15.12	Added categories.
	 * @return 	void
	 */
	function get_page() {
		?><div class="wrap wrap_wpt_extensions">
			<h1><?php esc_html_e( 'Theater for WordPress extensions','theatre' ); ?></h1>
			<p><?php _e( 'Extensions are plugins that <strong><em>add functionality</em></strong> to the Theater for WordPress plugin.', 'theatre' ); ?></p><?php

		$extensions_popular = $this->get_extensions_for_category('popular');
		if (!empty($extensions_popular)) {
			?><h2><?php _e('Our most popular extensions', 'theatre'); ?></h2><?php
			echo  $this->get_extensions_html($extensions_popular);
		}
		
		$extensions_cinema = $this->get_extensions_for_category('cinema');
		if (!empty($extensions_cinema)) {
			?><br class="clear" /><h2><?php _e('Extensions for your movie theater', 'theatre'); ?></h2><?php
			echo  $this->get_extensions_html($extensions_cinema);
		}
		
		?><br class="clear" /><h2><?php _e('All extensions', 'theatre'); ?></h2><?php
		
		$extensions = $this->get_extensions();
		echo $this->get_extensions_html($extensions);

		if ( empty($extensions) ) {
			$extensions_link = add_query_arg(
				array(
					'utm_source'   => 'plugin-extensions-page',
					'utm_medium'   => 'browse-button',
					'utm_campaign' => 'admin',
				), 'https://wp.theater/extensions/'
			);
			?><p>
				<a href="<?php echo $extensions_link; ?>" class="button-primary"><?php 
					esc_html_e( 'Browse all extensions','theatre' ); 
				?></a>
			</p><?php
		}
	}

	/**
	 * Gets the HTML for a set of extensions.
	 * 
	 * @access 	protected
	 * @since	0.15.12
	 * @param 	array	$extensions	The extensions.
	 * @return	string				The HTML for the set of extensions
	 */
	protected function get_extensions_html($extensions) {
		
		ob_start();
		?><div class="widefat">
			<?php
			foreach ( $extensions as $extension ) {
				$extension_link = add_query_arg(
					array(
						'utm_source'   => 'plugin-extensions-page',
						'utm_medium'   => 'plugin-card',
						'utm_campaign' => 'admin',
						'utm_content'  => urlencode( $extension->title ),
					), $extension->permalink
				);
	
				?><div class="plugin-card">
					<div class="plugin-card-top">
						<div class="name column-name">
							<h3>
								<a href="<?php echo $extension_link; ?>"><?php 
									echo esc_html( $extension->title ); 
									?><span class="plugin-icon"><?php echo $extension->thumbnail; ?></span>
								</a>
							</h3>
						</div>
	
						<div class="action-links">
							<ul class="plugin-action-buttons">
								<li>
									<a href="<?php echo $extension_link; ?>" class="button"><?php _e( 'Get extension','theatre' ); ?></a>
								</li>
	
							</ul>
						</div>
	
						<div class="desc column-description"><?php echo $extension->excerpt; ?></div>
					</div>
	
				</div><?php
			}
			?></div><?php
		
		return ob_get_clean();
		
		
	}


}