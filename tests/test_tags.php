<?php
/**
 * Test the event and production tags.
 *
 * @group 	tags
 * @since	0.15.5
 */

class WPT_Test_Tags extends WPT_UnitTestCase {

	function test_are_productions_filtered_by_tag() {
		$this->setup_test_data();
		
		$html = do_shortcode( '[wpt_productions tag="historic"]' );
		
		$actual = $html;
		$expected = get_the_title($this->production_with_historic_event);

		$this->assertContains( $expected, $actual );
		
		$actual = substr_count($html, '"wp_theatre_prod"');
		$expected = 2;
		
		$this->assertEquals($expected, $actual, $html);
	}

	function test_are_events_filtered_by_tag() {
		$this->setup_test_data();
		
		$actual = do_shortcode( '[wpt_events tag="upcoming"]' );
		$expected = get_the_title($this->production_with_upcoming_event);

		$this->assertContains( $expected, $actual );
		
		$actual = substr_count(do_shortcode( '[wpt_events tag="upcoming"]' ), '"wp_theatre_event"');
		$expected = 1;
		
		$this->assertEquals($expected, $actual);
	}
	
	function test_are_tags_in_productions_list() {
		
		$this->setup_test_data();
		
		$actual = do_shortcode( '[wpt_productions]{{tags}}[/wpt_productions]' );

		$expected = '<ul class="wp_theatre_prod_tags">';		
		$this->assertContains( $expected, $actual );
		
		$expected = '<li class="wp_theatre_prod_tag wp_theatre_prod_tag_upcoming">upcoming';		
		$this->assertContains( $expected, $actual );
		
		$expected = '<li class="wp_theatre_prod_tag wp_theatre_prod_tag_historic">historic';		
		$this->assertContains( $expected, $actual );
		
	}
	
	function test_are_tags_in_events_list() {

		$this->setup_test_data();
		
		$actual = do_shortcode( '[wpt_events]{{title}}{{tags}}[/wpt_events]' );

		$expected = '<ul class="wp_theatre_prod_tags">';		
		$this->assertContains( $expected, $actual );
		
		$expected = '<li class="wp_theatre_prod_tag wp_theatre_prod_tag_upcoming">upcoming';		
		$this->assertContains( $expected, $actual );
		
	}


}
