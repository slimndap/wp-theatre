<?php

/**
 * Abstract Theater Item class.
 * 
 * @abstract
 * @since	0.16
 * @package	Theater/Abstracts
 */
abstract class Theater_Item {

	/**
	 * The internal name of this item.
	 * @internal
	 */
	const name = 'undefined';	
	
	/**
	 * The post type of this item.
	 * @internal
	 */
	const post_type_name = 'undefined';

	/**
	 * ID of this item.
	 * 
	 * @since	0.16
	 * @var 	int
	 */
	var $ID;
	
	/**
	 * The template for the HTML output of this item.
	 * 
	 * @var		string
	 * @since	0.16
	 * @internal
	 */
	var $template;
	
	/**
	 * Sets the ID and template of this item.
	 * 
	 * @since	0.16
	 * @uses	Theater_Item::$ID to set the ID of this item.
	 * @uses	Theater_Item::$template to set the template for the HTML output of this item.
	 *
	 * @param 	int|WP_Post	$ID 	ID of post object or post object of this item.
	 * @return 	void
	 */
	function __construct( $ID = false, $template = '' ) {
		
		if ( $ID instanceof WP_Post ) {
			$this->post = $ID;
			$ID = $ID->ID;
		}

		$this->ID = $ID;
		
		if (!empty($template)) {
			$this->template = $template;
		}
		
	}

	/**
	 * Gets the value for a field.
	 * 
	 * @uses	Theater_Item::get_field() to get the value for a field.
	 * @since	0.16
	 * @internal
	 * @param	string	$name	The field name.
	 * @param 	array	$args	Not used.
	 * @return	mixed			The value for the field.
	 */
	function __call( $name, $args ) {

		// Handle deprecated usage of the $args['html'] param.
		if ( !empty ($args[0]['html']) ) {		
			if (!empty($args[0]['filters'])) {
				return $this->get_field_html( $name, $args[0]['filters'] );
			}
			return $this->get_field_html( $name );
		}
		
		// Handle deprecated usage of '{field}_html' item methods.
		$name_parts = explode('_', $name);
		if ('html' == $name_parts[count($name_parts) - 1]) {
			array_pop($name_parts);
			$name = implode('_', $name_parts);
			if (!empty($args[0])) {
				return $this->get_field_html( $name, $args[0] );
			}
			return $this->get_field_html( $name );
		}
		
		return $this->get_field( $name );
	}
	
	/**
	 * Gets the HTML output for a field.
	 * 
	 * @uses 	Theater_Item::get_field_html() to get the HTML output for a field.
	 * @since	0.16
	 * @internal
	 * @param 	string	$name	The field name.
	 * @return 	string			The field HTML output.
	 */
	function __get( $name ) {
		$value = $this->get_field_html($name);
		return $value;	
	}
	
	/**
	 * Gets the HTML output of this item.
	 * 
	 * @since	0.16
	 * @internal
	 * @return 	string	The HTML output of this item.
	 */
	function __toString() {
		$html = $this->get_html();
		return $html;
	}
	
	/**
	 * Applies template filters to a field value string.
	 * 
	 * @access 	protected
	 * @since	0.16
	 * @param 	string 								$value		The value.
	 * @param 	WPT_Template_Placeholder_Filter[] 	$filters	The template filters.
	 * @return	string											The filtered value.
	 */
	protected function apply_template_filters( $value, $filters ) {
		foreach ( $filters as $filter ) {
			$value = $filter->apply_to( $value, $this );
		}
		return $value;
	}
	
	/**
	 * Gets the value for a field.
	 * 
	 * @since	0.16
	 * @uses	Theater_Event_Field::get() to get the value of a field.
	 * @param 	string 	$name		The field name.
	 * @return	mixed
	 */
	function get_field( $name ) {
		$field = new Theater_Event_Field($name, NULL, $this);
		return $field->get();		
	}
	
	/**
	 * Gets the HTML output for a field.
	 * 
	 * @since	0.16
	 * @uses	Theater_Event_Field::get_html() to get the HTML of a field value.
	 * @param 	string 	$name		The field name.
	 * @param 	array 	$filters 	(default: array())
	 * @return	string				The HTML output for a field.
	 */
	function get_field_html( $name, $filters = array() ) {
		$field = new Theater_Event_Field($name, $filters, $this);
		return $field->get_html();	
	}
	
	/**
	 * Gets a list of of available fields for this item.
	 * 
	 * @since	0.16
	 * @abstract
	 * @return 	array
	 */
	abstract function get_fields();
	
	/**
	 * Gets the HTML output of this item.
	 * 
	 * @since	0.16
	 * @abstract
	 * @return 	string	The HTML output of this item.
	 */
	abstract function get_html();
	
	/**
	 * Gets the name of this item.
	 * 
	 * @since	0.16
	 * @uses	Theater_Item::$name to get the name of this item.
	 * @return	string
	 */
	function get_name() {
		return static::name;
	}

	/**
	 * Get the post type of this item.
	 * 
	 * @since	0.16
	 * @uses	Theater_Item::$post_type_name to get the post type of this item.
	 * @return	string
	 */
	function get_post_type() {
		return static::post_type_name;
	}
	
	/**
	 * Checks if this item has a certain field.
	 * 
	 * @since	0.16
	 * @param	string	$name
	 * @uses	Theater_Item::get_fields() to get all fields of an item.
	 * @return 	bool
	 */
	function has_field( $name ) {
		$has_field = in_array( $name, $this->get_fields() );
		return $has_field;
	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	function html( $template = '' ) {
		if ( is_array( $template ) ) {
			$defaults = array(
				'template' => '',
			);
			$args = wp_parse_args( $template, $defaults );
			$template = $args['template'];
		}

		$this->template = $template;
		return $this->get_html();
	}
	
	/**
	 * @deprecated	0.4	
	 * @internal
	 */
	function compile() {
		return $this->html();
	}

	/**
	 * @deprecated	0.4	
	 * @internal
	 */
	function render() {
		echo $this->html();
	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	function post_class() {
		$classes = array();
		$classes[] = static::post_type_name;
		return implode( ' ',$classes );
	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	public function post() {
		return $this->get_post();
	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	protected function get_post() {
		if ( ! isset( $this->post ) ) {
			$this->post = get_post( $this->ID );
		}
		return $this->post;
	}
	

}

?>
