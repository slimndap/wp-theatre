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
				
			$filters = array(
				'limit'=>$instance['limit']
			);
			
			if (!empty($instance['template'])) {
				$filters['template'] = $instance['template'];
			}

			if ( ! ( $html = $wp_theatre->transient->get('events', array_merge($filters)) ) ) {
				$html = $wp_theatre->events->html($filters);
				$wp_theatre->transient->set('events', array_merge($filters), $html);
			}

			echo $html;

			echo $args['after_widget'];

		}

		public function form( $instance ) {
			$defaults = array(
				'title' => __( 'Upcoming events', 'wp_theatre' ),
				'limit' => 5,
				'template' => ''
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>
			<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of events to show', 'wp_theatre' ); ?>:</label> 
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="3" type="text" value="<?php echo esc_attr( $values['limit'] ); ?>">
			</p>
			<p class="wpt_widget_template">
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','wp_theatre' ); ?>:</label> 
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_attr( $values['template'] ); ?></textarea>
			<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template" target="_blank">documentation</a>.','wp_theatre');?></em>
			</p>
			<?php 
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
				
			$filters = array(
				'limit' => $instance['limit'],
				'upcoming' => true
			);

			if (!empty($instance['template'])) {
				$filters['template'] = $instance['template'];
			}

			$transient_key = 'wpt_prods_'.md5(serialize($filters));
			if ( ! ( $html = get_transient($transient_key) ) ) {
				$html = $wp_theatre->productions->html($filters);
				set_transient($transient_key, $html, 4 * MINUTE_IN_SECONDS );
			}
			echo $html;
			echo $args['after_widget'];

		}

		public function form( $instance ) {
			$defaults = array(
				'title' => __( 'Upcoming productions', 'wp_theatre' ),
				'limit' => 5,
				'template' => ''
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>
			<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of productions to show', 'wp_theatre' ); ?>:</label> 
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="3" type="text" value="<?php echo esc_attr( $values['limit'] ); ?>">
			</p>
			<p class="wpt_widget_template">
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','wp_theatre' ); ?>:</label> 
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_attr( $values['template'] ); ?></textarea><br />
			<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template-2" target="_blank">documentation</a>.','wp_theatre');?></em>
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