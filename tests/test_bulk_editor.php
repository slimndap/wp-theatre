<?php
/**
 * Test case for the Ajax callbacks.
 *
 * @group ajax
 */
class WPT_Test_Bulk_Editor_Ajax extends WP_Ajax_UnitTestCase {
	function create_event() {
		$event_args = array(
			'post_type' => WPT_Event::post_type_name,
		);
		return $this->factory->post->create( $event_args );
	}

	function create_event_for_production($production_id) {
		$event_id = $this->create_event();
		add_post_meta( $event_id, WPT_Production::post_type_name, $production_id, true );
		return $event_id;
	}

	function create_production() {
		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		return $this->factory->post->create( $production_args );
	}

	function test_events_inherit_production_status_in_bulk() {
		global $wp_theatre;

		$production1 = $this->create_production();
		wp_update_post(
			array(
				'ID' => $production1,
				'post_status' => 'draft',
			)
		);

		$event1 = $this->create_event_for_production( $production1 );
		wp_update_post(
			array(
				'ID' => $event1,
				'post_status' => 'draft',
			)
		);

		$production2 = $this->create_production();
		wp_update_post(
			array(
				'ID' => $production2,
				'post_status' => 'draft',
			)
		);

		$event2 = $this->create_event_for_production( $production2 );
		wp_update_post(
			array(
				'ID' => $event2,
				'post_status' => 'draft',
			)
		);

		$_POST = array(
			'wpt_bulk_editor_ajax_nonce' => wp_create_nonce( 'wpt_bulk_editor_ajax_nonce' ),
			'post_ids' => array( $production1, $production2 ),
			'post_status' => 'publish',
		);

		try {
			$this->_handleAjax( 'wpt_bulk_editor' );
		} catch ( WPAjaxDieContinueException $e ) {
			// We expected this, do nothing.
		}

		// Expect 2 upcoming and published events.
		$this->assertCount( 2, $wp_theatre->events->get() );
	}

}

