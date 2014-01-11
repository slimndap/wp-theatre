<?php
class WPT_Admin {
	function __construct() {
		add_action( 'admin_init', array($this,'admin_init'));
		add_action( 'admin_menu', array($this, 'admin_menu' ));
		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action( 'edit_post', array( $this, 'edit_post' ));
		add_action( 'delete_post',array( $this, 'delete_post' ));
		add_action( 'save_post', array( $this, 'save_post' ) );

		add_action( 'wp_dashboard_setup', array($this,'wp_dashboard_setup' ));

		$this->options = get_option( 'wp_theatre' );
	}	

	function admin_init() {
		wp_enqueue_script( 'wp_theatre_js', plugins_url( '../main.js', __FILE__ ), array('jquery') );
		wp_enqueue_style( 'wp_theatre_css', plugins_url( '../style.css', __FILE__ ) );
		wp_enqueue_script( 'jquery-ui-timepicker', plugins_url( '../js/jquery-ui-timepicker-addon.js', __FILE__ ), array('jquery-ui-datepicker','jquery-ui-slider')  );
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

        register_setting(
            'wp_theatre_group', // Option group
            'wp_theatre' // Option name
        );

        add_settings_section(
            'display_section_id', // ID
            __('Display','wp_theatre'), // Title
            '', // Callback
            'theatre-admin' // Page
        );  

        add_settings_field(
            'settings_field_show_events', // ID
            __('Show events on production page.','wp_theatre'), // Title 
            array( $this, 'settings_field_show_events' ), // Callback
            'theatre-admin', // Page
            'display_section_id' // Section           
        );      
	}

	function admin_menu() {
		add_menu_page( __('Theatre','wp_theatre'), __('Theatre','wp_theatre'), 'edit_posts', 'theatre', array(), 'dashicons-calendar', 30);
		add_submenu_page( 'theatre', 'Theatre '.__('Settings'), __('Settings'), 'manage_options', 'theatre-admin', array( $this, 'admin_page' ));
	}
	
	function add_meta_boxes() {
		add_meta_box(
            'wp_theatre_events',
            WPT_Event::post_type()->labels->name,
            array($this,'meta_box_events'),
            WPT_Production::post_type()->name,
            'side'
        ); 		
		add_meta_box(
            'wp_theatre_event_data',
            __('Event data','wp_theatre'),
            array($this,'meta_box_event_data'),
            WPT_Event::post_type()->name,
            'normal'
        ); 		
		add_meta_box(
            'wp_theatre_tickets',
            __('Tickets','wp_theatre'),
            array($this,'meta_box_tickets'),
            WPT_Event::post_type()->name,
            'normal'
        ); 	
		add_meta_box(
            'wp_theatre_seasons',
            WPT_Season::post_type()->labels->name,
            array($this,'meta_box_seasons'),
            WPT_Production::post_type()->name,
            'side'
        ); 	
	}
	
	function meta_box_events($production) {
		$production = new WPT_Production(get_the_id());
		
		if (get_post_status($production->ID) == 'auto-draft') {
			echo __('You need to save this production before you can add events.','wp_theatre');
		} else {
			$events = $production->upcoming_events();
			if (count($events)>0) {
				echo '<ul>';
				foreach ($events as $event) {
					echo '<li>';
					echo $this->render_event($event);
					echo '</li>';
					
				}
				echo '</ul>';	
			}	
			$events = $production->past_events();
			if (count($events)>0) {
				echo '<h4>'.__('Past events','wp_theatre').'</h4>';
				echo '<ul>';
				foreach ($events as $event) {
					echo '<li>';
					echo $this->render_event($event);
					echo '</li>';
					
				}
				echo '</ul>';	
			}	
			echo '<p><a href="'.get_bloginfo('url').'/wp-admin/post-new.php?post_type='.WPT_Event::post_type_name.'&'.WPT_Production::post_type_name.'='.$production->ID.'" class="button button-primary">'.WPT_Event::post_type()->labels->new_item.'</a></p>';	
		}		
	}

	function meta_box_event_data($event) {
		global $wp_theatre;
		
		wp_nonce_field(WPT_Event::post_type()->name, WPT_Event::post_type()->name.'_nonce' );

		echo '<table class="form-table">';
		echo '<tbody>';

		echo '<tr class="form-field">';		
		echo '<th>';
		echo '<label>';
		echo WPT_Production::post_type()->labels->singular_name;
		echo '</label> ';
		echo '</th>';
		
		echo '<td>';

		if (isset($_GET[WPT_Production::post_type_name])) {
			$current_production = (int) $_GET[WPT_Production::post_type_name];
		} else {
			$current_production = get_post_meta($event->ID,WPT_Production::post_type_name,true);
		}

		if (is_numeric($current_production)) {
			$production = new WPT_Production($current_production);
			echo '<input type="hidden" name="'.WPT_Production::post_type_name.'" value="'.$current_production.'" />';
			echo $this->render_production($production);
		} else {
			echo '<select name="'.WPT_Production::post_type_name.'">';
			$args = array(
				'post_type'=>WPT_Production::post_type_name,
				'posts_per_page' => -1
			);
			$productions = get_posts($args);
			foreach ($productions as $production) {
				echo '<option value="'.$production->ID.'">';
				echo get_the_title($production->ID);
				echo '</option>';
			}
			echo '</select>';
			
		}	
		
		echo '</td>';
		echo '</tr>';
		
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Event date','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" class="wp_theatre_datepicker" name="event_date"';
        echo ' value="' . get_post_meta($event->ID,'event_date',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Venue','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" name="venue"';
        echo ' value="' . get_post_meta($event->ID,'venue',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
		echo '<tr class="form-field">';
		echo '<th><label>'.__('City','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" name="city"';
        echo ' value="' . get_post_meta($event->ID,'city',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Remark','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" name="remark"';
        echo ' value="' . get_post_meta($event->ID,'remark',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
        echo '</tbody>';
        echo '</table>';		
	}
	
	function meta_box_tickets($event) {
		echo '<table class="form-table">';
		echo '<tbody>';
		
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Tickets URL','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="url" name="tickets_url"';
        echo ' value="' . get_post_meta($event->ID,'tickets_url',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
  		echo '<tr>';
		echo '<th><label>'.__('Status','wp_theatre').'</label></th>';	
		echo '<td>';
				
		$status = get_post_meta($event->ID,'tickets_status',true);
		
		echo '<label>';
		echo '<input type="radio" name="tickets_status" value=""';
		if ($status=='') {
			echo ' checked="checked"';
		}
		echo '> ';
		echo '<span>'.__('On sale','wp_theatre').'</span>';
		echo '</label><br />';
		
		echo '<label>';
		echo '<input type="radio" name="tickets_status" value="soldout"';
		if ($status=='soldout') {
			echo ' checked="checked"';
		}
		echo '> ';
		echo '<span>'.__('Sold out','wp_theatre').'</span>';
		echo '</label><br />';
		
 		echo '</td>';
		echo '</tr>';
		
  		echo '<tr>';
		echo '<th><label>'.__('Text on button','wp_theatre').'</label></th>';	
		echo '<td>';
						
		echo '<input type="text" name="tickets_button"';
        echo ' value="' . get_post_meta($event->ID,'tickets_button',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
        echo '</tbody>';
        echo '</table>';		
	}

	function meta_box_seasons($production) {
		global $wp_theatre;
		
		wp_nonce_field(WPT_Production::post_type_name, WPT_Production::post_type_name.'_nonce' );

		$args = array(
			'post_type'=>WPT_Season::post_type_name,
			'posts_per_page' => -1
		);

		$seasons = get_posts($args);
		if (count($seasons)>0) {
			echo '<select name="'.WPT_Season::post_type_name.'">';
			echo '<option></option>';
			foreach ($seasons as $season) {
				echo '<option value="'.$season->ID.'"';
				if (get_post_meta($production->ID,WPT_Season::post_type_name,true)==$season->ID) {
					echo ' selected="selected"';
				}
				echo '>';
				echo $season->post_title;
				echo '</option>';
			}
			echo '</select>';

		}
	
	}
	
	function edit_post( $post_id ) {
		$this->flush_cache();
	}
	
	function delete_post( $post_id ) {
		$this->flush_cache();	
	}
	
	function save_post( $post_id ) {
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
        //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$production = sanitize_text_field( $_POST[WPT_Production::post_type_name] );
		$event_date = sanitize_text_field( $_POST['event_date'] );
		$venue = sanitize_text_field( $_POST['venue'] );
		$city = sanitize_text_field( $_POST['city'] );
		$remark = sanitize_text_field( $_POST['remark'] );
		$tickets_url = sanitize_text_field( $_POST['tickets_url'] );
		$tickets_button = sanitize_text_field( $_POST['tickets_button'] );
		$tickets_status = $_POST['tickets_status'];
		
		// Update the meta field.
		update_post_meta( $post_id, WPT_Production::post_type_name, $production );
		update_post_meta( $post_id, 'event_date', $event_date );
		update_post_meta( $post_id, 'venue', $venue );
		update_post_meta( $post_id, 'city', $city );
		update_post_meta( $post_id, 'remark', $remark );
		update_post_meta( $post_id, 'tickets_url', $tickets_url );
		update_post_meta( $post_id, 'tickets_status', $tickets_status );
		update_post_meta( $post_id, 'tickets_button', $tickets_button );
	
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST[WPT_Production::post_type_name.'_nonce'] ) )
			return $post_id;

		$nonce = $_POST[WPT_Production::post_type_name.'_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, WPT_Production::post_type_name ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$season = sanitize_text_field( $_POST[WPT_Season::post_type_name] );

		// Update the meta field.
		update_post_meta( $post_id, WPT_Season::post_type_name, $season );
		
		$this->flush_cache();
	}
	
	function flush_cache() {
		if(!class_exists('W3_Plugin_TotalCacheAdmin'))		
			return;	
		if (
			!in_array(
				get_post_type($post_id),
				array(WPT_Production::post_type_name,WPT_Event::post_type_name,WPT_Season::post_type_name)
			)
		) return;   
			
		if (function_exists('w3tc_pgcache_flush')) { w3tc_pgcache_flush(); }		
	}

    public function settings_field_show_events() {
        printf(
            '<input type="checkbox" id="show_events" name="wp_theatre[show_events]" value="yes" %s />',
    		(isset( $this->options['show_events'] ) && (esc_attr( $this->options['show_events'])=='yes')) ? 'checked="checked"' : ''
        );
    }

    public function admin_page()
    {
        // Set class property
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Theatre <?php echo __('Settings');?></h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'wp_theatre_group' );   
                do_settings_sections( 'theatre-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }
    
    function wp_dashboard_setup() {
		wp_add_dashboard_widget(
                 'dashboard_wp_theatre',         // Widget slug.
                 __('Theatre','wp_theatre'),         // Title.
                 array($this,'wp_add_dashboard_widget') // Display function.
        );		    
    }
    
    function wp_add_dashboard_widget() {
    	global $wp_theatre;
		$html = '';
		$html.= '<h4>'.__('Upcoming events','wp_theatre').'</h4>';
		$html.= '<ul class="events">';
		foreach ($wp_theatre->get_events() as $event) {
			$html.= '<li>';
			$html.= $this->render_event($event);

			$html.= '</li>';
		}
		$html.= '</ul>';

		$html.= '<h4>'.__('Current productions','wp_theatre').'</h4>';
		$html.= '<ul class="productions">';
		foreach ($wp_theatre->get_productions() as $production) {
			$html.= '<li>';
			$html.= $this->render_production($production);

			$html.= '</li>';
		}
		$html.= '</ul>';
		echo $html;
    }

	function render_event($event) {
		$html = '';
		
		$html.= '<div class="'.WPT_Event::post_type_name.'">';
			
		$html.= '<div class="date">';
		$html.= '<a href="'.get_edit_post_link($event->ID).'">';
		$html.= $event->date().' '.$event->time(); 
		$html.= '</a>';
		$html.= '</div>'; // .date
		
		$html.= '<div class="content">';
		$html.= '<div class="title">'.$event->production()->post()->post_title.'</div>';
		
		$remark = get_post_meta($event->ID,'remark',true);
		if ($remark!='') {
			$html.= '<div class="remark">'.$remark.'</div>';
		}
		
		$html.= get_post_meta($event->ID,'venue',true);
		$html.= ', ';
		$html.= get_post_meta($event->ID,'city',true);
		$html.= '</div>'; //.content

		$html.= '<div class="tickets">';
		if (get_post_meta($event->ID,'tickets_status',true) == 'soldout') {
			$html.= __('Sold out', 'wp_theatre');
		} else {
			$url = get_post_meta($event->ID,'tickets_url',true);
			if ($url!='') {
				$html.= '<a href="'.get_post_meta($event->ID,'tickets_url',true).'" class="button">';
				$button_text = get_post_meta($event->ID,'tickets_button',true);
				if ($button_text!='') {
					$html.= $button_text;
				} else {
					$html.= __('Tickets','wp_theatre');			
				}
				$html.= '</a>';
				
			}
		}
		$html.= '</div>'; //.tickets

		$html.='</div>'; // .event
		
		return $html;	
	}

	function render_production($production) {
		$html = '';
		
		$html.= '<div class="'.WPT_Production::post_type_name.'">';
			
		$html.= '<div class="thumbnail">';
		$html.= get_the_post_thumbnail($production->ID, 'thumbnail'); 
		$html.= '</div>'; // .thumbnail
		
		$html.= '<div class="content">';
		$html.= '<a href="'.get_edit_post_link($production->ID).'">';
		$html.= $production->post()->post_title;
		$html.= '</a>';
		$html.= '<br />';
		$html.= $production->dates();
		$html.= '<br />';
		$html.= $production->cities();
		
		$html.= '</div>'; //.content


		$html.='</div>'; // .production
		
		return $html;	
	}

}

if (is_admin()) {
	new WPT_Admin();
}

?>