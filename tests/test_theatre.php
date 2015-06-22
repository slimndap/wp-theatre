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
		add_post_meta($this->upcoming_event_with_prices, 'venue', 'Paard van Troje');
		add_post_meta($this->upcoming_event_with_prices, 'city', 'Den Haag');
		
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
		$this->assertCount(7, $this->wp_theatre->events->get());		
	}

	function test_productions_are_loaded() {
		$this->assertCount(5, $this->wp_theatre->productions->get());		
	}
	
	function test_seasons_are_loaded() {
		$this->assertCount(2, $this->wp_theatre->seasons());
	}


	function test_upcoming_productions() {
		$args = array(
			'upcoming' => TRUE
		);
		$this->assertCount(4, $this->wp_theatre->productions->get($args));		
		
	}

	// Test sync between productions and connected events
	function test_connected_events_are_trashed_when_production_is_trashed() {
		foreach($this->wp_theatre->productions->get() as $production) {
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
		foreach($this->wp_theatre->productions->get() as $production) {
			wp_trash_post($production->ID);
			wp_untrash_post($production->ID);
		}
		$this->assertCount(7, $this->wp_theatre->events->get());		
		
	}
	
	function test_connected_events_are_deleted_when_production_is_deleted() {
		foreach($this->wp_theatre->productions->get() as $production) {
			wp_delete_post($production->ID);
		}
		$this->assertCount(0, $this->wp_theatre->events->get());
	}
	
	function test_event_inherits_categories_from_production() {
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		$production = $this->factory->post->create($production_args);

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		$event = $this->factory->post->create($event_args);
		add_post_meta($event, WPT_Production::post_type_name, $production);
		add_post_meta($event, 'event_date', date('Y-m-d H:i:s', strtotime('tomorrow')));

		$category = wp_create_category('testcategory');
		wp_set_post_categories($production, array($category));

		$this->assertEquals(1, substr_count(do_shortcode('[wpt_events category="testcategory"]{{title}}{{categories}}[/wpt_events]'), '"wp_theatre_event"'));
		
	}
	
	function test_event_inherits_season_from_production() {
		$season_args = array(
			'post_type'=>WPT_Season::post_type_name
		);
		$season = $this->factory->post->create($season_args);

		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		$production = $this->factory->post->create($production_args);
		add_post_meta($production, WPT_Season::post_type_name, $season);
		
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		$event = $this->factory->post->create($event_args);
		add_post_meta($event, WPT_Production::post_type_name, $production);
		add_post_meta($event, 'event_date', date('Y-m-d H:i:s', strtotime('tomorrow')));
		
		$html = do_shortcode('[wpt_events season='.$season.']');

		$this->assertEquals(1, substr_count($html, '"wp_theatre_event"'), $html);
		
	}
	
	// Test shortcodes
	function test_shortcode_wpt_productions() {
		$this->assertEquals(5, substr_count(do_shortcode('[wpt_productions]'), '"wp_theatre_prod"'));
	}
	
	function test_shortcode_wpt_productions_default_template_filter() {
		$func = create_function(
			'$template',
			'$template = "{{title}} test content";	return $template;'
		);
		
		add_filter('wpt_production_template_default', $func);
		
		$this->assertContains('test content', do_shortcode('[wpt_productions]'));
	}
	
	function test_shortcode_wpt_productions_filter_season() {
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_productions season="'.$this->season1.'"]'), '"wp_theatre_prod"'));
	}

	function test_shortcode_wpt_productions_filter_post() {
		
		// test with post__in
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_productions post__in="'.$this->production_with_upcoming_events.'"]'), '"wp_theatre_prod"'));

		$this->assertEquals(2, substr_count(do_shortcode('[wpt_productions post__in="'.$this->production_with_upcoming_events.','.$this->production_with_upcoming_and_historic_events.'"]'), '"wp_theatre_prod"'));

		// test with an excluded post__not_in
		$this->assertEquals(4, substr_count(do_shortcode('[wpt_productions post__not_in="'.$this->production_with_upcoming_events.'"]'), '"wp_theatre_prod"'));
		
		$this->assertEquals(3, substr_count(do_shortcode('[wpt_productions post__not_in="'.$this->production_with_upcoming_events.','.$this->production_with_upcoming_and_historic_events.'"]'), '"wp_theatre_prod"'));
	}
	
	function test_shortcode_wpt_productions_filter_category() {

		// test with cat
		$result = do_shortcode('[wpt_productions cat="'.$this->category_film.','.$this->category_muziek.'"]');
		$this->assertEquals(2, substr_count($result, '"wp_theatre_prod"'), $result);

		$result = do_shortcode('[wpt_productions cat="-'.$this->category_film.','.$this->category_muziek.'"]');
		$this->assertEquals(1, substr_count($result, '"wp_theatre_prod"'), $result);
		
		// test with category_name
		$result = do_shortcode('[wpt_productions category_name="muziek,film"]');
		$this->assertEquals(2, substr_count($result, '"wp_theatre_prod"'), $result);
		
		// test with an excluded category__in
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_productions category__in="'.$this->category_film.'"]'), '"wp_theatre_prod"'));

		// test with an excluded category__not_in
		// should list all productions except 1.
		$this->assertEquals(4, substr_count(do_shortcode('[wpt_productions category__not_in="'.$this->category_film.'"]'), '"wp_theatre_prod"'));

		// test with an excluded category__and_in
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_productions category__and="'.$this->category_film.','.$this->category_muziek.'"]'), '"wp_theatre_prod"'));

	}
	
	function test_shortcode_wpt_productions_filter_category_deprecated() {
		// test with mixed category-slug and category-id
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_productions category="muziek,'.$this->category_film.'"]'), '"wp_theatre_prod"'));
		
		// test with an excluded category
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_productions category="muziek,-'.$this->category_film.'"]'), '"wp_theatre_prod"'));

		
	}
	
	function test_shortcode_wpt_event_tickets() {
		$this->assertEquals(4, substr_count(do_shortcode('[wpt_events]'), '"wp_theatre_event"'));
	}
	
	function test_shortcode_wpt_events_default_template_filter() {
		$func = create_function(
			'$template',
			'$template = "{{title}} test content";	return $template;'
		);
		
		add_filter('wpt_event_template_default', $func);
		
		$this->assertContains('test content', do_shortcode('[wpt_events]'));
	}
	
	function test_shortcode_wpt_events_magic_dates() {
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		$production = $this->factory->post->create($production_args);

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		$event_today = $this->factory->post->create($event_args);
		add_post_meta($event_today, WPT_Production::post_type_name, $production);
		add_post_meta($event_today, 'event_date', date('Y-m-d H:i:s', strtotime('today')));

		$this->assertEquals(1, substr_count(do_shortcode('[wpt_events day="today" upcoming="false"]'), '"wp_theatre_event"'));
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_events day="tomorrow"]'), '"wp_theatre_event"'));
	}
	
	function test_shortcode_wpt_events_start_end() {
		$this->assertEquals(5, substr_count(do_shortcode('[wpt_events start="yesterday"]'), '"wp_theatre_event"'));
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_events start="today" end="+2 days"]'), '"wp_theatre_event"'));
		$this->assertEquals(3, substr_count(do_shortcode('[wpt_events start="'.date('Y-m-d',time() + (2 * DAY_IN_SECONDS)).'"]'), '"wp_theatre_event"'));
		$this->assertEquals(3, substr_count(do_shortcode('[wpt_events end="now"]'), '"wp_theatre_event"'));
		$this->assertEquals(4, substr_count(do_shortcode('[wpt_events start="today" end="+2 weeks"]'), '"wp_theatre_event"'));
	}
	
	function test_shortcode_wpt_events_filter_season() {
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_events season="'.$this->season2.'"]'), '"wp_theatre_event"'));
	}
	
	function test_shortcode_wpt_events_filter_category() {

		// test with cat
		$result = do_shortcode('[wpt_events cat="'.$this->category_film.','.$this->category_muziek.'"]');
		$this->assertEquals(3, substr_count($result, '"wp_theatre_event"'), $result);

		$result = do_shortcode('[wpt_events cat="-'.$this->category_film.','.$this->category_muziek.'"]');
		$this->assertEquals(1, substr_count($result, '"wp_theatre_event"'), $result);
		
		// test with category_name
		$this->assertEquals(3, substr_count(do_shortcode('[wpt_events category_name="muziek"]'), '"wp_theatre_event"'));

		$result = do_shortcode('[wpt_events category_name="muziek,film"]');
		$this->assertEquals(3, substr_count($result, '"wp_theatre_event"'), $result);
		
		// test with an excluded category__in
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_events category__in="'.$this->category_film.'"]'), '"wp_theatre_event"'));

		// test with category__not_in
		// should list events from all productions except 1.
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_events category__not_in="'.$this->category_film.'"]'), '"wp_theatre_event"'));

		// test with category__and_in
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_events category__and="'.$this->category_film.','.$this->category_muziek.'"]'), '"wp_theatre_event"'));



	}
	
	function test_shortcode_wpt_events_filter_category_deprecated() {
		$this->assertEquals(3, substr_count(do_shortcode('[wpt_events category="muziek"]'), '"wp_theatre_event"'));
	}
	
	function test_shortcode_wpt_events_paginated_with_historic_events() {
		$html = do_shortcode('[wpt_events end="tomorrow - 11 months" paginateby="month"]');

		/*
		 * Should produce a paginated list with only one page 
		 * for $this->production_with_historic_event_sticky.
		 */
		 
		$expected_production = new WPT_Production($this->production_with_historic_event_sticky);
		$expected_events = $expected_production->events();
		$expected_event = $expected_events[0];
		$expected_class = 'month-'.date('Y-m',$expected_event->datetime());

		$this->assertEquals(1, substr_count($html, '<span class="wpt_listing_filter '.$expected_class.'"><a href="'));
	}

	function test_shortcode_wpt_events_order() {

		/*
		 * Ascending (default).
		 * Expect the production with the first upcoming event.
		 */
		$link = '<a href="'.get_permalink($this->production_with_upcoming_events).'">';
		$output = do_shortcode('[wpt_events limit=1]');
		$this->assertContains($link,$output);

		/*
		 * Descending.
		 * Expect the production with the last upcoming event.
		 */
		$link = '<a href="'.get_permalink($this->production_with_upcoming_and_historic_events).'">';
		$output = do_shortcode('[wpt_events limit=1 order=desc]');
		$this->assertContains($link,$output);

	}
	
	function test_shortcode_wpt_season_production() {
		$season = get_post($this->season1);
	}
	
	function test_shortcode_wpt_season_events() {
		$season = get_post($this->season2);
	}
	
	function test_shortcode_wpt_production_events() {
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_production_events production="'.$this->production_with_upcoming_events.'"]'), '"wp_theatre_event"'));		
	}
	
	/**
	 * Tests if [wpt_production_events] only shows upcoming events 
	 * when no time filters are set.
	 */
	function test_shortcode_production_events_defaults_to_upcoming() {
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_production_events production="'.$this->production_with_upcoming_and_historic_events.'"]'), '"wp_theatre_event"'));		
	}
	
	// Test templates
	
	function test_wpt_events_template_permalink_filter() {
		$link = '<a href="'.get_permalink($this->production_with_upcoming_event).'">';
		$output = do_shortcode('[wpt_events]{{location|permalink}}[/wpt_events]');

		$this->assertContains($link,$output);
	}
	
	function test_wpt_events_template_date_filter() {
		$date_format = 'j M xxx';

		$event = new WPT_Event($this->upcoming_event_with_prices);

		$formatted_date = date( $date_format , $event->datetime());
		
		$output = do_shortcode('[wpt_events]{{datetime|date("'.$date_format.'")|permalink}}[/wpt_events]');
		
		$this->assertContains($formatted_date, $output);
	}
	
	function test_wpt_events_with_post_args() {
		
		$html = do_shortcode('[wpt_events post__in="'.$this->upcoming_event_with_prices.'"]');

		$this->assertEquals(1, substr_count($html, '"wp_theatre_event"'));		

		$html = do_shortcode('[wpt_events post__not_in="'.$this->upcoming_event_with_prices.'"]');

		$this->assertEquals(3, substr_count($html, '"wp_theatre_event"'));		
	}

	function test_wpt_events_with_custom_atts() {
		
		$defaults_func = create_function(
			'$defaults',
			'$defaults[\'venue\'] = false; return $defaults;'
		);
		add_filter( 'wpt/frontend/shortcode/events/defaults', $defaults_func, 10 );
		add_filter( 'wpt/events/get/defaults', $defaults_func, 10 );
		
		$args_func = create_function(
			'$args,$filters',
			'if ($filters[\'venue\']) { $args[\'meta_query\'][] = array(\'key\'=>\'venue\', \'value\'=>$filters[\'venue\']); } return $args;'
		);
		add_filter( 'wpt/events/get/args', $args_func, 10, 2 );

		$html = do_shortcode('[wpt_events venue="Paard van Troje"]');
		
		$this->assertEquals(1, substr_count($html, '"wp_theatre_event"'));		
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

		$this->assertEquals(5, substr_count($html, 'wp_theatre_prod_director'));		
	}
	
	function test_shortcode_wpt_productions_with_custom_field_and_filter() {
		$director = 'Steven Spielberg';
	
		update_post_meta(
			$this->production_with_upcoming_event, 
			'director', 
			$director
		);
		
		$html = do_shortcode('[wpt_productions]{{title}}{{director|permalink}}[/wpt_productions]');

		$this->assertContains($director,$html);

		$this->assertEquals(1, substr_count($html, 'wp_theatre_prod_director"><a'));		
	}
	
	function test_shortcode_wpt_events_with_custom_field() {
		$director = 'Steven Spielberg';
	
		update_post_meta(
			$this->production_with_upcoming_and_historic_events, 
			'director', 
			$director
		);
		
		update_post_meta(
			$this->upcoming_event_with_prices, 
			'director', 
			'George Lucas'
		);
		
		$html = do_shortcode('[wpt_events]{{title}}{{director}}[/wpt_events]');

		$this->assertContains($director,$html);

		$this->assertEquals(4, substr_count($html, 'wp_theatre_event_director'), $html);		
		$this->assertEquals(1, substr_count($html, $director), $html);		
		$this->assertEquals(1, substr_count($html, 'George Lucas'), $html);		
	}
	
	// Test event features
	function test_wpt_event_tickets_status_cancelled() {
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_events]'), 'wp_theatre_event_tickets_status_cancelled'));		
	}
	
	function test_wpt_event_tickets_status_hidden() {
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_events]'), '"wp_theatre_event_tickets_status'), do_shortcode('[wpt_events]'));		
	}
	
	function test_wpt_event_tickets_status_other() {
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_events]'), 'wp_theatre_event_tickets_status_other'));		
	}
	
	function test_wpt_event_tickets_prices() {
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_events]'), 'wp_theatre_event_prices'));		
	}
	
	function test_wpt_event_tickets_status_filter() {
		global $wp_theatre;
		
		$func = create_function(
			'$status, $event',
			'$status = "new status";	return $status;'
		);
		add_filter( 'wpt_event_tickets_status', $func, 10 , 2 );
		
		$event = new WPT_Event($this->upcoming_event_with_prices);
		$args = array(
			'html' => true,
		);
		$html = $event->tickets($args);
		$this->assertContains('new status', $event->tickets($args));
	}
	
	function test_wpt_event_tickets_prices_filter() {
		global $wp_theatre;
		
		$func = create_function(
			'$html, $event',
			'return "tickets prices";'
		);
		add_filter( 'wpt_event_tickets_prices_html', $func, 10 , 2 );
		
		$event = new WPT_Event($this->upcoming_event_with_prices);
		$args = array(
			'html' => true,
		);
		$this->assertContains('tickets prices', $event->tickets($args));
	}
	
	
	function test_wpt_event_tickets() {
		$url = 'http://slimndap.com';
		update_post_meta($this->upcoming_event_with_prices,'tickets_url',$url);
		$event = new WPT_Event($this->upcoming_event_with_prices);
		$this->assertEquals($url, $event->tickets());
	}
	
	function test_wpt_event_tickets_filter() {
		global $wp_theatre;
		
		$func = create_function(
			'$url, $event',
			'return "tickets url";'
		);
		add_filter( 'wpt_event_tickets', $func, 10 , 2 );
		
		add_post_meta($this->upcoming_event_with_prices, 'tickets_url', 'http://slimndap.com');

		
		$event = new WPT_Event($this->upcoming_event_with_prices);
		$this->assertContains('tickets url', $event->tickets());
	}
	
	function test_wpt_event_tickets_html_filter() {
		global $wp_theatre;
		
		$func = create_function(
			'$html, $event',
			'return "tickets button";'
		);
		add_filter( 'wpt_event_tickets_html', $func, 10 , 2 );
		
		$event = new WPT_Event($this->upcoming_event_with_prices);
		$this->assertContains('tickets button', $event->tickets(array('html'=>true)));
	}
	
	function test_wpt_event_tickets_url() {
		add_post_meta($this->upcoming_event_with_prices, 'tickets_url', 'http://slimndap.com');
		$this->assertEquals(1, substr_count(do_shortcode('[wpt_events]'), 'wp_theatre_event_tickets_url'));			
	}
	
	function test_wpt_event_tickets_url_with_iframe() {
		
		global $wp_theatre;
		
		$wp_theatre->wpt_tickets_options = 	array(
			'integrationtype' => 'iframe',
			'iframepage' => '',
			'currencysymbol' => '$',
		);
		
		add_post_meta($this->upcoming_event_with_prices, 'tickets_url', 'http://slimndap.com');
		
		$html = do_shortcode('[wpt_events]');
		
		$this->assertEquals(1, substr_count($html, 'wp_theatre_integrationtype_iframe'));			
		$this->assertNotContains('http://slimndap.com', $html);
	}
	
	function test_wpt_event_tickets_prices_summary() {
		$event = new WPT_Event($this->upcoming_event_with_prices);
		$args = array(
			'summary'=>true
		);
		$this->assertContains('8.50', $event->prices($args));
	}
	
	function test_wpt_event_tickets_for_past_events_are_hiddedn() {

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_historic_event);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS));
		add_post_meta($event_id, '_wpt_event_tickets_price', 12);
		
		$event = new WPT_Event($event_id);
		$this->assertEmpty($event->tickets());
		
	}
	
	function test_wpt_event_tickets_html_for_past_events_are_hiddedn() {

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		$event_id = $this->factory->post->create($event_args);
		add_post_meta($event_id, WPT_Production::post_type_name, $this->production_with_historic_event);
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS));
		add_post_meta($event_id, '_wpt_event_tickets_price', 12);
		
		$html = do_shortcode('[wpt_events end="now"]');
		
		$this->assertNotContains('wp_theatre_event_prices',$html);
		
	}
	
	/**
	 * Test if named prices are sanitized.
	 */
	function test_wpt_event_tickets_prices_named() {
		add_post_meta($this->upcoming_event_with_prices, '_wpt_event_tickets_price', '1123|named_price');
		
		$event = new WPT_Event($this->upcoming_event_with_prices);
		$prices = $event->prices();
		$this->assertNotContains("1123|named_price",implode('',$prices));		

	}
	
	function test_wpt_events_content() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$this->assertEquals(4, substr_count(do_shortcode('[wpt_events]{{title}}{{content}}[/wpt_events]'), 'wp_theatre_prod_content'));	}
	
	function test_wpt_productions_content() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$this->assertEquals(5, substr_count(do_shortcode('[wpt_productions]{{title}}{{content}}[/wpt_productions]'), 'wp_theatre_prod_content'));
	}
	
	function test_wpt_events_excerpt() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$this->assertEquals(4, substr_count(do_shortcode('[wpt_events]{{title}}{{excerpt}}[/wpt_events]'), 'wp_theatre_prod_excerpt'));
	}
	
	function test_wpt_productions_excerpt() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$this->assertEquals(5, substr_count(do_shortcode('[wpt_productions]{{title}}{{excerpt}}[/wpt_productions]'), 'wp_theatre_prod_excerpt'));
	}
	
	function test_wpt_events_categories() {
		$this->assertEquals(5, substr_count(do_shortcode('[wpt_events]{{title}}{{categories}}[/wpt_events]'), '"wpt_production_category'));
	}
	
	function test_wpt_productions_categories() {
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_productions]{{title}}{{categories}}[/wpt_productions]'), 'wpt_production_category_muziek'));
	}
	
	// Test order
	function test_order_productions() {
		$actual = array();
		$productions = $this->wp_theatre->productions->get();
		foreach($productions as $production) {
			$actual[] = $production->ID;
		}
		
		$expected = array(
			$this->production_with_historic_event, // no upcoming events, follows creation order.
			$this->production_with_historic_event_sticky, // no upcoming events, follows creation order.
			$this->production_with_upcoming_events, // tomorrow
			$this->production_with_upcoming_event, // in 2 days
			$this->production_with_upcoming_and_historic_events // next week
		);	
		
		$this->assertEquals($expected,$actual);
	}
	 
	function test_order_productions_desc() {
		$actual = array();
		$args = array(
			'order' => 'desc'
		);
		$productions = $this->wp_theatre->productions->get($args);
		foreach($productions as $production) {
			$actual[] = $production->ID;
		}

		$expected = array(
			$this->production_with_upcoming_and_historic_events, // next week
			$this->production_with_upcoming_event, // in 2 days
			$this->production_with_upcoming_events, // tomorrow
			$this->production_with_historic_event, // no upcoming events, follows creation order.
			$this->production_with_historic_event_sticky, // no upcoming events, follows creation order.
		);
		$this->assertEquals($expected,$actual);
	}
	 
	// Test transients
	function test_wpt_transient_productions() {
		global $wp_query;
		
		do_shortcode('[wpt_productions]');
		
		$args = array(
			'paginateby' => array(),
			'post__in' => false,
			'post__not_in' => false,
			'upcoming' => false,
			'season'=> false,
			'category'=> false, // deprecated since v0.9.
			'cat'=>false,
			'category_name'=>false,
			'category__and'=>false,
			'category__in'=>false,
			'category__not_in'=>false,
			'groupby'=>false,
			'limit'=>false,
			'order'=>'asc',
		);
		$unique_args = array_merge(
			array( 'atts' => $args ), 
			array( 'wp_query' => $wp_query->query_vars )
		);
		
		$this->assertEquals(5, substr_count($this->wp_theatre->transient->get('p',$unique_args), '"wp_theatre_prod"'));
		
		/* 
		 * Test if transients are off for logged in users 
		 */
		 
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		wp_set_current_user( $user->ID );		
        $this->assertFalse($this->wp_theatre->transient->get('p',$args));		
		wp_set_current_user(0);		
	}
	
	/*
	 * Tests if the transients don't mess up paginated views.
	 * See: https://github.com/slimndap/wp-theatre/issues/88
	 */
	function test_wpt_transient_productions_with_pagination() {
		global $wp_query;
		
		/*
		 * Test if the film tab is active.
		 */
		$wp_query->query_vars['wpt_category'] = 'film';
		$html = do_shortcode('[wpt_productions paginateby=category]');
		$this->assertContains('category-film wpt_listing_filter_active',$html);

		/*
		 * Test if the muziek tab is active.
		 */
		$wp_query->query_vars['wpt_category'] = 'muziek';
		$html = do_shortcode('[wpt_productions paginateby=category]');
		$this->assertContains('category-muziek wpt_listing_filter_active',$html);
	}
	
	function test_wpt_transient_events() {
		global $wp_query;
		
		do_shortcode('[wpt_events]');
		
		/** 
		 * Copy the defaults from WPT_Frontend::wpt_events
		 * Set 'start' to 'now' (with quotes).
		 */
		$defaults = array(
			'paginateby'=>array(),
			'post__in' => false,
			'post__not_in' => false,
			'category'=> false, // deprecated since v0.9.
			'cat'=>false,
			'category_name'=>false,
			'category__and'=>false,
			'category__in'=>false,
			'category__not_in'=>false,
			'day' => false,
			'month' => false,
			'year' => false,
			'season'=> false,
			'start' => 'now',
			'end' => false,
			'groupby'=>false,
			'limit'=>false,
			'order'=>'asc',
		);
		
		$unique_args = array_merge(
			array( 'atts' => $defaults ), 
			array( 'wp_query' => $wp_query->query_vars )
		);
		
		$this->assertEquals(4, substr_count($this->wp_theatre->transient->get('e',$unique_args), '"wp_theatre_event"'));
	}
	
	/*
	 * Tests if the transients don't mess up paginated views.
	 * See: https://github.com/slimndap/wp-theatre/issues/88
	 */
	function test_wpt_transient_events_with_pagination() {
		global $wp_query;
		
		/*
		 * Test if the film tab is active.
		 */
		$wp_query->query_vars['wpt_category'] = 'film';
		$html = do_shortcode('[wpt_events paginateby=category]');
		$this->assertContains('category-film wpt_listing_filter_active',$html);

		/*
		 * Test if the muziek tab is active.
		 */
		$wp_query->query_vars['wpt_category'] = 'muziek';
		$html = do_shortcode('[wpt_events paginateby=category]');
		$this->assertContains('category-muziek wpt_listing_filter_active',$html);
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
		$this->assertEquals(4, substr_count($this->wp_theatre->feeds->get_upcoming_productions(), '<item'));
	}
	
	function test_upcoming_events_feed() {
		$this->assertEquals(4, substr_count($this->wp_theatre->feeds->get_upcoming_events(), '<item'));		
	}
	
	// Sticky posts
	
	function test_ignore_sticky_posts() {
		$args = array(
			'upcoming' => TRUE,
			'ignore_sticky_posts' => TRUE
		);
		$this->assertCount(3, $this->wp_theatre->productions->get($args));		
		
	}
	
	function test_sticky_productions_with_post__not_in() {

		/*
		 * Exclude a regular production.
		 * Expect all productions (5), except $this->production_with_upcoming_event.
		 */
		$html = do_shortcode('[wpt_productions post__not_in="'.$this->production_with_upcoming_event.'"]');
		$this->assertEquals(4, substr_count($html, '"wp_theatre_prod"'));

		/*
		 * Exclude a sticky production.
		 * Expect all productions (5), except $this->production_with_historic_event_sticky.
		 */
		$html = do_shortcode('[wpt_productions post__not_in="'.$this->production_with_historic_event_sticky.'"]');
		$this->assertEquals(4, substr_count($html, '"wp_theatre_prod"'));
	}
		
	function test_sticky_productions_with_category__not_in() {


		// Give one of the sticky productions a category as well.
		wp_set_post_categories($this->production_with_historic_event_sticky, array($this->category_film));

		/*
		 * Expect all productions (5), except productions in the film category (2).
		 */
		$html = do_shortcode('[wpt_productions category__not_in="'.$this->category_film.'"]');
		$this->assertEquals(3, substr_count($html, '"wp_theatre_prod"'));

		
	}
		
	function test_wpt_events_groupby_day() {
				
		$html = do_shortcode('[wpt_events groupby="day"]');
		
		// should contain 'wpt_listing_group day'.
		$this->assertEquals(4, substr_count($html, '<h3 class="wpt_listing_group day">'));
		
		//should show the same number of events as 'wpt_events'.
		$this->assertEquals(4, substr_count($html, '"wp_theatre_event"'));
		
	}
	
	function test_wpt_events_groupby_month() {

		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
			
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		// one year from now.
		$event_date = time() + YEAR_IN_SECONDS;

		// create production with 1 upcoming event in a year.
		$production_in_a_year = $this->factory->post->create($production_args);
		$upcoming_event = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event, WPT_Production::post_type_name, $production_in_a_year);
		add_post_meta($upcoming_event, 'event_date', date('Y-m-d H:i:s', $event_date));
				
		$html = do_shortcode('[wpt_events groupby="month"]');
		
		// should contain 'wpt_listing_group month'.
		$this->assertContains('<h3 class="wpt_listing_group month">'.date_i18n('F',$event_date).'</h3>', $html);
		
		//should show the same number of events as 'wpt_events' (4) + the new event.
		$this->assertEquals(5, substr_count($html, '"wp_theatre_event"'));
		
	}

	function test_wpt_events_groupby_category() {
				
		$html = do_shortcode('[wpt_events groupby="category"]');
		
		// should contain 'wpt_listing_group day'.
		$this->assertEquals(2, substr_count($html, '<h3 class="wpt_listing_group category">'));
		$this->assertEquals(1, substr_count($html, '<h3 class="wpt_listing_group category">muziek'));
		$this->assertEquals(1, substr_count($html, '<h3 class="wpt_listing_group category">film'));
		
		//should show the 2 events for film and 3 events for muziek.
		$this->assertEquals(5, substr_count($html, '"wp_theatre_event"'));
		
	}
	function test_wpt_productions_groupby_category() {
				
		$html = do_shortcode('[wpt_productions groupby="category"]');
		
		// should contain 'wpt_listing_group day'.
		$this->assertEquals(2, substr_count($html, '<h3 class="wpt_listing_group category">'));
		$this->assertEquals(1, substr_count($html, '<h3 class="wpt_listing_group category">muziek'));
		$this->assertEquals(1, substr_count($html, '<h3 class="wpt_listing_group category">film'));
		
		//should show the 2 events for film and 3 events for muziek.
		$this->assertEquals(3, substr_count($html, '"wp_theatre_prod"'));
		
	}
	
	function test_wpt_productions_groupby_season() {
	
		$html = do_shortcode('[wpt_productions groupby="season"]');
		
		$this->assertEquals(2, substr_count($html, '<h3 class="wpt_listing_group season">'));
		
	}
	
	
	function test_wpt_productions_load_args_filter() {
		global $wp_theatre;
		
		$func = create_function(
			'$args',
			'$args["category_name"] = "muziek";	return $args;'
		);
		
		add_filter('wpt_productions_load_args', $func);
		
		// Should return 2 productions in the muziek category.
		$this->assertCount(2, $this->wp_theatre->productions->get());		
		
	}
	
	function test_wpt_events_load_args_filter() {
		global $wp_theatre;
		
		$func = create_function(
			'$args',
			'$args["category_name"] = "muziek";	return $args;'
		);
		add_filter('wpt_events_load_args', $func);
		
		// Should only return all events of 2 productions that are in the muziek and film categories.
		$this->assertCount(3, $this->wp_theatre->events->get());		
		
	}
	
	function test_theatre_class_is_global() {
		global $wp_theatre;
		$this->assertTrue( 
			is_object($wp_theatre) && 
			get_class($wp_theatre) == 'WP_Theatre'
		);
	}
	
	function test_wpt_events_unique_args() {
		global $wp_query;
		$wp_query->query_vars['category__in'] = array(123);
		
		$html_with_one_event = do_shortcode('[wpt_events category__in="'.$this->category_muziek.'"]');
		$html_with_two_events = do_shortcode('[wpt_events post__in="'.$this->category_muziek.','.$this->category_film.'"]');
		$this->assertNotEquals($html_with_one_event, $html_with_two_events);
		
	}
	
	function test_wpt_productions_unique_args() {
		global $wp_query;
		$wp_query->query_vars['post__in'] = array(123);
		
		$html_with_one_production = do_shortcode('[wpt_productions post__in="'.$this->production_with_upcoming_event.'"]');
		$html_with_two_productions = do_shortcode('[wpt_productions post__in="'.$this->production_with_upcoming_event.','.$this->production_with_upcoming_events.'"]');
		$this->assertNotEquals($html_with_one_production, $html_with_two_productions);
	}
	
	function test_wpt_listing_filter_pagination_option_name_filter() {
		$func = create_function(
			'$name, $field',
			'$name = "filtered_name"; return $name;'
		);
		add_filter('wpt_listing_filter_pagination_option_name', $func, 10, 3 );
		
		$html = do_shortcode('[wpt_events paginateby=month]');
		
		$this->assertContains('filtered_name', $html);
	}
	
	function test_wpt_listing_filter_pagination_month_option_name_filter() {
		$func = create_function(
			'$name',
			'$name = "filtered_name"; return $name;'
		);
		add_filter('wpt_listing_filter_pagination_month_option_name', $func, 10, 2 );
		
		$html = do_shortcode('[wpt_events paginateby=month]');
		
		$this->assertContains('filtered_name', $html);
	}
	
	function test_wpt_listing_filter_pagination_option_url_filter() {
		$func = create_function(
			'$url, $field, $name, $slug',
			'$url = "filtered_url"; return $url;'
		);
		add_filter('wpt_listing_filter_pagination_option_url', $func, 10, 4 );
		
		$html = do_shortcode('[wpt_events paginateby=month]');
		
		$this->assertContains('filtered_url', $html);
	}
	
	function test_wpt_listing_filter_pagination_month_option_url_filter() {
		$func = create_function(
			'$url, $name, $slug',
			'$url = "filtered_url"; return $url;'
		);
		add_filter('wpt_listing_filter_pagination_month_option_url', $func, 10, 3);
		
		$html = do_shortcode('[wpt_events paginateby=month]');
		
		$this->assertContains('filtered_url', $html);
	}
	
	function test_wpt_listing_filter_pagination_option_html_filter() {
		$func = create_function(
			'$html, $field, $name, $slug',
			'$html = "filtered_html"; return $html;'
		);
		add_filter('wpt_listing_filter_pagination_option_html', $func, 10, 4 );
		
		$html = do_shortcode('[wpt_events paginateby=month]');
		
		$this->assertContains('filtered_html', $html);
	}
	
	function test_wpt_listing_filter_pagination_month_option_html_filter() {
		$func = create_function(
			'$html, $name, $slug',
			'$html = "filtered_html"; return $html;'
		);
		add_filter('wpt_listing_filter_pagination_month_option_html', $func, 10, 3);
		
		$html = do_shortcode('[wpt_events paginateby=month]');
		
		$this->assertContains('filtered_html', $html);
	}
	
	/**
	 * Tests if the events are hidden from listings if you set the post_date of 
	 * a production to a date in the future.
	 * See: https://github.com/slimndap/wp-theatre/issues/109
	 */
	function test_scheduled_productions_dont_show_in_listings() {
		
		global $current_screen, $wp_theatre;
		
		// Switch to an admin screen so is_admin() is true.
		$screen = WP_Screen::get( 'admin_init' );
        $current_screen = $screen;
        
        // Assume the role of admin.
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		wp_set_current_user( $user->ID );		

		// Fake a production admin screen submit.
		$nonce = wp_create_nonce(WPT_Production::post_type_name);
		$_POST[WPT_Production::post_type_name.'_nonce'] = $nonce;
		$_POST[WPT_Season::post_type_name] = '';

		$post_date = date('Y-m-d H:i:s',strtotime('next year'));
		$post = array(
			'ID' => $this->production_with_upcoming_event,
			'post_date' => $post_date,
			'post_date_gmt'=>get_gmt_from_date($post_date)
		);
		
		wp_update_post($post);
		
		$events = $wp_theatre->events->get(
			array(
				'start' => 'now',
			)
		);
		
		$this->assertCount(3,$events);
		
	}
	
	/**
	 * Tests if a trashed event is not accidentally untrashed when you update a production.
	 * See: https://github.com/slimndap/wp-theatre/issues/47
	 */
	function test_trashed_event_remains_trashed_when_production_is_updated() {
		
		global $current_screen;
		
		// Trash the event.
		wp_trash_post($this->upcoming_event_with_prices);

		// Switch to an admin screen so is_admin() is true.
		$screen = WP_Screen::get( 'admin_init' );
        $current_screen = $screen;
        
        // Assume the role of admin.
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		wp_set_current_user( $user->ID );		

		// Fake a production admin screen submit.
		$nonce = wp_create_nonce(WPT_Production::post_type_name);
		$_POST[WPT_Production::post_type_name.'_nonce'] = $nonce;
		$_POST[WPT_Season::post_type_name] = '';
		
		//Update the production.
		$post = array(
			'ID' => $this->production_with_upcoming_event,
			'post_title' => 'hallo',
		);
		wp_update_post($post);

		$production = new WPT_Production($this->production_with_upcoming_event);
		$events = $production->events();
		
		$this->assertCount(0,$events);
		
	}
	
	/**
	 * Test is relative date filters use the right time offset.
	 *
	 * Tricky situation: displaying all events that start today.
	 * Solution: use 'Yesterday 23:59' for the 'start' argument.
	 * Problem: UTC may give a different value for yesterday than your local timezone.
	 * See: https://github.com/slimndap/wp-theatre/issues/117
	 */
	function test_timezones() {
		global $wp_theatre;
		
		// Set the timezone to a problematic offset.
		update_option('gmt_offset', (date('H')+1) * -1 );
		
		// Recalculate the post orders, based on the new time offset.
		$wp_theatre->order->update_post_order();
		
		$html = do_shortcode('[wpt_events start="-2 days 23:59" end="now"]');
		
		$this->assertEquals(1, substr_count($html, '"wp_theatre_event"'));
		
		/*
		 * More possible tests:
		 * event yesterday 23:59
		 * event today 00:00
		 * event today 23:59
		 * event tomorrow 00:00
		 */
		
	}
	
}
