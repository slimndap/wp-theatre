<?php
class WPT_Event_Editor {
	
	function __construct() {
		add_action('add_meta_boxes_'.WPT_Production::post_type_name, array($this, 'add_meta_box'));
		
		/*
		 * Process the form by hooking into the `save_post`-action.
		 * We can't use the `save_post_wpt_event'-action because this causes problems 
		 * with ACF's `save_post`-hooks.
		 * See: https://github.com/slimndap/wp-theatre/issues/76
		 */
		add_action('save_post', array( $this, 'save_event' ) );
	}
	
	public function add_meta_box() {
		add_meta_box(
			'wpt_event_editor', 
			__('Events','wp_theatre'), 
			array($this,'render_meta_box'), 
			WPT_Production::post_type_name, 
			'normal', 
			'high'
		);
	}
	
	public function render_meta_box($post) {
		
		$production = new WPT_Production($post);
		
		$this->render_event_listing($production);

		$this->render_form($production);
		
	}
	
	private function render_event_actions($event) {
		$html = '';
		
		
		$html.= '<a href="'.get_edit_post_link($event->ID).'">'.__('Edit').'</a>';
		$html.= ' | ';
		$html.= '<a href="'.get_delete_post_link($event->ID,'',true).'">'.__('Delete').'</a>';
		
		echo apply_filters('wpt_event_editor_event_actions', $html, $event);

	}
	
	private function render_event_listing($production) {
		
		$events = $production->events();
		
		if (empty($events)) {
			echo __('No events yet.','wp_theatre');
		} else {
			echo '<table class="widefat">';
			for ($i=0;$i<count($events);$i++) {
				$event = $events[$i];					

				if ($i%2 == 1) {
					echo '<tr class="alternate">';
				} else {
					echo '<tr>';						
				}

				$this->render_event($event);
				do_action('wpt_event_editor_event_after', $event);
				
				echo '<td class="wpt_event_editor_event_actions">';
				$this->render_event_actions($event);
				echo '</td>';
				do_action('wpt_event_editor_event_actions_after', $event);
				
				echo '</tr>';
			}
			echo '</table>';
		}
	}
	
	private function render_event($event) {
		
		$html = '';
		
		$args = array(
			'html' => true,	
		);
		
		$html.= '<td>';
		$html.= $event->date($args);
		$html.= $event->time($args);
		$html.= '</td>';

		$html.= '<td>';
		$html.= $event->venue($args);
		$html.= $event->city($args);
		$html.= '</td>';
		
		$html.= '<td>';
		$html.= $event->tickets($args);
		$html.= '</td>';
		
		echo apply_filters('wpt_event_editor_event', $html, $event);
	}
	
	private function render_form($production) {

		wp_nonce_field( 'wpt_event_editor', 'wpt_event_editor_nonce' );

		echo '<table class="form-table">';


		/*
		 * Start date/time for event.
		 */
		$html = '';
		$html.= '<tr>';
		$html.= '<th><label for="wpt_event_editor_start_date_field">'.__('Start','wp_theatre').'</label></th>';
		$html.= '<td>';
		$html.= '<input type="text" id="wpt_event_editor_start_date_field" name="wpt_event_editor_start_date" class="all-options" />';
		$html.= '</td>';
		$html.= '</tr>';
		echo apply_filters('wpt_event_editor_form_datetime', $html);
		do_action('wpt_event_editor_form_datetime_after');
	
		echo '</table>';
	}
	
	public function save_event($post_id) {
		if ( ! isset( $_POST['wpt_event_editor_nonce'] ) )
			return $post_id;
			
		if ( ! wp_verify_nonce( $_POST['wpt_event_editor_nonce'], 'wpt_event_editor' ) )
			return $post_id;
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;
			
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return $post_id;
			
		// unhook to avoid loops
		remove_action( 'save_post', array( $this, 'save_event' ) );

		$post = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => 'publish',
		);
		
		if ($event_id = wp_insert_post($post)) {
			add_post_meta($event_id, WPT_Production::post_type_name, $post_id, true);
			add_post_meta($event_id, 'event_date', $_POST['wpt_event_editor_start_date'], true);

			return new WPT_Event($post_id);

		} else {
			return false;
		}

	}	
}