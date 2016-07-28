<?php
/**
 * @deprecated	0.16
 * @extends Theater
 */
class WP_Theatre extends Theater {
	
	function __construct() {
		$this->deprecated_properties();
		
	}
}

/**
 * @var	WP_Theatre	
 * @deprecated	0.16
 */
global $wp_theatre;
$wp_theatre = Theater();
