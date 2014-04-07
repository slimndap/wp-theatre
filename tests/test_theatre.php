<?php

class WPT_Test extends WP_UnitTestCase {

	function setUp() {
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		
		//create 5 productions
		
		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta(event_id, WPT_Production::post_type_name, $production_id);
		
		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta(event_id, WPT_Production::post_type_name, $production_id);

		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta(event_id, WPT_Production::post_type_name, $production_id);

		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta(event_id, WPT_Production::post_type_name, $production_id);

		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta(event_id, WPT_Production::post_type_name, $production_id);

		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta(event_id, WPT_Production::post_type_name, $production_id);
		
	}

	function test_events_are_loaded() {
		global $wp_theatre;
		$events = $wp_theatre->events();
		$this->assertTrue( 
			count($events)==5
		);
		
	}

	function test_connected_events_are_trashed_when_production_is_trashed() {
		
	}
	
	function test_connected_events_are_untrashed_when_production_is_untrashed() {
		
	}
	
	function test_connected_events_are_deleted_when_production_is_deleted() {
		
	}
	
	function test_event_inherits_categories_from_production() {
		
	}
	
	function test_theatre_class_is_global() {
		global $wp_theatre;
		$this->assertTrue( 
			is_object($wp_theatre) && 
			get_class($wp_theatre) == 'WP_Theatre'
		);
	}
	
}
