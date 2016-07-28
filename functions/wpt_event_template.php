<?php

/**
 * WPT_Event_Template class.
 * 
 * @extends WPT_Template
 * @internal
 */
class WPT_Event_Template extends WPT_Template {

	/**
	 * Gets the default template for events.
	 * 
	 * @since	0.12.1
	 * @access 	protected
	 * @return 	string		The default template.
	 */
	protected function get_default() {

		$default = '{{thumbnail|permalink}} {{title|permalink}} {{remark}} {{datetime}} {{location}} {{tickets}}';
		
		/**
		 * Filter the default template for events.
		 * 
		 * @param 	string	$default	The default template.
		 */
		$default = apply_filters('wpt_event_template_default', $default);
		$default = apply_filters('wpt/event/template/default', $default);
		
		return $default;
	}

	/**
	 * Gets the value for a field from an event.
	 *
	 * @since 	0.12.1
	 * @since	0.15	Fixed an error when no production is set for the event.
	 *
	 * @access 	protected
	 * @param 	string	$field		The field.
	 * @param 	array 	$args		Arguments for the field (optional).
	 * 								Eg. the 'thumbnail'-field can have an optional 'size' argument:
	 *								{{thumbnail('full')}}
	 * @param 	array 	$filters 	Array of WPT_Template_Filter objects.
	 *								Filters to apply to the value (optional).
	 *
	 * @return 	string				The value.
	 */
	protected function get_field_value($field, $args = array(), $filters = array()) {

		$value = '';

		$value_args = array(
			'html' => true,
			'filters' => $filters,
		);

		switch ( $field ) {
			case 'datetime':
				$value = $this->object->get_field_html( 'startdatetime', $filters );
				break;
			case 'thumbnail':
				$size = 'thumbnail';
				if ( ! empty($args[0]) ) {
					$size = $args[0];
				}
				if ($production = $this->object->production()) {
					$value = $production->thumbnail_html($size, $filters);				
				}
				break;
			case 'categories':
			case 'content':
			case 'excerpt':
				if ($production = $this->object->get_event()) {
					$value = $production->get_field_html($field, $filters);
				}
				break;
			case 'date':
				$value = $this->object->get_field_html( 'startdate', $filters );
				break;
			case 'time':
				$value = $this->object->get_field_html( 'starttime', $filters );
				break;
			case 'prices':
			case 'tickets':
				$value = $this->object->{$field.'_html'}($filters);
				break;
			default:
				$value = $this->object->get_field_html( $field, $filters );
		}

		/**
		 * Filter the value for an event field.
		 *
		 * @since	0.12.1
		 * @param	string		$value		The value.
		 * @param	string		$field		The field.
		 * @param	array		$args		Arguments for the field (optional).
		 * 									Eg. the 'thumbnail'-field can have an optional 'size' argument:
		 *									{{thumbnail('full')}}
		 * @param	array		$filters	Array of WPT_Template_Filter objects.
		 *									Filters to apply to the value (optional).
		 * @param	WPT_Event	$event		The event.
		 */
		$value = apply_filters( 'wpt/event/template/field/value', $value, $field, $args, $filters );

		return $value;
	}
}