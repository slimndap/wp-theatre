<?php

class WPT_Test_Listing_Page extends WP_UnitTestCase {

	function setUp() {
		global $wp_rewrite; 
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


		/* 
		 * Make sure permalink structure is consistent when running query tests.
		 * @see: https://core.trac.wordpress.org/ticket/27704#comment:7
		 * @see: https://core.trac.wordpress.org/changeset/28967
		 * @see: https://github.com/slimndap/wp-theatre/issues/48
		 */
		$wp_rewrite->init(); 
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies(); 
		$wp_rewrite->flush_rules();
	}


	/* Test the basics */

	function test_dedicated_listing_page_is_set() {
		$this->assertInstanceOf('WP_Post',$this->wp_theatre->listing_page->page());
	}
	
	function test_listing_appears_on_listing_page() {
		$this->go_to( get_permalink( $this->wp_theatre->listing_page->page() ) );

		$html = get_echo( 'the_content' );
		$this->assertEquals(1, substr_count($html, '<div class="wpt_listing'), $html);
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

		$html = get_echo( 'the_content' );

		/*
		 * Unlike [wpt_productions], listing page only shows productions with upcoming events.
		 * Expected: 3 productions with upcoming events + 1 sticky production
		 */

		$this->assertEquals(4, substr_count($html, '"wp_theatre_prod"'), $html);
	}
	
	function test_events_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);
		
		$this->go_to( get_permalink( $this->wp_theatre->listing_page->page() ) );

		$html = get_echo( 'the_content' );
		$this->assertEquals(4, substr_count($html, '"wp_theatre_event"'), $html);
	}
	
	function test_events_on_listing_page_with_content_field() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_template'] = '{{title}}{{content}}{{tickets}}';
		$this->options['listing_page_position'] = 'above';

		update_option('wpt_listing_page', $this->options);

		$this->go_to( get_permalink( $this->wp_theatre->listing_page->page() ) );

		$html = get_echo( 'the_content' );
		
		$this->assertEquals(4, substr_count($html, '"wp_theatre_event"'), $html);
		$this->assertEquals(4, substr_count($html, '"wp_theatre_prod_content"'), $html);

	}
	
	function test_events_are_paginated_by_day_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'paginated';
		$this->options['listing_page_groupby'] = 'day';
		update_option('wpt_listing_page', $this->options);

		$days = $this->wp_theatre->events->get_days(array('upcoming' => true));
		$days = array_keys($days);
		
		$url = add_query_arg(
			'wpt_day',
			$days[0],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);
		
		$this->go_to($url);
		
		$html = get_echo( 'the_content' );

		$this->assertEquals(1, substr_count($html, '"wpt_listing_filter_pagination day"'), $html);
		
		// Test if pagination navigation is present.
		$this->assertEquals(4, substr_count($html, '<span class="wpt_listing_filter'), $html);
		
		$this->assertEquals(1, substr_count($html, 'wpt_listing_filter_active'), $html);
		$this->assertEquals(1, substr_count($html, '"wpt_listing_filter_pagination day"'), $html);
		
	}
	
	function test_events_are_paginated_by_week_on_listing_page() {
		
	}
	
	function test_events_are_paginated_by_month_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'paginated';
		$this->options['listing_page_groupby'] = 'month';
		update_option('wpt_listing_page', $this->options);

		$months = $this->wp_theatre->events->get_months(array('upcoming' => true));
		$months = array_keys($months);
		
		$url = add_query_arg(
			'wpt_month',
			$months[0],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);
		
		$this->go_to($url);
		
		$html = get_echo( 'the_content' );

		$this->assertEquals(1, substr_count($html, '"wpt_listing_filter_pagination month"'), $html);
		$this->assertContains('"wp_theatre_event"', $html);
	}
	
	function test_events_are_paginated_by_year_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'paginated';
		$this->options['listing_page_groupby'] = 'year';
		update_option('wpt_listing_page', $this->options);

		$years = $this->wp_theatre->events->get_years(array('upcoming' => true));
		$years = array_keys($years);
		
		$url = add_query_arg(
			'wpt_year',
			$years[0],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);
		
		$this->go_to($url);
		
		$html = get_echo( 'the_content' );

		$this->assertEquals(1, substr_count($html, '"wpt_listing_filter_pagination year"'), $html);
		$this->assertContains('"wp_theatre_event"', $html);
		
	}
	
	function test_productions_are_paginated_by_category_on_listing_page() {
		$this->options['listing_page_nav'] = 'paginated';
		$this->options['listing_page_groupby'] = 'category';
		update_option('wpt_listing_page', $this->options);

		$url = add_query_arg(
			'wpt_category',
			'film',
			get_permalink( $this->wp_theatre->listing_page->page() )
		);

		$this->go_to($url);
		
		$html= get_echo( 'the_content' );
		$this->assertEquals(1, substr_count($html, '"wpt_listing_filter_pagination category"'), $html);
		$this->assertEquals(1, substr_count($html, '"wp_theatre_prod"'), $html);
		
	}

	function test_events_are_paginated_by_category_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'paginated';
		$this->options['listing_page_groupby'] = 'category';
		update_option('wpt_listing_page', $this->options);

		$url = add_query_arg(
			'wpt_category',
			'film',
			get_permalink( $this->wp_theatre->listing_page->page() )
		);

		$this->go_to($url);
		
		$html= get_echo( 'the_content' );
		$this->assertEquals(1, substr_count($html, '"wpt_listing_filter_pagination category"'), $html);
		$this->assertEquals(2, substr_count($html, '"wp_theatre_event"'), $html);
		
	}

	function test_events_are_grouped_by_day_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'grouped';
		$this->options['listing_page_groupby'] = 'day';
		update_option('wpt_listing_page', $this->options);

		$this->go_to(
			get_permalink($this->wp_theatre->listing_page->page())
		);

		$html= get_echo( 'the_content' );
		$this->assertEquals(4, substr_count($html, '"wpt_listing_group day"'), $html);
		
	}
	
	function test_events_are_grouped_by_week_on_listing_page() {
		
	}
	
	function test_events_are_grouped_by_month_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'grouped';
		$this->options['listing_page_groupby'] = 'month';
		update_option('wpt_listing_page', $this->options);

		$this->go_to(
			get_permalink($this->wp_theatre->listing_page->page())
		);

		$html= get_echo( 'the_content' );
		$this->assertContains('"wpt_listing_group month"', $html);
	}
	
	function test_events_are_grouped_by_year_on_listing_page() {
		
	}
	
	function test_productions_are_grouped_by_season_on_listing_page() {
		$this->options['listing_page_nav'] = 'grouped';
		$this->options['listing_page_groupby'] = 'season';
		update_option('wpt_listing_page', $this->options);

		$this->go_to(
			get_permalink($this->wp_theatre->listing_page->page())
		);

		$html= get_echo( 'the_content' );
		$this->assertEquals(2, substr_count($html, '"wpt_listing_group season"'), $html);
	}
	
	function test_productions_are_grouped_by_category_on_listing_page() {
		$this->options['listing_page_nav'] = 'grouped';
		$this->options['listing_page_groupby'] = 'category';
		update_option('wpt_listing_page', $this->options);

		$this->go_to(
			get_permalink($this->wp_theatre->listing_page->page())
		);
		$html= get_echo( 'the_content' );
		$this->assertEquals(2, substr_count($html, '"wpt_listing_group category"'), $html);
	}
	
	function test_events_are_grouped_by_category_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'grouped';
		$this->options['listing_page_groupby'] = 'category';
		update_option('wpt_listing_page', $this->options);

		$this->go_to(
			get_permalink($this->wp_theatre->listing_page->page())
		);

		$html= get_echo( 'the_content' );
		$this->assertEquals(2, substr_count($html, '"wpt_listing_group category"'), $html);

	}
	
	function test_events_are_filtered_by_day_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);

		$days = $this->wp_theatre->events->get_days(array('upcoming' => true));
		$days = array_keys($days);
		
		$url = add_query_arg(
			'wpt_day',
			$days[0],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);
		
		$this->go_to($url);

		$html= get_echo( 'the_content' );

		// Is the active filter shown?
		$this->assertEquals(1, substr_count($html, 'wpt_listing_filter_active"><a'), $html);

		// Are the filtered events shown?
		$this->assertEquals(1, substr_count($html, '"wp_theatre_event"'), $html);
		
	}
	
	function test_events_are_filtered_by_week_on_listing_page() {
		
	}
	
	function test_events_are_filtered_by_month_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);

		$months = $this->wp_theatre->events->get_months(array('upcoming' => true));
		$months = array_keys($months);
		
		$url = add_query_arg(
			'wpt_month',
			$months[0],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);
		
		$this->go_to($url);

		$html= get_echo( 'the_content' );

		// Is the active filter shown?
		$this->assertEquals(1, substr_count($html, 'wpt_listing_filter_active"><a'));

		// Are the filtered events shown?
		$this->assertContains('"wp_theatre_event"',$html);
	}
	
	function test_events_are_filtered_by_year_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);

		$years = $this->wp_theatre->events->get_years(array('upcoming' => true));
		$years = array_keys($years);
		
		$url = add_query_arg(
			'wpt_year',
			$years[0],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);
		
		$this->go_to($url);

		$html= get_echo( 'the_content' );

		// Is the active filter shown?
		$this->assertEquals(1, substr_count($html, 'wpt_listing_filter_active"><a'));

		// Are the filtered events shown?
		$this->assertContains('"wp_theatre_event"',$html);
		
	}
	
	function test_productions_are_filtered_by_category_on_listing_page() {
		update_option('wpt_listing_page', $this->options);

		$url = add_query_arg(
			'wpt_category',
			'film',
			get_permalink( $this->wp_theatre->listing_page->page() )
		);

		$this->go_to($url);
		
		$html= get_echo( 'the_content' );

		// Is the active filter shown?
		$this->assertEquals(1, substr_count($html, 'wpt_listing_filter_active"><a'), $html);

		// Are the filtered events shown?
		$this->assertEquals(1, substr_count($html, '"wp_theatre_prod"'), $html);
		
	}
	
	function test_events_are_filtered_by_category_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);

		$url = add_query_arg(
			'wpt_category',
			'film',
			get_permalink( $this->wp_theatre->listing_page->page() )
		);

		$this->go_to($url);
		
		$html= get_echo( 'the_content' );

		// Is the active filter shown?
		$this->assertEquals(1, substr_count($html, 'wpt_listing_filter_active"><a'), $html);

		// Are the filtered events shown?
		$this->assertEquals(2, substr_count($html, '"wp_theatre_event"'), $html);
		
	}

	
	function test_events_are_filtered_by_category_on_listing_page_with_pretty_permalinks() {
		
		/*
		 * Tried to write a test for this, but failed.
		 * Bail for now.
		 */
		return;
		
		global $wp_theatre;
		global $wp_rewrite;
		global $wp_query;

		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);

		$url = $wp_theatre->listing_page->url(array('wpt_category'=>'film'));
		$this->go_to($url);  // this doesn't work!!
		
		$html= get_echo( 'the_content' );

		// Is the active filter shown?
		$this->assertEquals(1, substr_count($html, 'wpt_listing_filter_active"><a'), $html);

		// Are the filtered events shown?
		$this->assertEquals(2, substr_count($html, '"wp_theatre_event"'), $html);
		
	}

	function test_events_on_production_page() {
		$this->options['listing_page_position_on_production_page'] = 'below';
		update_option('wpt_listing_page', $this->options);

		$this->go_to(
			get_permalink($this->production_with_upcoming_events)
		);

		$html= get_echo( 'the_content' );
		$this->assertEquals(2, substr_count($html, '"wp_theatre_event"'), $html);
	}
	
	function test_events_with_template_on_production_page() {
		$this->options['listing_page_position_on_production_page'] = 'above';
		$this->options['listing_page_template_on_production_page'] = '{{title}}template!';
		update_option('wpt_listing_page', $this->options);

		$this->go_to(
			get_permalink($this->production_with_upcoming_events)
		);

		$html = get_echo( 'the_content' );

        $this->assertContains('template!', $html, $html);			
	}
	
	function test_events_on_production_page_with_custom_heading() {
		
		function custom_heading() {
			return '<h3>custom heading</h3>';
		}
		
		$this->options['listing_page_position_on_production_page'] = 'below';
		update_option('wpt_listing_page', $this->options);

		add_filter('wpt_production_page_events_header', 'custom_heading');

		$this->go_to(
			get_permalink($this->production_with_upcoming_events)
		);

		$html= get_echo( 'the_content' );
		$this->assertEquals(1, substr_count($html, '<h3>custom heading</h3>'), $html);
	}
	
	function test_events_on_production_page_with_custom_content() {
		
		function custom_content() {
			return '<p>custom content</p>';
		}
		
		$this->options['listing_page_position_on_production_page'] = 'before';
		update_option('wpt_listing_page', $this->options);

		add_filter('wpt_production_page_content', 'custom_content');

		$this->go_to(
			get_permalink($this->production_with_upcoming_events)
		);

		$html= get_echo( 'the_content' );
		$this->assertEquals(1, substr_count($html, '<p>custom content</p>'), $html);
	}
	
	
	/**
	 * Tests if the 'events' header is not showing on a the page
	 * of a production without events.
	 */
	function test_events_on_production_page_for_production_without_events() {
		
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name,
			'post_content' => 'hallo',
		);
		
		// create production with upcoming event
		$production_without_events = $this->factory->post->create($production_args);

		$this->options['listing_page_position_on_production_page'] = 'below';
		update_option('wpt_listing_page', $this->options);

		$this->go_to(
			get_permalink($production_without_events)
		);

		$html= get_echo( 'the_content' );

		$this->assertEquals(0, substr_count($html, '<h3>Events</h3>'), $html);
		
	}
	
	function test_events_on_production_page_with_translated_header() {
		global $wp_theatre;
		update_option('wpt_listing_page', array('listing_page_position_on_production_page' => 'below'));
		
		update_option('wpt_language', array('language_events' => 'Shows'));
		$wp_theatre->wpt_language_options = get_option('wpt_language');
		

		$this->go_to(
			get_permalink($this->production_with_upcoming_event)
		);
		
		$html = get_echo( 'the_content' );
		
		$this->assertEquals(1, substr_count($html, '<h3>Shows</h3>'), $html);
		
		
	}
	
	function test_filter_pagination_urls_on_listing_page() {
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		$this->options['listing_page_nav'] = 'paginated';
		$this->options['listing_page_groupby'] = 'month';
		update_option('wpt_listing_page', $this->options);

		$months = $this->wp_theatre->events->get_months(array('upcoming' => true));
		$months = array_keys($months);
		
		$listing_page_url = get_permalink( $this->wp_theatre->listing_page->page() );
		$month_url = $listing_page_url.str_replace('-','/',$months[0]);
				
		$this->go_to($listing_page_url);
		
		$html = get_echo( 'the_content' );

		$this->assertContains($month_url, $html);
		
	}
	
	
	
	function test_shortcode_wpt_calendar() {
		$html = do_shortcode('[wpt_calendar]');
		$this->assertEquals(4, substr_count($html, '<td><a'), $html);		
	}
	
	/* 
	 * Test backwards compatibility
	 */
}
