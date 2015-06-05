<?php

class WPT_Test_Event_Editor extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		$this->wp_theatre = $wp_theatre;

		parent::setUp();

	}
	
	function assume_role($role='author') {
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
		add_post_meta($event_id, WPT_Production::post_type_name, $production_id, true);
		return $event_id;
	}
	
	function create_production() {
		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		return $this->factory->post->create( $production_args );
	}

	function test_event_is_created_on_production_page() {

		$this->assume_role('author');

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

		$this->assume_role('author');

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
		$event_id = $this->create_event_for_production($production_id);

		$this->assume_role('author');
		
		// Go to the event edit page.
		set_current_screen(WPT_Event::post_type_name);
		
		// There should be a hidden input on the form with the production_id.
		$form_html = $this->wp_theatre->event_editor->get_form_html($production_id, $event_id);
		$this->assertContains('<input type="hidden" id="wpt_event_editor_'.WPT_Production::post_type_name.'" name="wpt_event_editor_'.WPT_Production::post_type_name.'" value="'.$production_id.'" />', $form_html);
	}
	
	function test_is_disabled_field_value_preserved() {
		
	}
}
