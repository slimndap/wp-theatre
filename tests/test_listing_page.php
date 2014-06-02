<?php

class WPT_Test_Listing_Page extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		
		parent::setUp();
		
		$this->wp_theatre = $wp_theatre;
		
		// create a page for our listing
		$args = array(
			'post_type'=>'page'
		);
		
		$this->options = array(
			'listing_page_post_id' => $this->factory->post->create($args),
			'listing_page_position' => 'above'
		);	
		update_option('wpt_listing_page', $this->options);

		$season_args = array(
			'post_type'=>WPT_Season::post_type_name
		);
		
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		
		/*
		 * Test content
		 */
		 
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


	/* Test the basics */

	function test_dedicated_listing_page_is_set() {
		$this->assertInstanceOf('WP_Post',$this->wp_theatre->listing_page->page());
	}
	
	function test_listing_appears_on_listing_page() {
		$this->go_to( get_permalink( $this->wp_theatre->listing_page->page() ) );

		$xml = new DomDocument;
		$xml->loadHTML( get_echo( 'the_content' ) );
		$this->assertSelectCount('.wpt_listing', 1, $xml);		
	}
		
	/* 
	 * Test output 
	 * 
	 * type (productions, events)
	 * pagination (month, category)
	 * grouping (month)
	 * template 
	 */

	function test_productions_on_listing_page() {
		$this->go_to( get_permalink( $this->wp_theatre->listing_page->page() ) );

		$xml = new DomDocument;
        $xml->loadHTML( get_echo( 'the_content' ) );

		/*
		 * Unlike [wpt_productions], listing page only shows productions with upcoming events.
		 * Expected: 3 productions with upcoming events + 1 sticky production
		 */
        $this->assertSelectCount('.wpt_productions .wp_theatre_prod', 4, $xml);		
	}
	
	function test_events_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);
		
		$this->go_to( get_permalink( $this->wp_theatre->listing_page->page() ) );

		$xml = new DomDocument;
        $xml->loadHTML( get_echo( 'the_content' ) );
        $this->assertSelectCount('.wpt_events .wp_theatre_event', 4, $xml);			
	}
	
	function test_events_are_paginated_by_day_on_listing_page() {
		
	}
	
	function test_events_are_paginated_by_week_on_listing_page() {
		
	}
	
	function test_events_are_paginated_by_month_on_listing_page() {
		flush_rewrite_rules();

		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'paginated';
		$this->options['listing_page_groupby'] = 'month';
		update_option('wpt_listing_page', $this->options);

		$months = $this->wp_theatre->events->months();
		$months = array_keys($months);
		
		$url = add_query_arg(
			'wpt_month',
			$months[0],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);
		
		$this->go_to($url);
		
        $matcher = array(
			'tag'        => 'nav',
			'attributes' => array('class' => 'wpt_listing_filter_pagination month')
		);
        $this->assertTag($matcher,  get_echo( 'the_content' ) );
        
        $matcher = array(
			'tag'        => 'div',
			'attributes' => array('class' => 'wp_theatre_event')
		);
        $this->assertTag($matcher,  get_echo( 'the_content' ) , get_echo( 'the_content' ));
	}
	
	function test_events_are_paginated_by_year_on_listing_page() {
		
	}
	
	function test_productions_are_paginated_by_category_on_listing_page() {
		flush_rewrite_rules();
		
		$this->options['listing_page_nav'] = 'paginated';
		$this->options['listing_page_groupby'] = 'category';
		update_option('wpt_listing_page', $this->options);

		$url = add_query_arg(
			'wpt_category',
			$this->category_film,
			get_permalink( $this->wp_theatre->listing_page->page() )
		);

		$this->go_to($url);
		
		$this->assertNotEmpty( get_query_var( 'wpt_category' ) );
		
		$content = get_echo( 'the_content' );
		$message = $content;
		
        $matcher = array(
			'tag'        => 'div',
			'attributes' => array('class' => 'wpt_listing wpt_productions')
		);
        $this->assertTag($matcher,  $content, $message );
        
        $matcher = array(
			'tag'        => 'div',
			'attributes' => array('class' => 'wp_theatre_prod')
		);
        $this->assertTag($matcher,  $content , $message);
	}

	function test_events_are_paginated_by_category_on_listing_page() {
		
	}

	function test_events_are_grouped_by_day_on_listing_page() {
		
	}
	
	function test_events_are_grouped_by_week_on_listing_page() {
		
	}
	
	function test_events_are_grouped_by_month_on_listing_page() {
		
	}
	
	function test_events_are_grouped_by_year_on_listing_page() {
		
	}
	
	function test_productions_are_grouped_by_category_on_listing_page() {
		
	}
	
	function test_events_are_grouped_by_category_on_listing_page() {
		
	}
	
	function test_events_are_filtered_by_day_on_listing_page() {
		
	}
	
	function test_events_are_filtered_by_week_on_listing_page() {
		
	}
	
	function test_events_are_filtered_by_month_on_listing_page() {
		
	}
	
	function test_events_are_filtered_by_year_on_listing_page() {
		
	}
	
	function test_productions_are_filtered_by_category_on_listing_page() {
		
	}
	
	function test_events_are_filtered_by_category_on_listing_page() {
		
	}
	
	/* 
	 * Test backwards compatibility
	 */
}
