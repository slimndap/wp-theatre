<?php

/**
 * Theater Event Field retrieves field values of events and event dates.
 *
 * @since	0.16
 * @package	Theater/Events
 */
class Theater_Event_Field {
	
	protected $name;
	protected $filters = array();
	protected $item;
	
	function __construct( $name, $filters = array(), $item ) {
		$this->name = $name;
		$this->filters = $filters;
		$this->item = $item;
	}
	
	function __invoke( $args = array() ) {
		return $this->get();
	}
	
	function __toString() {
		return $this->get_html();
	}
	
	protected function apply_template_filters( $value, $filters ) {
		foreach ( $filters as $filter ) {
			$value = $filter->apply_to( $value, $this->item );
		}
		return $value;
	}

	function get() {
		if (method_exists($this->item, 'get_'.$this->name)) {
			$value = $this->item->{'get_'.$this->name}();			
		} else {
			$value = get_post_meta($this->item->ID, $this->name, true);
		}
		
		$value = apply_filters( 
			'theater/'.$this->item->get_name().'/field', 
			$value, $this->name, $this->item 
		);
		
		$value = apply_filters( 
			'theater/'.$this->item->get_name().'/field?name='.$this->name, 
			$value, $this->item 
		);

		return $value;
	}
	
	protected function get_callback( $action ) {
		
		foreach( $this->item->get_additional_fields() as $field) {

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
		
		if (method_exists($this->item, 'get_'.$this->name.'_html')) {
			$html = $this->item->{'get_'.$this->name.'_html'}( $this->filters );			
		} else {
			$value = (string) $this->get();
			
			ob_start();
			?><div class="<?php echo $this->item->get_post_type(); ?>_<?php echo $this->name; ?>"><?php 
				echo $this->apply_template_filters( $value, $this->filters ); 
			?></div><?php
			$html = ob_get_clean();
		}
		
		$html = apply_filters( 
			'theater/'.$this->item->get_name().'/field/html', 
			$html, $this->name, $this->filters, $this->item 
		);
		
		$html = apply_filters( 
			'theater/'.$this->item->get_name().'/field/html?name='.$this->name, 
			$html, $this->filters, $this->item 
		);
		
		return $html;

	}
	
}