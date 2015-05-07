<?php
class WPT_Event_Editor {
	
	function __construct() {

		add_action( 'admin_init', array($this,'enqueue_scripts'));

		add_action('add_meta_boxes_'.WPT_Production::post_type_name, array($this, 'add_events_meta_box'));
		
		/*
		 * Process the form by hooking into the `save_post`-action.
		 * We can't use the `save_post_wpt_event'-action because this causes problems 
		 * with ACF's `save_post`-hooks.
		 * See: https://github.com/slimndap/wp-theatre/issues/76
		 */
		add_action('save_post', array( $this, 'save_event' ) );
		
		add_action( 'wp_ajax_wpt_event_editor_delete_event', array($this,'delete_event_over_ajax'));
		
	}
	
	public function add_events_meta_box() {
		add_meta_box(
			'wpt_event_editor', 
			__('Event dates','wp_theatre'), 
			array($this,'events_meta_box'), 
			WPT_Production::post_type_name, 
			'normal', 
			'high'
		);
	}
	
	public function enqueue_scripts() {
		wp_localize_script( 'wp_theatre_admin', 'wpt_editor_defaults', $this->get_defaults() ); 
		
		wp_localize_script( 
			'wp_theatre_admin', 
			'wpt_editor_security', 
			array(
				'nonce' => wp_create_nonce('wpt_editor_nonce'),
			)
		);
	}
	
	public function delete_event_over_ajax() {

		check_ajax_referer( 'wpt_editor_nonce', 'nonce' , true);

		$event_id = $_POST['event_id'];

		// Check if this is a real event.
		if (is_null(get_post($event_id))) {
			wp_die();
		}

		$event = new WPT_Event($event_id);
		$production = $event->production();

		wp_delete_post($event_id, true);
		$this->render_event_listing($production);

		wp_die();
	}
	
	private function get_defaults() {
		$defaults = array(
			'duration' => 2 * HOUR_IN_SECONDS,
			'datetime_format' => 'Y-m-d H:i',
			'event_date' => date('Y-m-d H:i',strtotime('Today 8 PM')),
			'tickets_button' => __('Tickets', 'wp_theatre'),
			'tickets_status' => WPT_Event::tickets_status_onsale,
			'confirm_delete_message' => __('Are you sure that you want to delete this event?','wp_theatre'),
		);
		
		return apply_filters('wpt/event_editor/defaults', $defaults);
	}
	
	public function get_fields() {
		$defaults = $this->get_defaults();
		
		$event_fields = array(
			array(
				'id' => 'event_date',
				'title' => __('Start time','wp_theatre'),
			),
			array(
				'id' => 'enddate',
				'title' => __('End time','wp_theatre'),
				'save' => array(
					'callback' => array($this, 'save_field_enddate'),
				),
			),
			array(
				'id' => 'venue',
				'title' => __('Venue','wp_theatre'),
			),
			array(
				'id' => 'city',
				'title' => __('City','wp_theatre'),
			),
			array(
				'id' => 'remark',
				'title' => __('Remark','wp_theatre'),
				'edit' => array(
					'placeholder' => __('e.g. Premiere or Try-out','wp_theatre'),
				),
			),
			array(
				'id' => 'tickets_status',
				'title' => __('Tickets status','wp_theatre'),
				'edit' => array(
					'callback' => array($this, 'get_control_tickets_status'),
				),
				'save' => array(
					'callback' => array($this, 'save_field_tickets_status'),
				),
			),
			array(
				'id' => 'tickets_url',
				'title' => __('Tickets URL','wp_theatre'),
				'edit' => array(
					'placeholder' => 'http://',
				),
			),
			array(
				'id' => 'tickets_button',
				'title' => __('Text for tickets link','wp_theatre'),
				'edit' => array(
					'description' => sprintf(__('Leave blank for \'%s\'','wp_theatre'), $defaults['tickets_button']),
				),
			),
			array(
				'id' => '_wpt_event_tickets_price',
				'title' => __('Prices','wp_theatre'),
				'edit' => array(
					'callback' => array($this, 'get_control_prices'),
					'description' => __('Place extra prices on a new line.','wp_theatre'),
				),
				'save' => array(
					'callback' => array($this, 'save_field_prices'),
				),
			),
		);
		
		return apply_filters('wpt/event_editor/fields', $event_fields);
	}
	
	public function events_meta_box($post) {
		
		$production = new WPT_Production($post);
		
		echo '<div class="wpt_event_editor_event_listing">';
		$this->render_event_listing($production);
		echo '</div>';

		$this->render_form($production);
		
	}
	
	private function get_actions($event_id) {

		$actions = array(
			'edit' => 	array(
				'label' => __('Edit'),
				'link' => get_edit_post_link($event_id),
			),	
			'delete' => array(
				'label' => __('Delete'),
				'link' => get_delete_post_link($event_id,'',true),
			),	
		);

		return apply_filters('wpt/event_editor/actions', $actions, $event_id);
		

	}

	private function get_actions_html($event_id) {
		
		$html = '';
		
		foreach ($this->get_actions($event_id) as $action=>$action_args) {
			$html.= '<a class="wpt_event_editor_event_action_'.$action.'" href="'.$action_args['link'].'">'.$action_args['label'].'</a> ';
		}
		
		return apply_filters('wpt/event_editor/actions/html', $html, $event_id, $actions);
		
	}
	
	public function get_control($field, $event_id=false) {		
		
		$html = '';
		
		if (!empty($field['edit']['callback'])) {
			$html.= call_user_func_array($field['edit']['callback'], array($field, $event_id) );
		} else {
			$html.= '<input type="text" id="wpt_event_editor_'.$field['id'].'" name="wpt_event_editor_'.$field['id'].'"';
			
			if (!empty($field['edit']['placeholder'])) {
				$html.= ' placeholder="'.$field['edit']['placeholder'].'"';
			}
			
			if(is_numeric($event_id)) {
				$value = get_post_meta($event_id, $field['id'], true);
				
				$html.= ' value="'.esc_attr($value).'"';
			}
			
			$html.= ' />';
		}
		
		$html = apply_filters('wpt/event_editor/control/html/field='.$field['id'], $html);
		$html = apply_filters('wpt/event_editor/control/html', $html, $field);
		
		return $html;
	}
		
	public function get_control_label($field) {
		$html = '';
		

		$label = apply_filters('wpt/event_editor/control/label/field='.$field['id'], $field['title']);
		$label = apply_filters('wpt/event_editor/control/label/', $field['title'], $field);
		
		$html.= '<label for="wpt_event_editor_'.$event_field['id'].'">'.$label.'</label>';
		
		if (!empty($field['edit']['description'])) {
			$html.= '<p class="description">'.$field['edit']['description'].'</p>';
		}
		
		return $html;
	}
	
	public function get_control_prices($field, $event_id=false) {
		$html = '';
		$html.= '<textarea id="wpt_event_editor_'.$field['id'].'" name="wpt_event_editor_'.$field['id'].'">';

		if(is_numeric($event_id)) {
			$values = get_post_meta($event_id, $field['id'], false);
			$html.= implode("\n",$values);
		}

		$html.= '</textarea>';
		return $html;
	}
	
	public function get_control_tickets_status($field, $event_id=false) {
		
		$defaults = $this->get_defaults();
		
		$html = '';

		$tickets_status_options = array(
			WPT_Event::tickets_status_onsale => __('On sale','wp_theatre'),
			WPT_Event::tickets_status_soldout => __('Sold Out','wp_theatre'),
			WPT_Event::tickets_status_cancelled => __('Cancelled','wp_theatre'),
			WPT_Event::tickets_status_hidden => __('Hidden','wp_theatre'),	
		);
		$tickets_status_options = apply_filters('wpt_event_editor_tickets_status_options', $tickets_status_options);

		if(is_numeric($event_id)) {
			$value = get_post_meta($event_id, $field['id'], true);
		}
		
		if (empty($value)) {
			$value = $defaults['tickets_status'];
		}

		foreach ($tickets_status_options as $status=>$name) {
			$html.= '<label>';
			$html.= '<input type="radio" name="wpt_event_editor_'.$field['id'].'" value="'.$status.'"';
			$html.= checked($value, $status, false);
			$html.= ' />';
			$html.= '<span>'.$name.'</span>';
			$html.= '</label><br />	';
		}

		$html.= '<label>';
		$html.= '<input type="radio" name="wpt_event_editor_'.$field['id'].'" value="'.WPT_Event::tickets_status_other.'"';
		$html.= checked(
			in_array(
				$value, 
				array_keys($tickets_status_options)
			), 
			false, 
			false
		);
		$html.= '/>';
		$html.= '<span>'.__('Other','wp_theatre').': </span>';
		$html.= '</label>';
		$html.= '<input type="text" name="wpt_event_editor_'.$field['id'].'_other"';
		if (!in_array($value, array_keys($tickets_status_options))) {
			$html.= ' value="'.esc_attr($value).'"';
		}
		$html.= ' />';

		$html = apply_filters('wpt/event_editor/control/tickets_status/html', $html, $field);

		return $html;
	}
	
	private function render_event_listing($production) {

		$events = $production->events();
		
		if (!empty($events)) {
			echo '<table>';
			for ($i=0;$i<count($events);$i++) {
				$event = $events[$i];					

				if ($i%2 == 1) {
					echo '<tr class="alternate" data-event_id="'.$event->ID.'">';
				} else {
					echo '<tr data-event_id="'.$event->ID.'">';						
				}

				$this->render_event($event);
				
				$html_actions = '';
				$html_actions.= '<td class="wpt_event_editor_event_actions">';
				$html_actions.= $this->get_actions_html($event->ID);
				$html_actions.= '</td>';
				$html_actions = apply_filters('wpt/event_editor/listing/event/actions', $html_actions, $event->ID);
				
				echo $html_actions;

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
		$html.= $event->remark($args);
		$html.= '</td>';
		
		$html.= '<td>';
		$html.= $event->tickets($args);
		$html.= '</td>';
		
		echo apply_filters('wpt_event_editor_event', $html, $event);
	}
	
	private function render_form($production) {
		
		$defaults = $this->get_defaults();
		$events = $production->events();
		
		wp_nonce_field( 'wpt_event_editor', 'wpt_event_editor_nonce' );

		if (empty($events)) {
			echo '<h4>'.__('Add the first date:','wp_theatre').'</h4>';					
		} else {
			echo '<h4>'.__('Add a new date','wp_theatre').'</h4>';		
		}

		echo '<table class="wpt_event_editor_event_form">';

		$event_fields = $this->get_fields();
		foreach ($event_fields as $field) {
			
			$html = '';
			$html.= '<tr>';
			$html.= '<th>'.$this->get_control_label($field).'</th>';
			$html.= '<td>';
			$html.= $this->get_control($field);
			$html.= '</td>';
			$html.= '</tr>';

			$html = apply_filters('wpt/editor/form/event_control/field='.$field['id'], $html);
			$html = apply_filters('wpt/editor/form/event_control', $html);

			echo $html;
		}
	
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
		
		/*
		 * Event needs at least at start time.
		 */
		if (empty($_POST['wpt_event_editor_event_date'])) {
			return $post_id;
		}
		
		// Unhook to avoid loops
		remove_action( 'save_post', array( $this, 'save_event' ) );

		$post = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => 'publish',
		);
		
		if ($event_id = wp_insert_post($post)) {
			
			add_post_meta($event_id, WPT_Production::post_type_name, $post_id, true);

			foreach ($this->get_fields() as $field) {
				$this->save_field($field, $event_id);
			}
			
			return new WPT_Event($post_id);

		} else {
			return false;
		}

	}	
	
	public function save_field($field, $event_id) {

		if (!empty($field['save']['callback'])) {
			call_user_func_array( $field['save']['callback'], array($field, $event_id) );
		} else {
			$value = $_POST[ 'wpt_event_editor_'.$field[ 'id' ] ];
			
			$value = apply_filters( 'wpt/event_editor/save/field/value/field='.$field['id'], $value, $event_id);
			$value = apply_filters( 'wpt/event_editor/save/field/value', $value, $field, $event_id);
			
			$field_id = update_post_meta($event_id, $field['id'], $value);			
		}

		do_action( 'wpt/event_editor/save/field/field='.$field['id'], $value, $event_id );
		do_action( 'wpt/event_editor/save/field', $value, $field, $event_id );
		
		return $field_id;
			
	}
	
	public function save_field_enddate($field, $event_id) {
		
		$defaults = $this->get_defaults();
		
		$value = $_POST['wpt_event_editor_'.$field['id']];
		
		$event_date = strtotime( $_POST['wpt_event_editor_event_date'] );
		$enddate = strtotime( $value );
		if ($enddate < $event_date) {
			$value = date( 'Y-m-d H:i', $event_date + $defaults['duration'] );
		}
		
		$field_id = update_post_meta($event_id, $field['id'], $value);
		
		do_action( 'wpt/event_editor/save/field/enddate', $value, $field, $event_id );
		
		return $field_id;
		
	}
	
	public function save_field_prices($field, $event_id) {

		delete_post_meta($event_id, $field['id']);

		$values = explode("\n",$_POST['wpt_event_editor_'.$field['id']]);
		
		foreach($values as $value) {
			$value = apply_filters( 'wpt/event_editor/save/field/prices/value', $value, $field, $event_id);
			add_post_meta($event_id, $field['id'], $value);			
		}		
		
		do_action( 'wpt/event_editor/save/field/prices', $value, $field, $event_id );
		
	}
	
	public function save_field_tickets_status($field, $event_id) {

		$value = $_POST['wpt_event_editor_'.$field['id']];

		if ($value==WPT_Event::tickets_status_other) {
			$value = $_POST['wpt_event_editor_'.$field['id'].'_other'];
		}

		$value = apply_filters( 'wpt/editor/event/save/field/tickets_status/value', $value, $field, $event_id);

		update_post_meta( $event_id, $field['id'], $value );

		do_action( 'wpt/event_editor/save/field/tickets_status', $value, $field, $event_id );
		
	}
	
}