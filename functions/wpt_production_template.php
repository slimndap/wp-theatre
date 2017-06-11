<?php

/**
 * Production template class.
 *
 * @since	0.12.1
 * @extends	WPT_Template
 */
class WPT_Production_Template extends WPT_Template {

	/**
	 * Gets the default template for productions.
	 * 
	 * @since	0.12.1
	 * @access 	protected
	 * @return 	string		The default template.
	 */
	protected function get_default() {
		$default = '{{thumbnail|permalink}} {{title|permalink}} {{dates}} {{cities}}';
		
		/**
		 * Filter the default template for productions.
		 * 
		 * @param 	string	$default	The default template.
		 */
		$default = apply_filters('wpt_production_template_default', $default);
		$default = apply_filters('wpt/production/template/default', $default);
		
		return $default;
	}

	/**
	 * Gets the value for a field from a production.
	 *
	 * @since 	0.12.1
	 * @since	0.15.27	Added the 'tags' field.
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
			case 'thumbnail':
				$size = 'thumbnail';
				if ( ! empty($args[0]) ) {
					$size = $args[0];
				}
				$value = $this->object->thumbnail_html($size, $filters);
				break;
			case 'prices' :
				$value = $this->object->prices_html();
				break;
			case 'tags' :
				$value = $this->object->tags_html();
				break;
			case 'title':
			case 'dates':
			case 'cities':
			case 'content':
			case 'excerpt':
			case 'summary':
			case 'categories':
				$value = $this->object->{$field}($value_args);
				break;
			default:
				$value = $this->object->custom( $field, $value_args );
		}

		/**
		 * Filter the value for a field.
		 *
		 * @since	0.12.1
		 * @param	string			$value		The value.
		 * @param	string			$field		The field.
		 * @param	array			$args		Arguments for the field (optional).
		 * 										Eg. the 'thumbnail'-field can have an optional 'size' argument:
		 *										{{thumbnail('full')}}
		 * @param	array			$filters	Array of WPT_Template_Filter objects.
		 *										Filters to apply to the value (optional).
		 * @param	WPT_Production	$production	The production.
		 */
		$value = apply_filters( 'wpt/production/template/field/value', $value, $field, $args, $filters, $this->object );

		return $value;
	}

}