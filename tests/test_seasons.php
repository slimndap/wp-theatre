<?php
/**
 * Test the seasons.
 *
 * @group 	seasons
 * @since	0.15.3
 */

class WPT_Test_Seasons extends WPT_UnitTestCase {

	function test_seasons_are_loaded() {
		$this->setup_test_data();
		$this->assertCount( 2, $this->wp_theatre->seasons() );
	}

	function test_event_inherits_season_from_production() {
		$this->setup_test_data();
		$season_args = array(
			'post_type' => WPT_Season::post_type_name,
		);
		$season = $this->factory->post->create( $season_args );

		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		$production = $this->factory->post->create( $production_args );
		add_post_meta( $production, WPT_Season::post_type_name, $season );

		$event_args = array(
			'post_type' => WPT_Event::post_type_name,
		);
		$event = $this->factory->post->create( $event_args );
		add_post_meta( $event, WPT_Production::post_type_name, $production );
		add_post_meta( $event, 'event_date', date( 'Y-m-d H:i:s', strtotime( 'tomorrow' ) ) );

		$html = do_shortcode( '[wpt_events season='.$season.']' );

		$this->assertEquals( 1, substr_count( $html, '"wp_theatre_event"' ), $html );

	}

	function test_shortcode_wpt_events_filter_season() {
		$this->setup_test_data();
		$this->assertEquals( 2, substr_count( do_shortcode( '[wpt_events season="'.$this->season2.'"]' ), '"wp_theatre_event"' ) );
	}

	/**
	 * Tests if seasons are hidden from serach results.
	 * Confirms #193.
	 *
	 * @since	0.15.3
	 */
	function test_seasons_are_hidden_in_search() {
		$this->setup_test_data();

		$season_id = $this->season1;

		$q = new WP_Query( array(
			's' => get_the_title( $season_id ),
			'fields' => 'ids',
		) );

		$actual = $q->posts;
		$expected = $season_id;

		$this->assertNotContains( $expected, $actual );
	}
}
