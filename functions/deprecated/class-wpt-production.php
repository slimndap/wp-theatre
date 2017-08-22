<?php
/**
 * WPT_Production class.
 *
 * @package	Theater/Production
 * @deprecated	0.16
 */
class WPT_Production extends Theater_Event {

	/**
	 * @deprecated	0.16
	 */
	function custom( $name, $args = array() ) {

		_deprecated_function( 'Theater_Event::custom()', '0.16', 'Theater_Event::get_field()' );

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
}

/**
 * Handles deprecated event field HTML filters.
 * @deprecated	0.16
 */
function deprecated_wpt_production_field_html_filter( $html, $field, $filters, $event, $data) {
	
	switch ($field) {
		case 'thumbnail' :
			if (empty($data['size'])) {
				$size = '';
			} else {
				$size = $data['size'];
			}
			$html = apply_filters( 'wpt/production/'.$field.'/html', $html, $size, $filters, $event );
			break;
		case 'prices_summary' :
			$html = apply_filters( 'wpt/production/prices/summary/html', $html, $event );
		default :
			$html = apply_filters( 'wpt/production/'.$field.'/html', $html, $event );
	}
	
	$html = apply_filters( 'wpt_production_'.$field.'_html', $html, $event );
	
	return $html;
}
add_filter('theater/event/field/html', 'deprecated_wpt_production_field_html_filter', 10, 5);

/**
 * Handles deprecated event field filters.
 * @deprecated	0.16
 */
function deprecated_wpt_production_field_filters($value, $field, $event) {

	switch($field) {
		case 'dates' :
		case 'prices' :
		case 'thumbnail' :
			$value = apply_filters( 'wpt/production/'.$field, $value, $event );		
			break;
		case 'dates_summary' :
			$value = apply_filters( 'wpt/production/dates/summary', $value, $event );
			break;
		case 'prices_summary' :
			$value = apply_filters( 'wpt/production/prices/summary', $value, $event );
			break;
		default :
			$value = apply_filters( 'wpt/production/'.$field, $value, $field, $event );
	}
	
	switch($field) {
		case 'categories' :
		case 'cities' :
		case 'content' :
		case 'dates' :
		case 'excerpt' :
		case 'permalink' :
		case 'thumbnail' :
		case 'title' :
			$value = apply_filters( 'wpt_production_'.$field, $value, $event );
			break;
		default:
			$value = apply_filters( 'wpt_production_'.$field, $value, $field, $event );
	}
	
	return $value;
}
add_filter('theater/event/field', 'deprecated_wpt_production_field_filters', 10 , 3);?>