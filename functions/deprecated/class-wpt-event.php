<?php

/**
 * WPT_Event class.
 * 
 * @extends 	Theater_Date
 * @deprecated	0.16
 * @package		Theater/Deprecated
 */
class WPT_Event extends Theater_Event_Date {
	
	function __construct($ID = false, $PostClass = false) {
		$this->PostClass = $PostClass;		
		parent::__construct($ID);
	}
	
	function post_type() {
		return get_post_type_object( parent::post_type_name );
	}

	/**
	 * Event production.
	 *
	 * Returns the production of the event as a WPT_Production object.
	 *
	 * @since 	0.4
	 * @since	0.15	Removed local caching of event production.
	 *					Return <false> if no production is set.
	 *
	 * @return 	WPT_Production 	The production.
	 *							Returns <false> if no production is set.
	 */
	function production() {
		$production = $this->get_event();	
		return new WPT_Production( $production->ID, $this->PostClass );
	}

	/**
	 * @deprecated 0.12
	 * @see WPT_Event::startdate()
	 * @see WPT_Event::enddate()
	 */
	function date( $deprecated = array() ) {
		if ( empty( $deprecated['html'] ) ) {
			if ( isset( $deprecated['start'] ) && false === $deprecated['start'] ) {
				_deprecated_function( 'WPT_Event::date()', '0.12', 'WPT_Event::enddate()' );
				return $this->enddate( $deprecated );
			} else {
				_deprecated_function( 'WPT_Event::date()', '0.12', 'WPT_Event::startdate()' );
				return $this->startdate( $deprecated );
			}
		} else {
			$filters = array();
			if ( ! empty( $deprecated['filters'] ) ) {
				$filters = $deprecated['filters'];
			}

			if ( isset( $deprecated['start'] ) && false === $deprecated['start'] ) {
				_deprecated_function( 'WPT_Event::date_html()', '0.12', 'WPT_Event::enddate_html()' );
				return $this->enddate_html( $filters );
			} else {
				_deprecated_function( 'WPT_Event::date_html()', '0.12', 'WPT_Event::startdate_html()' );
				return $this->startdate_html( $filters );
			}
		}
	}

	/**
	 * @deprecated	0.16
	 */
	function custom( $name, $args = array(), $fallback_to_production = true ) {

		_deprecated_function( 'Theater_Date::custom()', '0.16', 'Theater_Date::get_field()' );

		$defaults = array(
			'html' => false,
			'filters' => array(),
		);
		$args = wp_parse_args( $args, $defaults );
		if ( $args['html'] ) {
			return $this->get_field_html($name, $args['filters']);
		} else {
			return $this->get_field($name, $args['filters']);
		}

	}


	/**
	 * @deprecated 0.12
	 * @see WPT_Event::starttime()
	 * @see WPT_Event::endtime()
	 */
	function time( $deprecated = array() ) {
		if ( empty( $deprecated['html'] ) ) {
			if ( isset( $deprecated['start'] ) && false === $deprecated['start'] ) {
				_deprecated_function( 'WPT_Event::time()', '0.12', 'WPT_Event::endtime()' );
				return $this->endtime( $deprecated );
			} else {
				_deprecated_function( 'WPT_Event::time()', '0.12', 'WPT_Event::starttime()' );
				return $this->starttime( $deprecated );
			}
		} else {
			$filters = array();
			if ( ! empty( $deprecated['filters'] ) ) {
				$filters = $deprecated['filters'];
			}

			if ( isset( $deprecated['start'] ) && false === $deprecated['start'] ) {
				_deprecated_function( 'WPT_Event::time()', '0.12', 'WPT_Event::endtime_html()' );
				return $this->endtime_html( $filters );
			} else {
				_deprecated_function( 'WPT_Event::time()', '0.12', 'WPT_Event::starttime_html()' );
				return $this->starttime_html( $filters );
			}
		}
	}
}
	

function deprecated_wpt_event_field_html_filter( $html, $field, $date) {
	switch($field) {
		case 'endtime' :
		case 'startdate' :
		case 'starttime' :
		case 'prices' :
		case 'tickets' :
			$html = apply_filters( 'wpt/event/'.$field.'/html', $html, $field, $date );
			break;
		case 'tickets_url' :
			$html = apply_filters( 'wpt/event/tickets/url/html', $html, $field, $date );
			break;
		case 'tickets_status' :
			$html = apply_filters( 'wpt/event/tickets/status/html', $html, $field, $date );
			break;
	}

	$html = apply_filters( 'wpt_event_'.$field.'_html', $html, $field, $date );
	return $html;
}
add_filter('theater/date/field/html', 'deprecated_wpt_event_field_html_filter', 10, 3);

/**
 * Handles deprecated date field filters.
 * @deprecated	0.16
 */
function deprecated_wpt_event_field_filters($value, $field, $date) {
	
	switch($field) {
		case 'enddate' :
		case 'endtime' :
		case 'startdate' :
		case 'starttime' :
		case 'prices' :
		case 'tickets' :
			$value = apply_filters( 'wpt/event/'.$field, $value, $date );
			break;
		case 'prices_summary' :
			$value = apply_filters( 'wpt/event/prices/summary', $value, $date );
			break;
		case 'tickets_button' :
			$value = apply_filters( 'wpt/event/tickets/button', $value, $date );
			break;
		case 'tickets_url' :
			$value = apply_filters( 'wpt/event/tickets/url', $value, $date );
			break;
		case 'tickets_status' :
			$value = apply_filters( 'wpt/event/tickets/status', $value, $date );
			break;
	}

	$value = apply_filters( 'wpt_event_'.$field, $value, $field, $date );
	
	return $value;
}
add_filter('theater/date/field', 'deprecated_wpt_event_field_filters', 10 , 3);

/**
 * Handle deprecated field HTML filters.
 */

function deprecated_wpt_event_enddate_html_filter($html, $filters, $event) {
	$html = apply_filters( 'wpt/event/enddate/html', $html, $filters, $event );
	return $html;
}
add_filter('theater/date/field/enddate/html', 'deprecated_wpt_event_enddate_html_filter', 10 , 3);

function deprecated_wpt_event_endtime_html_filter($html, $filters, $event) {
	$html = apply_filters( 'wpt/event/endtime/html', $html, $filters, $event );
	return $html;
}
add_filter('theater/date/field/endtime/html', 'deprecated_wpt_event_endtime_html_filter', 10 , 3);

