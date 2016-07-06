<?php
/**
 * Test bulk editing in the (new) productions list table.
 * @since	0.15.3
 * @group	bulk_edit
 */
class WPT_Bulk_Edit extends WPT_UnitTestCase {

	/**
	 * Tests if productions are published with bulk updates.
	 *
	 * @since 0.15.5
	 */
	function test_productions_are_published() {
		global $wp_theatre;

		$production_id = $this->factory->post->create( array(
			'post_type' => WPT_Production::post_type_name,
			'post_status' => 'draft',
			'post_title' => 'A draft production',	
		) );

		$_POST = array(
			'production' => array( $production_id ),
			'_wpnonce' => wp_create_nonce( 'bulk-productions' ),
		);

		$wp_theatre->productions_admin->process_bulk_actions( 'publish' );

		$actual = array(
			get_post_status( $production_id ),
		);
		$expected = array( 'publish' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests if productions are published with bulk updates.
	 *
	 * @since 0.15.5
	 */
	function test_productions_are_published_with_post_name() {
		global $wp_theatre;

		$production_id = $this->factory->post->create( array(
			'post_type' => WPT_Production::post_type_name,
			'post_status' => 'draft',
			'post_title' => 'A draft production',	
		) );

		$_POST = array(
			'production' => array( $production_id ),
			'_wpnonce' => wp_create_nonce( 'bulk-productions' ),
		);

		$wp_theatre->productions_admin->process_bulk_actions( 'publish' );

		$production = get_post($production_id);

		$actual = $production->post_name;
		$expected = 'a-draft-production';
		$this->assertEquals( $expected, $actual );
	}

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
			'_wpnonce' => wp_create_nonce( 'bulk-productions' ),
		);

		$wp_theatre->productions_admin->process_bulk_actions( 'draft' );

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
			'_wpnonce' => wp_create_nonce( 'bulk-productions' ),
		);

		$wp_theatre->productions_admin->process_bulk_actions( 'draft' );

		$actual = $wp_theatre->events->get(
			array(
				'production' => array( $this->production_with_upcoming_event, $this->production_with_upcoming_events ),
				'status' => array( 'draft' ),
			)
		);

		$expected = 3;

		$this->assertCount( $expected, $actual );
	}

	function test_productions_trash_is_emptied() {
		global $wp_theatre;

		$this->setup_test_data();

		wp_trash_post( $this->production_with_upcoming_event );

		// Check if production is still there.
		$actual = get_post( $this->production_with_upcoming_event );
		$this->assertNotNull( $actual );

		$_POST = array(
			'_wpnonce' => wp_create_nonce( 'bulk-productions' ),
			'delete_all' => 1234,
		);

		$wp_theatre->productions_admin->empty_trash();

		// Check if production is gone.
		$actual = get_post( $this->production_with_upcoming_event );
		$this->assertNull( $actual );
	}
}

