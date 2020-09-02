<?php
	
	/*
	 * Theater Production Events widget.
	 * Display all events for the current production.
	 * The widget is only visible on a production detail page: is_singular(WPT_Production::post_type_name)
	 * @since 0.8.3
	 */

	class WPT_Production_Events_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_production_events_widget',
				__('Theater Production Events','theatre'), // Name
				array( 'description' => __( 'Display all events for the current production.', 'theatre' ), )
			);
		}
	
		/**
		 * Outputs the production events widget HTML.
		 * 
		 * @since	0.8.3
		 * @since	0.15.26	Now uses the [wpt_events] shortcode.
		 *
		 * @param 	array	$args
		 * @param	array	$instance
		 * @return 	void
		 */
		public function widget( $args, $instance ) {
			global $wp_theatre;
			global $post;
			
			if (is_singular(WPT_Production::post_type_name)) {
				echo $args['before_widget'];

				if ( ! empty( $instance['title'] ) ) {			
					$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base  );
					echo $args['before_title'] . $title . $args['after_title'];
				}
								
				$shortcode = '[wpt_events production="'.$post->ID.'"]';
				if (!empty($instance['template'])) {
					$shortcode.= $instance['template'].'[/wpt_events]';
				}

				echo do_shortcode( $shortcode );

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


	class WPT_Productions_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_productions_widget',
				__('Theater Productions','theatre'), // Name
				array( 'description' => __( 'List of upcoming productions', 'theatre' ), ) // Args
			);
		}
	
		/**
		 * Outputs the productions widget HTML.
		 * 
		 * @since	0.8.3
		 * @since	0.15.26	Now uses the [wpt_productions] shortcode.
		 *
		 * @param 	array	$args
		 * @param	array	$instance
		 * @return 	void
		 */
		public function widget( $args, $instance ) {
			global $wp_theatre;

			echo $args['before_widget'];

			if ( ! empty( $instance['title'] ) ) {			
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base  );
				echo $args['before_title'] . $title . $args['after_title'];
			}
								
			$filters = array(
				'limit' => $instance['limit'],
				'end_after' => 'now',
			);

			if (!empty($instance['template'])) {
				$filters['template'] = $instance['template'];
			}

			$shortcode = '[wpt_productions end_after="now" limit="'.$instance['limit'].'"]';
			if (!empty($instance['template'])) {
				$shortcode.= $instance['template'].'[/wpt_productions]';
			}
			echo do_shortcode( $shortcode );

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