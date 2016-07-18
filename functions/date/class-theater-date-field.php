<?php

/**
 * Theater_Date_Field class.
 *
 * @since	0.16
 * @package	Theater/Date
 */
class Theater_Date_Field {
	
	protected $name;
	protected $filters = array();
	protected $date;
	
	function __construct( $name, $filters = array(), $date ) {
		$this->name = $name;
		$this->filters = $filters;
		$this->date = $date;
	}
	
	function __invoke( $args = array() ) {
		
		return $this->get();
	}
	
	function __toString() {
		return $this->get_html();
	}
		
	protected function apply_template_filters( $value, $filters ) {
		foreach ( $filters as $filter ) {
			$value = $filter->apply_to( $value, $this->date );
		}
		return $value;
	}

	function get() {
		
		if ( $callback = $this->get_callback('get') ) {
			$value = call_user_func( $callback );
		} else {
			$value = get_post_meta($this->date->ID, $this->name, true);
		}
		
        if ( empty($value) && $event = $this->date->production() ) {
            $value = $event->custom( $this->name );
        }

		$value = apply_filters( 'wpt_event_'.$this->name, $value, $this->name, $this->date );

		return $value;
	}
	
	protected function get_callback( $action ) {
		
		foreach( $this->date->get_fields() as $field) {

			if ( $field['id'] != $this->name ) {
				continue;
			}
			
			if ( !empty($field['callbacks'][$action]))	{
				return $field['callbacks'][$action];
			}
		};
		
		return false;
			
	}
	
	function get_html() {
		if ( $callback = $this->get_callback('get_html') ) {
			$html = call_user_func( $callback, $this->filters );
		} else {
			$value = $this->get();
			
			ob_start();
			?><div class="<?php echo Theater_Date::post_type_name; ?>_<?php echo $this->name; ?>"><?php 
				echo $this->apply_template_filters( $value, $this->filters ); 
			?></div><?php
			$html = ob_get_clean();
		}
		
		$html = apply_filters( 'wpt_event_'.$this->name.'_html', $html, $this->name, $this->date );
		
		return $html;

	}
	
}