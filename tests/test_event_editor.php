<?php

class WPT_Test_Event_Editor extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		$this->wp_theatre = $wp_theatre;

		parent::setUp();

	}

	function assume_role($role = 'author') {
		$user = new WP_User( $this->factory->user->create( array( 'role' => $role ) ) );
		wp_set_current_user( $user->ID );
		return $user;
	}

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

	function test_event_is_created_on_production_page() {

		$this->assume_role( 'author' );

		// Create a fake post submission.
		$_POST['wpt_event_editor_nonce'] = wp_create_nonce( 'wpt_event_editor' );
		$event_date = date( 'Y-m-d H:i', + WEEK_IN_SECONDS );
		$_POST['wpt_event_editor_event_date'] = $event_date;

		// Create a production.
		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		$production_id = $this->factory->post->create( $production_args );
		$production = new WPT_Production( $production_id );
		$events = $production->events();

		$this->assertEquals( $event_date, date( 'Y-m-d H:i', $events[0]->datetime() ) );

	}

	function test_event_is_created_on_event_page() {

		$this->assume_role( 'author' );

		// Save a production.
		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		$production_id = $this->factory->post->create( $production_args );

		// Create a fake post submission.
		$_POST[ WPT_Event::post_type_name.'_nonce' ] = wp_create_nonce( WPT_Event::post_type_name );
		$event_date = date( 'Y-m-d H:i', + WEEK_IN_SECONDS );
		$_POST['wpt_event_editor_event_date'] = $event_date;
		$_POST[ WPT_Production::post_type_name ] = $production_id;

		// Create the event
		$event_args = array(
			'post_type' => WPT_Event::post_type_name,
		);
		$event_id = $this->factory->post->create( $event_args );

		$production = new WPT_Production( $production_id );
		$events = $production->events();

		$this->assertEquals( $event_date, date( 'Y-m-d H:i', $events[0]->datetime() ) );

	}

	function test_production_value_is_preserved_on_event_page() {
		// Create a production with an event.
		$production_id = $this->create_production();
		$event_id = $this->create_event_for_production( $production_id );

		$this->assume_role( 'author' );

		// Go to the event edit page.
		set_current_screen( WPT_Event::post_type_name );

		// There should be a hidden input on the form with the production_id.
		$form_html = $this->wp_theatre->event_editor->get_form_html( $production_id, $event_id );
		$this->assertContains( '<input type="hidden" id="wpt_event_editor_'.WPT_Production::post_type_name.'" name="wpt_event_editor_'.WPT_Production::post_type_name.'" value="'.$production_id.'" />', $form_html );
	}

	function test_is_disabled_field_value_preserved() {

	}

	function test_2nd_event_is_created_on_production_page() {

		$this->assume_role( 'author' );

		// Create a production with an event.
		$production_id = $this->create_production();
		$first_event_id = $this->create_event_for_production( $production_id );

		// Create a fake post submission.

		// Trigger WPT_Event_editor::save_event().
		$_POST['wpt_event_editor_nonce'] = wp_create_nonce( 'wpt_event_editor' );
		$event_date = date( 'Y-m-d H:i', + WEEK_IN_SECONDS );
		$_POST['wpt_event_editor_event_date'] = $event_date;

		// Trigger WPT_Admin::save_production().
		$_POST[ WPT_Production::post_type_name.'_nonce' ] = wp_create_nonce( WPT_Production::post_type_name );
		$_POST[ WPT_Season::post_type_name ] = '';

		$production_post = array(
			'ID' => $production_id,
		);
		wp_update_post( $production_post );

		$production = new WPT_Production( $production_id );
		$events = $production->events();
		$this->assertCount( 2, $events );

		/*
		 * Also test for ghost events.
		 * @see: https://github.com/slimndap/wp-theatre/issues/125
		 */
		$events = $this->wp_theatre->events->get();
		$this->assertCount( 2, $events );

	}

	function test_event_inherits_production_status_on_production_page() {

		$this->assume_role( 'author' );

		// Create a fake post submission.
		$_POST['wpt_event_editor_nonce'] = wp_create_nonce( 'wpt_event_editor' );
		$event_date = date( 'Y-m-d H:i', + WEEK_IN_SECONDS );
		$_POST['wpt_event_editor_event_date'] = $event_date;

		$post_status = 'future';
		$post_date = date( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );

		// Create a production.
		$production_id = $this->create_production();
		$production_post = array(
			'ID' => $production_id,
			'post_status' => $post_status,
			'post_date' => $post_date,
			'post_date_gmt' => get_gmt_from_date( $post_date ),
		);
		wp_update_post( $production_post );

		$production = new WPT_Production( $production_id );
		$events = $production->events();

		$this->assertEquals( $post_status, get_post_status( $events[0]->ID ) );
	}

	function test_event_editor_lists_scheduled_events() {
		$this->assume_role( 'author' );

		$post_status = 'future';

		$date = date( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );

		// Create a production with an event.
		$production_id = $this->create_production();
		$production_post = array(
			'ID' => $production_id,
			'post_status' => $post_status,
			'post_date' => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
		);
		wp_update_post( $production_post );

		$event_id = $this->create_event_for_production( $production_id );
		$event_post = array(
			'ID' => $event_id,
			'post_status' => $post_status,
			'post_date' => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
		);

		wp_update_post( $event_post );

		$html = $this->wp_theatre->event_editor->get_listing_html( $production_id );

		$this->assertContains( '<tr data-event_id="'.$event_id.'">', $html );

	}
}

/**
 * Test case for the Ajax callbacks.
 *
 * @group ajax
 */
class WPT_Test_Event_Editor_Ajax extends WP_Ajax_UnitTestCase {
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

	function test_event_is_deleted_on_production_page() {

		// Create a production with two events.
		$production_id = $this->create_production();
		$first_event_id = $this->create_event_for_production( $production_id );
		$second_event_id = $this->create_event_for_production( $production_id );

		$this->_setRole( 'administrator' );

		$_POST['nonce'] = wp_create_nonce( 'wpt_event_editor_nonce' );
		$_POST['event_id'] = $second_event_id;

		try {
			$this->_handleAjax( 'wpt_event_editor_delete_event' );
		} catch ( WPAjaxDieContinueException $e ) {
			// We expected this, do nothing.
		}

		$production = new WPT_Production( $production_id );
		$events = $production->events();

		$this->assertCount( 1, $events );

	}

}