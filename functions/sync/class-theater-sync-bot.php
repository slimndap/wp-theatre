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
	
	function get_status() {

		switch ( $this->get( 'status' ) ) {
			case THEATER_SYNC_BOT_STATUS_NEW:
				$description = 'Waiting for activation';
				break;
			case THEATER_SYNC_BOT_STATUS_ACTIVATED:
				$description = 'Activated';
				break;
			case THEATER_SYNC_BOT_STATUS_PROVISIONING:
				$description = 'Installing';
				break;
			case THEATER_SYNC_BOT_STATUS_PROVISIONED:
				$description = 'Installed';
				break;
			case THEATER_SYNC_BOT_STATUS_CONFIGURING:
				$description = 'Needs configuration';
				break;
			case THEATER_SYNC_BOT_STATUS_READY:
				$description = 'Ready';
				break;
			case THEATER_SYNC_BOT_STATUS_WORKING:
				$description = 'Syncing';
				break;
			default :
				$description = '';
		}
		
		$status = array(
			'code' => $this->get('status'),
			'description' => $description,
		);
		
		
		return $status;
		
	}
	
	function load( $🤖_id ) {
		
		$🤖 = Theater_Sync_Data::get_🤖( $🤖_id );
		
		$this->ID = $🤖_id;
		$this->provider = $🤖->get( 'provider' );
		$this->license_key = $🤖->get( 'license_key' );
		
		$this->load_license();
		$this->load_status();

		return $this;		
	}
	
	function load_license() {
		
		$key = '🤖'.$this->ID.'_license';
		
		if ( false === ( $this->license = get_transient( $key ) ) ) {
			$provider = Theater_Sync_Data::get_provider( $this->provider );
			$this->license = Theater_Sync_Mother::check_license( $this->license_key, $provider );
			set_transient( $key, $this->license, MINUTE_IN_SECONDS );
		}
		
	}
	
	function load_status() {

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
		$key = '🤖'.$this->ID.'_license';		
		set_transient( $key, $this->license, MINUTE_IN_SECONDS );		

		return Theater_Sync_Data::save_🤖( $this ) ;
	}

}
