<?php
/**
 * WPT_Test_WPT_Event class.
 *
 * Tests the deprecated use of the WPT_Event object.
 *
 * @extends WPT_UnitTestCase
 * @group 	wpt_event
 * @since	1.6
 */
class WPT_Test_WPT_Event extends WPT_UnitTestCase {

	var $production_post_id;
	var $production;

	var $event_post_id;
	var $event;

	var $cat_music_id;
	var $cat_film_id;

	function setUp() {
		global $wp_theatre;

		parent::setUp();

		$this->cat_music_id = wp_create_category( 'music' );
		$this->cat_film_id = wp_create_category( 'film' );

		$production_args = array(
			'post_type' => 'wp_theatre_prod',
			'post_title' => 'Test production',
			'post_excerpt' => 'Test excerpt',
			'post_content' => 'Test content',
		);

		$this->production_post_id = $this->factory->post->create( $production_args );

		$this->production = new WPT_Production( $this->production_post_id );

		wp_set_post_categories( $this->production_post_id, array( $this->cat_music_id, $this->cat_film_id ) );

		add_post_meta( $this->production_post_id, 'director', 'Spielberg' );

		$event_args = array(
			'post_type' => 'wp_theatre_event',
		);
		$this->event_date = current_time( 'timestamp' ) + (2 * DAY_IN_SECONDS);

		$this->event_post_id = $this->factory->post->create( $event_args );
		$this->event = new WPT_Event( $this->event_post_id );

		add_post_meta( $this->event_post_id, WPT_Production::post_type_name, $this->production_post_id );
		add_post_meta( $this->event_post_id, 'event_date', date( 'Y-m-d H:i:s', $this->event_date ) );
		add_post_meta( $this->event_post_id, 'enddate', date( 'Y-m-d H:i:s', $this->event_date + HOUR_IN_SECONDS ) );
		add_post_meta( $this->event_post_id, '_wpt_event_tickets_price', 12 );
		add_post_meta( $this->event_post_id, '_wpt_event_tickets_price', 8.5 );
		add_post_meta( $this->event_post_id, 'city', 'Den Haag' );
		add_post_meta( $this->event_post_id, 'venue', 'Cafe Pan' );
		add_post_meta( $this->event_post_id, 'remark', 'Premiere' );
		add_post_meta( $this->event_post_id, 'tickets_url', 'https://slimndap.com' );
		add_post_meta( $this->event_post_id, 'more_info', 'https://wp.theater' );

		$wp_theatre->wpt_tickets_options = array(
			'currencysymbol' => '$',
		);
	}

	function filter_value( $value, $production ) {
		return 'Filtered value';
	}

	function filter_value_html( $html, $production ) {
		return 'Filtered HTML';
	}

	function filter_classes( $classes, $production ) {
		return array( 'filtered-classes' );
	}

	function test_wpt_event_object() {
		$expected = 'WPT_Event';
		$actual = $this->event;
		$this->assertInstanceOf( $expected, $actual );
	}

	function test_wpt_event_html() {
		$expected = '<div class="wp_theatre_event"> <div class="wp_theatre_event_title"><a href="' . get_permalink( $this->production->ID ) . '">Test production</a></div> <div class="wp_theatre_event_remark">Premiere</div> <div class="wp_theatre_event_datetime wp_theatre_event_startdatetime"><div class="wp_theatre_event_date wp_theatre_event_startdate">' . date_i18n( get_option( 'date_format' ), $this->event_date ) . '</div><div class="wp_theatre_event_time wp_theatre_event_starttime">' . date_i18n( get_option( 'time_format' ), $this->event_date ) . '</div></div> <div class="wp_theatre_event_location"><div class="wp_theatre_event_venue">Cafe Pan</div><div class="wp_theatre_event_city">Den Haag</div></div> <div class="wp_theatre_event_tickets"><a href="https://slimndap.com" rel="nofollow" class="wp_theatre_event_tickets_url">Tickets</a><div class="wp_theatre_event_prices">from&nbsp;$&nbsp;8.50</div></div></div>';
		$actual = $this->event->html();
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_template() {
		$expected = '<div class="wp_theatre_event"><div class="wp_theatre_event_title">Test production</div><ul class="wpt_production_categories"><li class="wpt_production_category wpt_production_category_film">film</li><li class="wpt_production_category wpt_production_category_music">music</li></ul><p class="wp_theatre_prod_excerpt">Test excerpt</p><div class="wp_theatre_event_prices">from&nbsp;$&nbsp;8.50</div></div>';
		$actual = $this->event->html( '{{ title }}{{ categories }}{{ excerpt }}{{ prices }}' );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_city() {
		$expected = 'Den Haag';
		$actual = $this->event->city();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_city">Den Haag</div>';
		$actual = $this->event->city( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_datetime() {
		$expected = date( 'Y-m-d H:i', $this->event_date );
		$actual = date( 'Y-m-d H:i', $this->event->datetime() );
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_datetime wp_theatre_event_startdatetime"><div class="wp_theatre_event_date wp_theatre_event_startdate">' . date_i18n( get_option( 'date_format' ), $this->event_date ) . '</div><div class="wp_theatre_event_time wp_theatre_event_starttime">' . date_i18n( get_option( 'time_format' ), $this->event_date ) . '</div></div>';
		$actual = $this->event->datetime( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_duration() {
		$expected = '60 minutes';
		$actual = $this->event->duration();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_duration">60 minutes</div>';
		$actual = $this->event->duration( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_enddate() {
		$expected = date( get_option( 'date_format' ), $this->event_date + HOUR_IN_SECONDS );
		$actual = $this->event->enddate();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_date wp_theatre_event_enddate">' . date_i18n( get_option( 'date_format' ), $this->event_date + HOUR_IN_SECONDS ) . '</div>';
		$actual = $this->event->enddate_html( );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_endtime() {
		$expected = date( get_option( 'time_format' ), $this->event_date + HOUR_IN_SECONDS );
		$actual = $this->event->endtime();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_time wp_theatre_event_endtime">' . date_i18n( get_option( 'time_format' ), $this->event_date + HOUR_IN_SECONDS ) . '</div>';
		$actual = $this->event->endtime_html( );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_location() {
		$expected = 'Cafe Pan Den Haag';
		$actual = $this->event->location();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_location"><div class="wp_theatre_event_venue">Cafe Pan</div><div class="wp_theatre_event_city">Den Haag</div></div>';
		$actual = $this->event->location( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_prices() {
		$expected = array( 12.0, 8.5 );
		$actual = $this->event->prices();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_prices">from&nbsp;$&nbsp;8.50</div>';
		$actual = $this->event->prices( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_remark() {
		$expected = 'Premiere';
		$actual = $this->event->remark();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_remark">Premiere</div>';
		$actual = $this->event->remark( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_startdate() {
		$expected = date( get_option( 'date_format' ), $this->event_date );
		$actual = $this->event->startdate();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_date wp_theatre_event_startdate">' . date_i18n( get_option( 'date_format' ), $this->event_date ) . '</div>';
		$actual = $this->event->startdate_html( );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_starttime() {
		$expected = date( get_option( 'time_format' ), $this->event_date );
		$actual = $this->event->starttime();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_time wp_theatre_event_starttime">' . date_i18n( get_option( 'time_format' ), $this->event_date ) . '</div>';
		$actual = $this->event->starttime_html( );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_tickets() {
		$expected = 'https://slimndap.com';
		$actual = $this->event->tickets();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_tickets"><a href="https://slimndap.com" rel="nofollow" class="wp_theatre_event_tickets_url">Tickets</a><div class="wp_theatre_event_prices">from&nbsp;$&nbsp;8.50</div></div>';
		$actual = $this->event->tickets( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_tickets_button() {
		$expected = 'Tickets';
		$actual = $this->event->tickets_button();
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_tickets_status() {
		$expected = '_onsale';
		$actual = $this->event->tickets_status();
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_tickets_url() {
		$expected = 'https://slimndap.com';
		$actual = $this->event->tickets_url();
		$this->assertEquals( $expected, $actual );

		$expected = '<a href="https://slimndap.com" rel="nofollow" class="wp_theatre_event_tickets_url">Tickets</a>';
		$actual = $this->event->tickets_url( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_tickets_url_iframe() {
		global $wp_theatre;

		$args = array(
			'post_type' => 'page',
		);
		$iframe_page_id = $this->factory->post->create( $args );

		$wp_theatre->wpt_tickets_options = array(
			'integrationtype' => 'iframe',
			'iframepage' => $iframe_page_id,
			'currencysymbol' => '$',
		);

		$expected = get_permalink( $iframe_page_id ) . $this->production->post()->post_name . '/' . $this->event_post_id;
		$actual = $this->event->tickets_url_iframe();
		$this->assertEquals( $expected, $actual );

		$expected = '<a href="' . get_permalink( $iframe_page_id ) . $this->production->post()->post_name . '/' . $this->event_post_id . '" rel="nofollow" class="wp_theatre_event_tickets_url wp_theatre_integrationtype_iframe">Tickets</a>';
		$actual = $this->event->tickets_url( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

		$wp_theatre->wpt_tickets_options = array(
			'currencysymbol' => '$',
		);

	}

	function test_wpt_event_title() {
		$expected = 'Test production';
		$actual = $this->event->title();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_title">Test production</div>';
		$actual = $this->event->title( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_event_venue() {
		$expected = 'Cafe Pan';
		$actual = $this->event->venue();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_venue">Cafe Pan</div>';
		$actual = $this->event->venue( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @expectedDeprecated Theater_Event_Date::custom()	 
	 */
	function test_wpt_event_custom() {
		$expected = 'https://wp.theater';
		$actual = $this->event->custom( 'more_info' );
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_more_info">https://wp.theater</div>';
		$actual = $this->event->custom( 'more_info', array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_production_custom() {
		$expected = 'Spielberg';
		$actual = $this->event->custom( 'director' );
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_event_director">Spielberg</div>';
		$actual = $this->event->custom( 'director', array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_city_filter() {

		// 'wpt_event_city' filter is missing in WPT_Event!

		add_filter( 'wpt_event_city_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->city( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_datetime_filter() {
		add_filter( 'wpt_event_datetime', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->datetime();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_datetime', array( $this, 'filter_value' ) );
		unset( $this->event->datetime );

		add_filter( 'wpt/event/datetime', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->datetime();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/event/datetime', array( $this, 'filter_value' ) );
		unset( $this->event->datetime );

		add_filter( 'wpt/event/datetime/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->datetime( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_duration_filter() {
		add_filter( 'wpt_event_duration', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->duration();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_location_filter() {
		add_filter( 'wpt_event_location', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->location();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_location', array( $this, 'filter_value' ) );
		unset( $this->event->location );

		add_filter( 'wpt_event_location_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->location( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_prices_filter() {
		add_filter( 'wpt_event_prices', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->prices();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_prices', array( $this, 'filter_value' ) );
		unset( $this->event->prices );

		add_filter( 'wpt/event/prices', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->prices();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/event/prices', array( $this, 'filter_value' ) );
		unset( $this->event->prices );

		add_filter( 'wpt_event_prices_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->prices( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_prices_html', array( $this, 'filter_value_html' ) );

		add_filter( 'wpt/event/prices/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->prices( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_remark_filter() {
		add_filter( 'wpt_event_remark', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->remark();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_remark', array( $this, 'filter_value' ) );
		unset( $this->event->remark );

		add_filter( 'wpt_event_remark_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->remark( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_tickets_filter() {
		add_filter( 'wpt_event_tickets', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->tickets();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_tickets', array( $this, 'filter_value' ) );
		unset( $this->event->tickets );

		add_filter( 'wpt/event/tickets', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->tickets();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/event/tickets', array( $this, 'filter_value' ) );
		unset( $this->event->tickets );

		add_filter( 'wpt_event_tickets_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->tickets( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_tickets_html', array( $this, 'filter_value_html' ) );

		add_filter( 'wpt/event/tickets/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->tickets( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_tickets_status_filter() {
		add_filter( 'wpt_event_tickets_status', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->tickets_status();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_tickets_status', array( $this, 'filter_value' ) );
		unset( $this->event->tickets );

		add_filter( 'wpt/event/tickets/status', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->tickets_status();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/event/tickets/status', array( $this, 'filter_value' ) );
		unset( $this->event->tickets_status );

		add_filter( 'wpt/event/tickets/status/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->tickets_status_html( );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_tickets_url_filter() {
		add_filter( 'wpt_event_tickets_url', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->tickets_url();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_tickets_url', array( $this, 'filter_value' ) );
		unset( $this->event->tickets_url );

		add_filter( 'wpt/event/tickets/url', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->tickets_url();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/event/tickets_url', array( $this, 'filter_value' ) );
		unset( $this->event->tickets_url );

		add_filter( 'wpt_event_tickets_url_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->tickets_url( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_tickets_url_html', array( $this, 'filter_value_html' ) );

		add_filter( 'wpt/event/tickets/url/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->tickets_url( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_tickets_url_iframe_filter() {
		global $wp_theatre;

		$args = array(
			'post_type' => 'page',
		);
		$iframe_page_id = $this->factory->post->create( $args );

		$wp_theatre->wpt_tickets_options = array(
			'integrationtype' => 'iframe',
			'iframepage' => $iframe_page_id,
			'currencysymbol' => '$',
		);

		add_filter( 'wpt/event/tickets/url/iframe', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->tickets_url_iframe();
		$this->assertEquals( $expected, $actual );

		$wp_theatre->wpt_tickets_options = array(
			'currencysymbol' => '$',
		);

	}
	function test_wpt_event_title_filter() {
		add_filter( 'wpt_event_title', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->title();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_title', array( $this, 'filter_value' ) );
		unset( $this->event->title );

		add_filter( 'wpt_event_title_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->title( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_venue_filter() {
		add_filter( 'wpt_event_venue', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->venue();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_venue', array( $this, 'filter_value' ) );
		unset( $this->event->title );

		add_filter( 'wpt_event_venue_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->venue( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	/**
	 * @expectedDeprecated Theater_Event_Date::custom()	 
	 */
	function test_wpt_event_custom_filter() {
		add_filter( 'wpt_event_more_info', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->event->custom( 'more_info' );
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_more_info', array( $this, 'filter_value' ) );
		unset( $this->event->more_info );

		add_filter( 'wpt_event_more_info_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->event->custom( 'more_info', array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_classes_filter() {
		add_filter( 'wpt_event_classes', array( $this, 'filter_classes' ), 10, 2 );
		$expected = '<div class="filtered-classes"> <div class="wp_theatre_event_title"><a href="' . get_permalink( $this->production_post_id ) . '">Test production</a></div> <div class="wp_theatre_event_remark">Premiere</div> <div class="wp_theatre_event_datetime wp_theatre_event_startdatetime"><div class="wp_theatre_event_date wp_theatre_event_startdate">' . date_i18n( get_option( 'date_format' ), $this->event_date ) . '</div><div class="wp_theatre_event_time wp_theatre_event_starttime">' . date_i18n( get_option( 'time_format' ), $this->event_date ) . '</div></div> <div class="wp_theatre_event_location"><div class="wp_theatre_event_venue">Cafe Pan</div><div class="wp_theatre_event_city">Den Haag</div></div> <div class="wp_theatre_event_tickets"><a href="https://slimndap.com" rel="nofollow" class="wp_theatre_event_tickets_url">Tickets</a><div class="wp_theatre_event_prices">from&nbsp;$&nbsp;8.50</div></div></div>';
		$actual = $this->event->html();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_event_html_filter() {
		add_filter( 'wpt_event_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = '<div class="wp_theatre_event">Filtered HTML</div>';
		$actual = $this->event->html();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_event_html', array( $this, 'filter_value_html' ) );

		add_filter( 'wpt/event/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = '<div class="wp_theatre_event">Filtered HTML</div>';
		$actual = $this->event->html();
		$this->assertEquals( $expected, $actual );

	}

}
