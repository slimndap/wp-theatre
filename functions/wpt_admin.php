<?php
class WPT_Admin {
	function __construct() {
		// Hooks (only in admin screens)
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
		
		add_filter('views_edit-'.WPT_Production::post_type_name,array($this,'views_productions'));

		add_filter('wpt/event_editor/fields', array($this, 'add_production_to_event_editor'), 10, 2);
		
		// More hooks (always load, necessary for bulk editing through AJAX)
		add_filter('request', array($this,'request'));
		add_action( 'bulk_edit_custom_box', array($this,'bulk_edit_custom_box'), 10, 2 );

		// Options
		$this->options = get_option( 'wp_theatre' );
		
	}	

	function admin_init() {
        global $wp_theatre;
        
		wp_enqueue_script(
			'wp_theatre_admin', 
			plugins_url( '../js/admin.js', __FILE__ ), 
			array(
				'jquery'
			),
            $wp_theatre->wpt_version
		);
		wp_enqueue_style( 'wp_theatre_admin', plugins_url( '../css/admin.css', __FILE__ ), array(), $wp_theatre->wpt_version );
		wp_enqueue_style( 'wp_theatre', plugins_url( '../css/style.css', __FILE__ ), array(), $wp_theatre->wpt_version );


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
	 * @since 0.11	Replaced the 'Event data' and 'Tickets' forms with 
	 *				the new WPT_Event_Editor form.
	 * @access public
	 * @return void
	 */
	function add_meta_boxes() {
		
		add_meta_box(
			'wpt_event_editor', 
			__('Event dates','wp_theatre'), 
			array($this,'event_meta_box'), 
			WPT_Event::post_type_name, 
			'normal', 
			'high'
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
	 * Adds a production field to the event editor on the event admin page.
	 * 
	 * @since	0.11
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
					'title' => __('Production','wp_theatre'),
					'edit' => array(
						'callback' => array($this, 'get_control_production_html'),
					),
				)
			);				
		}
		
		return $fields;
		
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
	
	function event_meta_box($event) {
		global $wp_theatre;
		
		wp_nonce_field(WPT_Event::post_type_name, WPT_Event::post_type_name.'_nonce' );

		echo $wp_theatre->event_editor->get_form_html($production->ID, $event->ID);
		       
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
		update_post_meta( $post_id, WPT_Production::post_type_name, $_POST[WPT_Production::post_type_name] );
		
		foreach ($wp_theatre->event_editor->get_fields( $post_id ) as $field) {
			$wp_theatre->event_editor->save_field($field, $post_id);
		}
			
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
		remove_action( 'save_post', array( $this, 'save_production' ) );

		$post = get_post($post_id);
		$events = $this->get_events($post_id);

		foreach($events as $event) {
			
			// Keep trashed events in the trash.
			if ('trash' == get_post_status($event->ID)) {
				continue;
			}
			
			$post = array(
				'ID'=>$event->ID,
				'post_status'=>get_post_status($post_id),
				'edit_date'=>true,
				'post_date'=>$post->post_date,
				'post_date_gmt'=>get_gmt_from_date($post->post_date),
			);

			wp_update_post($post);

		}

		// rehook
		add_action( 'save_post', array( $this, 'save_production' ) );

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
	    if (is_admin()) {
			$html.= '<div class="row-actions">';
			$html.= '<span><a href="'.get_edit_post_link($event->production->ID).'">'.__('Edit').'</a></span>';;
			$html.= '<span> | <a href="'.get_delete_post_link($event->production->ID).'">'.__('Trash').'</a></span>';;
			$html.= '</div>'; //.row-actions		    
	    }
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