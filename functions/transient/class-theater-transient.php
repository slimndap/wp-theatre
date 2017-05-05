<?php
class Theater_Transient {
	
	private $key;
	
	function __construct( $name = '', $args = array() ) {
		
		$this->key = $this->get_key( $name, $args );
		
	}
	
	private function get_lifetime() {
		
		$lifetime = 10 * MINUTE_IN_SECONDS;
		
		$lifetime = apply_filters( 'theater/transient/lifetime', $lifetime, $this );
		
		return $lifetime;
		
	}
	
	public function get_key( $name, $args ) {
		
		$prefix = $this->get_prefix();
		
		$key = $prefix.$name.md5( serialize( $args ) );
		
		$key = apply_filters( 'theater/transient/key', $key, $this );
		
		return $key;
		
	}
	
	private function get_prefix() {
		
		$prefix = 'wpt';

		$prefix = apply_filters( 'theater/transient/prefix', $prefix, $this );

		return $prefix;
		
	}
	
	public function delete() {
		if ( $result = delete_transient( $this->key ) ) {
			$this->unregister();
		}
		
		return $result;
	}

	public function get() {

		if ( ! $this->is_active() ) {
			return false;
		}			
		return get_transient( $this->key );
		
	}
	
	private function is_active() {
		
		$active = true;
		
		if ( is_user_logged_in() ) {
			$active = false;
		}	
		
		$active = apply_filters( 'theater/transient/active', $active, $this );
		
		return $active;
	}
	
	function load_by_key( $key ) {
		$this->key = $key;
	}
	
	private function register() {
		$transient_keys = Theater_Transients::get_transient_keys();		
		$transient_keys[] = $this->key;
		$transient_keys = array_unique( $transient_keys );
		update_option( THEATER_TRANSIENTS_OPTION, $transient_keys, true );
	}
	
	private function unregister() {
		$transient_keys = Theater_Transients::get_transient_keys();	
		$transient_keys = array_diff( $transient_keys, array( $this->key ) );
		update_option( THEATER_TRANSIENTS_OPTION, $transient_keys, true );
	}
	
	public function set( $value ) {
		set_transient( $this->key, $value, $this->get_lifetime() );
		$this->register();
	}
	
}