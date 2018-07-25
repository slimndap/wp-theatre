<?php
/**
 * Theater_Embed class.
 *
 * Customizes the OEmbed iframe content for events.
 *
 * @author	Jeroen Schmit
 * @since	0.16
 * @package	Theater/Events
 */
class Theater_Embed {

	static function init() {
		add_filter( 'the_excerpt_embed', array( __CLASS__, 'get_excerpt_embed' ) );
	}
	
	/**
	 * Get the custom excerpt for events for use in the OEmbed iframe.
	 * 
	 * @static
	 * @param	string	$output	The default excerpt for the event.
	 * @return	string			The custom excerpt for the event.
	 */
	static function get_excerpt_embed( $output ) {
		
		if ( WPT_Production::post_type_name != get_post_type() ) {
			return $output;
		}
		
		ob_start();
		
		$production = new WPT_Production();
		
		?><p><?php echo $production->summary(); ?></p><?php
		
		return ob_get_clean();
	}

}
Theater_Embed::init();