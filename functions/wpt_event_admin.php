<?php

/**
 * Customizes the default admin pages for events.
 * @since	0.13
 */
class WPT_Event_Admin {

	function __construct() {
		add_filter( 'redirect_post_location', array($this, 'redirect_after_save_event' ), 10, 2);
		add_action( 'add_meta_boxes', array($this, 'add_event_editor_meta_box'));
		add_action( 'add_meta_boxes', array($this, 'add_publish_meta_box'));
		add_action( 'save_post', array( $this, 'save_event' ));
		add_filter( 'wp_link_query_args', array( $this, 'remove_events_from_link_query' ) );
		add_filter('wpt/event_editor/fields', array($this, 'add_production_to_event_editor'), 10, 2);
		add_action( 'add_meta_boxes_'.WPT_Event::post_type_name, array($this,'remove_add_new_button'));
		add_action( 'admin_menu', array($this, 'remove_meta_boxes') );
	}
	
	/**
	 * Adds the events editor meta box to the event admin page.
	 * 
	 * @since	0.11
	 * @since	0.13	Moved from WPT_Event_Editor to WPT_Event_Admin.
	 * @since	0.15	Renamed title of metabox from 'Event' to 'Event date'.
	 */
	public function add_event_editor_meta_box() {
		add_meta_box(
			'wpt_event_editor', 
			__('Event date','theatre'), 
			array($this,'event_editor_meta_box'), 
			WPT_Event::post_type_name, 
			'normal', 
			'high'
		);		
	}
	
	/**
	 * Adds a production field to the event editor on the event admin page.
	 * 
	 * @since	0.11
	 * @since	0.13	Moved from WPT_Event_Editor to WPT_Event_Admin.
	 * @since	0.15	Renamed production field label from 'Production' to 'Event'.
	 *
	 * @param 	array 	$fields		The currently defined fields for the event editor.
	 * @param 	int 	$event_id	The event that being edited.
	 * @return 	array				The fields, with a production field added at the beginning.
	 */
	public function add_production_to_event_editor($fields, $event_id) {
		
		$current_screen = get_current_screen();
		
		if (
			! is_null($current_screen) &&
			(WPT_Event::post_type_name == $current_screen->id)
		) {
			array_unshift(
				$fields,
				array(
					'id' => WPT_Production::post_type_name,
					'title' => __('Event','theatre'),
					'edit' => array(
						'callback' => array($this, 'get_control_production_html'),
					),
				)
			);				
		}
		
		return $fields;
		
	}

	/**
	 * Add a Publish meta box to the event admin page.
	 *
	 * Replaces the default Publish meta box for posts.
	 * @see WPT_Event_Admin::remove_meta_boxes().
	 * 
	 * @since	0.13
	 * @return 	void
	 */
	public function add_publish_meta_box() {
		add_meta_box(
			'wpt_event_publish', 
			__('Publish'), 
			array($this,'publish_meta_box'), 
			WPT_Event::post_type_name, 
			'side', 
			'high'
		);		
		
	}

	/**
	 * Outputs the contents of the event editor meta box.
	 * 
	 * @since	0.11
	 * @since	0.13	Moved from WPT_Event_Editor to WPT_Event_Admin.
	 * @param 	int		$event	The event ID.
	 * @return 	void
	 */
	public function event_editor_meta_box($event) {
		global $wp_theatre;
		
		wp_nonce_field(WPT_Event::post_type_name, WPT_Event::post_type_name.'_nonce' );

		$production_id = get_post_meta($event->ID, WPT_Production::post_type_name, true);
		echo $wp_theatre->event_editor->get_form_html($production_id, $event->ID);		
	}
	
	/**
	 * Gets the HTML for a production input control of an event.
	 *
	 * The input control consists of:
	 * 1. A hidden input with the production_id and
	 * 2. A link to the admin page of the production.	 
	 *
	 * @since 	0.11
	 * @param 	array 	$field		The field.
	 * @param 	int     $event_id   The event that is being edited.
	 * @return 	string				The HTML.
	 */
	public function get_control_production_html($field, $event_id) {
		$html = '';
		
		$production_id = get_post_meta($event_id, $field['id'], true);

		if (!empty($production_id)) {
			
			$production = new WPT_Production( $production_id );
			
			$html.= '<input type="hidden" id="wpt_event_editor_'.$field['id'].'" name="wpt_event_editor_'.$field['id'].'" value="'.$production->ID.'" />';
			$html.= '<a href="'.get_edit_post_link($production->ID).'">'.$production->title().'</a>';
		}
		
		return $html;		
	}

	/**
	 * Outputs the contents of the Publish meta box.
	 * 
	 * @since	0.13
	 * @since	0.15	Renamed button label from 'Update Event' to 'Update Event Date'.
	 *
	 * @param 	int		$event	The event ID.
	 */
	public function publish_meta_box($event) {
		$html = '';
		$html.= submit_button( __( 'Update Event Date', 'theatre' ), 'primary button-large' );
		echo $html;
	}
	
	/**
	 * Redirects the user to the production edit page when after saving an event.
	 * 
	 * @since	0.13
	 * @param 	string	$location	The default redirect URL.
	 * @param 	int		$post_id	The event ID.
	 * @return	string				The URL of the production edit page.
	 */
	public function redirect_after_save_event( $location, $post_id ) {
        if ( WPT_Event::post_type_name == get_post_type( $post_id ) ) {
	        $event = new WPT_Event($post_id);
	        $production = $event->production();
	        if (!empty($production)) {
				$location = admin_url('post.php?post='.$production->ID.'&action=edit');
		    }
        }
        return $location;
	}
	
	/**
	 * Removes the 'Add new' button at the top of the event admin page.
	 * 
	 * @since	0.13
	 * @return 	void
	 */
	public function remove_add_new_button() {
		unset($GLOBALS['post_new_file']);
	}

    /**
     * Removes events from link query.
     *
     * Removes events from the 'link to existing content' section on the 'Insert/edit link' dialog.
     * 
     * @since	0.12.3
	 * @since	0.13	Moved from WPT_Admin to WPT_Event_Admin.
     * @param 	array	$query	The current link query.
     * @return 	array			The updated link query.
     */
    public function remove_events_from_link_query($query) {
	    $key = array_search( WPT_Event::post_type_name, $query['post_type'] );
	    if( $key ) {
        	unset( $query['post_type'][$key] );		    
	    }
	    return $query;	    
    }

	/**
	 * Removes all default meta boxes from the event admin page.
	 * 
	 * @since	0.13
	 * @return 	void
	 */
	public function remove_meta_boxes() {
		remove_meta_box('submitdiv', WPT_Event::post_type_name, 'side');
		remove_meta_box('categorydiv', WPT_Event::post_type_name, 'side');
		remove_meta_box('tagsdiv-post_tag', WPT_Event::post_type_name, 'side');
	}

	/**
	 * Saves all custom fields for an event.
	 * 
	 * Runs when an event is submitted from the event admin form.
	 *
	 * @since 	?.?
	 * @since 	0.11	Use WPT_Event_Editor::save_field() to save all field values.
	 * @since 	0.11.5	Added the new $data param to WPT_Event_Editor::save_field().
	 * @since	0.13	Moved from WPT_Admin to WPT_Event_Admin.
	 * @since	0.15.29	Added an extra check to make sure we are saving the data of an event.
	 *
	 * @param 	int 	$post_id	The event_id.
	 * @return void
	 */
	function save_event( $post_id ) {
		
		global $wp_theatre;
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST[WPT_Event::post_type_name.'_nonce'] ) )
			return $post_id;

		$nonce = $_POST[WPT_Event::post_type_name.'_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, WPT_Event::post_type_name ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
		// so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Check if this is an event.
		if ( WPT_Event::post_type_name != get_post_type( $post_id ) ) {
			return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		foreach ($wp_theatre->event_editor->get_fields( $post_id ) as $field) {
			$wp_theatre->event_editor->save_field($field, $post_id, $_POST);
		}
	}
	
}
