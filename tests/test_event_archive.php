<?php
/**
 * WPT_Test_Event_Archive class.
 *
 * @extends WP_UnitTestCase
 * @group	event-archive
 */
class WPT_Test_Event_Archive extends WPT_UnitTestCase {

	function setUp() {
		parent::setUp();
	}


	function test_are_events_on_archive() {

		$this->setup_test_data();

		$query = new WP_Query();

		// Fake is_main_query.
		$wp_the_query = $query;

		$events = $query->query( 'post_type=wp_theatre_prod' );

		$actual = count( $events );
		$expected = 0;

		$this->assertGreaterThan( $expected, $actual );

	}

	function test_are_past_events_hidden() {

		global $wp_the_query;

		$this->setup_test_data();

		$query = new WP_Query();

		// Fake is_main_query.
		$wp_the_query = $query;

		$events = $query->query( 'post_type=wp_theatre_prod' );

		// Expect only three upcoming event.
		// Stickies are ignored on archive pages.
		$actual = count( $events );
		$expected = 3;

		$this->assertEquals( $expected, $actual );

	}

	function test_are_events_ordered() {

		global $wp_the_query;

		$this->setup_test_data();

		$query = new WP_Query();

		// Fake is_main_query.
		$wp_the_query = $query;

		$events = $query->query( 'post_type=wp_theatre_prod' );

		$actual = $events[0]->ID;
		$expected = $this->production_with_upcoming_events;
		$this->assertEquals( $expected, $actual );

		$actual = $events[1]->ID;
		$expected = $this->production_with_upcoming_event;
		$this->assertEquals( $expected, $actual );

		$actual = $events[2]->ID;
		$expected = $this->production_with_upcoming_and_historic_events;
		$this->assertEquals( $expected, $actual );

	}
}
