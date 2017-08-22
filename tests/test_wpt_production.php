<?php
/**
 * WPT_Test_WPT_Production class.
 *
 * Tests the deprecated use of the WPT_Production class.
 *
 * @extends WPT_UnitTestCase
 * @group 	wpt_production
 * @since	1.6
 */
class WPT_Test_WPT_Production extends WPT_UnitTestCase {

	var $post_id;
	var $production;
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

		$this->post_id = $this->factory->post->create( $production_args );

		$this->production = new WPT_Production( $this->post_id );

		wp_set_post_categories( $this->post_id, array( $this->cat_music_id, $this->cat_film_id ) );

		add_post_meta( $this->post_id, 'director', 'Spielberg' );

		$event_args = array(
			'post_type' => 'wp_theatre_event',
		);

		$this->event_date = time() + (2 * DAY_IN_SECONDS);

		$this->upcoming_event_with_prices = $this->factory->post->create( $event_args );
		add_post_meta( $this->upcoming_event_with_prices, WPT_Production::post_type_name, $this->post_id );
		add_post_meta( $this->upcoming_event_with_prices, 'event_date', date( 'Y-m-d H:i:s', $this->event_date ) );
		add_post_meta( $this->upcoming_event_with_prices, '_wpt_event_tickets_price', 12 );
		add_post_meta( $this->upcoming_event_with_prices, '_wpt_event_tickets_price', 8.5 );
		add_post_meta( $this->upcoming_event_with_prices, 'city', 'Den Haag' );

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

	function test_wpt_production_object() {
		$expected = 'WPT_Production';
		$actual = $this->production;
		$this->assertInstanceOf( $expected, $actual );
	}

	function test_wpt_production_html() {
		$expected = '<div class="wp_theatre_prod"> <div class="wp_theatre_prod_title"><a href="' . get_permalink( $this->post_id ) . '">Test production</a></div> <div class="wp_theatre_prod_dates">' . date_i18n( get_option( 'date_format' ), $this->event_date ) . '</div> <div class="wp_theatre_prod_cities">Den Haag</div></div>';
		$actual = $this->production->html();
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_production_template() {
		$expected = '<div class="wp_theatre_prod"><div class="wp_theatre_prod_title">Test production</div><ul class="wpt_production_categories"><li class="wpt_production_category wpt_production_category_film">film</li><li class="wpt_production_category wpt_production_category_music">music</li></ul><p class="wp_theatre_prod_excerpt">Test excerpt</p><div class="wp_theatre_prod_prices">from&nbsp;$&nbsp;8.50</div></div>';
		$actual = $this->production->html( '{{ title }}{{ categories }}{{ excerpt }}{{ prices }}' );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_production_categories() {
		$expected = array( $this->cat_film_id, $this->cat_music_id );
		$actual = $this->production->categories();
		$this->assertEquals( $expected, $actual );

		$expected = '<ul class="wpt_production_categories"><li class="wpt_production_category wpt_production_category_film">film</li><li class="wpt_production_category wpt_production_category_music">music</li></ul>';
		$actual = $this->production->categories( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_cities() {
		$expected = 'Den Haag';
		$actual = $this->production->cities();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_prod_cities">Den Haag</div>';
		$actual = $this->production->cities( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_content() {
		$expected = 'Test content';
		$actual = $this->production->content();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_prod_content"><p>Test content</p>' . "\n" . '</div>';
		$actual = $this->production->content( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_production_dates() {
		$expected = array( date_i18n( get_option( 'date_format' ), $this->event_date ) );
		$actual = $this->production->dates();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_prod_dates">' . date_i18n( get_option( 'date_format' ), $this->event_date ) . '</div>';
		$actual = $this->production->dates( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_dates_summary() {
		$expected = date_i18n( get_option( 'date_format' ), $this->event_date );
		$actual = $this->production->dates_summary();
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_production_excerpt() {
		$expected = 'Test excerpt';
		$actual = $this->production->excerpt();
		$this->assertEquals( $expected, $actual );

		$expected = '<p class="wp_theatre_prod_excerpt">Test excerpt</p>';
		$actual = $this->production->excerpt( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_production_permalink() {
		$expected = get_permalink( $this->post_id );
		$actual = $this->production->permalink();
		$this->assertEquals( $expected, $actual );

		$expected = '<a href="' . get_permalink( $this->post_id ) . '">Test production</a>';
		$actual = $this->production->permalink( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	function test_wpt_production_prices() {
		$expected = array( 8.5, 12.0 );
		$actual = $this->production->prices();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_prod_prices">from&nbsp;$&nbsp;8.50</div>';
		$actual = $this->production->prices_html();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_prices_summary() {
		$expected = 'from $ 8.50';
		$actual = $this->production->prices_summary();
		$this->assertEquals( $expected, $actual );

		$expected = 'from&nbsp;$&nbsp;8.50';
		$actual = $this->production->prices_summary_html();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_thumbnail() {
		add_theme_support( 'post-thumbnails' );
	    $this->assume_role( 'author' );

	    // create attachment
	    $filename = dirname( __FILE__ ) . '/assets/thumbnail.jpg';
	    $contents = file_get_contents( $filename );
	    $upload = wp_upload_bits( $filename, null, $contents );
	    $this->assertTrue( empty( $upload['error'] ) );

	    $attachment = array(
	      'post_title' => 'Post Thumbnail',
	      'post_type' => 'attachment',
	      'post_mime_type' => 'image/jpeg',
	      'guid' => $upload['url'],
	    );
	    $attachment_id = wp_insert_attachment( $attachment, $upload['file'] );

		set_post_thumbnail( $this->post_id, $attachment_id );

		$img_url = wp_get_attachment_url( $attachment_id );

		$actual = $this->production->thumbnail();
		$expected = $attachment_id;
		$this->assertEquals( $expected, $actual );

		$actual = $this->production->thumbnail_html();
		$expected = 'src="' . $img_url . '" class="';
		$this->assertContains( $expected, $actual );

		wp_delete_attachment( $attachment_id, true );

	    remove_theme_support( 'post-thumbnails' );
	}

	function test_wpt_production_title() {
		$expected = 'Test production';
		$actual = $this->production->title();
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_prod_title">Test production</div>';
		$actual = $this->production->title( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @expectedDeprecated Theater_Event::custom()	 
	 */
	function test_wpt_production_custom() {
		
		$expected = 'Spielberg';
		$actual = $this->production->custom( 'director' );
		$this->assertEquals( $expected, $actual );

		$expected = '<div class="wp_theatre_prod_director">Spielberg</div>';
		$actual = $this->production->custom( 'director', array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_categories_filter() {
		add_filter( 'wpt_production_categories', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->categories();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_categories', array( $this, 'filter_value' ) );
		unset( $this->production->categories );

		add_filter( 'wpt_production_categories_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->categories( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_cities_filter() {
		add_filter( 'wpt_production_cities', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->cities();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_cities', array( $this, 'filter_value' ) );
		unset( $this->production->cities );

		add_filter( 'wpt_production_cities_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->cities( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_content_filter() {
		add_filter( 'wpt_production_content', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->content();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_content', array( $this, 'filter_value' ) );
		unset( $this->production->content );

		add_filter( 'wpt_production_content_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->content( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_dates_filter() {
		add_filter( 'wpt_production_dates', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->dates();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_dates', array( $this, 'filter_value' ) );
		unset( $this->production->dates );

		add_filter( 'wpt/production/dates', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->dates();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/production/dates', array( $this, 'filter_value' ) );
		unset( $this->production->dates );

		add_filter( 'wpt_production_dates_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->dates( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_dates_html', array( $this, 'filter_value_html' ) );

		add_filter( 'wpt/production/dates/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->dates( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_dates_summary_filter() {
		add_filter( 'wpt/production/dates/summary', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->dates_summary();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_excerpt_filter() {
		add_filter( 'wpt_production_excerpt', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->excerpt();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_excerpt', array( $this, 'filter_value' ) );
		unset( $this->production->excerpt );

		add_filter( 'wpt_production_excerpt_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->excerpt( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_permalink_filter() {
		add_filter( 'wpt_production_permalink', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->permalink();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_permalink', array( $this, 'filter_value' ) );
		unset( $this->production->permalink );

		add_filter( 'wpt_production_permalink_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->permalink( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_prices_filter() {
		add_filter( 'wpt/production/prices', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->prices();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/production/prices', array( $this, 'filter_value' ) );
		unset( $this->production->prices );

		add_filter( 'wpt/production/prices/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->prices_html();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_prices_summary_filter() {
		add_filter( 'wpt/production/prices/summary', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->prices_summary();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/production/prices/summary', array( $this, 'filter_value' ) );
		unset( $this->production->prices_summary );

		add_filter( 'wpt/production/prices/summary/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->prices_summary_html();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_thumbnail_filter() {
		add_filter( 'wpt/production/prices/summary', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->prices_summary();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/production/prices/summary', array( $this, 'filter_value' ) );
		unset( $this->production->prices_summary );

		add_filter( 'wpt/production/prices/summary/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->prices_summary_html();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_title_filter() {
		add_filter( 'wpt/production/thumbnail', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->thumbnail();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt/production/thumbnail', array( $this, 'filter_value' ) );
		unset( $this->production->thumbnail );

		add_filter( 'wpt_production_thumbnail_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->thumbnail( array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	/**
	 * @expectedDeprecated Theater_Event::custom()	 
	 */
	function test_wpt_production_custom_filter() {
		add_filter( 'wpt_production_director', array( $this, 'filter_value' ), 10, 2 );
		$expected = 'Filtered value';
		$actual = $this->production->custom( 'director' );
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_director', array( $this, 'filter_value' ) );
		unset( $this->production->director );

		add_filter( 'wpt_production_director_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = 'Filtered HTML';
		$actual = $this->production->custom( 'director', array(
			'html' => true,
		) );
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_classes_filter() {
		add_filter( 'wpt_production_classes', array( $this, 'filter_classes' ), 10, 2 );
		$expected = '<div class="filtered-classes"> <div class="wp_theatre_prod_title"><a href="' . get_permalink( $this->post_id ) . '">Test production</a></div> <div class="wp_theatre_prod_dates">' . date_i18n( get_option( 'date_format' ), $this->event_date ) . '</div> <div class="wp_theatre_prod_cities">Den Haag</div></div>';
		$actual = $this->production->html();
		$this->assertEquals( $expected, $actual );

	}

	function test_wpt_production_html_filter() {
		add_filter( 'wpt_production_html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = '<div class="wp_theatre_prod">Filtered HTML</div>';
		$actual = $this->production->html();
		$this->assertEquals( $expected, $actual );

		remove_filter( 'wpt_production_html', array( $this, 'filter_value_html' ) );

		add_filter( 'wpt/production/html', array( $this, 'filter_value_html' ), 10, 2 );
		$expected = '<div class="wp_theatre_prod">Filtered HTML</div>';
		$actual = $this->production->html();
		$this->assertEquals( $expected, $actual );

	}

}
