<?php

/**
 * Add context information to listings, productions and events.
 * @since	0.14.7
 */
class WPT_Context {
	
	/**
	 * The value of the current context.
	 * 
	 * @since	0.15.2
	 * @var		string
	 * @access 	private
	 */
	private $context = '';
	
	function __construct() {

		add_filter( 'wpt/listing/classes', array( $this, 'add_context_to_listing_classes' ), 10, 2 );

		add_filter( 'shortcode_atts_wpt_events', array( $this, 'add_context_to_listing_shortcode' ), 10, 3 );
		add_filter( 'shortcode_atts_wpt_production_events', array( $this, 'add_context_to_production_events_shortcode' ), 10, 3 );
		add_filter( 'shortcode_atts_wpt_productions', array( $this, 'add_context_to_listing_shortcode' ), 10, 3 );

		add_filter( 'wpt/event/html', array( $this, 'add_event_html_context_filter' ), 10, 3 );
		add_filter( 'wpt/production/html', array( $this, 'add_production_html_context_filter' ), 10, 3 );
		
		add_action( 'wpt/listing/html/before', array($this, 'set_context'));
		add_action( 'wpt/listing/html/after', array($this, 'reset_context'));

	}

	/**
	 * Adds a 'wpt_context_*' class to a listing.
	 *
	 * @since	0.14.7
	 * @param	array	$classes	The classes of the listing.
	 * @param 	array	$args		The args of the listing.
	 * @return	array				The classes of the listing, with the context class added.
	 */
	function add_context_to_listing_classes( $classes, $args ) {
		$context = empty( $args['context'] ) ? 'default' : $args['context'];

		$context_class = 'wpt_context_'.$context;

		if ( in_array( $context_class, $classes ) ) {
			return $classes;
		}

		$classes[] = 'wpt_context_'.$context;
		return $classes;
	}

	/**
	 * Adds support for a 'context' parameter to [wpt_productions] and [wpt_events] shortcodes.
	 *
	 * @since	0.14.7
	 * @param 	array	$out		The output array of shortcode attributes.
	 * @param 	array	$pairs		The supported attributes and their defaults.
	 * @param 	array	$atts		The user defined shortcode attributes.
	 * @return	array				The output array of shortcode attributes, including the context.
	 */
	function add_context_to_listing_shortcode( $out, $pairs, $atts ) {
		if ( empty( $atts['context'] ) ) {
			return $out;
		}
		$out['context'] = $atts['context'];
		return $out;
	}

	/**
	 * Sets the 'context' parameter for the [wpt_production_events] shortcode.
	 *
	 * @since	0.14.7
	 * @param 	array	$out		The output array of shortcode attributes.
	 * @param 	array	$pairs		The supported attributes and their defaults.
	 * @param 	array	$atts		The user defined shortcode attributes.
	 * @return	array				The output array of shortcode attributes, including the context.
	 */
	function add_context_to_production_events_shortcode( $out, $pairs, $atts ) {
		$out['context'] = 'production_events';
		return $out;
	}

	/**
	 * Adds a new context-aware filter to the event HTML output.
	 *
	 * @since	0.14.7
	 * @param 	string				$html		The HTML of the event.
	 * @param	WPT_Event_Template	$template	The event template
	 * @param	array				$args		The listing args (if the event is part of a listing).
	 * @param	WPT_Event			$event		The event.
	 * @return	string							The filtered HTML of the event.
	 */
	function add_event_html_context_filter( $html, $template, $event ) {	
		/**
		 * Filter the HTML of an event, based on its context.
		 *
		 * @since	0.14.7
		 * @since	0.15.2				Removed the $args param.
		 * @param	string				$html		The HTML of an event.
		 * @param	WPT_Event_Template	$template	The event template.
		 * @param	WPT_Event			$event		The event.
		 */
		$html = apply_filters( 'wpt/event/html/context='.$this->get_context(), $html, $template, $event );
		return $html;
	}

	/**
	 * Adds a new context-aware filter to the production HTML output.
	 *
	 * @since	0.14.7
	 * @param 	string			$html		The HTML of the production.
	 * @param	WPT_Production	$production	The production.
	 * @param	array			$args		The listing args (if the production is part of a listing).
	 * @return	string						The filtered HTML of the production.
	 */
	function add_production_html_context_filter( $html, $template, $production ) {
		/**
		 * Filter the HTML of a production, based on its context.
		 *
		 * @since	0.14.7
		 * @since	0.15.2				Removed the $args param.
		 *								Added the $template param.
		 * @param	string				$html		The HTML of an production.
		 * @param	WPT_Event_Template	$template	The event template.
		 * @param	WPT_Production		$production	The production.
		 */
		$html = apply_filters( 'wpt/production/html/context='.$this->get_context(), $html, $template, $production );
		return $html;
	}
	
	/**
	 * Gets the current context.
	 * 
	 * @since	0.15.2
	 * @return	string	The current context.
	 */
	public function get_context() {
		$context = $this->context;
		if (empty($this->context)) {
			$context = 'default';
		}
		return $context;
	}
	
	/**
	 * Resets the current context to its default value.
	 * 
	 * @since	0.15.2
	 * @return	string	The current context.
	 */
	function reset_context() {
		$this->context = '';
		return $this->get_context();
	}
	
	/**
	 * Sets the current context.
	 * 
	 * @since	0.15.2
	 * @param	string|array	$context	The context value of an array of listing args.
	 * @return	string						The current context.
	 */
	function set_context( $context ) {

		if (is_array($context)) {
			$context = empty($context['context']) ? false : $context['context'];
		}
		
		if (!empty($context)) {
			$this->context = $context;		
		}

		return $this->get_context();
		
	}
}
