<?php
/**
 * Main widgets class.
 *
 * @since	0.16
 * @package	Theater/Widgets
 * @internal
 */
class Theater_Widgets {
	
	/**
	 * Adds the action hook that inits all widgets.
	 *
	 * @since	0.16
	 * @static
	 * @return void
	 */
	static function init() {
		add_action( 'widgets_init', array( __CLASS__, 'init_widgets' ));				
	}
	
	/**
	 * Init all widgets.
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	static function init_widgets() {
	     register_widget( 'WPT_Events_Widget' );
	     register_widget( 'WPT_Production_Widget' );
	     register_widget( 'WPT_Production_Events_Widget' );
	     register_widget( 'WPT_Productions_Widget' );
	     register_widget( 'WPT_Cart_Widget' );		
	}
	
}	
