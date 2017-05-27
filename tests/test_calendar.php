<?php
/**
 * WPT_Test_Transients class.
 * 
 * @extends WPT_UnitTestCase
 * @group	calendar
 */
class WPT_Test_Calendar extends WPT_UnitTestCase {

	function test_shortcode_wpt_calendar() {
		$this->setup_test_data();
		$html = do_shortcode('[wpt_calendar]');
		$this->assertEquals(4, substr_count($html, '<td><a'), $html);		
	}
	
	function test_shortcode_wpt_calendar_default_active_month() {
		$this->setup_test_data();
		$actual = do_shortcode('[wpt_calendar]');
		$expected = '<div class="wpt_calendar"><table class="wpt_month active">';
		$this->assertContains($expected, $actual);

	}

	function test_shortcode_wpt_calendar_active_month_when_filtered_by_month() {
		$this->setup_test_data();
	
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);

		// create production with event in two months
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		$production = $this->factory->post->create($production_args);
		$event = $this->factory->post->create($event_args);
		add_post_meta($event, WPT_Production::post_type_name, $production);
		add_post_meta($event, 'event_date', date('Y-m-d H:i:s', time() + (2 * 30* DAY_IN_SECONDS)));

		// Go to listing page with filetr set to the last day (= in 2 months).
		$months = $this->wp_theatre->events->get_months(array('upcoming' => true));
		$months = array_keys($months);
		$url = add_query_arg(
			'wpt_month',
			$months[ count($months) - 1],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);		
		$this->go_to($url);

		$actual = do_shortcode('[wpt_calendar]');

		// Check if first month is no active.
		$expected = '<div class="wpt_calendar"><table class="wpt_month">';
		$this->assertContains($expected, $actual);
		
		// Check if another month is active.
		$expected = '<table class="wpt_month active">';
		$this->assertContains($expected, $actual);
		
	}
	
	function test_shortcode_wpt_calendar_active_month_when_filtered_by_day() {
		$this->setup_test_data();
	
		$this->options['listing_page_type'] = WPT_Event::post_type_name;
		update_option('wpt_listing_page', $this->options);

		// create production with event in two months
		$production_args = array(
			'post_type'=>WPT_Production::post_type_name
		);
		
		$event_args = array(
			'post_type'=>WPT_Event::post_type_name
		);
		$production = $this->factory->post->create($production_args);
		$event = $this->factory->post->create($event_args);
		add_post_meta($event, WPT_Production::post_type_name, $production);
		add_post_meta($event, 'event_date', date('Y-m-d H:i:s', time() + (2 * 30* DAY_IN_SECONDS)));

		// Go to listing page with filetr set to the last day (= in 2 months).
		$days = $this->wp_theatre->events->get_days(array('upcoming' => true));
		$days = array_keys($days);
		$url = add_query_arg(
			'wpt_day',
			$days[ count($days) - 1],
			get_permalink( $this->wp_theatre->listing_page->page() )
		);		
		$this->go_to($url);

		$actual = do_shortcode('[wpt_calendar]');

		// Check if first month is no active.
		$expected = '<div class="wpt_calendar"><table class="wpt_month">';
		$this->assertContains($expected, $actual);
		
		// Check if another month is active.
		$expected = '<table class="wpt_month active">';
		$this->assertContains($expected, $actual);
		
	}

	function test_widget_wpt_calendar() {
		$this->setup_test_data();

		$widget = new WPT_Calendar_Widget();

		$args = array(
			'before_title'  => '<h2>',
			'after_title'   => "</h2>\n",
			'before_widget' => '<section>',
			'after_widget'  => "</section>\n",
		);
		$instance = array(
			'title'  => 'Foo',
		);
	
		ob_start();
		$widget->widget( $args, $instance );
		$html = ob_get_clean();
		
		$actual = substr_count($html, '<td><a');		
		$expected = 4;
		
		$this->assertEquals($expected, $actual );
	}
	

}