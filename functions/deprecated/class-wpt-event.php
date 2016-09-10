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
	/**
	 * @deprecated 0.4
	 * @internal
	 */
	function get_production() {
		_deprecated_function( 'Theater_Event_Date::get_production()', '0.4', 'Theater_Event_Dates::get_event()' );
		return $this->get_event();
	}

	/**
	 * @deprecated 0.4 Use $event->prices() instead.
	 * @internal
	 */
	function summary() {
		global $wp_theatre;
		if ( ! isset( $this->summary ) ) {
			$args = array(
			'summary' => true,
			);
			$this->summary = array(
			'prices' => $this->prices( $args ),
			);
		}
		return $this->summary;
	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	function datetime( $enddate = false ) {

		if ( true === $enddate ) {
			$value = $this->enddatetime();
		} else {
			$value = $this->startdatetime( $enddate );
		}

		return $value;
	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	function datetime_html( $filters = array() ) {
		$html = $this->get_field_html( 'startdatetime', $filters );

		return $html;
	}
}
	

/**
 * Handles deprecated date field HTML filters.
 * @deprecated	0.16
 */
function deprecated_wpt_event_field_html_filter( $html, $field, $filters, $date) {
	switch($field) {
		case 'datetime' :
		case 'enddate' :
		case 'endtime' :
		case 'startdate' :
		case 'starttime' :
			$html = apply_filters( 'wpt/event/'.$field.'/html', $html, $filters, $date );		
			break;
		case 'tickets_url' :
			$html = apply_filters( 'wpt/event/tickets/url/html', $html, $date );
			break;
		case 'tickets_status' :
			$html = apply_filters( 'wpt/event/tickets/status/html', $html, $date );
			break;
		default: 
			$html = apply_filters( 'wpt/event/'.$field.'/html', $html, $date );
	}

	switch($field) {
		case 'city' :
		case 'location' :
		case 'remark' :
		case 'tickets_prices' :
		case 'tickets' :
		case 'tickets_url' :
		case 'title' :
		case 'venue' :
			$html = apply_filters( 'wpt_event_'.$field.'_html', $html, $date );
			break;
		case 'prices' :
			$html = apply_filters( 'wpt_event_tickets_prices_html', $html, $date );
			break;
		default :
			$html = apply_filters( 'wpt_event_'.$field.'_html', $html, $field, $date );
	}

	return $html;
}
add_filter('theater/date/field/html', 'deprecated_wpt_event_field_html_filter', 10, 4);

/**
 * Handles deprecated date field filters.
 * @deprecated	0.16
 */
function deprecated_wpt_event_field_filters($value, $field, $date) {

	switch($field) {
		case 'prices_summary' :
			$value = apply_filters( 'wpt/event/prices/summary', $value, $date );
			break;
		case 'tickets_button' :
			$value = apply_filters( 'wpt/event/tickets/button', $value, $date );
			break;
		case 'tickets_url' :
			$value = apply_filters( 'wpt/event/tickets/url', $value, $date );
			break;
		case 'tickets_url_iframe' :
			$value = apply_filters( 'wpt/event/tickets/url/iframe', $value, $date );
			break;
		case 'tickets_status' :
			$value = apply_filters( 'wpt/event/tickets/status', $value, $date );
			break;
		case 'tickets_status' :
			$value = apply_filters( 'wpt/event/tickets/status', $value, $date );
			break;
		default :
			$value = apply_filters( 'wpt/event/'.$field, $value, $date );
	}

	switch($field) {
		case 'datetime' :
		case 'duration' :
		case 'location' :
		case 'prices' :
		case 'remark' :
		case 'tickets' :
		case 'tickets_status' :
		case 'tickets_url' :
		case 'title' :
		case 'venue' :
			$value = apply_filters('wpt_event_'.$field, $value, $date);
			break;
		default:
			$value = apply_filters( 'wpt_event_'.$field, $value, $field, $date );
	}
	
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

function deprecated_wpt_event_html($html, $template, $event) {
	$html = apply_filters( 'wpt/event/html', $html, $template, $event );
	$html = apply_filters( 'wpt_event_html', $html, $event );
	return $html;
}
add_filter('theater/date/html', 'deprecated_wpt_event_html', 10 , 3);

