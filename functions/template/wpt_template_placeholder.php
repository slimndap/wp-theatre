<?php

/**
 * Template Placeholder.
 *
 * Decodes a template placeholder tag into a field, field_args and filters.
 * Passes the object (a production or an event) to the filters.
 *
 * @since	0.12.1
 */
class WPT_Template_Placeholder {

	/**
	 * The placeholder tag.
	 * This is the full text that placed inside the curly brackets.
	 *
	 * @since	0.12.1
	 * @var	string
	 */
	public $tag;

	/**
	 * The field.
	 *
	 * @since	0.12.1
	 * @var 	string
	 */
	public $field;

	/**
	 * Arguments for the field (optional).
	 * Eg. the 'thumbnail'-field can have an optional 'size' argument:
	 * {{thumbnail('full')}}
	 *
	 * @since	0.12.1
	 * @var		array
	 */
	public $field_args = array();

	/**
	 * Array of WPT_Template_Filter objects.
	 * Filters to apply to the value (optional).
	 *
	 * @since	0.12.1
	 * @var		array
	 */
	public $filters = array();
	
	public $object;

	/**
	 * @param	string	$tag	The placeholder tag.
	 * @param 	mixed	$object	The object (a production or an event).
	 * @return 	void
	 */
	function __construct($tag, $object) {
		$this->tag = $tag;
		$this->object = $object;

		$this->field = $this->get_field();
		$this->field_args = $this->get_field_args();
		$this->filters = $this->get_filters();
	}

	/**
	 * Get the arguments from a function styled string.
	 *
	 * Example:
	 * - <full> is an argument of <thumbnail('full')>.
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @param 	string	$function
	 * @return	array				The arguments.
	 */
	protected function get_arguments($filter) {
		$arguments = array();

		$brackets_open = strpos( $filter, '(' );
		$brackets_close = strpos( $filter, ')' );
		if (
			false !== $brackets_open &&
			false !== $brackets_close &&
			$brackets_open < $brackets_close
		) {
			$arguments = explode( ',',substr( $filter, $brackets_open + 1, $brackets_close - $brackets_open -1 ) );
			$arguments = $this->sanitize_arguments( $arguments );
		}
		return $arguments;
	}

	/**
	 * Gets the field.
	 *
	 * @since 	0.12.1
	 * @access	protected
	 * @return 	string		The field.
	 */
	protected function get_field() {
		$placeholder_parts = $this->get_parts();

		if ( empty($placeholder_parts[0]) ) {
			return false;
		}

		return $this->get_function_name( $placeholder_parts[0] );
	}

	/**
	 * Gets the field arguments.
	 *
	 * @since 	0.12.1
	 * @access	protected
	 * @return 	array		The field arguments.
	 */
	protected function get_field_args() {
		$placeholder_parts = $this->get_parts();

		if ( empty($placeholder_parts[0]) ) {
			return false;
		}

		return $this->get_arguments( $placeholder_parts[0] );
	}

	/**
	 * Gets the filters.
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @return 	array		An array of WPT_Template_Placeholder_Filter objects.
	 */
	protected function get_filters() {
		$placeholder_parts = $this->get_parts();

		$template_filters = array();

		$filters = array();
		if ( ! empty($placeholder_parts[1]) ) {
			$filters = $placeholder_parts;
			array_shift( $filters );
		}

		foreach ( $filters as $filter ) {
			$template_filters[] = new WPT_Template_Placeholder_Filter(
				$this->get_function_name( $filter ),
				$this->get_arguments( $filter ),
				$this->object
			);
		}

		return $template_filters;
	}

	/**
	 * Get the function name from a function styled string.
	 *
	 * Example:
	 * - <thumbnail> is the function name of <thumbnail('full')>.
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @param 	string		$function
	 * @return	string					The function name.
	 */
	protected function get_function_name($filter) {
		$brackets_open = strpos( $filter, '(' );
		$brackets_close = strpos( $filter, ')' );
		if (
			false !== $brackets_open &&
			false !== $brackets_close &&
			$brackets_open < $brackets_close
		) {
			return trim( substr( $filter, 0, $brackets_open ) );
		} else {
			return trim( $filter );
		}
	}

	/**
	 * Gets the field and filters parts of the placeholder tag.
	 *
	 * Example:
	 * - <thumbnail('full')> is the field part of <thumbnail('full')|permalink>.
	 * - <permalink> is the filter part of <thumbnail('full')|permalink>.
	 *
	 * @since	0.12.1
	 * @access 	protected
	 * @return 	array(2)	The field part and the filter part as strings.
	 */
	protected function get_parts() {
		$placeholder_parts = explode( '|',$this->tag );
		return $placeholder_parts;
	}

	/*
	 * Sanitizes arguments.
	 * Removes surrounding quotes.
	 *
	 * @since 	0.12.1
	 * @param 	array	$arguments 	The arguments.
	 * @return	array				The sanitized arguments.
	 */
	private function sanitize_arguments($arguments) {
		if ( ! empty($arguments) && is_array( $arguments ) ) {
			for ( $i = 0;$i < count( $arguments );$i++ ) {
				$arguments[ $i ] = trim( $arguments[ $i ],'"' );
				$arguments[ $i ] = trim( $arguments[ $i ],"'" );
			}
		}
		return $arguments;
	}
}