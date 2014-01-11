<?php
	class WPT_Events_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_events_widget',
				__('Events','wp_theatre'), // Name
				array( 'description' => __( 'Sign-up form', 'wp_theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;
			echo $wp_theatre->render_events();
		}
	
	}
?>