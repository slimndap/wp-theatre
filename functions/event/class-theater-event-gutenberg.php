<?php
/**
 * Adds Gutenberg support to Events admin page.
 *
 * @since	0.18
 */
class Theater_Gutenberg {
	
	function __construct() {
		
		add_filter( 'wpt/setup/post_type/args/?post_type=wp_theatre_prod', array( $this, 'add_gutenberg_support' ) );
		add_filter( 'wpt/event_editor/form/html', array( $this, 'add_post_id_field' ), 10, 3 );
		
	}

	/**
	 * Adds a post_ID field to the event editor form.
	 *
	 * If Gutenberg is active on the Event page there form of the classic editor, including the post_ID field, is no longer present.
	 * 
	 * @since	0.18
	 * @param 	mixed	$html
	 * @param 	mixed	$production_id
	 * @param 	mixed	$event_id
	 * @return 	string
	 */
	function add_post_id_field( $html, $production_id, $event_id ) {
		
		if ( !$this->support_gutenberg() ) {
			return $html;
		}
		
		$current_screen = get_current_screen();

		if (
			( !is_null($current_screen) && (WPT_Production::post_type_name == $current_screen->id) ) ||
			wp_doing_ajax()
		) {
			
			ob_start();
			
			echo $html;
			
			?><input type="hidden" name="post_ID" value="<?php echo $production_id; ?>"><?php
			$html = ob_get_clean();
			
		}
		
		return $html;
	}
	
	/**
	 * Adds Gutenberg support to events in the Theater for WordPress plugin.
	 * 
	 * @since	0.18
	 * @param 	array	$post_type_args	The current settings for the events post type (wp_theatre_prod).
	 * @return 	array					The new settings for the events post type.
	 */
	function add_gutenberg_support( $post_type_args ) {
		
		if ( !$this->support_gutenberg() ) {
			return $post_type_args;
		}
		
		$post_type_args[ 'show_in_rest' ] = true;
		
		return $post_type_args;
		
	}
	
	/**
	 * Checks wether Theater for WordPress should support Gutenberg.
	 * 
	 * @since	0.18
	 * @return	bool
	 */
	function support_gutenberg() {
		
		$support = apply_filters( 'theater/gutenberg/support', true );
		return $support;
		
	}

}