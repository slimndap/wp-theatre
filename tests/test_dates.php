<?php
/**
 * Test the event and production date fields.
 *
 * @group 	dates
 * @since	0.15.3
 */

class WPT_Test_Dates extends WPT_UnitTestCase {

	function test_event_startdate() {
		$this->setup_test_data();
		$html = do_shortcode( '[wpt_events]{{startdate}}[/wpt_events]' );

		$expected = date_i18n(
			get_option( 'date_format' ),
			strtotime(
				get_post_meta(
					$this->upcoming_event_with_prices,
					'event_date',
					true
				)
			)
		);

		$this->assertContains( $expected, $html );
	}

	/**
	 * Uses an event that starts at 23:00 and the Dutch timezone (UTC+1),
	 * because this resulted in a date on the next day if you used the 'date'-filter.
	 * @since	0.15.1.
	 */
	function test_event_startdate_with_date_filter() {
		$this->setup_test_data();

		update_option( 'gmt_offset', '1' );

		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		$production_id = $this->factory->post->create( $production_args );

		$event_args = array(
			'post_type' => WPT_Event::post_type_name,
		);
		$event_id = $this->factory->post->create( $event_args );
		add_post_meta( $event_id, WPT_Production::post_type_name, $production_id, true );
		add_post_meta( $event_id, 'event_date', date( 'Y-m-d', time() + (2 * DAY_IN_SECONDS) ).' 23:00:00' );

		$html = do_shortcode( '[wpt_events production="'.$production_id.'"]{{startdate|date(\'D. j M\')}}[/wpt_events]' );

		$expected = date_i18n(
			'D. j M',
			strtotime(
				get_post_meta(
					$event_id,
					'event_date',
					true
				)
			)
		);

		$this->assertContains( $expected, $html );
	}

	function test_event_enddate() {
		$this->setup_test_data();

		$enddate = date( 'Y-m-d H:i:s', time() + (3 * DAY_IN_SECONDS) );
		add_post_meta( $this->upcoming_event_with_prices, 'enddate', $enddate );

		$html = do_shortcode( '[wpt_events]{{enddate}}[/wpt_events]' );

		$expected = date_i18n( get_option( 'date_format' ),	strtotime( $enddate ) );

		$this->assertContains( $expected, $html );
	}

	/**
	 * Tests the dates of productions.
	 * @since	0.15.3
	 */
	function test_wpt_production_dates() {
		$this->setup_test_data();

		$production = new WPT_Production( $this->production_with_upcoming_events );

		$date_format = get_option( 'date_format' );

		$actual = $production->dates();
		$expected = array(
			date( $date_format, time() + DAY_IN_SECONDS ),
			date( $date_format, time() + (3 * DAY_IN_SECONDS) ),
		);

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests the HTML of dates of productions.
	 * @since	0.15.3
	 */
	function test_wpt_production_dates_html() {
		$this->setup_test_data();

		$production = new WPT_Production( $this->production_with_upcoming_events );
		$date_format = get_option( 'date_format' );

		$actual = $production->dates_html();
		$expected = date( $date_format, time() + DAY_IN_SECONDS ).' to '.date( $date_format, time() + (3 * DAY_IN_SECONDS) );

		$this->assertContains( $expected, $actual );
	}

	/**
	 * Tests the HTML of dates of productions with the deprecated 'html' argument.
	 * @since	0.15.3
	 */
	function test_wpt_production_dates_html_deprated() {
		$this->setup_test_data();

		$production = new WPT_Production( $this->production_with_upcoming_events );
		$date_format = get_option( 'date_format' );

		$actual = $production->dates( array( 'html' => true ) );
		$expected = date( $date_format, time() + DAY_IN_SECONDS ).' to '.date( $date_format, time() + (3 * DAY_IN_SECONDS) );

		$this->assertContains( $expected, $actual );
	}

	/**
	 * Tests the template placeholder of dates of productions.
	 * @since	0.15.3
	 */
	function test_wpt_production_dates_template_placeholder() {
		$this->setup_test_data();

		$date_format = get_option( 'date_format' );
		$actual = do_shortcode( '[wpt_productions post__in="'.$this->production_with_upcoming_events.'"]{{dates}}[/wpt_productions]' );
		$expected = date( $date_format, time() + DAY_IN_SECONDS ).' to '.date( $date_format, time() + (3 * DAY_IN_SECONDS) );

		$this->assertContains( $expected, $actual );
	}

	/**
	 * Tests the HTML of dates of productions with both upcoming and historic events.
	 * @since	0.15.3
	 */
	function test_wpt_production_dates_html_with_running_events() {
		$this->setup_test_data();

		$production = new WPT_Production( $this->production_with_upcoming_and_historic_events );
		$date_format = get_option( 'date_format' );

		$actual = $production->dates_html();
		$expected = 'until '.date( $date_format, time() + WEEK_IN_SECONDS );

		$this->assertContains( $expected, $actual );
	}
}
