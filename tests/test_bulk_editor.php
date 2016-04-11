<?php
/**
 * Test bulk editing in the (new) productions list table.
 * @since	0.15.3
 * @group	bulk_edit
 */
class WPT_Bulk_Edit extends WPT_UnitTestCase {

	/**
	 * Tests if productions are saved as draft with bulk updates.
	 *
	 * @since 0.15.4
	 */
	function test_productions_are_draft() {
		global $wp_theatre;

		$this->setup_test_data();

		$_POST = array(
			'production' => array( $this->production_with_upcoming_event, $this->production_with_upcoming_events ),
			'action' => 'draft',
			'_wpnonce' => wp_create_nonce( 'bulk-productions' ),
		);

		$wp_theatre->productions_admin->process_bulk_actions();

		$actual = array(
			get_post_status( $this->production_with_upcoming_event ),
			get_post_status( $this->production_with_upcoming_events ),
			get_post_status( $this->production_with_historic_event ),
		);
		$expected = array( 'draft', 'draft', 'publish' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if events inherit the production status after bulk updates.
	 * Confirms #195.
	 *
	 * @since	0.15.4
	 */
	function test_events_inherit_status() {
		global $wp_theatre;

		$this->setup_test_data();

		$_POST = array(
			'production' => array( $this->production_with_upcoming_event, $this->production_with_upcoming_events ),
			'action' => 'draft',
			'_wpnonce' => wp_create_nonce( 'bulk-productions' ),
		);

		$wp_theatre->productions_admin->process_bulk_actions();

		$actual = $wp_theatre->events->get(
			array(
				'production' => array( $this->production_with_upcoming_event, $this->production_with_upcoming_events ),
				'status' => array( 'draft' ),
			)
		);

		$expected = 3;

		$this->assertCount( $expected, $actual );
	}
}

