<?php
	
	/**
	 * Theater Production Events widget.
	 *
	 * Display all events for the current production.
	 * The widget is only visible on a production detail page: is_singular(WPT_Production::post_type_name)
	 *
	 * @since 0.8.3
	 * @package	Theater/Widgets
	 * @internal
	 */
	class WPT_Production_Events_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_production_events_widget',
				__('Theater Production Events','theatre'), // Name
				array( 'description' => __( 'Display all events for the current production.', 'theatre' ), )
			);
		}
	
		public function widget( $args, $instance ) {
			global $post;
			
			if (is_singular(WPT_Production::post_type_name)) {
				echo $args['before_widget'];

				if ( ! empty( $instance['title'] ) ) {			
					$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base  );
					echo $args['before_title'] . $title . $args['after_title'];
				}
								
				$filters = array();
				if (!empty($instance['template'])) {
					$filters['template'] = $instance['template'];
				}
	
				$filters['production'] = $post->ID;
				
				if ( ! ( $html = Theater()->transient->get('e', array_merge($filters)) ) ) {
					$html = Theater()->events->get_html($filters);
					Theater()->transient->set('e', array_merge($filters), $html);
				}
	
				echo $html;
	
				echo $args['after_widget'];
			}
			
			

		}

		public function form( $instance ) {
			$defaults = array(
				'title' => __( 'Upcoming events', 'theatre' ),
				'limit' => 5,
				'template' => ''
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>
			<p class="wpt_widget_template">
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','theatre' ); ?>:</label> 
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_html( $values['template'] ); ?></textarea>
			<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template" target="_blank">documentation</a>.','theatre');?></em>
			</p>
			<?php 
		}
	}


	/**
	 * WPT_Productions_Widget class.
	 * 
	 * @extends 	WP_Widget
	 * @package		Theater/Widgets
	 * @internal
	 */
	class WPT_Productions_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_productions_widget',
				__('Theater Productions','theatre'), // Name
				array( 'description' => __( 'List of upcoming productions', 'theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			echo $args['before_widget'];

			if ( ! empty( $instance['title'] ) ) {			
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base  );
				echo $args['before_title'] . $title . $args['after_title'];
			}
								
			$filters = array(
				'limit' => $instance['limit'],
				'upcoming' => true
			);

			if (!empty($instance['template'])) {
				$filters['template'] = $instance['template'];
			}

			$transient_key = 'wpt_prods_'.md5(serialize($filters));
			if ( ! ( $html = get_transient($transient_key) ) ) {
				$html = Theater()->productions->html($filters);
				set_transient($transient_key, $html, 4 * MINUTE_IN_SECONDS );
			}
			echo $html;
			echo $args['after_widget'];

		}

		public function form( $instance ) {
			$defaults = array(
				'title' => __( 'Upcoming productions', 'theatre' ),
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
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of productions to show', 'theatre' ); ?>:</label> 
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="3" type="text" value="<?php echo esc_attr( $values['limit'] ); ?>">
			</p>
			<p class="wpt_widget_template">
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','theatre' ); ?>:</label> 
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_html( $values['template'] ); ?></textarea><br />
			<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template-2" target="_blank">documentation</a>.','theatre');?></em>
			</p>
			<?php 
		}
	}
	
	/**
	 * WPT_Cart_Widget class.
	 * 
	 * @extends 	WP_Widget
	 * @package		Theater/Widgets
	 * @internal
	 */
	class WPT_Cart_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_cart_widget',
				__('Theatre Cart','theatre'), // Name
				array( 'description' => __( 'Contents of the shopping cart.', 'theatre' ), ) // Args
			);
		}
	
		public function widget( $args, $instance ) {
			if (!Theater()->cart->is_empty()) {

				echo $args['before_widget'];

				if ( ! empty( $instance['title'] ) ) {			
					$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base  );
					echo $args['before_title'] . $title . $args['after_title'];
				}
				
				echo Theater()->cart->render();
				echo $args['after_widget'];
			}


		}

		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			}
			else {
				$title = __( 'Cart', 'theatre' );
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