<?php
/*
 * Theater Events widget.
 * Displays all upcoming events in a widget.
 *
 * @since	0.?
 * @since	0.12.1	Added filters to the widget() method.
 */

class WPT_Events_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'wpt_events_widget',
			__( 'Theater Events','theatre' ),
			array(
				'description' => __( 'Display all upcoming events.', 'theatre' ),
			)
		);
	}

	/**
	 * Outputs the widget.
	 * 
	 * @since 	0.?
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
			'limit' => 5,
		);
		$instance = wp_parse_args( $instance, $defaults );

		$html = '';

		/**
		 * Filters the title of the Events widget.
		 * 
		 * @since 	0.12.1
		 *
		 * @param	string	$title		The title of the Events widget.
		 * @param 	array 	$args		Display arguments including before_title, after_title,
		 *                        		before_widget, and after_widget.
		 * @param 	array	$instance	The settings for the particular instance of the widget.
		 */
		$title = apply_filters( 'wpt/events/widget/title', $instance['title'], $args, $instance );
		
		if ( ! empty($title) ) {
			$html .= $args['before_title'].$title.$args['after_title'];
		}

		/**
		 * Filter the template for the events listing.
		 * 
		 * @since 	0.12.1
		 *
		 * @param	string	$template	The template for the events listing.
		 * @param 	array 	$args		Display arguments including before_title, after_title,
		 *                        		before_widget, and after_widget.
		 * @param 	array	$instance	The settings for the particular instance of the widget.
		 */
		$template = apply_filters( 'wpt/events/widget/template', $instance['template'], $args, $instance );

		$events_shortcode = '[wpt_events limit="'.$instance['limit'].'"]';
		if ( ! empty($template) ) {
			$events_shortcode .= $template.'[/wpt_events]';
		};
	
		$html_events = do_shortcode($events_shortcode);
		
		/**
		 * Filter the HTML of the events listing.
		 * 
		 * @since 	0.12.1
		 * 
		 * @param 	string 	$html_events	The HTML of the events listing.
		 * @param 	array 	$args			Display arguments including before_title, after_title,
		 *                        			before_widget, and after_widget.
		 * @param 	array	$instance		The settings for the particular instance of the widget.
		 */
		$html_events = apply_filters( 'wpt/events/widget/listing/html', $html_events, $args, $instance );

		$html .= $html_events;

		$html = $args['before_widget'].$html.$args['after_widget'];

		/**
		 * Filter the full widget HTML.
		 * 
		 * @since 	0.12.1
		 * 
		 * @param 	string 	$html		The full widget HTML.
		 * @param 	array 	$args		Display arguments including before_title, after_title,
		 *                        		before_widget, and after_widget.
		 * @param 	array	$instance	The settings for the particular instance of the widget.
		 */
		$html = apply_filters( 'wpt/events/widget/html', $html, $args, $instance );

		echo $html;

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
		<p>
		<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of events to show', 'theatre' ); ?>:</label> 
		<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="3" type="text" value="<?php echo esc_attr( $values['limit'] ); ?>">
		</p>
		<p class="wpt_widget_template">
		<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template','theatre' ); ?>:</label> 
		<textarea class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo esc_html( $values['template'] ); ?></textarea>
		<em><?php _e('Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes#template" target="_blank">documentation</a>.','theatre');?></em>
		</p>
		<?php 
	}
}
