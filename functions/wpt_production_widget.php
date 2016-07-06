<?php
/*
 * Theater Production widget.
 * Display a single production in a widget.
 *
 * @since	0.8.2
 * @since	0.12.1	Added filters to the widget() method.
 */

class WPT_Production_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'wpt_production_widget',
			__( 'Theater Production','theatre' ),
			array(
				'description' => __( 'Display a single theater production.', 'theatre' ),
			)
		);
	}

	/**
	 * Outputs the widget.
	 * 
	 * @since 	0.8.2
	 * @since 	0.12.1	Added filters.
	 * @since 	0.12.1	Fixed PHP warnings. See #146.
	 *
	 * @param 	array 	$args		Display arguments including before_title, after_title,
	 *                        		before_widget, and after_widget.
	 * @param 	array	$instance	The settings for the particular instance of the widget.
	 * @return 	void
	 */
	public function widget( $args, $instance ) {
		global $wp_theatre;

		$defaults = array(
			'title' => '',
			'template' => false,
			'production' => false,
		);
		$instance = wp_parse_args( $instance, $defaults );

		$html = '';

		if ( is_numeric( $instance['production'] ) ) {

			$production = new WPT_Production( $instance['production'] );

			/**
			 * Filter the widget title.
			 * 
			 * @since	0.12.1
			 * @param	string	$title	The widget title.
			 * @param 	array 	$args		Display arguments including before_title, after_title,
			 *                        		before_widget, and after_widget.
			 * @param 	array	$instance	The settings for the particular instance of the widget.
			 */
			$title = apply_filters( 'wpt/production/widget/title', $instance['title'], $args, $instance );
			if ( ! empty($title) ) {
				$html .= $args['before_title'].$title.$args['after_title'];
			}

			/**
			 * Filter the production template.
			 * 
			 * @since	0.12.1
			 * @param	string	$template	The production template.
			 * @param	array	$args
			 * @param	array	$instance
			 */
			$template = apply_filters( 'wpt/production/widget/template', $instance['template'], $args, $instance );

			$html_production = $production->html( $template );
			
			/**
			 * Filter the HTML of the production.
			 * 
			 * @since	0.12.1
			 * @param	string	$html_production	The HTML of the production.
			 */
			$html_production = apply_filters( 'wpt/production/widget/production/html', $html_production, $production );

			$html .= $html_production;

			$html = $args['before_widget'].$html.$args['after_widget'];
		}

		/**
		 * Filter the HTML of the widget.
		 *
		 * @since	0.12.1
		 * @param	string	$html		The HTML of the widget.
		 * @param 	array 	$args		Display arguments including before_title, after_title,
		 *                        		before_widget, and after_widget.
		 * @param 	array	$instance	The settings for the particular instance of the widget.
		 */
		$html = apply_filters( 'wpt/production/widget', $html, $args, $instance );

		echo $html;

	}

	public function form( $instance ) {
		global $wp_theatre;
		$defaults = array(
			'title' => __( 'Production', 'theatre' ),
			'template' => '',
		);
		$values = wp_parse_args( $instance, $defaults );

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'production' ); ?>"><?php _e( 'Production','theatre' ); ?>:</label> 
		<select class="widefat" id="<?php echo $this->get_field_id( 'production' ); ?>" name="<?php echo $this->get_field_name( 'production' ); ?>">
			<option value=""></option>
			<?php
				$productions = $wp_theatre->productions->get();

			foreach ( $productions as $production ) {
				echo '<option value="'.$production->ID.'"';
				if ( ! empty($instance['production']) && $instance['production'] == $production->ID ) {
					echo ' selected="selected"';
				}
				echo '>';
				echo $production->title();
				echo '</option>';
			}
			?>
		</select>
		</p>


		<p class="wpt_widget_template">
		<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','theatre' ); ?>:</label> 
		<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_html( $values['template'] ); ?></textarea>
		<em><?php _e( 'Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template" target="_blank">documentation</a>.','theatre' );?></em>
		</p>
		<?php
	}
}
