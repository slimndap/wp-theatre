<?php
/**
 * Test the prices.
 *
 * @group 	prices
 * @since	0.15.3
 */

class WPT_Test_Pricess extends WPT_UnitTestCase {

	function test_wpt_event_tickets_prices() {
		$this->setup_test_data();
		$this->assertEquals( 1, substr_count( do_shortcode( '[wpt_events]' ), 'wp_theatre_event_prices' ) );
	}

	function test_wpt_event_tickets_prices_filter() {
		$this->setup_test_data();
		global $wp_theatre;

		$func = create_function(
			'$html, $event',
			'return "tickets prices";'
		);
		add_filter( 'wpt_event_tickets_prices_html', $func, 10 , 2 );

		$event = new WPT_Event( $this->upcoming_event_with_prices );
		$args = array(
			'html' => true,
		);
		$this->assertContains( 'tickets prices', $event->tickets( $args ) );
	}

	function test_wpt_event_tickets_prices_summary() {
		$this->setup_test_data();
		$event = new WPT_Event( $this->upcoming_event_with_prices );
		$args = array(
			'summary' => true,
		);
		$this->assertContains( '8.50', $event->prices( $args ) );
	}

	/**
	 * Tests the prices of productions.
	 * @since	0.15.3
	 */
	function test_wpt_production_tickets_prices() {
		$this->setup_test_data();
		$another_event = $this->create_upcoming_event();

		add_post_meta( $another_event, WPT_Production::post_type_name, $this->production_with_upcoming_event );

		add_post_meta( $another_event, '_wpt_event_tickets_price', 5 );
		add_post_meta( $another_event, '_wpt_event_tickets_price', 20 );

		$production = new WPT_Production( $this->production_with_upcoming_event );

		$actual = $production->prices();
		$expected = array( 5.0, 8.5, 12.0, 20.0 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests the HTML of prices of productions.
	 * @since	0.15.3
	 */
	function test_wpt_production_tickets_prices_html() {
		$this->setup_test_data();
		$another_event = $this->create_upcoming_event();

		add_post_meta( $another_event, WPT_Production::post_type_name, $this->production_with_upcoming_event );

		add_post_meta( $another_event, '_wpt_event_tickets_price', 5 );
		add_post_meta( $another_event, '_wpt_event_tickets_price', 20 );

		$production = new WPT_Production( $this->production_with_upcoming_event );

		$actual = $production->prices_html();
		$expected = 'from&nbsp;5.00';

		$this->assertContains( $expected, $actual );
	}

	/**
	 * Tests the template placeholder of prices of productions.
	 * @since	0.15.3
	 */
	function test_wpt_production_tickets_prices_template_placeholder() {
		$this->setup_test_data();
		$another_event = $this->create_upcoming_event();

		add_post_meta( $another_event, WPT_Production::post_type_name, $this->production_with_upcoming_event );

		add_post_meta( $another_event, '_wpt_event_tickets_price', 5 );
		add_post_meta( $another_event, '_wpt_event_tickets_price', 20 );

		$actual = do_shortcode( '[wpt_productions post__in="'.$this->production_with_upcoming_event.'"]{{prices}}[/wpt_productions]' );
		$expected = 'from&nbsp;5.00';

		$this->assertContains( $expected, $actual );
	}

	/**
	 * Test if named prices are sanitized.
	 */
	function test_wpt_event_tickets_prices_named() {
		$this->setup_test_data();
		add_post_meta( $this->upcoming_event_with_prices, '_wpt_event_tickets_price', '1123|named_price' );

		$event = new WPT_Event( $this->upcoming_event_with_prices );
		$prices = $event->prices();
		$this->assertNotContains( '1123|named_price',implode( '',$prices ) );

	}
}
