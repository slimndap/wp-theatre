<?php

class WPT_Test extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		
		parent::setUp();
		
		$this->wp_theatre = $wp_theatre;
		
		$season_args = array(
			'post_type'=>WPT_Season::post_type_name
		);
		
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		
		//create 2 seasons
		$this->season1 = $this->factory->post->create($season_args);
		$this->season2 = $this->factory->post->create($season_args);
		
		//create 6 productions
		
		// production with upcoming event
		$this->production_with_upcoming_event = $this->factory->post->create($production_args);
		add_post_meta($this->production_with_upcoming_event, WPT_Season::post_type_name, $this->season1);
		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_event);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)));
		
		// production with 2 upcoming events
		$this->production_with_upcoming_events = $this->factory->post->create($production_args);

		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_events);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', time() + DAY_IN_SECONDS));

		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_events);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', time() + (3 * DAY_IN_SECONDS)));
		add_post_meta($upcoming_event, 'tickets_status', 'cancelled' );
		
		// production with a historic event
		$this->production_with_historic_event = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_historic_event);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() - DAY_IN_SECONDS));
		
		// production with an upcoming and a historic event
		$this->production_with_upcoming_and_historic_events = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_upcoming_and_historic_events);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() - WEEK_IN_SECONDS));
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_upcoming_and_historic_events);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() + WEEK_IN_SECONDS));
		
	}

	function dump_events() {
		$args = array(
			'post_type'=>WPT_Event::post_type_name,
			'posts_er_page' => -1
		);
		$events = get_posts($args);
		
		$dump = '';
		foreach($events as $event) {
			$dump.= print_r($event,true);
			$dump.= print_r(get_post_meta($event->ID),true);
		}
		
		return $dump;
	}

	function dump_productions() {
		$args = array(
			'post_type'=>WPT_Production::post_type_name,
			'posts_er_page' => -1
		);
		$productions = get_posts($args);
		
		$dump = '';
		foreach($productions as $production) {
			$dump.= print_r($production,true);
			$dump.= print_r(get_post_meta($production->ID),true);
		}
		
		return $dump;
	}

	function test_events_are_loaded() {
		$this->assertCount(6, $this->wp_theatre->events());		
	}

	function test_productions_are_loaded() {
		$this->assertCount(4, $this->wp_theatre->productions());		
	}


	function test_upcoming_productions() {
		$args = array(
			'upcoming' => TRUE
		);
		$this->assertCount(3, $this->wp_theatre->productions($args));		
		
	}

	// Test sync between productions and connected events
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
	
	function test_event_inherits_season_from_production() {
		
	}
	
	// Test shortcodes
	function test_shortcode_wpt_productions() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_productions]'));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod', 4, $xml);		
	}
	
	function test_shortcode_wpt_production_filter_season() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_productions season="'.$this->season1.'"]'));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod', 1, $xml);				
	}

	function test_shortcode_wpt_events() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event', 4, $xml);		
	}
	
	function test_shortcode_wpt_events_filter_season() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events season="'.$this->season1.'"]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event', 2, $xml);		
	}
	
	// Test event features
	function test_wpt_event_tickets_status_cancelled() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event .wp_theatre_event_tickets_status_cancelled', 1, $xml);			
	}
	
	// Test order
	function test_order_productions() {
		$message = $this->dump_productions();
	
		$actual = array();
		$productions = $this->wp_theatre->productions();
		foreach($productions as $production) {
			$actual[] = $production->ID;
		}
		
		$expected = array(
			$this->production_with_historic_event,
			$this->production_with_upcoming_events,
			$this->production_with_upcoming_event,
			$this->production_with_upcoming_and_historic_events
		);	
		
		$this->assertEquals($expected,$actual, $message);
	}
	 
	function test_order_events() {
					
	}
	
	// Test RSS feeds
	function test_upcoming_productions_feed() {
		$xml = new DomDocument;
        $xml->loadXML($this->wp_theatre->feeds->get_upcoming_productions());
        $this->assertSelectCount('rss channel item', 3, $xml);
	}
	
	function test_upcoming_events_feed() {
		$xml = new DomDocument;
        $xml->loadXML($this->wp_theatre->feeds->get_upcoming_events());
        $this->assertSelectCount('rss channel item', 4, $xml);
		
	}
	
	function test_theatre_class_is_global() {
		global $wp_theatre;
		$this->assertTrue( 
			is_object($wp_theatre) && 
			get_class($wp_theatre) == 'WP_Theatre'
		);
	}
	
}
