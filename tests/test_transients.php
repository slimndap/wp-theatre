<?php
/**
 * WPT_Test_Transients class.
 * 
 * @group	transients
 */
class WPT_Test_Transients extends WPT_UnitTestCase {
	
	function get_events_args() {
		global $wp_query;
		
		/** 
		 * Copy the defaults from WPT_Frontend::wpt_events
		 * Set 'start' to 'now' (with quotes).
		 */
		$defaults = array(
			'cat' => false,
			'category' => false, // deprecated since v0.9.
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'tag' => false,
			'day' => false,
			'end' => false,
			'groupby' => false,
			'limit' => false,
			'month' => false,
			'order' => 'asc',
			'paginateby' => array(),
			'post__in' => false,
			'post__not_in' => false,
			'production' => false,
			'season' => false,
			'start' => 'now',
			'year' => false,
		);
		
		$unique_args = array_merge(
			array( 'atts' => $defaults ), 
			array( 'wp_query' => $wp_query->query_vars )
		);
		
		return $unique_args;
	}
	
	function get_production_args() {
		global $wp_query;

		$defaults = array(
			'paginateby' => array(),
			'post__in' => false,
			'post__not_in' => false,
			'upcoming' => false,
			'season' => false,
			'category' => false, // deprecated since v0.9.
			'cat' => false,
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'tag' => false,
			'start' => false,
			'start_before' => false,
			'start_after' => false,
			'end' => false,
			'end_after' => false,
			'end_before' => false,
			'groupby' => false,
			'limit' => false,
			'order' => 'asc',
			'ignore_sticky_posts' => false,
		);
		
		$unique_args = array_merge(
			array( 'atts' => $defaults ), 
			array( 'wp_query' => $wp_query->query_vars )
		);
		
		return $unique_args;		
	}
	
	/**
	 * test_wpt_transient_events function.
	 * 
	 * @group transients
	 */
	function test_wpt_transient_events() {
		global $wp_query;
		
		$this->setup_test_data();

		do_shortcode('[wpt_events]');
		
		$transient = new Theater_Transient( 'e', $this->get_events_args() );
		$actual = substr_count( $transient->get(), '"wp_theatre_event"' );
		
		$expected = 4;
		
		$this->assertEquals( $expected, $actual );
	}
	
	/*
	 * Tests if the transients don't mess up paginated views.
	 * See: https://github.com/slimndap/wp-theatre/issues/88
	 *
	 * @group transients
	 */
	function test_wpt_transient_events_with_pagination() {
		global $wp_query;
		
		$this->setup_test_data();

		/*
		 * Test if the film tab is active.
		 */
		$wp_query->query_vars['wpt_category'] = 'film';
		$html = do_shortcode('[wpt_events paginateby=category]');
		$this->assertContains('category-film wpt_listing_filter_active',$html);

		/*
		 * Test if the muziek tab is active.
		 */
		$wp_query->query_vars['wpt_category'] = 'muziek';
		$html = do_shortcode('[wpt_events paginateby=category]');
		$this->assertContains('category-muziek wpt_listing_filter_active',$html);
	}
	
	/**
	 * test_wpt_transient_reset function.
	 * 
	 * @group transients
	 */
	function test_wpt_transient_productions() {
		
		$this->setup_test_data();

		do_shortcode('[wpt_productions]');

		$transient = new Theater_Transient( 'p', $this->get_production_args() );
		$actual = substr_count( $transient->get(), '"wp_theatre_prod"' );
		$expected = 5;
		$this->assertEquals( $expected, $actual, $transient->get() );					
	}
	
	function test_wpt_transient_reset() {
		global $wp_query;

		$this->setup_test_data();

		do_shortcode('[wpt_productions]');
		
		$this->factory->post->create(); // trigger save_post hook
				
		$transient = new Theater_Transient( 'p', $this->get_production_args() );
		$actual = $transient->get();
		
		$this->assertFalse( $actual );					
	}
	
	function test_wpt_transient_is_registered() {
		global $wp_query;
		
		$this->setup_test_data();

		do_shortcode('[wpt_events]');
		
		$actual = count( Theater_Transients::get_transient_keys() );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		$actual = Theater_Transients::get_transient_keys();
		$transient = new Theater_Transient();
		$expected = $transient->calculate_key('e', $this->get_events_args() );
		$this->assertContains( $expected, $actual );
	}
	
	function test_wpt_transient_is_unregistered() {
		global $wp_query;
		
		$this->setup_test_data();

		do_shortcode('[wpt_events]');
		
		$actual = count( Theater_Transients::get_transient_keys() );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		$this->factory->post->create(); // trigger save_post hook

		$actual = Theater_Transients::get_transient_keys();
		$this->assertEmpty( $actual );
	}
	
	function test_transients_are_off_for_logged_in_users() {
		$this->setup_test_data();

		do_shortcode('[wpt_productions]');
		
		$transient = new Theater_Transient( 'p', $this->get_production_args() );
		$actual = substr_count( $transient->get(), '"wp_theatre_prod"' );
		$expected = 5;
		$this->assertEquals( $expected, $actual, $transient->get() );					
		

		$user = new WP_User( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		wp_set_current_user( $user->ID );		

		$actual = $transient->get();		
		$this->assertFalse( $actual );					

		wp_set_current_user(0);		
	}
	
	/*
	 * Tests if the transients don't mess up paginated views.
	 * See: https://github.com/slimndap/wp-theatre/issues/88
	 */
	function test_wpt_transient_productions_with_pagination() {
		$this->setup_test_data();

		global $wp_query;
		
		/*
		 * Test if the film tab is active.
		 */
		$wp_query->query_vars['wpt_category'] = 'film';
		$html = do_shortcode('[wpt_productions paginateby=category]');
		$this->assertContains('category-film wpt_listing_filter_active',$html);

		/*
		 * Test if the muziek tab is active.
		 */
		$wp_query->query_vars['wpt_category'] = 'muziek';
		$html = do_shortcode('[wpt_productions paginateby=category]');
		$this->assertContains('category-muziek wpt_listing_filter_active',$html);
	}
	
	function test_if_corrupted_list_of_transients_is_emptied() {
		
		$this->setup_test_data();
		update_option( THEATER_TRANSIENTS_OPTION, 'not a valid json object' );
		
		$actual = Theater_Transients::get_transient_keys();
		$expected = array();
		$this->assertEquals( $expected, $actual );
		
		
	}

}