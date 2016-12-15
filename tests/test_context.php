<?php
/**
 * Test the context parameter for events and productions.
 *
 * @group context
 */
 
 class WPT_Test_Context extends WP_UnitTestCase {
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

	function test_event_context_default() {
		
		$actual = do_shortcode('[wpt_events]');
		$expected = 'wpt_context_default';
		
		$this->assertContains($expected, $actual);
		
	}

	function test_event_context_production_events() {
		
		$actual = do_shortcode('[wpt_production_events production="'.$this->production_with_upcoming_events.'"]');
		$expected = 'wpt_context_production_events';
		
		$this->assertContains($expected, $actual);		
		
	}

	function test_event_context_custom() {

		$actual = do_shortcode('[wpt_events context="custom"]');
		$expected = 'wpt_context_custom';
		
		$this->assertContains($expected, $actual);
		
	}

	function test_event_html_filter_for_default_context() {
		
		$wrap_event = create_function(
			'$html, $event',
			'return \'<div class="wrap">\'.$html.\'</div>\';'
		);

		add_filter('wpt/event/html/context=default', $wrap_event, 10, 2);
		
		$actual = do_shortcode('[wpt_events]');
		$expected = '<div class="wrap">';
		
		$this->assertContains($expected, $actual);


	}
	
	function test_event_html_filter_for_production_events_context() {
		$wrap_event = create_function(
			'$html, $event',
			'return \'<div class="wrap">\'.$html.\'</div>\';'
		);
		add_filter('wpt/event/html/context=production_events', $wrap_event, 10, 2);
		
		$actual = do_shortcode('[wpt_production_events production="'.$this->production_with_upcoming_events.'"]');
		$expected = '<div class="wrap">';
		
		$this->assertContains($expected, $actual);
		
	}
	
	function test_event_html_filter_for_custom_context() {
		$wrap_event = create_function(
			'$html, $event',
			'return \'<div class="wrap">\'.$html.\'</div>\';'
		);
		add_filter('wpt/event/html/context=custom', $wrap_event, 10, 2);
		
		$actual = do_shortcode('[wpt_events context="custom"]');
		$expected = '<div class="wrap">';
		
		$this->assertContains($expected, $actual);
		
	}
	
	function test_event_html_filter_not_for_production_events_context() {
		$wrap_event = create_function(
			'$html, $event',
			'return \'<div class="wrap">\'.$html.\'</div>\';'
		);
		add_filter('wpt/event/html/context=default', $wrap_event, 10, 2);
		
		$actual = do_shortcode('[wpt_production_events production="'.$this->production_with_upcoming_events.'"]');
		$expected = '<div class="wrap">';
		
		$this->assertNotContains($expected, $actual);
		
	}

	function test_productions_context_default() {
		
		$actual = do_shortcode('[wpt_productions]');
		$expected = 'wpt_context_default';
		
		$this->assertContains($expected, $actual);
		
	}

	function test_productions_context_custom() {

		$actual = do_shortcode('[wpt_productions context="custom"]');
		$expected = 'wpt_context_custom';
		
		$this->assertContains($expected, $actual);
		
	}

	function test_productions_html_filter_for_default_context() {
		
		$wrap_production = create_function(
			'$html, $production',
			'return \'<div class="wrap">\'.$html.\'</div>\';'
		);

		add_filter('wpt/production/html/context=default', $wrap_production, 10, 2);
		
		$actual = do_shortcode('[wpt_productions]');
		$expected = '<div class="wrap">';
		
		$this->assertContains($expected, $actual);


	}
	
	function test_productions_html_filter_for_custom_context() {
		$wrap_production = create_function(
			'$html, $production',
			'return \'<div class="wrap">\'.$html.\'</div>\';'
		);
		add_filter('wpt/production/html/context=custom', $wrap_production, 10, 2);
		
		$actual = do_shortcode('[wpt_productions context="custom"]');
		$expected = '<div class="wrap">';
		
		$this->assertContains($expected, $actual);
		
	}
	
	function test_productions_html_filter_not_for_custom_context() {
		$wrap_production = create_function(
			'$html, $production',
			'return \'<div class="wrap">\'.$html.\'</div>\';'
		);
		add_filter('wpt/production/html/context=default', $wrap_production, 10, 2);
		
		$actual = do_shortcode('[wpt_productions context="custom"]');
		$expected = '<div class="wrap">';
		
		$this->assertNotContains($expected, $actual);
	}
	
	function test_production_title_filter_for_custom_context() {
		$set_custom_production_title = create_function(
			'$title, $production',
			'global $wp_theatre; if (\'custom\'==$wp_theatre->context->get_context()) { $title = \'Custom title\'; } return $title;'
		);
		add_filter('wpt_production_title', $set_custom_production_title, 10, 2);
		
		$actual = do_shortcode('[wpt_productions context="custom"]');
		$expected = 'Custom title';
		
		$this->assertContains($expected, $actual);
	}
	
}
