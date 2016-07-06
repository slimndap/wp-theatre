<?php

/**
 * Manages the updates for licensed extensions.
 *
 * Extensions from the Theater for Wordpress store require a license key for updates.
 * User can enter and activate their license keys on the 'licenses' tab on the settings page.
 *
 * @since 	0.12.3
 */
class WPT_Extensions_Updater {

	function __construct() {
		
		// Add the 'Licenses' tab to the settings page.
		add_filter( 'wpt_admin_page_tabs',array( $this, 'add_settings_tab' ), 90 );
		add_filter( 'admin_init', array( $this, 'add_licenses_section' ) );
		add_filter( 'admin_init', array( $this, 'add_licenses_settings' ) );

		// Manage updates and licenses with the EDD Plugin Updater.
		add_action( 'admin_init', array( $this, 'create_plugin_updaters' ), 0 );
		add_action( 'admin_init', array( $this, 'activate_licenses' ) );
		add_action( 'admin_init', array( $this, 'deactivate_licenses'  ) );

		$this->load_dependencies();
	}

	/**
	 * Activates a license for an extension.
	 *
	 * Triggered by the 'Activate License' button on the settings page.
	 *
	 * @since 	0.12.3
	 * @since	0.15.6	Updated the updater URL to the wp.theater domain.
	 * @return 	void
	 */
	function activate_licenses() {
		foreach ( $this->get_extensions() as $extension ) {
			if ( isset( $_POST[ $extension['slug'].'_license_activate' ] ) ) {
			 	if ( ! check_admin_referer( $extension['slug'].'_nonce', $extension['slug'].'_nonce' ) ) {
					return;
				}

				$license_key = trim( get_option( $extension['slug'].'_license_key' ) );

				$api_params = array(
					'edd_action' => 'activate_license',
					'license' 	=> $license_key,
					'item_name' => urlencode( $extension['name'] ),
					'url'       => home_url()
				);

				$response = wp_remote_post( 'https://wp.theater', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

				if ( is_wp_error( $response ) ) {
					return false; }

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				update_option( $extension['slug'].'_license_status', $license_data->license );
			}
		}
	}

	/**
	 * Adds the 'Licenses' tab to the Theater settings page.
	 *
	 * @since	0.12.3
	 * @param	array	$tabs	The current tabs.
	 * @return	array			The next tabs.
	 */
	public function add_settings_tab($tabs) {
		$extensions = $this->get_extensions();
		if (!empty($extensions)) {
			$tabs['wpt_licenses'] = __( 'Licenses', 'theatre' );		
		}
		return $tabs;
	}

	/**
	 * Adds a 'License keys' section to the 'Licenses' tab.
	 *
	 * @since	0.12.3
	 * @return 	void
	 */
	public function add_licenses_section() {
		add_settings_section(
			'wpt_license_keys',
			__( 'License keys','theatre' ),
			'',
			'wpt_licenses'
		);
	}

	/**
	 * Adds a licence key settings field for every extensions to the 'License Keys' section.
	 *
	 * @since	0.12.3
	 * @return 	void
	 */
	public function add_licenses_settings() {
		foreach ( $this->get_extensions() as $extension ) {
			$option = $extension['slug'].'_license_key';

			register_setting( 'wpt_licenses', $option );

	        add_settings_field(
	            $option, // ID
	            $extension['name'], // Title
	            array( $this, 'license_setting' ), // Callback
	            'wpt_licenses', // Page
				'wpt_license_keys',
				array(
				   'extension' => $extension,
				)
	        );
		}
	}

	/**
	 * Creates an EDD Plugin Updater for every extension.
	 *
	 * The EDD Plugin Updater periodically checks for valid license keys and available updates.
	 *
	 * @since	0.12.3
	 * @since	0.15.6	Updated the updater URL to the wp.theater domain.
	 * @return 	void
	 */
	public function create_plugin_updaters() {
		foreach ( $this->get_extensions() as $extension ) {
			$license_key = trim( get_option( $extension['slug'].'_license_key' ) );

			$edd_updater = new EDD_SL_Plugin_Updater( 'https://wp.theater', $extension['plugin_file'], array(
					'version' 	=> $extension['version'], 	// current version number
					'license' 	=> $license_key, 			// license key (used get_option above to retrieve from DB)
					'item_name' => $extension['name'], 	// name of this plugin
					'author' 	=> $extension['author'],  // author of this plugin
				)
			);
		}
	}

	/**
	 * Deactivates a license for an extension.
	 *
	 * Triggered by the 'Deactivate License' button on the settings page.
	 *
	 * @since 	0.12.3
	 * @since	0.15.6	Updated the updater URL to the wp.theater domain.
	 * @return 	void
	 */
	function deactivate_licenses() {
		foreach ( $this->get_extensions() as $extension ) {

			if ( isset( $_POST[ $extension['slug'].'_license_deactivate' ] ) ) {
			 	if ( ! check_admin_referer( $extension['slug'].'_nonce', $extension['slug'].'_nonce' ) ) {
					return; // get out if we didn't click the Activate button
				}

				$license_key = trim( get_option( $extension['slug'].'_license_key' ) );

				$api_params = array(
					'edd_action' => 'deactivate_license',
					'license' 	=> $license_key,
					'item_name' => $extension['name'], // the name of our product in EDD
					'url'       => home_url()
				);

				$response = wp_remote_post( 'https://wp.theater', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

				if ( is_wp_error( $response ) ) {
					return false; }

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if ( 'deactivated' == $license_data->license ) {
					delete_option( $extension['slug'].'_license_status' ); }
			}
		}
	}

	/**
	 * Gets all extensions that use the updater.
	 *
	 * @since	0.12.3
	 * @return 	array	The extensions.
	 */
	public function get_extensions() {
		/**
		 * Filter the  extensions that use the updater.
		 *
		 * Extensions should use this filter to be included.
		 *
		 * @since	0.12.3
		 * @param	array	The current extensions.
		 */
		$extensions = apply_filters( 'wpt/extensions/updater/extensions', array() );
		return $extensions;
	}

	/**
	 * Outputs the license setting HTML.
	 *
	 * @since	0.12.3
	 * @param 	array	$args {
	 *		@type	array {
	 *			@type	string	$slug		The slug of the extension.
	 *			@type	string  $name       The name of the extension.
	 *										This has to be the exact name that is used in the Theater store.
	 *			@type	string	version		The version of the extension.
	 *			@type	string  plugin_file Path to the extension file.
	 *			@type	string	author		The author of the extension.
	 *		}
	 * }
	 * @return 	void
	 */
	public function license_setting($args) {
		$extension = $args['extension'];
		$license_key = get_option( $extension['slug'].'_license_key' );
		$license_status = get_option( $extension['slug'].'_license_status' );

		$html = '';
		$html .= '<input type="text" id="'.$extension['slug'].'_license_key" name="'.$extension['slug'].'_license_key" value="'.esc_attr( $license_key ).'" class="regular-text" />';

		if ( false !== $license_key ) {
			$html .= wp_nonce_field( $extension['slug'].'_nonce', $extension['slug'].'_nonce' );
			if ( false !== $license_status && 'valid' == $license_status ) {
				$html .= '<input type="submit" class="button-secondary" name="'.$extension['slug'].'_license_deactivate" value="'.__( 'Deactivate License', 'theatre' ).'"/>';
			} else {
				$html .= '<input type="submit" class="button-secondary" name="'.$extension['slug'].'_license_activate" value="'.__( 'Activate License', 'theatre' ).'"/>';
			}
		}
		echo $html;
	}

	/**
	 * Loads the EDD Plugin Updater.
	 *
	 * @since	0.12.3
	 * @return 	void
	 */
	private function load_dependencies() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
		}
	}
}