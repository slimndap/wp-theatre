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
		$response = wp_remote_get( 'https://ql621w5yfk.execute-api.eu-west-1.amazonaws.com/jeero/v1/suggest/theater/?tickets_url='.$url_parts[ 'host' ] );
		
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

		$guide_url = sprintf( 'https://jeero.ooo/import-%s-into-theater-for-wordpress/', $body->name );
		printf( __( 'Tired of copy-pasting? Learn how to sync your %s events with this <a href="%s">guide</a>.', 'theatre' ), $body->title, $guide_url );
		
		wp_die();
	}
	
	
}