<?php
	
/**
 * Theater Jeero Suggest class.
 *
 * Manages sync suggestions from Jeero.
 *
 * @since	0.17
 */
class Theater_Jeero_Suggest {
	
	function __construct() {
		
		add_action( 'wp_ajax_wpt_jeero_suggest', array( $this, 'get_suggestion_html' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );

	}
	
	
	/**
	 * Add the extensions page to the admin menu.
	 *
	 * @since	0.17.1
	 * @return 	void
	 */
	function add_admin_menu() {
		add_submenu_page(
			'theater-events',
			__( 'Import events','theatre' ),
			__( 'Import events', 'theatre' ),
			'manage_options',
			'wpt_jeero',
			array( $this, 'get_admin_page' )
		);
	}
	
	/**
	 * Outputs the HTML for the Import events page.
	 *
	 * @since	0.17.1
	 * @return 	void
	 */
	function get_admin_page() {
		
		?><div class="wrap wrap_wpt_jeero_suggestions">
			<h1><?php esc_html_e( 'Import events','theatre' ); ?></h1>
			<p><?php _e( 'Learn how to import events from your existing ticketing solution in to Theater for WordPress with one of the following guides:', 'theatre' ); ?></p><?php


			// Submit request to the Jeero API.
			$response = $this->get( 'suggest/theater/' );
	
			if ( is_wp_error( $response ) ) {
				return;
			}
			
			$theaters = json_decode( wp_remote_retrieve_body( $response ) );
	
			?><div class="widefat">
				<?php
				foreach ( $theaters as $theater ) {
					
					$url = $this->get_howto_url_for_theater( $theater->name );
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin-jeero-suggestions-page',
							'utm_medium'   => 'plugin-card',
							'utm_campaign' => 'admin',
							'utm_content'  => urlencode( $theater->name ),
						), $url
					);
					
					?><div class="plugin-card">
						<div class="plugin-card-top">
							<div class="name column-name">
								<h3>
									<a href="<?php echo $url; ?>"><?php 
										echo esc_html( $theater->title ); 
										?><span class="plugin-icon"><img src="<?php echo $theater->logo; ?>"></span>
									</a>
								</h3>
							</div>
		
							<div class="action-links">
								<ul class="plugin-action-buttons">
									<li>
										<a href="<?php echo $url; ?>" class="button"><?php _e( 'Get started','theatre' ); ?></a>
									</li>
		
								</ul>
							</div>
		
							<div class="desc column-description"><?php
								esc_html_e( sprintf( 'Import %s events into Theater for WordPress.', $theater->title ), 'theatre' ); ?></div>
						</div>
		
					</div><?php
				}
			?></div>
		</div><?php

	}
	
	function get( $endpoint ) {
		
		$transient_key = 'wpt_jeero_get_'.$endpoint;
		
		if ( false === ( $response = get_transient( $transient_key ) ) ) {
			
			$url = 'https://ql621w5yfk.execute-api.eu-west-1.amazonaws.com/jeero/v1/'.$endpoint;
			$response = wp_remote_get( $url );
			
			set_transient( $transient_key, $response, HOUR_IN_SECONDS );
			
		}
		
		return $response;
	}
	
	function get_howto_url_for_theater( $name ) {
		return sprintf( 'https://jeero.ooo/import-%s-into-theater-for-wordpress/', $name );
	}
	
	/**
	 * Gets a sync suggestion from Jeero for a tickets URL.
	 * 
	 * @since	0.17
	 * @return 	void
	 */
	function get_suggestion_html() {
		
		// Bail if no tickets URL is submitted.
		if ( empty( $_POST[ 'tickets_url' ] ) ) {
			wp_die();
		}
		
		// Only send the hostname of the tickets URL.
		$url_parts = parse_url( $_POST[ 'tickets_url' ] );
		if ( empty( $url_parts[ 'host' ] ) ) {
			wp_die();
		}
		
		// Submit request to the Jeero API.
		$response = $this->get( 'suggest/theater/?tickets_url='.$url_parts[ 'host' ] );
		
		if ( is_wp_error( $response ) ) {
			wp_die();
		}
		
		$body = json_decode( wp_remote_retrieve_body( $response ) );
		
		// Bail if there is no suggestin for this tickets URL.
		if ( empty( $body ) ) {
			wp_die();
		}
		
		// Output the suggestion.
		if ( !empty( $body->logo ) ) {		
			?><img src="<?php echo $body->logo; ?>"> <?php
		}

		printf( __( 'Tired of copy-pasting? Learn how to sync your %s events with this <a href="%s">guide</a>.', 'theatre' ), $body->title, $this->get_howto_url_for_theater( $body->name ) );
		
		wp_die();
	}
	
	
}