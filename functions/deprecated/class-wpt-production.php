<?php
/**
 * WPT_Production class.
 *
 * @package	Theater/Production
 */
class WPT_Production extends Theater_Event {

}

function deprecated_wpt_production_field_html_filter( $html, $field, $event) {
	/*
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
	*/

	$html = apply_filters( 'wpt_production_'.$field.'_html', $html, $field, $event );
	return $html;
}
add_filter('theater/event/field/html', 'deprecated_wpt_production_field_html_filter', 10, 3);

/**
 * Handles deprecated date field filters.
 * @deprecated	0.16
 */
function deprecated_wpt_production_field_filters($value, $field, $event) {
	/*	
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
	*/

	$value = apply_filters( 'wpt_production_'.$field, $value, $field, $event );
	
	return $value;
}
add_filter('theater/event/field', 'deprecated_wpt_production_field_filters', 10 , 3);?>
