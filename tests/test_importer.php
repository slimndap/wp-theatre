
<?php

class WPT_Demo_Importer extends WPT_Importer {

	function __construct() {
		global $wp_theatre;

		$args = array(
			'slug' => 'wpt_demoimporter',
			'name' => 'Demo Importer',
			'options' => get_option( 'wpt_demoimporter' ),
			'callbacks' => array(
				'reimport_production' => array($this, 'process_reimport_production'),
			),
		);

		$this->feed = array(
			array( 'tomorrow', 'tomorrow + 1 day' ),
			array( 'next week' ),
			array( 'next month' ),
		);

		parent::init( $args );

	}

	/**
	 * Creates some dummy productions and events.
	 *
	 * @return bool Returns <true> if the feed is successfully processed. Default: <false>.
	 */
	function process_feed() {

		for ( $p = 0; $p < count( $this->feed );$p++ ) {

			$production_ref = 'demo_'.$p;

			if ( ! ($production = $this->get_production_by_ref( $production_ref )) ) {
				$production_args = array(
					'title' => 'Production '.$p,
					'content' => 'Supercool stuff',
					'ref' => $production_ref,
				);
				$production = $this->create_production( $production_args );
			}

			for ( $e = 0;$e < count( $this->feed[ $p ] );$e++ ) {
				$event_ref = $production_ref.'_'.$e;
				$event_date = strtotime( $this->feed[ $p ][ $e ] );
				$event_args = array(
					'production' => $production->ID,
					'venue' => 'venue',
					'city' => 'city',
					'tickets_url' => 'http://slimndap.com',
					'event_date' => date_i18n( 'Y-m-d H:i:s',$event_date ),
					'ref' => $event_ref,
					'prices' => array( 1, 2, '3|kids', 4 ),
				);
				$event_args = apply_filters( 'wpt/test/importer/process_feed/event/args', $event_args, $this->feed[ $p ][ $e ] );
				$event = $this->update_event( $event_args );
			}
		}

		return true;

	}
	
	function process_reimport_production($production_id) {

		if (!$this->ready_for_import()) {
			return false;	
		}
		
		$source_ref = get_post_meta($production_id, '_wpt_source_ref', true);

		if (empty($source_ref)) {
			return false;
		}

		for ( $p = 0; $p < count( $this->feed );$p++ ) {
			$production_ref = 'demo_'.$p;
			if ($production_ref==$source_ref) {

				$production_post = array(
					'ID' => $production_id,
					'post_title' => 'Production '.$p,
					'post_content' => 'Supercool stuff',
				);
				wp_update_post($production_post);
				
				for ( $e = 0;$e < count( $this->feed[ $p ] );$e++ ) {
					$event_ref = $production_ref.'_'.$e;
					$event_date = strtotime( $this->feed[ $p ][ $e ] );
					$event_args = array(
						'production' => $production_id,
						'venue' => 'venue',
						'city' => 'city',
						'tickets_url' => 'http://slimndap.com',
						'event_date' => date_i18n( 'Y-m-d H:i:s',$event_date ),
						'ref' => $event_ref,
						'prices' => array( 1, 2, '3|kids', 4 ),
					);
					$event_args = apply_filters( 'wpt/test/importer/process_feed/event/args', $event_args, $this->feed[ $p ][ $e ] );
					$event = $this->update_event( $event_args );
				}
			}
		}

		return true;
		

	}

	static function process_alternate_reimport_production( $production_id ) {
		$production_post = array(
			'ID' => $production_id,
			'post_title' => 'Boring show 123',
			'post_content' => 'Supercool stuff',
		);
		wp_update_post($production_post);		
	}

	function ready_for_import() {
		return true;
	}

}

/**
 * WPT_Test_Importer class.
 * 
 * @group	importer
 */
class WPT_Test_Importer extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		$this->wp_theatre = $wp_theatre;

		parent::setUp();

	}

	/**
	 * Sets the post_status of _all_ productions and events to 'publish'.
	 * 
	 * @since 0.11.6
	 */
	function publish_all() {
		global $wp_theatre;
		
		$args = array(
			'post_type' => WPT_Production::post_type_name,
			'post_status' => array('all'),	
			'posts_per_page' => -1,
		);
		foreach( get_posts($args) as $production) {
			wp_update_post( array(
				'ID' => $production->ID,
				'post_status' => 'publish',
			));		
		}
		
		$args = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => array('all'),	
			'posts_per_page' => -1,
		);
		foreach( get_posts($args) as $event) {
			wp_update_post( array(
				'ID' => $event->ID,
				'post_status' => 'publish',
			));		
		}
	}


	// settings

	// import

	function test_productions_are_imported() {
		$importer = new WPT_Demo_Importer();
		$importer->execute();
		$this->publish_all();
		$this->assertCount( 3, $this->wp_theatre->productions->get() );
	}

	function test_events_are_imported() {
		$importer = new WPT_Demo_Importer();
		$importer->execute();
		$this->publish_all();
		$this->assertCount( 4, $this->wp_theatre->events->get() );
	}

	function test_events_from_other_source_are_not_overwritten() {
		// create a new event
		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		$production_with_upcoming_event = $this->factory->post->create( $production_args );

		$event_args = array(
			'post_type' => WPT_Event::post_type_name,
		);
		$upcoming_event_with_prices = $this->factory->post->create( $event_args );
		add_post_meta( $upcoming_event_with_prices, WPT_Production::post_type_name, $production_with_upcoming_event );
		add_post_meta( $upcoming_event_with_prices, 'event_date', date( 'Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS) ) );
		add_post_meta( $upcoming_event_with_prices, '_wpt_event_tickets_price', 12 );
		add_post_meta( $upcoming_event_with_prices, '_wpt_event_tickets_price', 8.5 );
		add_post_meta( $upcoming_event_with_prices, 'venue', 'Paard van Troje' );
		add_post_meta( $upcoming_event_with_prices, 'city', 'Den Haag' );

		$importer = new WPT_Demo_Importer();
		$importer->execute();
		$this->publish_all();
		$this->assertCount( 5, $this->wp_theatre->events->get() );
	}

	// re-import

	function test_absent_event_is_removed_after_update() {
		$importer = new WPT_Demo_Importer();

		$importer->execute();

		// remove an event from the feed.
		array_shift( $importer->feed );

		$importer->execute();

		$this->publish_all();

		$this->assertCount( 2, $this->wp_theatre->events->get() );

	}

	/**
	 * Test if a newly imported event for an existing production inherits the status of the production.
	 * See: https://github.com/slimndap/wp-theatre/issues/129
	 */
	function test_new_events_inherit_status_from_existing_production() {
		global $wp_theatre;

		$importer = new WPT_Demo_Importer();

		$importer->execute();
		$this->publish_all();
		
		$productions = $wp_theatre->productions->get();
		$production = $productions[0];

		/*
		 * Add a new event to the first production, without setting a post status.
		 * We are expecting the post_status to be set to 'publish' since the Demo Importer
		 * already set the status of all imported productions and events to 'publish'.
		 */
		$event_ref = 'new_event';
		$event_date = strtotime( 'next year' );
		$event_args = array(
			'production' => $production->ID,
			'venue' => 'venue',
			'city' => 'city',
			'tickets_url' => 'http://slimndap.com',
			'event_date' => date_i18n( 'Y-m-d H:i:s',$event_date ),
			'ref' => $event_ref,
			'prices' => array( 1, 2, '3|kids', 4 ),
		);
		$event = $importer->update_event( $event_args );

		/*
		 * Get all published events for our production.
		 */
		$events = $production->events();

		$this->assertCount( 3, $events );

	}

	function test_new_events_prices_are_set() {
		global $wp_theatre;

		$importer = new WPT_Demo_Importer();

		$importer->execute();
		$this->publish_all();

		$events = $wp_theatre->events->get();
		$prices = $events[0]->prices();

		$this->assertCount( 4, $prices );
	}

	function test_fields_not_part_of_import_are_not_overwritten() {
		global $wp_theatre;

		$func = create_function(
			'$event_args, $event_data',
			'unset($event_args[\'prices\']); unset($event_args[\'venue\']); return $event_args;'
		);

		add_filter( 'wpt/test/importer/process_feed/event/args', $func, 10 ,2 );

		$importer = new WPT_Demo_Importer();

		$importer->execute();
		$this->publish_all();

		$events = $wp_theatre->events->get();

		update_post_meta( $events[0]->ID, '_wpt_event_tickets_price', 10 );
		update_post_meta( $events[0]->ID, 'venue', 'Paradiso' );

		$importer->execute();

		$prices = $events[0]->prices();
		$this->assertCount( 1, $prices );

		$venue = $events[0]->venue();
		$this->assertEquals( 'Paradiso', $events[0]->venue() );
	}
	
	function test_import_error() {
		
		$error = 'Something went wrong';
		
		$importer = new WPT_Demo_Importer();
		$importer->execute();
		$importer->add_error($error);
		
		$actual = $importer->stats['errors'];
		$expected = $error;
		
		$this->assertContains($expected, $actual);
	}
	
	
	/**
	 * Test if previously imported events are properly removed after the next import.
	 * See: https://github.com/slimndap/wp-theatre/issues/182
	 */
	function test_import_events_cleanup() {
		global $wp_theatre;

		$importer = new WPT_Demo_Importer();

		// Store the originals feed (with only 4 events).
		$feed_small = $importer->feed;
		
		// Replace feed with 30 events.
		$feed_large = array(
			array('Today', 'Tomorrow'),
		);
		for($i=2;$i<30;$i++) {
			$feed_large[] = array('Today + '.$i.' days');
		}
		$importer->feed = $feed_large;

		// Import the 30 events
		$importer->execute();
		$this->publish_all();
		$events = $wp_theatre->events->get();
		$this->assertCount(30, $events);
		
		// Import again, but with the originals feed.
		$importer->feed = $feed_small;
		$importer->execute();
		$this->publish_all();
		$events = $wp_theatre->events->get();
		
		// All events from the large feed should be gone.
		$this->assertCount(4, $events);
		
	}
	
	function test_reimport_production() {
		global $wp_theatre;

		$importer = new WPT_Demo_Importer();

		$importer->execute();
		$this->publish_all();

		$productions = $wp_theatre->productions->get();
		
		// Pick 'Production 0'.
		$production_id = $productions[0]->ID;
		
		$production_args = array(
			'ID' => $production_id,
			'post_title' => 'A changed title',
			'post_content' => 'Changed content',
		);
		wp_update_post($production_args);
		

		$production_html_args = array(
			'template' => '{{title}}{{content}}',	
		);
		
		// Make sure the content was updated.
		$production = new WPT_Production($production_id);
		$actual = $production->html($production_html_args);
		$expected = 'Changed content';
		$this->assertContains($expected, $actual);
		
		$importer->execute_reimport($production_id);

		// Reload production to refresh 'title' and 'content' values.
		$production = new WPT_Production($production_id);

		$actual = $production->html($production_html_args);

		// Test if original content title is back.
		$expected = 'Supercool stuff';
		$this->assertContains($expected, $actual);

		// Test if original production title is back.
		$expected = 'Production 0';
		$this->assertContains($expected, $actual);
		
	}

	function test_reimport_production_events() {
		global $wp_theatre;

		$importer = new WPT_Demo_Importer();

		$importer->execute();

		$this->publish_all();

		$productions = $wp_theatre->productions->get();
		
		// Pick 'Production 0'.
		$production_id = $productions[0]->ID;		
		$events = $productions[0]->events();
		
		// Delete the first event.
		wp_delete_post($events[0]->ID, true);
		
		// Make sure it is gone.
		$production = new WPT_Production($production_id);
		$actual = $production->events();
		$expected = 1;
		$this->assertCount($expected, $actual);
		
		// Prepare for a clean re-import by clearing all preloaded productiosn and events.
		$importer->clear_preloaded_productions();
		$importer->clear_preloaded_events();				
		
		$importer->execute_reimport($production_id);

		// Test if the deleted event is back.
		$production = new WPT_Production($production_id);
		$actual = $production->events();
		$expected = 2;
		$this->assertCount($expected, $actual);

	}

	function test_reimport_production_with_alternate_callback() {
		
		$func = create_function(
			'$value, $key, $importer',
			'if ( "callbacks" == $key ) { $value["reimport_production"] = array( "WPT_Demo_Importer", "process_alternate_reimport_production"); } return $value;'
		);
		
		add_filter('wpt/importer/get/value', $func, 10 , 3 );		
		
		global $wp_theatre;

		$importer = new WPT_Demo_Importer();

		$importer->execute();
		$this->publish_all();

		$productions = $wp_theatre->productions->get();
		
		// Pick 'Production 0'.
		$production_id = $productions[0]->ID;
		
		$production_args = array(
			'ID' => $production_id,
			'post_title' => 'A changed title',
			'post_content' => 'Changed content',
		);
		wp_update_post($production_args);
		

		$production_html_args = array(
			'template' => '{{title}}{{content}}',	
		);
		
		// Make sure the content was updated.
		$production = new WPT_Production($production_id);
		$actual = $production->html($production_html_args);
		$expected = 'Changed content';
		$this->assertContains($expected, $actual);
		
		$importer->execute_reimport($production_id);

		// Reload production to refresh 'title' and 'content' values.
		$production = new WPT_Production($production_id);

		$actual = $production->html($production_html_args);

		// Test if original content title is back.
		$expected = 'Supercool stuff';
		$this->assertContains($expected, $actual);

		// Test if alternate production title is set.
		$expected = 'Boring show 123';
		$this->assertContains($expected, $actual);
		
	}
	
	function test_transients_are_reset_after_import() {
		
		$importer = new WPT_Demo_Importer();
		$importer->execute();
		$this->publish_all();

		$actual = do_shortcode('[wpt_events]');

		$importer->feed = array(
			array( 'tomorrow', 'tomorrow + 1 day' ),
			array( 'next year' ),
		);
		$importer->execute();

		$expected = do_shortcode('[wpt_events]');
		
		$this->assertNotEquals($expected, $actual);		
	}
	
	function test_productions_are_preloaded_during_import() {

		$importer = new WPT_Demo_Importer();
		$importer->execute();
		
		// Production should be preloaded after import.
		$actual = $importer->get_preloaded_production_by_ref( 'demo_1');
		$expected = 'WPT_Production';
		$this->assertInstanceOf( $expected, $actual );
		
		$importer->clear_preloaded_productions();
		
		// Production should no longer be preloaded after clearing.
		$actual = $importer->get_preloaded_production_by_ref( 'demo_1');
		$this->assertFalse( $actual );
		
		$production_refs = array();
		for ( $p = 0; $p < count( $importer->feed );$p++ ) {
			$production_refs[] = 'demo_'.$p;
		}
		
		$importer->preload_productions_by_ref( $production_refs );		

		// Production should be preloaded again from the database.
		$actual = $importer->get_preloaded_production_by_ref( 'demo_1');
		$expected = 'WPT_Production';
		
		$this->assertInstanceOf( $expected, $actual );
		
	}
	
	function test_events_are_preloaded_during_import() {

		$importer = new WPT_Demo_Importer();
		$importer->execute();
		
		// Event should be preloaded after import.
		$actual = $importer->get_preloaded_event_by_ref( 'demo_1_0');
		$expected = 'WPT_Event';
		$this->assertInstanceOf( $expected, $actual );
		
		$importer->clear_preloaded_productions();
		
		// Production should no longer be preloaded after clearing.
		$actual = $importer->get_preloaded_production_by_ref( 'demo_1');
		$this->assertFalse( $actual );
		
		$event_refs = array();
		for ( $p = 0; $p < count( $importer->feed );$p++ ) {
			$production_ref = 'demo_'.$p;
			for ( $e = 0;$e < count( $this->feed[ $p ] );$e++ ) {
				$event_refs[] = $production_ref.'_'.$e;
			}
		}
		
		$importer->preload_events_by_ref( $event_refs );		

		// Event should be preloaded again from the database.
		$actual = $importer->get_preloaded_event_by_ref( 'demo_1_0');
		$expected = 'WPT_Event';
		
		$this->assertInstanceOf( $expected, $actual );
				
		
	}
}



