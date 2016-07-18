<?php

/**
 * WPT_Event class.
 * 
 * @extends 	Theater_Date
 * @deprecated	0.16
 * @package		Theater/Deprecated
 */
class WPT_Event extends Theater_Date {
	
	function __construct($ID = false, $PostClass = false) {
		$this->PostClass = $PostClass;		
		parent::__construct($ID);
	}
	
	function post_type() {
		return get_post_type_object( parent::post_type_name );
	}

	/**
	 * Event production.
	 *
	 * Returns the production of the event as a WPT_Production object.
	 *
	 * @since 	0.4
	 * @since	0.15	Removed local caching of event production.
	 *					Return <false> if no production is set.
	 *
	 * @return 	WPT_Production 	The production.
	 *							Returns <false> if no production is set.
	 */
	function production() {
		$production = parent::production();	
		return new WPT_Production( $production->ID, $this->PostClass );
	}

}