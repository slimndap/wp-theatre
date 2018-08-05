<?php
/**
 * Theater_Sync_🤖 class.
 *
 * @author	Jeroen Schmit
 */
class Theater_Sync_🤖 {

	private $ID;
	private $title;
	private $provider;
	private $license_key;
	private $license;
	private $config;
	private $status;
	
	function __construct( $🤖_id = NULL ) {

		if (! is_null( $🤖_id ) ) {
			$this->load( $🤖_id );
		}
		
	}
	
	function get( $field ) {
		return $this->{$field};
	}
	
	function load( $🤖_id ) {
		
		$🤖 = Theater_Sync_Data::get_🤖( $🤖_id );
		
		$this->ID = $🤖_id;
		$this->provider = $🤖->get( 'provider' );
		$this->license_key = $🤖->get( 'license_key' );
		$this->license = $🤖->get( 'license' );

		$this->set_status();

		return $this;		
	}
	
	function set_status() {
		
		if ( !empty( $this->license->license ) && 'valid' === $this->license->license ) {
			$this->status = THEATER_SYNC_BOT_STATUS_ACTIVATED;
			return;
		}
		
		$this->status = THEATER_SYNC_BOT_STATUS_NEW;
		
		
		
		
	}
	
	function set( $field, $value ) {
		$this->{$field}	= $value;
		return $this;
	}
	
	function save() {
		return Theater_Sync_Data::save_🤖( $this ) ;
	}

}
