<?php

class WPT_Test_Event_Editor extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		$this->wp_theatre = $wp_theatre;

		parent::setUp();

	}

	function test_event_is_created_on_production_page() {

		// Login as a user with editing rights.
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'author' ) ) );
		wp_set_current_user( $user->ID );

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

		// Reset the logged in user.
		wp_set_current_user( 0 );

	}

	function test_event_is_created_on_event_page() {

		// Login as a user with editing rights.
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'author' ) ) );
		wp_set_current_user( $user->ID );

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

		// Reset the logged in user.
		wp_set_current_user( 0 );

	}

}
