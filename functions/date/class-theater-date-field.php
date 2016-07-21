<?php

/**
 * Theater_Date_Field class.
 *
 * @since	0.16
 * @package	Theater/Date
 */
class Theater_Date_Field extends Theater_Field {
	
	function get() {
		
		if (method_exists($this->item, 'get_'.$this->name)) {
			$value = $this->item->{'get_'.$this->name}();			
		} else {
			$value = get_post_meta($this->item->ID, $this->name, true);
		}
		
        if ( empty($value) && $event = $this->item->production() ) {
            $value = $event->custom( $this->name );
        }

		$value = apply_filters( 
			'theater/'.$this->item->get_name().'/field', 
			$value, $this->name, $this->item 
		);
		
		$value = apply_filters( 
			'theater/'.$this->item->get_name().'/field/'.$this->name, 
			$value, $this->item 
		);

		return $value;

	}
		
}