<?php
/**
 * Test event RSS feeds.
 * @since	0.15.16
 * @group	feeds
 */
class WPT_Test_Feeds extends WPT_UnitTestCase {

	function test_upcoming_productions_feed() {
		global $wp_theatre;
		$this->setup_test_data();
		$this->assertEquals(4, substr_count($wp_theatre->feeds->get_upcoming_productions(), '<item'));
	}
	
	function test_upcoming_events_feed() {
		global $wp_theatre;
		$this->setup_test_data();
		$this->assertEquals(4, substr_count($wp_theatre->feeds->get_upcoming_events(), '<item'));		
	}
	
	function test_upcoming_productions_feed_is_valid() {
		global $wp_theatre;
		$this->setup_test_data();


		$production_with_tricky_name_id = $this->factory->post->create( 
			array(
				'post_type' => WPT_Production::post_type_name,
				'post_title' => 'Töm & Jerrÿ',
			)
		);
		$event_with_tricky_name_id = $this->factory->post->create( 
			array(
				'post_type' => WPT_Event::post_type_name,
			)
		);
		add_post_meta( $event_with_tricky_name_id, WPT_Production::post_type_name, $production_with_tricky_name_id );
		add_post_meta( $event_with_tricky_name_id, 'event_date', date( 'Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS) ) );

		
		$feed = $wp_theatre->feeds->get_upcoming_productions();
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($feed);		
//		$xml=simplexml_load_string($wp_theatre->feeds->get_upcoming_productions());

		$this->assertEquals(4, substr_count($wp_theatre->feeds->get_upcoming_productions(), '<item'));
		
	}
	
	function test_upcoming_events_feed_is_valid() {
		global $wp_theatre;


		$production_with_tricky_name_id = wp_insert_post( 
			array(
				'post_type' => WPT_Production::post_type_name,
				'post_title' => 'Töm & Jerrÿ',
				'post_excerpt' => 'Töm & Jerrÿ',
				'post_content' => 'Töm & Jerrÿ',
			)
		);
								
		
		$event_with_tricky_name_id = $this->factory->post->create( 
			array(
				'post_type' => WPT_Event::post_type_name,
			)
		);
		add_post_meta( $event_with_tricky_name_id, WPT_Production::post_type_name, $production_with_tricky_name_id );
		add_post_meta( $event_with_tricky_name_id, 'event_date', date( 'Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS) ) );

		$production = new WPT_Production($production_with_tricky_name_id);
		print_r( $production->post());

		echo get_the_title( $production_with_tricky_name_id );exit;
		$feed = $wp_theatre->feeds->get_upcoming_events();
echo $feed;
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($feed);		
//		$xml=simplexml_load_string($wp_theatre->feeds->get_upcoming_productions());
echo $xmlDoc->saveXML();
		$this->assertEquals(4, substr_count($wp_theatre->feeds->get_upcoming_productions(), '<item'));
		
	}


}

