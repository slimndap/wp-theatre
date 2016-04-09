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
