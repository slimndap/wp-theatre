<?php
/**
 * Theater_Sync_Data class.
 *
 * @author	Jeroen Schmit
 */
class Theater_Sync_Data {


	static function get_unique_id() {
		
		$🤖🤖🤖 = self::get_🤖🤖🤖();
		
		do {
	        $id = uniqid();
		} while ( array_key_exists( $id, $🤖🤖🤖 ) );
		
		return $id;

	}
	
	static function get_🤖( $🤖_id ) {

		$🤖🤖🤖 = self::get_🤖🤖🤖();

		if ( array_key_exists( $🤖_id, $🤖🤖🤖) ) {
			return $🤖🤖🤖[ $🤖_id ];
		}
		
		return false;

		
	}
	
	static function get_provider( $provider_id ) {
		
		$providers = self::get_providers();
		
		foreach( $providers as $provider ) {
			
			if ( $provider->slug == $provider_id ) {
				return $provider;
			}
			
		}
		
		return false;
		
	}
	
	static function get_providers() {
		
		return Theater_Sync_Mother::get_providers();
		
	}

	static function get_🤖🤖🤖() {
		return get_option( 'theater_🤖🤖🤖', array() );			
	}
	
	static function save_🤖( $🤖 ) {

		$🤖🤖🤖 = self::get_🤖🤖🤖();

		if ( empty( $🤖->get('ID') ) ) {
			$🤖->set( 'ID', self::get_unique_id() );
		}
		
		
		$🤖🤖🤖[ $🤖->get('ID') ] = $🤖;
		update_option( 'theater_🤖🤖🤖', $🤖🤖🤖 );

		return $🤖;
	}
		

}
