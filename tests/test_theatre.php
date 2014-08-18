<?php

class WPT_Test extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		
		parent::setUp();
		
		$this->wp_theatre = new WP_Theatre();
		
		$season_args = array(
			'post_type'=>WPT_Season::post_type_name
		);
		
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		
		// create seasons
		$this->season1 = $this->factory->post->create($season_args);
		$this->season2 = $this->factory->post->create($season_args);
		
		//create categories
		$this->category_muziek = wp_create_category('muziek');
		$this->category_film = wp_create_category('film');
		
		// create production with upcoming event
		$this->production_with_upcoming_event = $this->factory->post->create($production_args);
		add_post_meta($this->production_with_upcoming_event, WPT_Season::post_type_name, $this->season1);
		wp_set_post_categories($this->production_with_upcoming_event, array($this->category_muziek));
		wp_set_post_tags($this->production_with_upcoming_event,array('upcoming'));

		$this->upcoming_event_with_prices = $this->factory->post->create($event_args);
		add_post_meta($this->upcoming_event_with_prices, WPT_Production::post_type_name, $this->production_with_upcoming_event);
		add_post_meta($this->upcoming_event_with_prices, 'event_date', date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)));
		add_post_meta($this->upcoming_event_with_prices, '_wpt_event_tickets_price', 12);
		add_post_meta($this->upcoming_event_with_prices, '_wpt_event_tickets_price', 8.5);
		
		// create production with 2 upcoming events
		$this->production_with_upcoming_events = $this->factory->post->create($production_args);
		add_post_meta($this->production_with_upcoming_events, WPT_Season::post_type_name, $this->season2);
		wp_set_post_categories($this->production_with_upcoming_events, array($this->category_muziek,$this->category_film));

		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_events);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', time() + DAY_IN_SECONDS));
		add_post_meta($upcoming_event, 'tickets_status', 'other tickets status' );

		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_events);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', time() + (3 * DAY_IN_SECONDS)));
		add_post_meta($upcoming_event, 'tickets_status', WPT_Event::tickets_status_cancelled );
		
		// create production with a historic event
		$this->production_with_historic_event = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_historic_event);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() - DAY_IN_SECONDS));
		wp_set_post_tags($this->production_with_historic_event,array('historic'));

		// create sticky production with a historic event
		$this->production_with_historic_event_sticky = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_historic_event_sticky);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() - YEAR_IN_SECONDS));
		stick_post($this->production_with_historic_event_sticky);
		wp_set_post_tags($this->production_with_historic_event_sticky,array('historic'));
		
		// create sticky production with an upcoming and a historic event
		$this->production_with_upcoming_and_historic_events = $this->factory->post->create($production_args);
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_upcoming_and_historic_events);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() - WEEK_IN_SECONDS));
		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_upcoming_and_historic_events);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() + WEEK_IN_SECONDS));
		stick_post($this->production_with_upcoming_and_historic_events);
		add_post_meta($this->upcoming_event_with_prices, '_wpt_event_tickets_price', 12);
		add_post_meta($upcoming_event, 'tickets_status', WPT_Event::tickets_status_hidden );
		
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
		$this->assertCount(7, $this->wp_theatre->events->load());		
	}

	function test_productions_are_loaded() {
		$this->assertCount(5, $this->wp_theatre->productions->load());		
	}
	
	function test_seasons_are_loaded() {
		$this->assertCount(2, $this->wp_theatre->seasons());
	}


	function test_upcoming_productions() {
		$args = array(
			'upcoming' => TRUE
		);
		$this->assertCount(4, $this->wp_theatre->productions->load($args));		
		
	}

	// Test sync between productions and connected events
	function test_connected_events_are_trashed_when_production_is_trashed() {
		foreach($this->wp_theatre->productions->load() as $production) {
			wp_trash_post($production->ID);
		}
		$args = array(
			'post_type'=>WPT_Event::post_type_name,
			'post_status'=>'trash',
			'posts_per_page'=>-1
		);
		$this->assertCount(7, get_posts($args));		
	}
	
	function test_connected_events_are_untrashed_when_production_is_untrashed() {
		foreach($this->wp_theatre->productions->load() as $production) {
			wp_trash_post($production->ID);
			wp_untrash_post($production->ID);
		}
		$this->assertCount(7, $this->wp_theatre->events->load());		
		
	}
	
	function test_connected_events_are_deleted_when_production_is_deleted() {
		foreach($this->wp_theatre->productions->load() as $production) {
			wp_delete_post($production->ID);
		}
		$this->assertCount(0, $this->wp_theatre->events->load());
	}
	
	function test_event_inherits_categories_from_production() {
		
	}
	
	function test_event_inherits_season_from_production() {
		
	}
	
	// Test shortcodes
	function test_shortcode_wpt_productions() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_productions]'));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod', 5, $xml);		
	}
	
	function test_shortcode_wpt_productions_filter_season() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_productions season="'.$this->season1.'"]'));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod', 1, $xml);				
	}

	function test_shortcode_wpt_productions_filter_category() {
		// test with mixed category-slug and category-id
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_productions category="muziek,'.$this->category_film.'"]'));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod', 2, $xml);				
	}
	
	function test_shortcode_wpt_events() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event', 4, $xml);		
	}
	
	function test_shortcode_wpt_events_filter_season() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events season="'.$this->season2.'"]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event', 2, $xml);		
	}
	
	function test_shortcode_wpt_events_filter_category() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events category="muziek"]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event', 3, $xml);		
	}
	
	function test_shortcode_wpt_season_production() {
		$season = get_post($this->season1);
	}
	
	function test_shortcode_wpt_season_events() {
		$season = get_post($this->season2);
	}
	
	function test_shortcode_wpt_production_events() {
		$html = do_shortcode('[wpt_production_events production="'.$this->production_with_upcoming_events.'"]');
	
		$xml = new DomDocument;
        $xml->loadHTML($html);
        $this->assertSelectCount('.wpt_events .wp_theatre_event', 2, $xml, $html);		
		
	}
	
	// Test templates
	
	function test_wpt_events_template_permalink() {
		$matcher = array(
			'tag' => 'div',
			'descendant' => array(
				'tag' => 'div',
				'attributes' => array(
					'class' => 'wp_theatre_event_location'
				),
				'child' => array(
					'tag' => 'a',
					'attributes' => array(
						'href' => get_permalink($this->production_with_upcoming_event)
					)
				)
			)	
		);
        $this->assertTag($matcher, do_shortcode('[wpt_events]{{location|permalink}}[/wpt_events]'));
	}

	function test_shortcode_wpt_productions_with_custom_field() {
		$director = 'Steven Spielberg';
	
		update_post_meta(
			$this->production_with_upcoming_event, 
			'director', 
			$director
		);
		
		$html = do_shortcode('[wpt_productions]{{title}}{{director}}[/wpt_productions]');

		$this->assertContains($director,$html);

		$xml = new DomDocument;
        $xml->loadHTML($html);
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod_director', 5, $xml);		
	}
	
	// Test event features
	function test_wpt_event_tickets_status_cancelled() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event .wp_theatre_event_tickets_status_cancelled', 1, $xml);			
	}
	
	function test_wpt_event_tickets_status_hidden() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event .wp_theatre_event_tickets_status', 2, $xml);			
	}
	
	function test_wpt_event_tickets_status_other() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event .wp_theatre_event_tickets_status_other', 1, $xml);			
	}
	
	function test_wpt_event_tickets_prices() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event .wp_theatre_event_prices', 1, $xml);		
	}
	
	function test_wpt_event_tickets_prices_summary() {
		$event = new WPT_Event($this->upcoming_event_with_prices);
		$args = array(
			'summary'=>true
		);
		$this->assertContains('8.50', $event->prices($args));
	}
	
	
	function test_wpt_events_content() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]{{title}}{{content}}[/wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event .wp_theatre_prod_content', 4, $xml);		
	}
	
	function test_wpt_productions_content() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_productions]{{title}}{{content}}[/wpt_productions]'));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod .wp_theatre_prod_content', 5, $xml);		
	}
	
	function test_wpt_events_excerpt() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]{{title}}{{excerpt}}[/wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event .wp_theatre_prod_excerpt', 4, $xml);		
	}
	
	function test_wpt_productions_excerpt() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_productions]{{title}}{{excerpt}}[/wpt_productions]'));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod .wp_theatre_prod_excerpt', 5, $xml);		
	}
	
	function test_wpt_events_categories() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_events]{{title}}{{categories}}[/wpt_events]'));
        $this->assertSelectCount('.wpt_events .wp_theatre_event .wpt_production_categories .wpt_production_category', 5, $xml);		
	}
	
	function test_wpt_productions_categories() {
		$xml = new DomDocument;
        $xml->loadHTML(do_shortcode('[wpt_productions]{{title}}{{categories}}[/wpt_productions]'));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod .wpt_production_categories .wpt_production_category_muziek', 2, $xml);		
	}
	
	// Test order
	function test_order_productions() {
		$actual = array();
		$productions = $this->wp_theatre->productions->load();
		foreach($productions as $production) {
			$actual[] = $production->ID;
		}
		
		$expected = array(
			$this->production_with_historic_event,
			$this->production_with_historic_event_sticky,
			$this->production_with_upcoming_events,
			$this->production_with_upcoming_event,
			$this->production_with_upcoming_and_historic_events
		);	
		
		$this->assertEquals($expected,$actual);
	}
	 
	function test_order_events() {
					
	}
	
	// Test transients
	function test_wpt_transient_productions() {
		do_shortcode('[wpt_productions]');
		
		$args = array(
			'paginateby' => array(),
			'upcoming' => false,
			'season'=> false,
			'category'=> false,
			'groupby'=>false,
			'limit'=>false
		);
		
		$xml = new DomDocument;
        $xml->loadHTML($this->wp_theatre->transient->get('p',$args));
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod', 5, $xml);		
		
		/* 
		 * Test if transients are off for logged in users 
		 */
		 
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		wp_set_current_user( $user->ID );		
        $this->assertFalse($this->wp_theatre->transient->get('p',$args));		
	}
	
	function test_wpt_transient_events() {
		do_shortcode('[wpt_events]');
		
		$args = array(
			'upcoming' => true,
			'past' => false,
			'paginateby'=>array(),
			'category'=> false,
			'day'=> false,
			'month'=> false,
			'season'=> false,
			'groupby'=>false,
			'limit'=>false
		);
		
		$xml = new DomDocument;
        $xml->loadHTML($this->wp_theatre->transient->get('e',$args));
        $this->assertSelectCount('.wpt_events .wp_theatre_event', 4, $xml);				
	}
	
	function test_wpt_transient_reset() {
		/*
		 * This test will always fail if the transients are not stored in the DB (eg. memcached).
		 * Skip for now.
		 *
		 */
		
		return;
		
		do_shortcode('[wpt_productions]');
		
		$this->factory->post->create(); // trigger save_post hook

		$args = array(
			'paginateby' => array(),
			'upcoming' => false,
			'season'=> false,
			'category'=> false,
			'groupby'=>false,
			'limit'=>false
		);
		$this->assertFalse($this->wp_theatre->transient->get('prods',$args));					
	}
		
	// Tags
	function test_tag_archive() {
		return;
		
		// how do I test the output of a tag archive page?
		$args = array(
			'tag' => 'historic',
			'posts_per_page' => -1
		);
		$this->assertCount(2,get_posts($args));
	}
	
	// Test RSS feeds
	function test_upcoming_productions_feed() {
		$xml = new DomDocument;
        $xml->loadXML($this->wp_theatre->feeds->get_upcoming_productions());
        $this->assertSelectCount('rss channel item', 4, $xml);
	}
	
	function test_upcoming_events_feed() {
		$xml = new DomDocument;
        $xml->loadXML($this->wp_theatre->feeds->get_upcoming_events());
        $this->assertSelectCount('rss channel item', 4, $xml);
		
	}
	
	// Sticky posts
	
	function test_ignore_sticky_posts() {
		$args = array(
			'upcoming' => TRUE,
			'ignore_sticky_posts' => TRUE
		);
		$this->assertCount(3, $this->wp_theatre->productions->load($args));		
		
	}
	
	function test_theatre_class_is_global() {
		global $wp_theatre;
		$this->assertTrue( 
			is_object($wp_theatre) && 
			get_class($wp_theatre) == 'WP_Theatre'
		);
	}
	
}
