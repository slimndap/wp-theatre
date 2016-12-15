<?php
/**
 * Base WPT Unittests class.
 * @since	0.15.3
 */

class WPT_UnitTestCase extends WP_UnitTestCase {

	function setUp() {
		global $wp_rewrite;

		parent::setUp();

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

	function assume_role( $role = 'author' ) {
		$user = new WP_User( $this->factory->user->create( array( 'role' => $role ) ) );
		wp_set_current_user( $user->ID );
		return $user;
	}

	function create_event() {
		$args = array(
			'post_type' => WPT_Event::post_type_name,
		);

		return $this->factory->post->create( $args );
	}

	function create_upcoming_event() {
		$event_id = $this->create_event();
		add_post_meta( $event_id, 'event_date', date( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) );
		return $event_id;
	}

	function setup_test_data() {
		global $wp_rewrite;
		global $wp_theatre;

		$this->wp_theatre = $wp_theatre;

		// create a page for our listing
		$args = array(
			'post_type' => 'page',
		);

		$this->options = array(
			'listing_page_post_id' => $this->factory->post->create( $args ),
			'listing_page_position' => 'above',
		);
		update_option( 'wpt_listing_page', $this->options );

		$season_args = array(
			'post_type' => WPT_Season::post_type_name,
		);

		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);

		$event_args = array(
			'post_type' => WPT_Event::post_type_name,
		);

		/*
		 * Test content
		 */

		// create seasons
		$this->season1 = $this->factory->post->create( $season_args );
		$this->season2 = $this->factory->post->create( $season_args );

		//create categories
		$this->category_muziek = wp_create_category( 'muziek' );
		$this->category_film = wp_create_category( 'film' );

		// create production with upcoming event
		$this->production_with_upcoming_event = $this->factory->post->create( $production_args );
		add_post_meta( $this->production_with_upcoming_event, WPT_Season::post_type_name, $this->season1 );
		wp_set_post_categories( $this->production_with_upcoming_event, array( $this->category_muziek ) );
		wp_set_post_tags( $this->production_with_upcoming_event,array( 'upcoming' ) );

		$this->upcoming_event_with_prices = $this->factory->post->create( $event_args );
		add_post_meta( $this->upcoming_event_with_prices, WPT_Production::post_type_name, $this->production_with_upcoming_event );
		add_post_meta( $this->upcoming_event_with_prices, 'event_date', date( 'Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS) ) );
		add_post_meta( $this->upcoming_event_with_prices, '_wpt_event_tickets_price', 12 );
		add_post_meta( $this->upcoming_event_with_prices, '_wpt_event_tickets_price', 8.5 );

		// create production with 2 upcoming events
		$this->production_with_upcoming_events = $this->factory->post->create( $production_args );
		add_post_meta( $this->production_with_upcoming_events, WPT_Season::post_type_name, $this->season2 );
		wp_set_post_categories( $this->production_with_upcoming_events, array( $this->category_muziek, $this->category_film ) );

		$upcoming_event = $this->factory->post->create( $event_args );
		add_post_meta( $upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_events );
		add_post_meta( $upcoming_event, 'event_date', date( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) );
		add_post_meta( $upcoming_event, 'tickets_status', 'other tickets status' );

		$upcoming_event = $this->factory->post->create( $event_args );
		add_post_meta( $upcoming_event, WPT_Production::post_type_name, $this->production_with_upcoming_events );
		add_post_meta( $upcoming_event, 'event_date', date( 'Y-m-d H:i:s', time() + (3 * DAY_IN_SECONDS) ) );
		add_post_meta( $upcoming_event, 'tickets_status', WPT_Event::tickets_status_cancelled );

		// create production with a historic event
		$this->production_with_historic_event = $this->factory->post->create( $production_args );
		$event_id = $this->factory->post->create( $event_args );
		add_post_meta( $event_id, WPT_Production::post_type_name, $this->production_with_historic_event );
		add_post_meta( $event_id, 'event_date', date( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		wp_set_post_tags( $this->production_with_historic_event,array( 'historic' ) );

		// create sticky production with a historic event
		$this->production_with_historic_event_sticky = $this->factory->post->create( $production_args );
		$event_id = $this->factory->post->create( $event_args );
		add_post_meta( $event_id, WPT_Production::post_type_name, $this->production_with_historic_event_sticky );
		add_post_meta( $event_id, 'event_date', date( 'Y-m-d H:i:s', time() - YEAR_IN_SECONDS ) );
		stick_post( $this->production_with_historic_event_sticky );
		wp_set_post_tags( $this->production_with_historic_event_sticky,array( 'historic' ) );

		// create sticky production with an upcoming and a historic event
		$this->production_with_upcoming_and_historic_events = $this->factory->post->create( $production_args );
		$event_id = $this->factory->post->create( $event_args );
		add_post_meta( $event_id, WPT_Production::post_type_name, $this->production_with_upcoming_and_historic_events );
		add_post_meta( $event_id, 'event_date', date( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS ) );
		$event_id = $this->factory->post->create( $event_args );
		add_post_meta( $event_id, WPT_Production::post_type_name, $this->production_with_upcoming_and_historic_events );
		add_post_meta( $event_id, 'event_date', date( 'Y-m-d H:i:s', time() + WEEK_IN_SECONDS ) );
		stick_post( $this->production_with_upcoming_and_historic_events );
		add_post_meta( $this->upcoming_event_with_prices, '_wpt_event_tickets_price', 12 );
		add_post_meta( $upcoming_event, 'tickets_status', WPT_Event::tickets_status_hidden );

	}
}
