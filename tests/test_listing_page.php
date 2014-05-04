<?php

class WPT_Test_Listing_Page extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		
		parent::setUp();
		
		$this->wp_theatre = $wp_theatre;
		
		// create a page for our listing
		$args = array(
			'post_type'=>'page'
		);
		$this->listing_page = $this->factory->post->create($args);		
		update_option('wpt_listing_page', $this->listing_page);
				
	}


	/* Test the basics */

	function test_dedicated_listing_page_is_set() {
		
	}
	
	function test_listing_appears_on_page() {
		
	}
		
	/* 
	 * Test output 
	 * 
	 * type (productions, events)
	 * pagination (month, category)
	 * grouping (month)
	 * template 
	 */

	function test_listing_productions() {
		
	}
	
	function test_listing_productions_are_paginated_by_day() {
		
	}
	
	function test_listing_productions_are_paginated_by_week() {
		
	}
	
	function test_listing_productions_are_paginated_by_month() {
		
	}
	
	function test_listing_productions_are_paginated_by_year() {
		
	}
	
	function test_listing_productions_are_paginated_by_season() {
		
	}
	
	function test_listing_productions_are_paginated_by_category() {
		
	}

	function test_listing_productions_are_grouped_by_day() {
		
	}
	
	function test_listing_productions_are_grouped_by_week() {
		
	}
	
	function test_listing_productions_are_grouped_by_month() {
		
	}
	
	function test_listing_productions_are_grouped_by_year() {
		
	}
	
	function test_listing_productions_are_grouped_by_category() {
		
	}
	
	function test_listing_productions_are_filtered_by_day() {
		
	}
	
	function test_listing_productions_are_filtered_by_week() {
		
	}
	
	function test_listing_productions_are_filtered_by_month() {
		
	}
	
	function test_listing_productions_are_filtered_by_year() {
		
	}
	
	function test_listing_productions_are_filtered_by_season() {
		
	}
	
	function test_listing_productions_are_filtered_by_category() {
		
	}
	
}
