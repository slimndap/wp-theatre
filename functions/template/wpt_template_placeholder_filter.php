<?php

/**
 * A template placeholder filter.
 *
 * Runs a template filter over a template placeholders field value.
 *
 * @since 	0.12.1
 */
class WPT_Template_Placeholder_Filter {

	/**
	 * The name of the filter.
	 * 
	 * @since 	0.12.1
	 * @var 	string
	 */
	var $name;

	/**
	 * The filter arguments.
	 *
	 * @since 	0.12.1
	 * @var 	array
	 * @access 	protected
	 */
	protected $args;

	/**
	 * The object (a production or event).
	 *
	 * @since 	0.12.1
	 * @var 	mixed
	 * @access 	protected
	 */
	protected $object;

	/**
	 * The callback for the filter.
	 *
	 * @since 	0.12.1
	 * @var 	array
	 * @access 	protected
	 */
	protected $callback;

	/**
	 * @param 	string 	$name	The name of the filter.
	 * @param 	array 	$args	The arguments of the filter.
	 * @param 	mixed 	$object	The object (a production or event).
	 * @return 	void
	 */
	function __construct($name, $args, $object) {
		$this->name = $name;
		$this->args = $args;
		$this->object = $object;
		$this->callback = $this->get_callback( $name );
	}

	/**
	 * Applies the filter to a content string.
	 *
	 * @since	0.12.1
	 * @since	0.15.11				No longer corrupts $this->args.
	 *								Fixes #215.
	 * @param 	string	$content
	 * @return 	string				The content string with the filter applied.
	 */
	public function apply_to($content) {
		
		/*
		 * Prepare the callback args.
		 */
		$callback_args = $this->args;
		array_unshift( $callback_args, $content, $this->object );
		
		$content = call_user_func_array( $this->callback, $callback_args );
		return $content;
	}

	/**
	 * The default filter callback.
	 *
	 * This callback is used for all filters that do not have a callback assigned.
	 *
	 * @since	0.12.1
	 * @access 	protected
	 * @param 	string		$content
	 * @param	mixed		$object		The object (a production or event).
	 * @return 	string					The unchanged content.
	 */
	protected function callback_default($content, $object) {
		return $content;
	}

	/**
	 * Runs the 'date' filter.
	 *
	 * Applies a date format to a date or time string.
	 *
	 * @since 	0.12.1
	 * @since	0.15.11.1	Added support for next day start time offset.
	 * @since	0.15.30		Don't use next day start time offset to determine time.
	 *						Fixes #266.
	 * @uses	Theater_Helpers_Time::get_next_day_start_time_offset() to get the next day start time offset.
	 *
	 * @access 	protected
	 * @param 	string		$content	A date or time string.
	 * @param 	mixed		$object		The object (a production or event).
	 * @param 	string 		$format		The desired date format.
	 * @return 	string					The string in the desired format.
	 */
	protected function callback_date($content, $object, $format = '') {
		if ( ! empty($format) ) {
			
			if ( is_numeric( $content ) ) {
				$timestamp = $content;
			} else {
				$timestamp = strtotime( $content );
			}
			
			/**
			 * Use next day start time offset to determine day.
			 */
			$day = date( 
				'Y-m-d', 
				$timestamp + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS - Theater_Helpers_Time::get_next_day_start_time_offset()
			);
			
			/**
			 * Don't use next day start time offset to determine time.
			 */
			$time = date( 'H:i:s', $timestamp + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			
			$content = date_i18n(
				$format,
				strtotime( $day.' '.$time )
			);
			
		}
		return $content;
	}

	/**
	 * Runs the 'tickets_url' filter.
	 *
	 * Surrounds a string with an '<a>' element that links to the tickets URL.
	 * Only works with events.
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @param 	string      $content
	 * @param 	mixed		$object		The event.
	 * @return 	string					The string with an '<a>' element that links to the tickets URL.
	 */
	protected function callback_tickets_url($content, $object) {
		if ( ! empty($content) ) {
			$tickets_url_args = array(
				'html' => true,
				'text' => $content,
			);
			if ( method_exists( $object, 'tickets_url' ) ) {
				$tickets_url_content = $object->tickets_url( $tickets_url_args );
				if ( ! empty($tickets_url_content) ) {
					$content = $tickets_url_content;
				}
			}
		}
		return $content;
	}

	/**
	 * Runs the 'permlaink' filter.
	 *
	 * Surrounds a string with an '<a>' element that links to the production detail page.
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @param 	string      $content
	 * @param 	mixed		$object		The object (a production or event).
	 * @return 	string					The string with an '<a>' element that links to the production detail page.
	 */
	protected function callback_permalink($content, $object) {
		if ( ! empty($content) ) {
			$permalink_args = array(
				'html' => true,
				'text' => $content,
				'inside' => true,
			);
			if ( method_exists( $object, 'permalink' ) ) {
				$content = $object->permalink( $permalink_args );
			}
		}
		return $content;
	}

	/**
	 * Runs the 'wpautop' filter.
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @param 	string      $content
	 * @param 	mixed		$object		The object (a production or event).
	 * @return 	string
	*/
	protected function callback_wpautop($content) {
		return wpautop( $content );
	}

	/**
	 * Gets the callback for a filter.
	 *
	 * @since 	0.12.1
	 * @access 	protected
	 * @param 	string	$name	The name of the filter.
	 * @return 	array			The callback.
	 */
	protected function get_callback($name) {
		$callbacks = $this->get_callbacks();

		if ( ! empty($callbacks[ $name ]) ) {
			$callback = $callbacks[ $name ];
		} else {
			$callback = array( $this, 'callback_default' );
		}

		return $callback;
	}

	/**
	 * Gets all available filters with their callbacks.
	 *
	 * @since	0.12.1
	 * @access 	protected
	 * @return 	array		All available filters with their callbacks.
	 */
	protected function get_callbacks() {
		$callbacks = array(
			'date' => array( $this, 'callback_date' ),
			'permalink' => array( $this, 'callback_permalink' ),
			'wpautop' => array( $this, 'callback_wpautop' ),
			'tickets_url' => array( $this, 'callback_tickets_url' ),
		);

		/**
		 * Filter the available filters with their callbacks.
		 *
		 * Use this to add new filters or to change a callback for ean existing filter.
		 *
		 * @since	0.12.1
		 * @param	$callback	The currently available filters with their callbacks.
		 */
		$callbacks = apply_filters( 'wpt/template/filters/callbacks',$callbacks );

		return $callbacks;
	}

}