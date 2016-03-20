<?php

/**
 * Add context information to listings, productions and events.
 * @since	0.14.7
 */
class WPT_Context {
	function __construct() {

		add_filter( 'wpt_listing_classes', array( $this, 'add_context_to_listing_classes' ), 10, 2 );

		add_filter( 'shortcode_atts_wpt_events', array( $this, 'add_context_to_listing_shortcode' ), 10, 3 );
		add_filter( 'shortcode_atts_wpt_production_events', array( $this, 'add_context_to_production_events_shortcode' ), 10, 3 );
		add_filter( 'shortcode_atts_wpt_productions', array( $this, 'add_context_to_listing_shortcode' ), 10, 3 );

		add_filter( 'wpt_event_html', array( $this, 'add_event_html_context_filter' ), 10, 3 );
		add_filter( 'wpt_production_html', array( $this, 'add_production_html_context_filter' ), 10, 3 );

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
	 * @param 	string		$html	The HTML of the event.
	 * @param	WPT_Event	$event	The event.
	 * @param	array		$args	The listing args (if the event is part of a listing).
	 * @return	string				The filtered HTML of the event.
	 */
	function add_event_html_context_filter( $html, $event, $args ) {
		$context = empty( $args['context'] ) ? 'default' : $args['context'];

		/**
		 * Filter the HTML of an event, based on its context.
		 *
		 * @since	0.14.7
		 * @param	string		$html	The HTML of an event.
		 * @param	WPT_Event	$event	The event.
		 * @param	array		$args	The listing args (if the event is part of a listing).
		 */
		$html = apply_filters( 'wpt/event/html/?context='.$context, $html, $event, $args );
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
	function add_production_html_context_filter( $html, $production, $args ) {
		$context = empty( $args['context'] ) ? 'default' : $args['context'];

		/**
		 * Filter the HTML of a production, based on its context.
		 *
		 * @since	0.14.7
		 * @param	string			$html		The HTML of an production.
		 * @param	WPT_Production	$production	The production.
		 * @param	array			$args		The listing args (if the production is part of a listing).
		 */
		$html = apply_filters( 'wpt/production/html/?context='.$context, $html, $production, $args );
		return $html;
	}
}
