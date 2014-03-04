<?php
class WPT_Admin {
	function __construct() {
		// Hooks (only in admin screens)
		if (is_admin()) {
			add_action( 'admin_init', array($this,'admin_init'));
			add_action( 'admin_menu', array($this, 'admin_menu' ));
			add_action( 'add_meta_boxes', array($this, 'add_meta_boxes'));
			add_action( 'edit_post', array( $this, 'edit_post' ));
			add_action( 'delete_post',array( $this, 'delete_post' ));
			add_filter( 'wpt_event', array($this,'wpt_event'), 10 ,2);
			add_action( 'quick_edit_custom_box', array($this,'quick_edit_custom_box'), 10, 2 );
			add_action( 'wp_dashboard_setup', array($this,'wp_dashboard_setup' ));
			add_action( 'save_post_'.WPT_Production::post_type_name, array( $this, 'save_production' ) );
			add_action( 'save_post_'.WPT_Event::post_type_name, array( $this, 'save_event' ) );

			add_filter('manage_wp_theatre_prod_posts_columns', array($this,'manage_wp_theatre_prod_posts_columns'), 10, 2);
			add_filter('manage_wp_theatre_event_posts_columns', array($this,'manage_wp_theatre_event_posts_columns'), 10, 2);
			add_action('manage_wp_theatre_prod_posts_custom_column', array($this,'manage_wp_theatre_prod_posts_custom_column'), 10, 2);
			add_action('manage_wp_theatre_event_posts_custom_column', array($this,'manage_wp_theatre_event_posts_custom_column'), 10, 2);	
		}

		// More hooks (always load, necessary for bulk editing through AJAX)
		add_action( 'bulk_edit_custom_box', array($this,'bulk_edit_custom_box'), 10, 2 );

		// Options
		$this->options = get_option( 'wp_theatre' );
		
		// Tabs on settings screens
		$this->tabs = array(
			'wp_theatre'=>__('General'),
			'wpt_language'=>__('Language','wp_theatre'),
			'wpt_social'=>__('Social','wp_theatre')
		);
		$this->tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'wp_theatre';
	}	

	function admin_init() {
		wp_enqueue_script( 'wp_theatre_admin', plugins_url( '../js/admin.js', __FILE__ ), array('jquery') );
		wp_enqueue_style( 'wp_theatre_admin', plugins_url( '../css/admin.css', __FILE__ ) );
		wp_enqueue_script( 'jquery-ui-timepicker', plugins_url( '../js/jquery-ui-timepicker-addon.js', __FILE__ ), array('jquery-ui-datepicker','jquery-ui-slider')  );
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		wp_enqueue_style( 'wp_theatre', plugins_url( '../css/style.css', __FILE__ ) );


        register_setting(
            'wp_theatre', // Option group
            'wp_theatre' // Option name
        );
        
		if ($this->tab=='wp_theatre') {

	        add_settings_section(
	            'display_section_id', // ID
	            __('Display','wp_theatre'), // Title
	            '', // Callback
	            'wp_theatre' // Page
	        );  
	
	        add_settings_field(
	            'settings_field_show_events', // ID
	            __('Event listings on production page','wp_theatre'), // Title 
	            array( $this, 'settings_field_show_events' ), // Callback
	            'wp_theatre', // Page
	            'display_section_id' // Section           
	        );

	        add_settings_field(
	            'stylesheet', // ID
	            __('Stylesheet','wp_theatre'), // Title 
	            array( $this, 'settings_field_stylesheet' ), // Callback
	            'wp_theatre', // Page
	            'display_section_id' // Section           
	        );

	        add_settings_field(
	            'css', // ID
	            __('Custom CSS','wp_theatre'), // Title 
	            array( $this, 'settings_field_css' ), // Callback
	            'wp_theatre', // Page
	            'display_section_id' // Section           
	        );

	        add_settings_section(
	            'tickets_integration', // ID
	            __('Tickets integration','wp_theatre'), // Title
	            '', // Callback
	            'wp_theatre' // Page
	        );  

	        add_settings_field(
	            'currenysymbol', // ID
	            __('Currency symbol','wp_theatre'), // Title 
	            array( $this, 'settings_field_currencysymbol' ), // Callback
	            'wp_theatre', // Page
	            'tickets_integration' // Section           
	        );      
	
	        add_settings_field(
	            'integrationtype', // ID
	            __('Open tickets screens in','wp_theatre'), // Title 
	            array( $this, 'settings_field_integrationtype' ), // Callback
	            'wp_theatre', // Page
	            'tickets_integration' // Section           
	        );      
	        
	        add_settings_field(
	            'iframepage', // ID
	            __('Iframe page','wp_theatre'), // Title 
	            array( $this, 'settings_field_iframepage' ), // Callback
	            'wp_theatre', // Page
	            'tickets_integration' // Section           
	        );      
		}

        register_setting(
            'wpt_language', // Option group
            'wpt_language' // Option name
        );
		if ($this->tab=='wpt_language') {
 	        add_settings_section(
	            'language', // ID
	            __('Language','wp_theatre'), // Title
	            '', // Callback
	            'wpt_language' // Page
	        );  

	        add_settings_field(
	            'language_tickets', // ID
	            __('Tickets','wp_theatre'), // Title 
	            array( $this, 'settings_field_language_tickets' ), // Callback
	            'wpt_language', // Page
	            'language' // Section           
	        );      
	
	        add_settings_field(
	            'language_events', // ID
	            __('Events','wp_theatre'), // Title 
	            array( $this, 'settings_field_language_events' ), // Callback
	            'wpt_language', // Page
	            'language' // Section           
	        );      
	
	        add_settings_field(
	            'language_categories', // ID
	            __('Categories','wp_theatre'), // Title 
	            array( $this, 'settings_field_language_categories' ), // Callback
	            'wpt_language', // Page
	            'language' // Section           
	        );      
	
       
		}
		
        register_setting(
            'wpt_social', // Option group
            'wpt_social' // Option name
        );
		if ($this->tab=='wpt_social') {
        
	        add_settings_section(
	            'sharing', // ID
	            __('Sharing','wp_theatre'), // Title
	            '', // Callback
	            'wpt_social' // Page
	        );  
	
	        add_settings_field(
	            'social_meta_tags', // ID
	            __('Add social meta tags on production pages for:','wp_theatre'), // Title 
	            array( $this, 'settings_field_social_meta_tags' ), // Callback
	            'wpt_social', // Page
	            'sharing' // Section           
	        );
		}
	}

	function admin_menu() {
		add_menu_page( __('Theatre','wp_theatre'), __('Theatre','wp_theatre'), 'edit_posts', 'theatre', array(), 'none', 30);
		add_submenu_page( 'theatre', 'Theatre '.__('Settings'), __('Settings'), 'manage_options', 'wpt_admin', array( $this, 'admin_page' ));
	}
	
	function add_meta_boxes() {
		add_meta_box(
            'wp_theatre_events',
            WPT_Event::post_type()->labels->name,
            array($this,'meta_box_events'),
            WPT_Production::post_type()->name,
            'side',
            'core'
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
		add_meta_box(
            'wp_theatre_sticky',
            __('Display','wp_theatre'),
            array($this,'meta_box_sticky'),
            WPT_Production::post_type()->name,
            'side'
        ); 		
	}
	
	function meta_box_sticky($production) {
		echo '<label>';
		
		echo '<input type="checkbox" name="sticky"';
		if (is_sticky()) {
			echo ' checked="checked"';
		}
		echo ' />';
		echo __('Stick this production to all listings.','wp_theatre');
		
		echo '</label>';
	}

	function get_events($production_id) {
		$args = array(
			'post_type'=>WPT_Event::post_type_name,
			'meta_key' => 'event_date',
			'order_by' => 'meta_value',
			'order' => 'ASC',
			'posts_per_page' => -1,
			'post_status'=>'all',
			'meta_query' => array(
				array(
					'key' => WPT_Production::post_type_name,
					'value' => $production_id,
					'compare' => '=',
				),
			),
		);
		$posts = get_posts($args);

		$events = array();
		for ($i=0;$i<count($posts);$i++) {
			$datetime = strtotime(get_post_meta($posts[$i]->ID,'event_date',true));
			$events[$datetime.$posts[$i]->ID] = new WPT_Event($posts[$i]);
		}
		
		ksort($events);
		return array_values($events);

	}

	function meta_box_events($production) {
		global $wp_theatre;
		$production = new WPT_Production(get_the_id());
		
		$wp_theatre->events->args['production'] = $production->ID;
		
		if (get_post_status($production->ID) == 'auto-draft') {
			echo __('You need to save this production before you can add events.','wp_theatre');
		} else {
			$events = $production->upcoming();
			if (count($events)>0) {
				echo '<ul>';
				foreach ($events as $event) {
					echo '<li>';
					echo $this->render_event($event);
					echo '</li>';
					
				}
				echo '</ul>';	
			}	
			$events = $production->past();
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
		echo '<th><label>'.__('Start date','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" class="wp_theatre_datepicker" name="event_date"';
        echo ' value="' . get_post_meta($event->ID,'event_date',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
		echo '<tr class="form-field">';
		echo '<th><label>'.__('End date','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" class="wp_theatre_datepicker" name="enddate"';
        echo ' value="' . get_post_meta($event->ID,'enddate',true) . '" />';
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
		echo '<input type="text" name="remark" value="' . get_post_meta($event->ID,'remark',true) . '" placeholder="'.__('e.g. Premiere or Try-out','wp_theatre').'" />';
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
		wp_nonce_field(WPT_Production::post_type_name, WPT_Production::post_type_name.'_nonce' );

		$args = array(
			'post_type'=>WPT_Season::post_type_name,
			'posts_per_page' => -1
		);

		$seasons = get_posts($args);
		echo '<select name="'.WPT_Season::post_type_name.'">';
		echo '<option></option>';
		if (count($seasons)>0) {
			foreach ($seasons as $season) {
				echo '<option value="'.$season->ID.'"';
				if (get_post_meta($production->ID,WPT_Season::post_type_name,true)==$season->ID) {
					echo ' selected="selected"';
				}
				echo '>';
				echo $season->post_title;
				echo '</option>';
			}

		}
		echo '</select>';
	
	}
	
	function edit_post( $post_id ) {
		$this->flush_cache();
	}
	
	function delete_post( $post_id ) {
		$this->flush_cache();	
	}
	
	function save_post( $post_id ) {
		$this->save_production( $post_id );
		$this->save_event( $post_id );
		$this->flush_cache();
	}
	
	function save_event( $post_id ) {
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
		
		$event_date = strtotime($_POST['event_date']);
		$enddate = strtotime($_POST['enddate']);
		if ($enddate<$event_date) {
			$enddate = $event_date;
		}

		$venue = sanitize_text_field( $_POST['venue'] );
		$city = sanitize_text_field( $_POST['city'] );
		$remark = sanitize_text_field( $_POST['remark'] );
		$tickets_url = esc_url( $_POST['tickets_url'] );
		$tickets_button = sanitize_text_field( $_POST['tickets_button'] );
		$tickets_status = $_POST['tickets_status'];
		
		// Update the meta field.
		update_post_meta( $post_id, WPT_Production::post_type_name, $production );
		update_post_meta( $post_id, 'event_date', date('Y-m-d H:i:s',$event_date) );
		update_post_meta( $post_id, 'enddate', date('Y-m-d H:i:s',$enddate) );
		update_post_meta( $post_id, 'venue', $venue );
		update_post_meta( $post_id, 'city', $city );
		update_post_meta( $post_id, 'remark', $remark );
		update_post_meta( $post_id, 'tickets_url', $tickets_url );
		update_post_meta( $post_id, 'tickets_status', $tickets_status );
		update_post_meta( $post_id, 'tickets_button', $tickets_button );
		
	}
	
	function save_production( $post_id ) {
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
		
		$sticky = '';
		if (isset($_POST['sticky'])) {
			$sticky = sanitize_text_field( $_POST['sticky'] );
		}

		// Update the meta field.
		update_post_meta( $post_id, WPT_Season::post_type_name, $season );
		update_post_meta( $post_id, 'sticky', $sticky );
		
		// Update status of connected Events
		remove_action( 'save_post', array( $this, 'save_post' ) );
		$events = $this->get_events($post_id);
		foreach($events as $event) {
			$post = array(
				'ID'=>$event->ID,
				'post_status'=>get_post_status($post_id)
			);
			wp_update_post($post);
		}
		add_action( 'save_post', array( $this, 'save_post' ) );
	
		
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


    function wp_dashboard_setup() {
		wp_add_dashboard_widget(
             'dashboard_wp_theatre',         // Widget slug.
             __('Theatre','wp_theatre'),         // Title.
             array($this,'wp_add_dashboard_widget') // Display function.
        );		    
    }
    
    function wp_add_dashboard_widget() {
    	global $wp_theatre;
    	$args = array(
    		'paginateby' => array('month','category')
    	);
    	echo $wp_theatre->events->html($args);
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
		
		$venue = get_post_meta($event->ID,'venue',true);
		$city = get_post_meta($event->ID,'city',true);
		if ($venue!='') {
			$html.= '<span itemprop="name">'.$venue.'</span>';
		}
		if ($venue!='' && $city!='') {
			$html.= ', ';
		}
		if ($city!='') {
			$html.= '<span itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">';
			$html.= '<span itemprop="locality">'.$city.'</span>';
			$html.= '</span>';
		}

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

		$summary = $event->summary();
		if ($summary['prices']!='') {
			$html.= '<div class="prices">'.$summary['prices'].'</div>';
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

	function manage_wp_theatre_prod_posts_columns($columns) {
		$new_columns = array();
		foreach($columns as $key => $value) {
			$new_columns[$key] = $value;
			if ($key == 'title') {
				$new_columns['dates'] = __('Dates','wp_theatre');
				$new_columns['cities'] = __('Cities','wp_theatre');
			}
		}

		return $new_columns;
	}
	
	function manage_wp_theatre_event_posts_columns($columns, $post_type) {
		$new_columns = array();
		foreach($columns as $key => $value) {
			if (!in_array($key,array('title'))) {
				$new_columns[$key] = $value;				
			}
			$new_columns['event'] = __('Event','wp_theatre');
		}
		return $new_columns;		
	}
	
	function manage_wp_theatre_prod_posts_custom_column($column_name, $post_id) {
		$production = new WPT_Production($post_id);
		switch($column_name) {
			case 'dates':
				echo $production->dates();
				break;
			case 'cities':
				echo $production->cities();
				break;
		}
		
	}
	
	function manage_wp_theatre_event_posts_custom_column($column_name, $post_id) {
		$event = new WPT_Event($post_id);
		switch($column_name) {
			case 'event':
				echo $this->render_event($event);
				break;
			case 'production':
				echo $this->render_production($event->production());
				break;
		}
		
	}

	function quick_edit_custom_box($column_name, $post_type) {
	    static $printNonce = TRUE;
	    if ( $printNonce ) {
	        $printNonce = FALSE;
			wp_nonce_field($post_type, $post_type.'_nonce' );
	    }		
	}

	function bulk_edit_custom_box($column_name, $post_type) {
		wp_nonce_field($post_type, $post_type.'_nonce' );
	}
	
	function wpt_event($html,$event) {
		$html.= '<div class="row-actions">';
		$html.= '<span><a href="'.get_edit_post_link($event->production->ID).'">'.__('Edit').'</a></span>';;
		$html.= '<span> | <a href="'.get_delete_post_link($event->production->ID).'">'.__('Trash').'</a></span>';;
		$html.= '</div>'; //.row-actions
		return $html;
	}

	/**
	 * Admin setting.
	 */

	public function admin_page() {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
       		<h2><?php echo __('Theatre','wp_theatre').' '.__('Settings');?></h2>
            <h2 class="nav-tab-wrapper">
            <?php foreach ($this->tabs as $key=>$val) { ?>
            	<a class="nav-tab <?php echo $key==$this->tab?'nav-tab-active':'';?>" href="?page=wpt_admin&tab=<?php echo $key;?>">
            		<?php echo $val;?>
            	</a>
            <?php } ?>
            </h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( $this->tab );   
                do_settings_sections( $this->tab );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }
    
    public function settings_field_show_events() {
		$options = array(
			'above' => __('show above content','wp_theatre'),
			'below' => __('show below content','wp_theatre'),
			'not' => __('don\'t show (use <code>[wpt_production_events]</code> shortcode instead)','wp_theatre')
		);
		
		foreach($options as $key=>$value) {
			echo '<label>';
			echo '<input type="radio" name="wp_theatre[show_events]" value="'.$key.'"';
			if (!empty($this->options['show_events']) && $key==$this->options['show_events']) {
				echo ' checked="checked"';
			}
			echo '>'.$value.'</option>';
			echo '</label>';
			echo '<br />';
		}
    }

    public function settings_field_css() {
		echo '<p>';
		echo '<textarea id="wpt_custom_css" name="wp_theatre[custom_css]">';
		if (!empty($this->options['custom_css'])) {
			echo $this->options['custom_css'];
		}
		echo '</textarea>';
		echo '</p>';
    }

    public function settings_field_stylesheet() {
		echo '<label>';
		echo '<input type="checkbox" name="wp_theatre[stylesheet]"';
		if (!empty($this->options['stylesheet'])) {
			echo ' checked="checked"';
		}
		echo '>'.__('Enable built-in Theatre stylesheet','wp_theatre').'</option>';
		echo '</label>';
    }

	function settings_field_language_tickets() {
		global $wp_theatre;
		echo '<input type="text" id="language_tickets" name="wpt_language[language_tickets]" value="'.$wp_theatre->wpt_language_options['language_tickets'].'" />';
		echo '<p class="description">'.__('Displayed on ticket buttons.','wp_theatre').'</p>';
		echo '<p class="description">'.__('Can be overruled on a per-event basis.','wp_theatre').'</p>';

	}

	function settings_field_language_events() {
		global $wp_theatre;
		echo '<input type="text" id="language_events" name="wpt_language[language_events]" value="'.$wp_theatre->wpt_language_options['language_events'].'" />';
		echo '<p class="description">'.__('Displayed above event listings.','wp_theatre').'</p>';

	}

	function settings_field_language_categories() {
		global $wp_theatre;
		echo '<input type="text" id="language_categories" name="wpt_language[language_categories]" value="'.$wp_theatre->wpt_language_options['language_categories'].'" />';
		echo '<p class="description">'.__('Displayed in category listings.','wp_theatre').'</p>';

	}

	function settings_field_integrationtype() {
		$options = array(
			'self' => __('same window','wp_theatre'),
			'iframe' => __('iframe','wp_theatre'),
			'_blank' => __('new window','wp_theatre'),
			'lightbox' => __('lightbox','wp_theatre')
		);
		
		foreach($options as $key=>$value) {
			echo '<label>';
			echo '<input type="radio" name="wp_theatre[integrationtype]" value="'.$key.'"';
			if ($key==$this->options['integrationtype']) {
				echo ' checked="checked"';
			}
			echo '>'.$value.'</option>';
			echo '</label>';
			echo '<br />';
		}
		
	}

	function settings_field_iframepage() {
		$pages = get_pages();

		echo '<select id="iframepage" name="wp_theatre[iframepage]">';
		echo '<option></option>';
		foreach($pages as $page) {
			echo '<option value="'.$page->ID.'"';
			if ($page->ID==$this->options['iframepage']) {
				echo ' selected="selected"';
			}
			echo '>'.$page->post_title.'</option>';
		}
		echo '</select>';
		echo '<p class="description">'.__('Select the page that embeds all Ticketmatic iframes.','wp_theatre').'</p>';
		echo '<p class="description">'.__('The page must contain the following shortcode:','wp_theatre');
		echo '<pre>[wp_theatre_iframe]</pre>';
		echo '</p>';
	}

	function settings_field_currencysymbol() {
		echo '<input type="text" id="currencysymbol" name="wp_theatre[currencysymbol]"';
		if (!empty($this->options['currencysymbol'])) {
			echo ' value="'.$this->options['currencysymbol'].'"';
			
		}
		echo ' />';

	}

	function settings_field_social_meta_tags() {
		global $wp_theatre;
		
		$options = array(
			'facebook' => __('Facebook (Open Graph)','wp_theatre'),
			'twitter' => __('Twitter (Twitter Card)','wp_theatre'),
			'google+' => __('Google+ (Schema.org)','wp_theatre'),
		
		);
		foreach ($options as $key=>$value) {
			echo '<label>';
			echo '<input type="checkbox" id="'.$key.'" name="wpt_social[social_meta_tags][]" value="'.$key.'"';
			if (is_array($wp_theatre->wpt_social_options['social_meta_tags']) && in_array($key, $wp_theatre->wpt_social_options['social_meta_tags'])) {
				echo ' checked="checked"';
			}
			
			echo '/>';
			echo $value;
			echo '</label>';
			echo '<br />';
			
		}
		
		echo '<p class="description">'.__('Make your productions shine on social networks.','wp_theatre').'</p>';
		echo '<p class="description">'.__('Leave unchecked if this causes conflicts with SEO plugins.','wp_theatre').'</p>';
		
	}
    	
}



?>