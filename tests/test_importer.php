<?php

class WPT_Demo_Importer extends WPT_Importer {

	function __construct() {
		global $wp_theatre;
		
        $args = array(
            'slug' => 'wpt_demoimporter',
            'name' => 'Demo Importer',
            'options' => get_option('wpt_demoimporter'),
        );

		$this->feed = array(
			array('tomorrow', 'tomorrow + 1 day'),
			array('next week'),
			array('next month'),
		);

		parent::init( $args );
		
	}
	
	/**
	 * Creates some dummy productions and events.
	 * 
	 * @return bool Returns <true> if the feed is successfully processed. Default: <false>.
	 */
	function process_feed() {
		
		for ($p=0; $p<count($this->feed);$p++) {
			
			$production_ref = 'demo_'.$p;
			
			if (!($production = $this->get_production_by_ref($production_ref))) {
				$production_args = array(
					'title' => 	'Production '.$p,
					'content' => 'Supercool stuff',
					'ref' => $production_ref,
				);
				$production = $this->create_production($production_args);
			}

			wp_update_post( array(
				'ID' => $production->ID,
				'post_status' => 'publish'
			));
			
			for($e=0;$e<count($this->feed[$p]);$e++) {
				$event_ref = $production_ref.'_'.$e;
				$event_date = strtotime($this->feed[$p][$e]);					
				$event_args = array(
					'production' => $production->ID,
					'venue' => 'venue',
					'city' => 'city',
					'tickets_url' => 'http://slimndap.com',
					'event_date' => date_i18n('Y-m-d H:i:s',$event_date),
					'ref' => $event_ref,
					'prices' => array(1,2,'3|kids',4),
				);		
				$event = $this->update_event($event_args);

				wp_update_post( array(
					'ID' => $event->ID,
					'post_status' => 'publish'
				));
			
			}
		}
		
		return true;

	}

	function ready_for_import() {
		return true;
	}
	
}

class WPT_Test_Importer extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;		
		$this->wp_theatre = $wp_theatre;

		parent::setUp();
		
	}


	// settings
	
	// import
	
	function test_productions_are_imported() {
		$importer = new WPT_Demo_Importer();
		$importer->execute();		
		$this->assertCount(3, $this->wp_theatre->productions->get());
	}
	
	function test_events_are_imported() {
		$importer = new WPT_Demo_Importer();
		$importer->execute();		
		$this->assertCount(4, $this->wp_theatre->events->get());		
	}
	
	function test_events_from_other_source_are_not_overwritten() {
		// create a new event
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		$production_with_upcoming_event = $this->factory->post->create($production_args);

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		$upcoming_event_with_prices = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_with_prices, WPT_Production::post_type_name, $production_with_upcoming_event);
		add_post_meta($upcoming_event_with_prices, 'event_date', date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)));
		add_post_meta($upcoming_event_with_prices, '_wpt_event_tickets_price', 12);
		add_post_meta($upcoming_event_with_prices, '_wpt_event_tickets_price', 8.5);
		add_post_meta($upcoming_event_with_prices, 'venue', 'Paard van Troje');
		add_post_meta($upcoming_event_with_prices, 'city', 'Den Haag');

		$importer = new WPT_Demo_Importer();
		$importer->execute();		
		$this->assertCount(5, $this->wp_theatre->events->get());		
	}
	
	// re-import
	
	function test_absent_event_is_removed_after_update() {
		$importer = new WPT_Demo_Importer();
		
		$importer->execute();
		
		// remove an event from the feed.
		array_shift($importer->feed);

		$importer->execute();		

		$this->assertCount(2, $this->wp_theatre->events->get());		
		
	}
	
	function test_event_prices_are_set() {
		global $wp_theatre;
		
		$importer = new WPT_Demo_Importer();
		
		$importer->execute();
		
		$events = $wp_theatre->events->get();
		$prices = $events[0]->prices();
		
		$this->assertCount(4, $prices);
	}

}
