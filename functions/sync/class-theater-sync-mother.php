<?php
/**
 * Theater_Sync_Mother class.
 *
 * Handles all communication with mother.
 *
 * @author	Jeroen Schmit
 */
class Theater_Sync_Mother {

	static function activate_license( $license_key, $provider ) {
		
		$api_params = array(
			'edd_action' => 'activate_license',
			'license' 	=> $license_key,
			'item_name' => urlencode( $provider->title ),
			'url'       => home_url()
		);
		
		$response = wp_remote_post( THEATER_SYNC_MOTHER_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		
		if ( is_wp_error( $response ) ) {
			return false; 
		}
		
		return json_decode( wp_remote_retrieve_body( $response ) );
		
	}

	static function get_providers() {
		
		if ( false === ( $response = get_transient( 'theater_sync_providers_feed' ) ) ) {
			$args = array(
				'timeout' => 30,	
			);
			$response = wp_remote_get( THEATER_SYNC_MOTHER_URL.'/wp-json/theater/sync/v1/providers', $args );
			set_transient( 'theater_sync_providers_feed', $response, DAY_IN_SECONDS );
		}
		
		$providers = array();
		
		if (
			! is_wp_error( $response )
			&& isset( $response['response']['code'] )
			&& 200 === $response['response']['code'] 
		) {
			$body = wp_remote_retrieve_body( $response );
			$providers = json_decode( $body );
		}

		return $providers;
		
	}

}
