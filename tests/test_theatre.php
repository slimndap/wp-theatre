<?php

class WPT_Test extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		
		parent::setUp();
		
		$this->wp_theatre = $wp_theatre;
		
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		
		//create 6 productions
		
		// production with upcoming event
		$this->production_with_upcoming_event = $this->factory->post->create($production_args);
		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_event);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', time() + DAY_IN_SECONDS));
		$this->wp_theatre->order->set_post_order($upcoming_event);
		$this->wp_theatre->order->set_post_order($this->production_with_upcoming_event);
		
		// production with 2 upcoming events
		$this->production_with_upcoming_events = $this->factory->post->create($production_args);

		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_events);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', time() + DAY_IN_SECONDS));
		$this->wp_theatre->order->set_post_order($upcoming_event);

		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_events);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', time() + DAY_IN_SECONDS));
		$this->wp_theatre->order->set_post_order($upcoming_event);

		$this->wp_theatre->order->set_post_order($this->production_with_upcoming_events);
		
		// production that started yesterday
		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $production_id);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() - DAY_IN_SECONDS));
		$this->wp_theatre->order->set_post_order($event_id);
		$this->wp_theatre->order->set_post_order($production_id);

		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $production_id);
		$this->wp_theatre->order->set_post_order($event_id);
		$this->wp_theatre->order->set_post_order($production_id);

		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $production_id);
		$this->wp_theatre->order->set_post_order($event_id);
		$this->wp_theatre->order->set_post_order($production_id);

		$production_id = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $production_id);
		$this->wp_theatre->order->set_post_order($event_id);
		$this->wp_theatre->order->set_post_order($production_id);
		
	}

	function test_events_are_loaded() {
		$this->assertCount(6, $this->wp_theatre->events());		
	}

	function test_productions_are_loaded() {
		$this->assertCount(6, $this->wp_theatre->productions());		
	}


	function test_upcoming_productions() {
		$message = '';
		$message.= print_r($this->production_with_upcoming_event->event(),false);
	
		$args = array(
			'upcoming' => TRUE
		);
		$this->assertCount(2, $this->wp_theatre->productions($args), $message);		
		
	}

	function test_connected_events_are_trashed_when_production_is_trashed() {
		foreach($this->wp_theatre->productions() as $production) {
			wp_trash_post($production->ID);
		}
		$args = array(
			'post_type'=>WPT_Event::post_type_name,
			'post_status'=>'trash',
			'posts_per_page'=>-1
		);
		$this->assertCount(6, get_posts($args));		
	}
	
	function test_connected_events_are_untrashed_when_production_is_untrashed() {
		foreach($this->wp_theatre->productions() as $production) {
			wp_trash_post($production->ID);
			wp_untrash_post($production->ID);
		}
		$this->assertCount(6, $this->wp_theatre->events());		
		
	}
	
	function test_connected_events_are_deleted_when_production_is_deleted() {
		foreach($this->wp_theatre->productions() as $production) {
			wp_delete_post($production->ID);
		}
		$this->assertCount(0, $this->wp_theatre->events());
	}
	
	function test_event_inherits_categories_from_production() {
		
	}
	
	// RSS feeds
	
	function test_upcoming_productions_feed() {
		$productions = print_r($this->wp_theatre->productions(),FALSE);
	
		$xml = new DomDocument;
        $xml->loadXML($this->wp_theatre->feeds->get_upcoming_productions());
        $this->assertSelectCount('item', 2, $xml, $productions, FALSE);
	}
	
	function test_upcoming_events_feed() {
		
	}
	
	function test_theatre_class_is_global() {
		global $wp_theatre;
		$this->assertTrue( 
			is_object($wp_theatre) && 
			get_class($wp_theatre) == 'WP_Theatre'
		);
	}
	
}
