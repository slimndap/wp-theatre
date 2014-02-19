<?php
	class WPT_Events_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_events_widget',
				__('Theatre Events','wp_theatre'), // Name
				array( 'description' => __( 'List of upcoming events', 'wp_theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;
			$title = apply_filters( 'widget_title', $instance['title'] );
			
			echo $args['before_widget'];
			if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
			echo $wp_theatre->events->html_listing(array('limit'=>$instance['limit']));
			echo $args['after_widget'];

		}

		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			}
			else {
				$title = __( 'Upcoming events', 'wp_theatre' );
			}
			if ( isset( $instance[ 'limit' ] ) ) {
				$limit = $instance[ 'limit' ];
			}
			else {
				$limit = 5;
			}
			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of events to show:', 'wp_theatre' ); ?></label> 
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="3" type="text" value="<?php echo esc_attr( $limit ); ?>">
			</p>
			<?php 
		}
		
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
			$instance['limit'] = ( ! empty( $new_instance['limit'] ) ) ? strip_tags( $new_instance['limit'] ) : '';
	
			return $instance;
		}
	}

	class WPT_Productions_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_productions_widget',
				__('Theatre Productions','wp_theatre'), // Name
				array( 'description' => __( 'List of upcoming productions', 'wp_theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;
			$title = apply_filters( 'widget_title', $instance['title'] );
			
			echo $args['before_widget'];
			if ( ! empty( $title ) )
				echo $args['before_title'] . $title . $args['after_title'];
			echo $wp_theatre->productions->html_listing(
				array(
					'limit'=>$instance['limit'],
					'upcoming' => true
				)
			);
			echo $args['after_widget'];

		}

		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			}
			else {
				$title = __( 'Upcoming productions', 'wp_theatre' );
			}
			if ( isset( $instance[ 'limit' ] ) ) {
				$limit = $instance[ 'limit' ];
			}
			else {
				$limit = 5;
			}
			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of productions to show:', 'wp_theatre' ); ?></label> 
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="3" type="text" value="<?php echo esc_attr( $limit ); ?>">
			</p>
			<?php 
		}
	}
	
	class WPT_Cart_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_cart_widget',
				__('Theatre Cart','wp_theatre'), // Name
				array( 'description' => __( 'Contents of the shopping cart.', 'wp_theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			global $wp_theatre;			
			if (!$wp_theatre->cart->is_empty()) {
				$title = apply_filters( 'widget_title', $instance['title'] );
				
				echo $args['before_widget'];
				if ( ! empty( $title ) )
					echo $args['before_title'] . $title . $args['after_title'];
				echo $wp_theatre->cart->render();
				echo $args['after_widget'];
			}


		}

		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			}
			else {
				$title = __( 'Cart', 'wp_theatre' );
			}
			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			</p>
			<?php 
		}

	
	}
?>