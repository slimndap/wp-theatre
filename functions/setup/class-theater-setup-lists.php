<?php
/**
 * Setup Lists.
 *
 * @since	0.16
 * @package	Theater/Setup
 * @internal
 */
class Theater_Setup_Lists {

	static function init() {
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );		
	}
	
	/**
	 * Adds the page selectors for seasons and categories to the public query vars.
	 *
	 * This is needed to make `$wp_query->query_vars['wpt_category']` work.
	 *
	 * @since 0.10
	 *
	 * @param 	array $vars	The current public query vars.
	 * @return 	array		The new public query vars.
	 */
	static function add_query_vars( $vars ) {
		$vars[] = 'wpt_day';
		$vars[] = 'wpt_month';
		$vars[] = 'wpt_year';
		$vars[] = 'wpt_category';
		$vars[] = 'wpt_season';
		return $vars;
	}

}