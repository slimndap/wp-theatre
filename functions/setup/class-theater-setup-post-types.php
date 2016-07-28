<?php
/**
 * Setup Post Types.
 *
 * @since	0.16
 * @package	Theater/Setup
 * @internal
 */
class Theater_Setup_Post_Types {

	static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
	}

	/**
	 * Registers the custom post types for events, dates and seasons.
	 *
	 * @since 	0.?
	 * @since 	0.12	Slug is dynamic.
	 *					Archive is disabled.
	 * @since	0.15	Renamed post type title from 'Production' to 'Event'.
	 * @since	0.15.6	Fixed the text-domain used in the 'Production' post type.
	 * @since	0.15.9	Added a filter to the post type args.
	 * @since	0.16	Moved every post type to a separate function.
	 *
	 * @uses 	Theater_Setup_Post_Types::register_post_type_event to register the event custom post type.
	 * @uses 	Theater_Setup_Post_Types::register_post_type_event_date to register the event date custom post type.
	 * @uses 	Theater_Setup_Post_Types::register_post_type_season to register the season custom post type.
	 * @return void
	 */
	static function register_post_types() {

		self::register_post_type_event();
		self::register_post_type_event_date();
		self::register_post_type_season();

	}

	/**
	 * Registers the custom post type for events.
	 *
	 * @access 	protected
	 * @static
	 * @since	0.16
	 * @return 	void
	 * @uses	WPT_Production_Permalink::get_base()	to retrieve the slug for events.
	 */
	protected static function register_post_type_event() {

		$post_type_args = array(
			'labels' => array(
				'name' => __( 'Events','theatre' ),
				'singular_name' => __( 'Event','theatre' ),
				'new_item' => __( 'New event','theatre' ),
				'add_new_item' => __( 'Add new event','theatre' ),
				'edit_item' => __( 'Edit event','theatre' ),
			),
			'description' => 'WordPress for Theater event',
			'public' => true,
			'show_in_menu'  => false,
			'show_in_admin_bar' => true,
			'menu_position' => 30,
			'has_archive' => true,
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail','comments' ),
			'taxonomies' => array( 'category','post_tag' ),
			'rewrite' => array(
				'slug' => Theater()->production_permalink->get_base(),
				'with_front' => false,
				'feeds' => true,
			),
			'show_in_rest' => true,
		);

		/**
		 * Filter the post type args for productions.
		 *
		 * @since	0.15.9
		 * @param	$post_type_args	The post type args.
		 */
		$post_type_args = apply_filters( 'wpt/setup/post_type/args/?post_type='.WPT_Production::post_type_name, $post_type_args );

		register_post_type( WPT_Production::post_type_name, $post_type_args );

	}

	/**
	 * Registers the custom post type for event dates.
	 *
	 * @access 	protected
	 * @static
	 * @since	0.16
	 * @return 	void
	 */
	protected static function register_post_type_event_date() {
		$post_type_args = array(
			'labels' => array(
				'name' => __( 'Event dates','theatre' ),
				'singular_name' => __( 'Event date','theatre' ),
			),
			'description' => 'WordPress for Theater event date',
			'public' => true,
			'has_archive' => true,
			'show_in_menu'  => false,
			'supports' => array( '' ),
			'taxonomies' => array( 'category','post_tag' ),
			'show_in_nav_menus' => false,
		);

		/**
		 * Filter the post type args for events.
		 *
		 * @since	0.15.9
		 * @param	$post_type_args	The post type args.
		 */
		$post_type_args = apply_filters( 'wpt/setup/post_type/args/?post_type='.WPT_Event::post_type_name, $post_type_args );

		register_post_type( WPT_Event::post_type_name, $post_type_args );

	}

	/**
	 * Registers the custom post type for seasons.
	 *
	 * @access 	protected
	 * @static
	 * @since	0.16
	 * @return 	void
	 */
	protected static function register_post_type_season() {
		$post_type_args = array(
			'labels' => array(
				'name' => __( 'Seasons','theatre' ),
				'singular_name' => __( 'Season','theatre' ),
			),
			'description' => 'WordPress for Theater season',
			'public' => true,
			'has_archive' => true,
			'supports' => array( 'title','editor' ),
			'show_in_menu'  => 'theater-events',
			'exclude_from_search' => true,
		);

		/**
		 * Filter the post type args for seasons.
		 *
		 * @since	0.15.9
		 * @param	$post_type_args	The post type args.
		 */
		$post_type_args = apply_filters( 'wpt/setup/post_type/args/?post_type=wp_theatre_season', $post_type_args );

		register_post_type( 'wp_theatre_season', $post_type_args );

	}
}
