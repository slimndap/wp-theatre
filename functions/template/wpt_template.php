<?php

/**
 * Template class.
 * Merges an object template with object values using template placeholders.
 *
 * See the shortcode docs for examples:
 * https://github.com/slimndap/wp-theatre/wiki/Shortcodes
 *
 * This class is the parent class of WPT_Production_Template and WPT_Event_Template.
 *
 * @since 0.12.1
 */
class WPT_Template {

	/**
	 * The template.
	 * 
	 * @since 	0.12.1
	 * @var 	string
	 * @access 	protected
	 */
	protected $template;

	/**
	 * The object.
	 * 
	 * @since	0.12.1
	 * @var 	mixed
	 * @access 	protected
	 */
	protected $object;

	/**
	 * @since 	0.12.1
	 * @param 	mixed 	$object		The object.
	 * @param 	string	$template	The template (optional).
	 */
	function __construct($object, $template='') {
		$this->object = $object;

		if (empty($template)) {
			$template = $this->get_default();
		}
		$this->template = $template;		
	}

	/**
	 * Gets the default template.
	 * 
	 * Child classes should overwrite this method.
	 *
	 * @since	0.12.1
	 * @access 	protected
	 * @return 	string		The default template.
	 */
	protected function get_default() {
		return '';
	}

	/**
	 * Gets all placeholders inside the template.
	 *
	 * Finds all placeholders by looking for tags between curly brackets.
	 * Examples:
	 * - {{title}}
	 * - {{thumbnail('large')}}
	 * - {{title|permalink}}
	 * - {{startdate|date('D d')}}
	 * - {{startdate|date('D d')|permalink}}
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @return 	array		An array of WPT_Template_Placeholder objects.
	 */
	protected function get_placeholders() {
		$placeholders = array();

		$tags = array();
		preg_match_all( '~{{(.*?)}}~', $this->template, $tags );

		foreach ( $tags[1] as $tag ) {
			$placeholders[] = new WPT_Template_Placeholder( $tag, $this->object );
		}

		return $placeholders;
	}

	/**
	 * Gets the value for a field from the template object.
	 *
	 * Child classes should overwrite this method.
	 * @see 	WPT_Production_Template::get_field_value()
	 * @see 	WPT_Event_Template::get_field_value()
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @param 	string	$field		The field.
	 * @param 	array 	$args		Arguments for the field (optional).
	 * 								Eg. the 'thumbnail'-field can have an optional 'size' argument:
	 *								{{thumbnail('full')}}
	 * @param 	array 	$filters 	Array of WPT_Template_Placeholder_Filter objects.
	 *								Filters to apply to the value (optional).
	 *
	 * @return 	string				The value.
	 */
	protected function get_field_value($field, $args = array(), $filters = array()) {
		return '';
	}

	/**
	 * Merges the template with the placeholder field values.
	 *
	 * @since	0.12.1
	 * @access	protected
	 * @return  void
	 */
	protected function merge() {
		foreach ( $this->get_placeholders() as $placeholder ) {
			$field_value = $this->get_field_value(
				$placeholder->field,
				$placeholder->field_args,
				$placeholder->filters
			);
			$this->template = str_replace( '{{'.$placeholder->tag.'}}', $field_value, $this->template );
		}
	}

	/**
	 * Gets the merged template.
	 *
	 * @since	0.12.1
	 * @access 	public
	 * @return 	string	The merged template.
	 */
	public function get_merged() {
		$this->merge();
		return $this->template;
	}

}