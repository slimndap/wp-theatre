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

		$production_with_tricky_name_id = $this->factory->post->create( 
			array(
				'post_type' => WPT_Production::post_type_name,
				'post_title' => 'Gebr. de Nobel - Oud & Nieuw – Fuif',
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

		$this->go_to( '/?feed=upcoming_productions' );

		$xml = new DomDocument();
		$xml->loadXML( $wp_theatre->feeds->get_upcoming_productions() );

		$actual = substr_count($xml->saveXML(), '<item');
		$expected = 1;
		
		$this->assertEquals($expected, $actual);
		
	}
	
	function test_upcoming_events_feed_is_valid() {
		global $wp_theatre;


		$production_with_tricky_name_id = wp_insert_post( 
			array(
				'post_type' => WPT_Production::post_type_name,
				'post_title' => 'Gebr. de Nobel - Oud & Nieuw – Fuif',
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

		$this->go_to( '/?feed=upcoming_events' );

		$xml = new DomDocument();
		$xml->loadXML( $wp_theatre->feeds->get_upcoming_events() );
		
		$actual = substr_count($xml->saveXML(), '<item');
		$expected = 1;
		
		$this->assertEquals($expected, $actual);
		
	}


}

