<?php
/**
 * WPT_Test_Event_Archive class.
 *
 * @extends WP_UnitTestCase
 * @group	event-order
 */
class WPT_Test_Event_Order extends WPT_UnitTestCase {

	function setUp() {
		parent::setUp();
	
	}


	function test_are_events_ordered() {
		
		global $wp_theatre;

		$this->setup_test_data();
		
		$events = $wp_theatre->productions->get();

		$actual = wp_list_pluck( $events, 'ID');
		
		$expected = array(
			$this->production_with_historic_event_sticky, // - 1 year, sticky
			$this->production_with_historic_event, // - 1 day
			$this->production_with_upcoming_events, // + 1 day
			$this->production_with_upcoming_event, // + 2 days
			$this->production_with_upcoming_and_historic_events, // 1 week			
		);
		
		$this->assertEquals($expected, $actual);

	}
	
	function test_is_new_event_ordered() {

		global $wp_theatre;

		$this->setup_test_data();
		
		// create production with upcoming event
		$this->event_in_3_days = $this->factory->post->create( 
			array( 
				'post_type' => WPT_Production::post_type_name,
			) 
		);

		$this->event_date_in_3_days = $this->factory->post->create( 		 
			array( 
				'post_type' => WPT_Event::post_type_name,
			)  
		);
		
		add_post_meta( $this->event_date_in_3_days, WPT_Production::post_type_name, $this->event_in_3_days );
		add_post_meta( $this->event_date_in_3_days, 'event_date', date( 'Y-m-d H:i:s', time() + (3 * DAY_IN_SECONDS) ) );

		$events = $wp_theatre->productions->get();

		$actual = wp_list_pluck( $events, 'ID');
		
		$expected = array(
			$this->production_with_historic_event_sticky, // - 1 year, sticky
			$this->production_with_historic_event, // - 1 day
			$this->production_with_upcoming_events, // + 1 day
			$this->production_with_upcoming_event, // + 2 days
			$this->event_in_3_days, // + 3 days
			$this->production_with_upcoming_and_historic_events, // 1 week			
		);
		
		$this->assertEquals($expected, $actual);
		
	}

	function test_is_updated_event_ordered() {
		
		global $wp_theatre;

		$this->setup_test_data();

		update_post_meta( $this->upcoming_event_with_prices, 'event_date', date( 'Y-m-d H:i:s', time() + (MONTH_IN_SECONDS) ) );
		
		$events = $wp_theatre->productions->get();

		$actual = wp_list_pluck( $events, 'ID');
		
		$expected = array(
			$this->production_with_historic_event_sticky, // - 1 year, sticky
			$this->production_with_historic_event, // - 1 day
			$this->production_with_upcoming_events, // + 1 day
			$this->production_with_upcoming_and_historic_events, // 1 week			
			$this->production_with_upcoming_event, // + 1 month
		);
		
		$this->assertEquals($expected, $actual);

	}

  }
