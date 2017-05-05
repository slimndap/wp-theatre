<?php
/**
 * The Theater_Transient object.
 *
 * @since	0.15.24
 */
class Theater_Transient {

	/**
	 * The key used to store the transient.
	 *
	 * @since	0.15.24
	 *
	 * @var 	string
	 * @access 	private
	 */
	private $key;

	/**
	 * Constructor method for Theater_Transient objects.
	 *
	 * Set the $key property.
	 *
	 * @since	0.15.24
	 *
	 * @uses 	Theater_Transient::key to set the key based on $name and $args.
	 * @uses	Theater_Transient::calculate_key() to get the key based on $name and $args.
	 *
	 * @param 	string 	$name 	The name of the transient.
	 * @param 	array 	$args	The arguments of the transient.
	 * @return 	void
	 */
	function __construct( $name = '', $args = array() ) {
		$this->key = $this->calculate_key( $name, $args );
	}

	/**
	 * Calculates the key for this transient.
	 *
	 * @since	0.15.24
	 *
	 * @uses	Theater_Transient::get_prefix() to get the key prefix.
	 *
	 * @param 	string 	$name 	The name of the transient.
	 * @param 	array 	$args	The arguments of the transient.
	 * @return 	string			The key for this transient.
	 */
	function calculate_key( $name, $args ) {

		$prefix = $this->get_prefix();

		$key = $prefix . $name . md5( serialize( $args ) );

		/**
		 * Filter the key of this transient.
		 *
		 * @since	0.15.24
		 *
		 * @param	string				$key		The key of this transient.
		 * @param	Theater_Transient	$transient	The transient object.
		 */
		$key = apply_filters( 'theater/transient/key', $key, $this );

		return $key;

	}

	/**
	 * Gets the transient value.
	 *
	 * @since	0.15.24
	 *
	 * @uses	Theater_Transient::is_active() to check if the use of transients is active.
	 *
	 * @return 	mixed	The transient value.
	 *					If the transient does not exist, does not have a value, has expired, or if the use of transients in not active
	 *					then get_transient will return <false>.
	 */
	public function get() {

		if ( ! $this->is_active() ) {
			return false;
		}
		return get_transient( $this->key );

	}

	/**
	 * Gets the expiration of this transient.
	 *
	 * @since	0.15.24
	 * @return	int		The expiration of this transient.
	 */
	function get_expiration() {

		$expiration = 10 * MINUTE_IN_SECONDS;

		/**
		 * Filter the expiration of this transient.
		 *
		 * @since	0.15.24
		 *
		 * @param	int					$expiration	The expiration of this transient.
		 * @param	Theater_Transient	$transient	The transient object.
		 */
		$expiration = apply_filters( 'theater/transient/expiration', $expiration, $this );

		return $expiration;

	}

	/**
	 * Gets the prefix for this transient.
	 *
	 * @since	0.15.24
	 *
	 * @return 	string	The prefix for this transient.
	 */
	function get_prefix() {

		$prefix = 'wpt';

		/**
		 * Filter the prefix of this transient.
		 *
		 * @since	0.15.24
		 *
		 * @param	string				$prefix		The prefix of this transient.
		 * @param	Theater_Transient	$transient	The transient object.
		 */
		$prefix = apply_filters( 'theater/transient/prefix', $prefix, $this );

		return $prefix;

	}

	/**
	 * Checks if the use of transients is active.
	 *
	 * @since	0.15.24
	 *
	 * @return	bool
	 */
	function is_active() {

		$active = true;

		if ( is_user_logged_in() ) {
			$active = false;
		}

		/**
		 * Filter whether the use of transients if active.
		 *
		 * @since	0.15.24
		 *
		 * @param	bool				$active		Whether transients are currenlty active.
		 * @param	Theater_Transient	$transient	The transient object.
		 */
		$active = apply_filters( 'theater/transient/active', $active, $this );

		return $active;
	}

	/**
	 * Loads the transient by its key.
	 *
	 * @since	0.15.24
	 *
	 * @uses 	Theater_Transient::key to set the key property.
	 *
	 * @param 	string	$key	The transient key.
	 * @return 	void
	 */
	function load_by_key( $key ) {
		$this->key = $key;
	}

	/**
	 * Registers the transient in the list of Theater transients that are in use.
	 *
	 * @since	0.15.24
	 *
	 * @uses	Theater_Transients::get_transient_keys() to get all Theater transients that are in use.
	 * @uses	Theater_Transient::key to get the key of the current transient.
	 * @return 	void
	 */
	function register() {
		$transient_keys = Theater_Transients::get_transient_keys();
		$transient_keys[] = $this->key;
		$transient_keys = array_unique( $transient_keys );
		update_option( THEATER_TRANSIENTS_OPTION, $transient_keys, true );
	}

	/**
	 * Resets the transient.
	 *
	 * @since	0.15.24
	 *
	 * @uses 	Theater_Transient::unregister() to remove the transient from the list of Theater transients that
	 *			are in use.
	 *
	 * @return	bool	<true> if successful, <false> otherwise.
	 */
	public function reset() {

		$result = delete_transient( $this->key );

		if ( $result ) {
			$this->unregister();
		}

		return $result;
	}

	/**
	 * Sets the transient value.
	 *
	 * @since	0.15.24
	 *
	 * @uses	Theater_Transient::is_active() to check if the use of transients is active.
	 *
	 * @param 	mixed 	$value	The transient value.
	 * @return 	bool			<false> if value was not set and <true> if value was set.
	 */
	public function set( $value ) {

		if ( ! $this->is_active() ) {
			return false;
		}

		$result = set_transient( $this->key, $value, $this->get_expiration() );

		if ( $result ) {
			$this->register();
		}

		return $result;
	}

	/**
	 * Unregisters the transient from the list of Theater transients that are in use.
	 *
	 * @since	0.15.24
	 *
	 * @uses	Theater_Transients::get_transient_keys() to get all Theater transients that are in use.
	 * @uses	Theater_Transient::key to get the key of the current transient.
	 * @return 	void
	 */
	private function unregister() {
		$transient_keys = Theater_Transients::get_transient_keys();
		$transient_keys = array_diff( $transient_keys, array( $this->key ) );
		update_option( THEATER_TRANSIENTS_OPTION, $transient_keys, true );
	}


}
