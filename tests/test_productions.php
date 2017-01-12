<?php
	
	
	class WPT_Test_Production extends WP_UnitTestCase {

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
	
	function tearDown() {
		parent::tearDown();
		 wp_set_current_user( 0 );
	}

	function test_productions_are_loaded() {
		$this->assertCount(5, $this->wp_theatre->productions->get());		
	}
	
	function test_upcoming_productions() {
		$args = array(
			'upcoming' => TRUE
		);
		$this->assertCount(4, $this->wp_theatre->productions->get($args));		
		
	}

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

	// Test order
	function test_order_productions() {
		$actual = array();
		$productions = $this->wp_theatre->productions->get();
		foreach($productions as $production) {
			$actual[] = $production->ID;
		}
		
		$expected = array(
			$this->production_with_historic_event_sticky, // no upcoming events, follows last event order.
			$this->production_with_historic_event, // no upcoming events, follows last event order.
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
			'season' => false,
			'category' => false, // deprecated since v0.9.
			'cat' => false,
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'tag' => false,
			'start' => false,
			'start_before' => false,
			'start_after' => false,
			'end' => false,
			'end_after' => false,
			'end_before' => false,
			'groupby' => false,
			'limit' => false,
			'order' => 'asc',
			'ignore_sticky_posts' => false,
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

	function test_wpt_productions_excerpt() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$html = do_shortcode('[wpt_productions]{{title}}{{excerpt}}[/wpt_productions]');
		$expected = 5;
		$actual = substr_count( $html, 'wp_theatre_prod_excerpt');

		$this->assertEquals($expected, $actual, $html);
	}
	
	function test_wpt_productions_categories() {
		$this->assertEquals(2, substr_count(do_shortcode('[wpt_productions]{{title}}{{categories}}[/wpt_productions]'), 'wpt_production_category_muziek'));
	}
	
	function test_wpt_productions_content() {
		$my_post = array(
			'ID'           => $this->production_with_upcoming_events,
			'post_content' => 'This is the updated content.'
		);
		wp_update_post( $my_post );

		$this->assertEquals(5, substr_count(do_shortcode('[wpt_productions]{{title}}{{content}}[/wpt_productions]'), 'wp_theatre_prod_content'));
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

	function test_wpt_productions_unique_args() {
		global $wp_query;
		$wp_query->query_vars['post__in'] = array(123);
		
		$html_with_one_production = do_shortcode('[wpt_productions post__in="'.$this->production_with_upcoming_event.'"]');
		$html_with_two_productions = do_shortcode('[wpt_productions post__in="'.$this->production_with_upcoming_event.','.$this->production_with_upcoming_events.'"]');
		$this->assertNotEquals($html_with_one_production, $html_with_two_productions);
	}
	
	function test_wpt_productions_start_end() {
		$html = do_shortcode('[wpt_productions start="yesterday"]');
		$actual = substr_count($html, '"wp_theatre_prod"');
		$expected = 5; // All productions, including the production that starts yesterday and the sticky productions.
		$this->assertEquals($expected, $actual, $html);
		
		$returned = do_shortcode('[wpt_productions start="today" end="+2 days"]');
		$expected = 3; // 1 productions with matching events and 2 sticky productions.
		$this->assertEquals($expected, substr_count($returned, '"wp_theatre_prod"'), $returned);

		$returned = do_shortcode('[wpt_productions start="'.date('Y-m-d',time() + (2 * DAY_IN_SECONDS)).'"]');
		$expected = 4; // 2 productions with matching events and 2 sticky productions.
		$this->assertEquals($expected, substr_count($returned, '"wp_theatre_prod"'));

		$returned = do_shortcode('[wpt_productions end="now" ignore_sticky_posts="true"]');
		$expected = 2; // 2 productions with matching events.
		$this->assertEquals($expected, substr_count($returned, '"wp_theatre_prod"'), $returned);

		$returned = do_shortcode('[wpt_productions end="now"]');
		$expected = 3; // 1 productions with matching events and 2 sticky productions.
		$this->assertEquals($expected, substr_count($returned, '"wp_theatre_prod"'));

		$returned = do_shortcode('[wpt_productions start="today" end="+2 weeks"]');
		$expected = 4; // 2 productions with matching events and 2 sticky productions.
		$this->assertEquals($expected, substr_count($returned, '"wp_theatre_prod"'));

		$returned = do_shortcode('[wpt_productions start="+10 years"]');
		$expected = 2; // 0 productions with matching events and 2 sticky productions.
		$this->assertEquals($expected, substr_count($returned, '"wp_theatre_prod"'));
	}
	
	function test_wpt_productions_groupby_day() {
				
		$html = do_shortcode('[wpt_productions groupby="day"]');

		// Should have headers for 7 days.
		$this->assertEquals(7, substr_count($html, '<h3 class="wpt_listing_group day">'), $html);		
		
		// Should contain all 7 events.
		$this->assertEquals(7, substr_count($html, '"wp_theatre_prod"'), $html);
		
	}
	
	function test_wpt_productions_groupby_month() {

		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
			
		$production_with_two_months = $this->factory->post->create($production_args);

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		$next_month_date = strtotime('+ 1 month');
		$in_two_months_date = strtotime('+ 2 months');

		$upcoming_event_next_month = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_next_month, WPT_Production::post_type_name, $production_with_two_months);
		add_post_meta($upcoming_event_next_month, 'event_date', date('Y-m-d H:i:s', $next_month_date));

		$upcoming_event_in_two_months = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_in_two_months, WPT_Production::post_type_name, $production_with_two_months);
		add_post_meta($upcoming_event_in_two_months, 'event_date', date('Y-m-d H:i:s', $in_two_months_date));
				
		$html = do_shortcode('[wpt_productions groupby="month" post__in="'.$production_with_two_months.'"]');
		
		$this->assertEquals(2, substr_count($html, '<h3 class="wpt_listing_group month">'), $html);		
		$this->assertContains('<h3 class="wpt_listing_group month">'.date_i18n('F',$in_two_months_date).'</h3>', $html);
		$this->assertEquals(2, substr_count($html, '"wp_theatre_prod"'));
	}
	
	function test_wpt_productions_groupby_year() {

		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
			
		$production_with_two_years = $this->factory->post->create($production_args);

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		$next_year_date = strtotime('+ 1 year');
		$in_two_years_date = strtotime('+ 2 years');

		$upcoming_event_next_year = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_next_year, WPT_Production::post_type_name, $production_with_two_years);
		add_post_meta($upcoming_event_next_year, 'event_date', date('Y-m-d H:i:s', $next_year_date));

		$upcoming_event_in_two_years = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_in_two_years, WPT_Production::post_type_name, $production_with_two_years);
		add_post_meta($upcoming_event_in_two_years, 'event_date', date('Y-m-d H:i:s', $in_two_years_date));
				
		$html = do_shortcode('[wpt_productions groupby="year" post__in="'.$production_with_two_years.'"]');
		
		$this->assertEquals(2, substr_count($html, '<h3 class="wpt_listing_group year">'));		
		$this->assertContains('<h3 class="wpt_listing_group year">'.date_i18n('Y',$in_two_years_date).'</h3>', $html);
		$this->assertEquals(2, substr_count($html, '"wp_theatre_prod"'));
	}
	
	function test_wpt_productions_paginateby_day() {
				
		$html = do_shortcode('[wpt_productions paginateby="day"]');

		$event = new WPT_Event($this->upcoming_event_with_prices);

		$expected = 1;
		$returned = substr_count($html, '>'.date_i18n('D j M',$event->datetime()).'</a>');
		$this->assertEquals($expected, $returned);
		
		$expected = 7;
		$returned = substr_count($html, '<span class="wpt_listing_filter day-');
		$this->assertEquals($expected, $returned);
	}
	
	function test_wpt_production_paginateby_month() {
		
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
			
		$production_with_two_months = $this->factory->post->create($production_args);

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		$next_month_date = strtotime('+ 1 month');
		$in_two_months_date = strtotime('+ 2 months');

		$upcoming_event_next_month = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_next_month, WPT_Production::post_type_name, $production_with_two_months);
		add_post_meta($upcoming_event_next_month, 'event_date', date('Y-m-d H:i:s', $next_month_date));

		$upcoming_event_in_two_months = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_in_two_months, WPT_Production::post_type_name, $production_with_two_months);
		add_post_meta($upcoming_event_in_two_months, 'event_date', date('Y-m-d H:i:s', $in_two_months_date));
				
		$html = do_shortcode('[wpt_productions paginateby="month" post__in="'.$production_with_two_months.'"]');

		$expected = 1;
		$returned = substr_count($html, '>'.date_i18n('M Y',$in_two_months_date).'</a>');
		$this->assertEquals($expected, $returned);
		
		$expected = 2;
		$returned = substr_count($html, '<span class="wpt_listing_filter month-');
		$this->assertEquals($expected, $returned);
	}
	
	function test_wpt_production_paginateby_year() {
		
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
			
		$production_with_two_years = $this->factory->post->create($production_args);

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);

		$next_year_date = strtotime('+ 1 year');
		$in_two_years_date = strtotime('+ 2 years');

		$upcoming_event_next_year = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_next_year, WPT_Production::post_type_name, $production_with_two_years);
		add_post_meta($upcoming_event_next_year, 'event_date', date('Y-m-d H:i:s', $next_year_date));

		$upcoming_event_in_two_years = $this->factory->post->create($event_args);
		add_post_meta($upcoming_event_in_two_years, WPT_Production::post_type_name, $production_with_two_years);
		add_post_meta($upcoming_event_in_two_years, 'event_date', date('Y-m-d H:i:s', $in_two_years_date));
								
		$html = do_shortcode('[wpt_productions paginateby="year" post__in="'.$production_with_two_years.'"]');

		$expected = 1;
		$returned = substr_count($html, '>'.date_i18n('Y',$in_two_years_date).'</a>');
		$this->assertEquals($expected, $returned);
		
		$expected = 2;
		$returned = substr_count($html, '<span class="wpt_listing_filter year-');
		$this->assertEquals($expected, $returned);
	}
	
	function test_wpt_productions_before_1970() {
		
		$production_title = 'Production in 1960';
		
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name,
			'post_title'=>$production_title,
		);
		$production_in_1960 = $this->factory->post->create($production_args);
		
		$event_date_in_1960 = strtotime('1960-10-10');

		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		$event_in_1960 = $this->factory->post->create($event_args);
		
		add_post_meta($event_in_1960, WPT_Production::post_type_name, $production_in_1960);
		add_post_meta($event_in_1960, 'event_date', date('Y-m-d H:i:s', $event_date_in_1960));
		
		global $wp_query;		
		$wp_query->query_vars['wpt_year'] = '1960';
		
		$html = do_shortcode('[wpt_productions]');

		$returned = substr_count($html, $production_title); 
		$expected = 1;
		$this->assertEquals($expected, $returned);
		
		$returned = substr_count($html, '<div class="wp_theatre_prod">');
		$expected = 1;
		$this->assertEquals($expected, $returned);
	}
	
	/**
	 * Test if 'start' and 'post__not_in' work nicely together. 
	 * See: https://github.com/slimndap/wp-theatre/issues/183
	 */
	function test_wpt_productions_with_startdate_and_post__not_in() {
		global $wp_theatre;
		
		$args = array(
			'start' => 'now',
			'post__not_in' => array($this->production_with_upcoming_event),	
		);
		
		$productions = $wp_theatre->productions->get($args);
		
		$expected = 3; // All upcoming productions except $this->production_with_upcoming_event.
		$actual = count($productions);

		$this->assertEquals($expected, $actual);
		
		
	}
	
}
?>