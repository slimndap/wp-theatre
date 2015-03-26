<?php
class WPT_Admin {
	function __construct() {
		// Hooks (only in admin screens)
		if (is_admin()) {
			add_action( 'admin_init', array($this,'admin_init'));
			add_action( 'admin_menu', array($this, 'admin_menu' ));
			add_action( 'add_meta_boxes', array($this, 'add_meta_boxes'));
			add_filter( 'wpt_event', array($this,'wpt_event'), 10 ,2);
			add_action( 'quick_edit_custom_box', array($this,'quick_edit_custom_box'), 10, 2 );
			add_action( 'wp_dashboard_setup', array($this,'wp_dashboard_setup' ));

			add_action( 'save_post', array( $this, 'save_production' ));
			add_action( 'save_post', array( $this, 'save_event' ));

			add_filter('manage_wp_theatre_prod_posts_columns', array($this,'manage_wp_theatre_prod_posts_columns'), 10, 2);
			add_filter('manage_wp_theatre_event_posts_columns', array($this,'manage_wp_theatre_event_posts_columns'), 10, 2);
			add_action('manage_wp_theatre_prod_posts_custom_column', array($this,'manage_wp_theatre_prod_posts_custom_column'), 10, 2);
			add_action('manage_wp_theatre_event_posts_custom_column', array($this,'manage_wp_theatre_event_posts_custom_column'), 10, 2);	
			add_filter('manage_edit-wp_theatre_prod_sortable_columns', array($this,'manage_edit_wp_theatre_prod_sortable_columns') );
			
			add_filter('wpt_event_html',array($this,'wpt_event_html'), 10 , 2);
			add_filter('wpt_production_html',array($this,'wpt_production_html'), 10 , 2);
			
			add_filter('views_edit-'.WPT_Production::post_type_name,array($this,'views_productions'));
		}
		
		// More hooks (always load, necessary for bulk editing through AJAX)
		add_filter('request', array($this,'request'));
		add_action( 'bulk_edit_custom_box', array($this,'bulk_edit_custom_box'), 10, 2 );

		// Options
		$this->options = get_option( 'wp_theatre' );
		
	}	

	function admin_init() {
		wp_enqueue_script( 'wp_theatre_admin', plugins_url( '../js/admin.js', __FILE__ ), array('jquery') );
		wp_enqueue_style( 'wp_theatre_admin', plugins_url( '../css/admin.css', __FILE__ ) );
		wp_enqueue_script( 'jquery-ui-timepicker', plugins_url( '../js/jquery-ui-timepicker-addon.js', __FILE__ ), array('jquery-ui-datepicker','jquery-ui-slider')  );
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		wp_enqueue_style( 'wp_theatre', plugins_url( '../css/style.css', __FILE__ ) );


		$this->tabs = array(
			'wpt_style'=>__('Style','wp_theatre'),
			'wpt_tickets'=>__('Tickets','wp_theatre'),
			'wpt_language'=>__('Language','wp_theatre')
		);	
		$this->tabs = apply_filters('wpt_admin_page_tabs',$this->tabs);
	
		// Tabs on settings screens
		if (!empty($_GET['tab'])) {
			$this->tab = $_GET['tab'];
		} else {
			$tab_keys = array_keys($this->tabs);
			$this->tab = $tab_keys[0];
		}

        register_setting(
            'wpt_style', // Option group
            'wpt_style' // Option name
        );
        
        register_setting(
            'wpt_tickets', // Option group
            'wpt_tickets' // Option name
        );
        
        register_setting(
            'wpt_language', // Option group
            'wpt_language' // Option name
        );
        
		if ($this->tab=='wpt_language') {
 	        add_settings_section(
	            'language', // ID
	            '', // Title
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

		if ($this->tab=='wpt_style') {
	        add_settings_section(
	            'display_section_id', // ID
	            '', // Title
	            '', // Callback
	            'wpt_style' // Page
	        );  
	
	        add_settings_field(
	            'stylesheet', // ID
	            __('Stylesheet','wp_theatre'), // Title 
	            array( $this, 'settings_field_stylesheet' ), // Callback
	            'wpt_style', // Page
	            'display_section_id' // Section           
	        );

	        add_settings_field(
	            'css', // ID
	            __('Custom CSS','wp_theatre'), // Title 
	            array( $this, 'settings_field_css' ), // Callback
	            'wpt_style', // Page
	            'display_section_id' // Section           
	        );
		}
		
		if ($this->tab=='wpt_tickets') {
	        add_settings_section(
	            'tickets_integration', // ID
	            '', // Title
	            '', // Callback
	            'wpt_tickets' // Page
	        );  

	        add_settings_field(
	            'currenysymbol', // ID
	            __('Currency symbol','wp_theatre'), // Title 
	            array( $this, 'settings_field_currencysymbol' ), // Callback
	            'wpt_tickets', // Page
	            'tickets_integration' // Section           
	        );      
	
	        add_settings_field(
	            'integrationtype', // ID
	            __('Open tickets screens in','wp_theatre'), // Title 
	            array( $this, 'settings_field_integrationtype' ), // Callback
	            'wpt_tickets', // Page
	            'tickets_integration' // Section           
	        );      
	        
	        add_settings_field(
	            'iframepage', // ID
	            __('Iframe page','wp_theatre'), // Title 
	            array( $this, 'settings_field_iframepage' ), // Callback
	            'wpt_tickets', // Page
	            'tickets_integration' // Section           
	        );      
		}
	}

	function admin_menu() {
		add_menu_page( __('Theater','wp_theatre'), __('Theater','wp_theatre'), 'edit_posts', 'theatre', array(), 'none', 30);
		add_submenu_page( 'theatre',__('Theater','wp_theatre').' '.__('Settings'), __('Settings'), 'manage_options', 'wpt_admin', array( $this, 'admin_page' ));
	}
	
	/**
	 * Adds Theater metaboxes to the admin pages of productions, events and seasons.
	 * 
	 * @since 0.1
	 * @since 0.10 	Removed all static calls to public methods.
	 *				Fixes #77: https://github.com/slimndap/wp-theatre/issues/77
	 * @access public
	 * @return void
	 */
	function add_meta_boxes() {
		
		// Add an 'Events' metabox to the production admin screen.
		add_meta_box(
            'wp_theatre_events',
            __( 'Events','wp_theatre'),
            array($this,'meta_box_events'),
            WPT_Production::post_type_name,
            'side',
            'core'
        ); 		

		// Add an 'Event data' metabox to the event admin screen.
		add_meta_box(
            'wp_theatre_event_data',
            __('Event data','wp_theatre'),
            array($this,'meta_box_event_data'),
            WPT_Event::post_type_name,
            'normal'
        ); 		

		// Add a 'Tickets' metabox to the event admin screen.
		add_meta_box(
            'wp_theatre_tickets',
            __('Tickets','wp_theatre'),
            array($this,'meta_box_tickets'),
            WPT_Event::post_type_name,
            'normal'
        ); 	

		// Add a 'Seasons' metabox to the production admin screen.
		add_meta_box(
            'wp_theatre_seasons',
            __( 'Seasons','wp_theatre'),
            array($this,'meta_box_seasons'),
            WPT_Production::post_type_name,
            'side'
        ); 	
        
		// Add a 'Display' metabox to the production admin screen.
		add_meta_box(
            'wp_theatre_display',
            __('Display','wp_theatre'),
            array($this,'meta_box_display'),
            WPT_Production::post_type_name,
            'side'
        ); 	
	}
	
	/**
	 * Show a meta box with display settings for a production.
	 * http://codex.wordpress.org/Function_Reference/add_meta_box
	 * 
	 * @access public
	 * @since 0.9.2
	 * @param WP_Post $production
	 * @param mixed $metabox
	 * @see WPT_Admin::add_meta_boxes()
	 * @return void
	 */
	function meta_box_display($production, $metabox) {
		echo '<label>';
		
		echo '<input type="checkbox" name="sticky"';
		if (is_sticky()) {
			echo ' checked="checked"';
		}
		echo ' />';
		echo __('Stick this production to all listings.','wp_theatre');
		
		echo '</label>';
		
		/**
		 * Fires after the contents of the display settings meta box are echoed.
		 *
		 * @since 0.9.2
		 */
		do_action('wpt_admin_meta_box_display', $production, $metabox);
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
		
		
		if (get_post_status($production->ID) == 'auto-draft') {
			echo __('You need to save this production before you can add events.','wp_theatre');
		} else {
			$args = array(
				'production' => $production->ID,
				'status' => array('publish','draft')
			);
		
			$events = $wp_theatre->events->get($args);
			if (count($events)>0) {
				echo '<ul>';
				foreach ($events as $event) {
					echo '<li>';
					echo $this->render_event($event);
					echo '</li>';
					
				}
				echo '</ul>';	
			}	
			$create_event_url = admin_url(
				'post-new.php?post_type='.WPT_Event::post_type_name.'&'.
				WPT_Production::post_type_name.'='.$production->ID
			);
			echo '<p><a href="'.$create_event_url.'" class="button button-primary">'.__('New event','wp_theatre').'</a></p>';	
		}		
	}

	function meta_box_event_data($event) {
		wp_nonce_field(WPT_Event::post_type_name, WPT_Event::post_type_name.'_nonce' );

		echo '<table class="form-table">';
		echo '<tbody>';

		echo '<tr class="form-field">';		
		echo '<th>';
		echo '<label>';
		_e('Production','wp_theatre');
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
			echo $production->html();
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
				
		$current_status = get_post_meta($event->ID,'tickets_status',true);
		if (empty($current_status)) {
			$current_status=WPT_Event::tickets_status_onsale;
		}
		$statusses = array(
			WPT_Event::tickets_status_onsale => __('On sale','wp_theatre'),
			WPT_Event::tickets_status_soldout => __('Sold Out','wp_theatre'),
			WPT_Event::tickets_status_cancelled => __('Cancelled','wp_theatre'),
			WPT_Event::tickets_status_hidden => __('Hidden','wp_theatre'),	
		);
		foreach ($statusses as $status=>$name) {
			echo '<label>';
			echo '<input type="radio" name="tickets_status" value="'.$status.'"';
			if ($current_status==$status) {
				echo ' checked="checked"';
			}
			echo '>';
			echo '<span>'.$name.'</span>';
			echo '</label><br />	';
		}
				
		echo '<label>';
		
		$checked = '';
		$value = '';
		if (!in_array($current_status, array_keys($statusses))) {
			$checked = ' checked="checked"';
			$value = $current_status;
		}
		echo '<input type="radio" name="tickets_status" value="'.WPT_Event::tickets_status_other.'" '.$checked.' />';
		echo '<span>'.__('Other','wp_theatre').': </span>';
		echo '</label><input type="text" name="tickets_status_other" value="'.$value.'" /><br />';
		
 		echo '</td>';
		echo '</tr>';
		
  		echo '<tr>';
		echo '<th><label>'.__('Text on button','wp_theatre').'</label></th>';	
		echo '<td>';
						
		echo '<input type="text" name="tickets_button"';
        echo ' value="' . get_post_meta($event->ID,'tickets_button',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
       
       
  		// Prices
  		$label = __('Prices','wp_theatre');
  		if (!empty($this->options['currencysymbol'])) {
	  		$label.= ' ('.$this->options['currencysymbol'].')';
  		}
  		echo '<tr>';
		echo '<th><label>'.$label.'</label></th>';	
		echo '<td>';
						
		echo '<input type="text" name="_wpt_event_tickets_prices"';
        echo ' value="' . implode(', ',get_post_meta($event->ID,'_wpt_event_tickets_price')) . '" />';
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
		
		// Update the meta field.
		update_post_meta( $post_id, WPT_Production::post_type_name, $production );
		update_post_meta( $post_id, 'event_date', date('Y-m-d H:i:s',$event_date) );
		update_post_meta( $post_id, 'enddate', date('Y-m-d H:i:s',$enddate) );
		update_post_meta( $post_id, 'venue', $venue );
		update_post_meta( $post_id, 'city', $city );
		update_post_meta( $post_id, 'remark', $remark );
		update_post_meta( $post_id, 'tickets_url', $tickets_url );
		update_post_meta( $post_id, 'tickets_button', $tickets_button );
		
		// Prices
		delete_post_meta($post_id, '_wpt_event_tickets_price');

		$prices = explode(',',$_POST['_wpt_event_tickets_prices']);
		for ($p=0;$p<count($prices);$p++) {
			$price = (float) $prices[$p];
			if ($price>0) {
				add_post_meta($post_id,'_wpt_event_tickets_price', (float) $prices[$p]);			
			}
		}
		
		// Tickets status
		$tickets_status = $_POST['tickets_status'];
		if ($tickets_status==WPT_Event::tickets_status_other) {
			$tickets_status = sanitize_text_field($_POST['tickets_status_other']);
		}
		update_post_meta( $post_id, 'tickets_status', $tickets_status );
		
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
		
		// Update the meta field.
		update_post_meta( $post_id, WPT_Season::post_type_name, $season );
		
		/*
		 *	 Update connected Events
		 */
		
		// unhook to avoid loops
		remove_action( 'save_post_'.WPT_Event::post_type_name, array( $this, 'save_event' ) );

		$events = $this->get_events($post_id);
		foreach($events as $event) {
			$post = array(
				'ID'=>$event->ID,
				'post_status'=>get_post_status($post_id)
			);
			wp_update_post($post);
		}

		// rehook
		add_action( 'save_post_'.WPT_Event::post_type_name, array( $this, 'save_event' ) );

		/**
		 * Fires after a production is saved through the admin screen.
		 *
		 * @since 0.9.2
		 */
		do_action('wpt_admin_after_save_'.WPT_Production::post_type_name, $post_id);
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
		$html.= '<div class="title">'.$event->title().'</div>';
		
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
			switch ($key) {
				case 'date' :
					break;
				case 'title' :
					$new_columns['thumbnail'] = __('Thumbnail','wp_theatre');
					$new_columns[$key] = $value;			
					$new_columns['dates'] = __('Dates','wp_theatre');
					break;
				default :
					$new_columns[$key] = $value;								
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
			case 'thumbnail':
				echo $production->thumbnail(array('html'=>true));
				break;
			case 'dates':
				echo $production->dates().'<br />';
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

    function manage_edit_wp_theatre_prod_sortable_columns($columns) {
		$columns['dates'] = 'dates';
		return $columns;
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
	
	/**
	 * Admin setting.
	 */

	public function admin_page() {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
       		<h2><?php echo __('Theater','wp_theatre').' '.__('Settings');?></h2>
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
    
    public function settings_field_css() {
    	global $wp_theatre;
    	
		echo '<p>';
		echo '<textarea id="wpt_custom_css" name="wpt_style[custom_css]">';
		if (!empty($wp_theatre->wpt_style_options['custom_css'])) {
			echo $wp_theatre->wpt_style_options['custom_css'];
		}
		echo '</textarea>';
		echo '</p>';
    }

    public function settings_field_stylesheet() {
    	global $wp_theatre;

		echo '<label>';
		echo '<input type="checkbox" name="wpt_style[stylesheet]"';
		if (!empty($wp_theatre->wpt_style_options['stylesheet'])) {
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
		global $wp_theatre;

		$options = array(
			'self' => __('same window','wp_theatre'),
			'iframe' => __('iframe','wp_theatre'),
			'_blank' => __('new window','wp_theatre'),
			'lightbox' => __('lightbox','wp_theatre')
		);
		
		foreach($options as $key=>$value) {
			echo '<label>';
			echo '<input type="radio" name="wpt_tickets[integrationtype]" value="'.$key.'"';
			if ($key==$wp_theatre->wpt_tickets_options['integrationtype']) {
				echo ' checked="checked"';
			}
			echo '>'.$value.'</option>';
			echo '</label>';
			echo '<br />';
		}
		
	}

	function settings_field_iframepage() {
		global $wp_theatre;

		$pages = get_pages();

		echo '<select id="iframepage" name="wpt_tickets[iframepage]">';
		echo '<option></option>';
		foreach($pages as $page) {
			echo '<option value="'.$page->ID.'"';
			if ($page->ID==$wp_theatre->wpt_tickets_options['iframepage']) {
				echo ' selected="selected"';
			}
			echo '>'.$page->post_title.'</option>';
		}
		echo '</select>';
		echo '<p class="description">'.__('Select the page that embeds all Ticketing iframes.','wp_theatre').'</p>';
		echo '<p class="description">'.__('The page must contain the following shortcode:','wp_theatre');
		echo '<pre>[wp_theatre_iframe]</pre>';
		echo '</p>';
	}

	function settings_field_currencysymbol() {
		global $wp_theatre;
		echo '<input type="text" id="currencysymbol" name="wpt_tickets[currencysymbol]"';
		if (!empty($wp_theatre->wpt_tickets_options['currencysymbol'])) {
			echo ' value="'.$wp_theatre->wpt_tickets_options['currencysymbol'].'"';
			
		}
		echo ' />';

	}

	function request($vars) {
		global $wp_theatre;
		if ( isset( $vars['orderby'] ) && 'dates' == $vars['orderby'] ) {
		    $vars = array_merge( $vars, array(
		        'meta_key' => $wp_theatre->order->meta_key,
		        'orderby' => 'meta_value_num'
		    ) );
		}
		if (!empty($_GET['upcoming'])) {
			$vars['meta_query'] = array(
				array(
					'key' => $wp_theatre->order->meta_key,
					'value' => time(),
					'compare' => '>=',
					'type' => 'numeric'
					
				)
			);
		}
		return $vars;		
	}

    function wpt_event_html($html, $event) {
		$html.= '<div class="row-actions">';
		$html.= '<span><a href="'.get_edit_post_link($event->production->ID).'">'.__('Edit').'</a></span>';;
		$html.= '<span> | <a href="'.get_delete_post_link($event->production->ID).'">'.__('Trash').'</a></span>';;
		$html.= '</div>'; //.row-actions
		return $html;
    }

    function wpt_production_html($html, $production) {
		$html.= '<div class="row-actions">';
		$html.= '<span><a href="'.get_edit_post_link($production->ID).'">'.__('Edit').'</a></span>';;
		$html.= '<span> | <a href="'.get_delete_post_link($production->ID).'">'.__('Trash').'</a></span>';;
		$html.= '</div>'; //.row-actions
		return $html;
    }
    
    function views_productions($views) {
    	$url = add_query_arg( 
    		array(
    	   		'upcoming' => 1,
    	   		'post_status' => false
		   	)
    	);
    	$class = empty($_GET['upcoming'])?'':'current';
    	//$views['upcoming'] = '<a href="'.$url.'" class="'.$class.'">'.__('Upcoming','wp_theatre').'</a>';
    	return $views;
    }
}


?>