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

	function test_create_html_is_displayed_on_production_page() {
		global $wp_theatre;

		$production_id = $this->create_production();
		$create_html = $wp_theatre->event_editor->get_create_html( $production_id );

			$this->assume_role( 'author' );

		do_action( 'add_meta_boxes_'.WPT_Production::post_type_name );

		ob_start();
		do_meta_boxes( WPT_Production::post_type_name, 'normal', get_post( $production_id ) );
		$meta_boxes = ob_get_contents();
		ob_end_clean();

		$this->assertContains( $create_html, $meta_boxes );
	}

	function test_listing_html_is_displayed_on_production_page() {
		global $wp_theatre;

		$this->assume_role( 'author' );

		$production_id = $this->create_production();
		$this->create_event_for_production( $production_id );
		$listing_html = $wp_theatre->event_editor->get_listing_html( $production_id );

		do_action( 'add_meta_boxes_'.WPT_Production::post_type_name );

		ob_start();
		do_meta_boxes( WPT_Production::post_type_name, 'normal', get_post( $production_id ) );
		$meta_boxes = ob_get_contents();
		ob_end_clean();

		$this->assertContains( $listing_html, $meta_boxes );
	}

	function test_edit_form_is_displayed_on_event_page() {
		global $wp_theatre;

		$production_id = $this->create_production();
		$event_id = $this->create_event_for_production( $production_id );

			$this->assume_role( 'author' );
		set_current_screen( WPT_Event::post_type_name );

		$edit_form_html = $wp_theatre->event_editor->get_form_html( $production_id, $event_id );

		do_action( 'add_meta_boxes', WPT_Event::post_type_name, get_post( $event_id ) );

		ob_start();
		do_meta_boxes( WPT_Event::post_type_name, 'normal', get_post( $event_id ) );
		$meta_boxes = ob_get_contents();
		ob_end_clean();

		$this->assertContains( $edit_form_html, $meta_boxes );
	}

	function test_event_is_created_on_production_page() {

		$this->assume_role( 'author' );

		// Add an extra field
		$func = create_function(
			'$fields, $event_id',
			'$fields[] = array("id"=>"extra_field", "title"=>"Extra field"); return $fields;'
		);

		add_filter( 'wpt/event_editor/fields', $func, 10, 2 );

		// Create a fake post submission.
		$_POST['wpt_event_editor_nonce'] = wp_create_nonce( 'wpt_event_editor' );
		$event_date = date( 'Y-m-d H:i', + WEEK_IN_SECONDS );
		$_POST['wpt_event_editor_event_date'] = $event_date;
		$extra_value = 'extra value';
		$_POST['wpt_event_editor_extra_field'] = $extra_value;

		// Create a production.
		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		$production_id = $this->factory->post->create( $production_args );
		$production = new WPT_Production( $production_id );
		$events = $production->events();

		$this->assertEquals( $event_date, date( 'Y-m-d H:i', $events[0]->datetime() ) );
		$this->assertEquals( $extra_value, $events[0]->custom( 'extra_field' ) );

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
		$_POST[ 'wpt_event_editor_'.WPT_Production::post_type_name ] = $production_id;

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

	function test_event_is_deleted_with_ajax_on_production_page() {

		// Create a production with two events.
		$production_id = $this->create_production();
		$first_event_id = $this->create_event_for_production( $production_id );
		$second_event_id = $this->create_event_for_production( $production_id );

		$this->_setRole( 'administrator' );

		$_POST['nonce'] = wp_create_nonce( 'wpt_event_editor_ajax_nonce' );
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

	function test_event_is_created_with_ajax_on_production_page() {
		$production_id = $this->create_production();

		// Add an extra field
		$func = create_function(
			'$fields, $event_id',
			'$fields[] = array("id"=>"extra_field", "title"=>"Extra field"); return $fields;'
		);

		add_filter( 'wpt/event_editor/fields', $func, 10, 2 );

		$this->_setRole( 'administrator' );

		$_POST['nonce'] = wp_create_nonce( 'wpt_event_editor_ajax_nonce' );

		$event_date = date( 'Y-m-d H:i', + WEEK_IN_SECONDS );
		$venue = 'Paradiso';
		$extra_value = 'coming soon!';
		$post_data = array(
			'wpt_event_editor_nonce' => wp_create_nonce( 'wpt_event_editor' ),
			'wpt_event_editor_event_date' => $event_date,
			'wpt_event_editor_venue' => $venue,
			'post_ID' => $production_id,
			'wpt_event_editor_extra_field' => $extra_value,
		);
		$_POST['post_data'] = http_build_query( $post_data );

		try {
			$this->_handleAjax( 'wpt_event_editor_create_event' );
		} catch ( WPAjaxDieContinueException $e ) {
			// We expected this, do nothing.
		}

		$production = new WPT_Production( $production_id );
		$events = $production->events();

		$this->assertCount( 1, $events );

		$this->assertEquals( $event_date, date( 'Y-m-d H:i', $events[0]->datetime() ) );
		$this->assertEquals( $venue, $events['0']->venue() );
		$this->assertEquals( $extra_value, $events[0]->custom( 'extra_field' ) );

	}

	function test_event_value_can_be_emptied() {

		// Create a production with an event.
		$production_id = $this->create_production();
		$event_id = $this->create_event_for_production( $production_id );

		// Give the event a tickets_url.
		$tickets_url = 'http://slimndap.com';
		add_post_meta( $event_id, 'tickets_url', $tickets_url, true );

		// Check if it's set properly.
		$event_with_tickets_url = new WPT_Event( $event_id );
		$this->assertEquals( $tickets_url, $event_with_tickets_url->tickets_url() );

		// Assume admin.
		$this->_setRole( 'administrator' );

		// Create a fake post submission.
		$_POST[ WPT_Event::post_type_name.'_nonce' ] = wp_create_nonce( WPT_Event::post_type_name );
		$_POST['wpt_event_editor_tickets_url'] = '';
		$_POST[ 'wpt_event_editor_'.WPT_Production::post_type_name ] = $production_id;

		// Update the event
		$event_args = array(
			'ID' => $event_id,
			'post_type' => WPT_Event::post_type_name,
		);
		wp_update_post( $event_args );

		// Check if it's emptied.
		$event = new WPT_Event( $event_id );
		$this->assertEmpty( $event->tickets_url() );

	/**
	 * Tests if event for auto-saved productions also get the 'auto-draft' post_status.
	 * See: https://github.com/slimndap/wp-theatre/issues/141
	 */
	function test_event_for_auto_saved_production_inherits_status() {
		$production_id = $this->create_production();

		$production_post = array(
			'ID' => $production_id,
			'post_status' => 'auto-draft',
		);
		
		wp_update_post($production_post);

		$this->_setRole( 'administrator' );

		$_POST['nonce'] = wp_create_nonce( 'wpt_event_editor_ajax_nonce' );

		$event_date = date( 'Y-m-d H:i', + WEEK_IN_SECONDS );
		$post_data = array(
			'wpt_event_editor_nonce' => wp_create_nonce( 'wpt_event_editor' ),
			'wpt_event_editor_event_date' => $event_date,
			'post_ID' => $production_id,
		);
		$_POST['post_data'] = http_build_query( $post_data );

		try {
			$this->_handleAjax( 'wpt_event_editor_create_event' );
		} catch ( WPAjaxDieContinueException $e ) {
			// We expected this, do nothing.
		}

		$production = new WPT_Production( $production_id );
		$events = $production->events();
		
		$this->assertCount( 1, $events );
	}

}