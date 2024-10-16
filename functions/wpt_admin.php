<?php
class WPT_Admin {
	
	public $options;
	public $tabs;
	public $tab;
	
	function __construct() {
		// Hooks (only in admin screens)
		add_action( 'admin_init', array($this,'admin_init'));
		
		/*
		 * Add Theater menu with a priority of 5 to make it possible to add submenu items
		 * before register_post_type() does. This way the productions admin page (6) can
		 * appears above the seasons menu.
		 * @since 0.15
		 */
		add_action( 'admin_menu', array($this, 'add_theater_menu' ), 5);		
		add_action( 'admin_menu', array($this, 'add_settings_menu' ), 30);
		
		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action( 'quick_edit_custom_box', array($this,'quick_edit_custom_box'), 10, 2 );

		add_action( 'save_post', array( $this, 'save_production' ));

		// More hooks (always load, necessary for bulk editing through AJAX)
		add_filter('request', array($this,'request'));

		// Options
		$this->options = get_option( 'wp_theatre' );

	}

	/**
	 * admin_init function.
	 * 
	 * @since	0.?
	 * @since	0.15.16	Removed custom CSS section.
	 * @since	0.16.3	Added flatpickr.
	 * @since	0.18.4	Properly escape label of 'Tickets' field on 'Language' tab to prevent XSS exploits.
	 *					The label is dynamically using the value of the same field.
	 *					@see WPT_Setup::gettext().
	 * @return 	void
	 */
	function admin_init() {
        global $wp_theatre;

		wp_register_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), NULL );
		wp_register_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), NULL );	

		wp_register_script( 'flatpickr_l10n', sprintf( 'https://npmcdn.com/flatpickr/dist/l10n/%s.js', $wp_theatre->event_editor->get_language() ), array(), NULL );	

		wp_enqueue_script(
			'wp_theatre_admin',
			plugins_url( '../js/admin.js', __FILE__ ),
			array(
				'jquery',
				'flatpickr',
				'flatpickr_l10n',
			),
            $wp_theatre->wpt_version
		);

		wp_enqueue_style( 'wp_theatre_admin', plugins_url( '../css/admin.css', __FILE__ ), array( 'flatpickr' ), $wp_theatre->wpt_version );
		wp_enqueue_style( 'wp_theatre', plugins_url( '../css/style.css', __FILE__ ), array(), $wp_theatre->wpt_version );


		$this->tabs = array(
			'wpt_style'=>__('Style','theatre'),
			'wpt_tickets'=>__('Tickets','theatre'),
			'wpt_language'=>__('Language','theatre'),
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
	            esc_html( __('Tickets','theatre') ), // Title
	            array( $this, 'settings_field_language_tickets' ), // Callback
	            'wpt_language', // Page
	            'language' // Section
	        );

	        add_settings_field(
	            'language_events', // ID
	            __('Events','theatre'), // Title
	            array( $this, 'settings_field_language_events' ), // Callback
	            'wpt_language', // Page
	            'language' // Section
	        );

	        add_settings_field(
	            'language_categories', // ID
	            __('Categories','theatre'), // Title
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
	            __('Stylesheet','theatre'), // Title
	            array( $this, 'settings_field_stylesheet' ), // Callback
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
	            __('Currency symbol','theatre'), // Title
	            array( $this, 'settings_field_currencysymbol' ), // Callback
	            'wpt_tickets', // Page
	            'tickets_integration' // Section
	        );

	        add_settings_field(
	            'integrationtype', // ID
	            __('Open tickets screens in','theatre'), // Title
	            array( $this, 'settings_field_integrationtype' ), // Callback
	            'wpt_tickets', // Page
	            'tickets_integration' // Section
	        );

	        add_settings_field(
	            'iframepage', // ID
	            __('Iframe page','theatre'), // Title
	            array( $this, 'settings_field_iframepage' ), // Callback
	            'wpt_tickets', // Page
	            'tickets_integration' // Section
	        );
		}
	}

	/**
	 * Adds a 'Theater' admin menu.
	 * 
	 * @since	0.?
	 * @since	0.15	No longer adds the 'Settings' submenu.
	 *					@see WPT_Admin::add_settings_menu().
	 */
	function add_theater_menu() {
		add_menu_page( 
			__('Theater','theatre'), 
			__('Theater','theatre'), 
			'edit_posts', 
			'theater-events', 
			array(), 
			'none', 
			30
		);	
	}

	/**
	 * Adds a 'Settings' submenu to the 'Theater' menu.
	 * 
	 * @since	0.15
	 * @see		WPT_Admin::add_theater_menu().
	 */
	function add_settings_menu() {
		add_submenu_page( 
			'theater-events',
			__('Theater','theatre').' '.__('Settings'), 
			__('Settings'), 
			'manage_options', 
			'wpt_admin', 
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Adds Theater metaboxes to the admin pages of productions.
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

		// Add a 'Seasons' metabox to the production admin screen.
		add_meta_box(
            'wp_theatre_seasons',
            __( 'Seasons','theatre'),
            array($this,'meta_box_seasons'),
            WPT_Production::post_type_name,
            'side'
        );

		// Add a 'Display' metabox to the production admin screen.
		add_meta_box(
            'wp_theatre_display',
            __('Display','theatre'),
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
		echo __('Stick this production to all listings.','theatre');

		echo '</label>';

		/**
		 * Fires after the contents of the display settings meta box are echoed.
		 *
		 * @since 0.9.2
		 */
		do_action('wpt_admin_meta_box_display', $production, $metabox);
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

	/**
	 * Save meta data for a production and its events.
	 * Triggered by de 'save_post'-action when you save a production in the admin.
	 *
	 * @since 	?.?
	 * @since 	0.11.3	Unhook WPT_Event_Editor::save_event() to avoid loops.
	 *					See: https://github.com/slimndap/wp-theatre/issues/125
	 * @since 	0.12	Added support for events with an 'auto-draft' post_status.
	 * @since	0.14.3	Productions with multiple events were not saving properly.
	 *					Fixes #187.
	 *					@props tomaszkoziara
	 *
	 * @param 	int		$post_id
	 * @return 	void
	 */
	function save_production( $post_id ) {
		global $wp_theatre;

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
		if (isset($_POST[WPT_Season::post_type_name])) {
			$season = sanitize_text_field( $_POST[WPT_Season::post_type_name] );
			update_post_meta( $post_id, WPT_Season::post_type_name, $season );
		}

		/**
		 * Fires after a production is saved through the admin screen.
		 *
		 * @since 0.9.2
		 */
		do_action('wpt_admin_after_save_'.WPT_Production::post_type_name, $post_id);
	}

	/**
	 * Alters the columns of the production admin screen.
	 *
	 * Adds 'thumbnail' and 'dates' columns.
	 * Removes the 'date' column.
	 *
	 * @since 	0.?
	 * @since	0.12.5	Moved the thumbnail column behind the title column to
	 *					better support the new responsive columns of WordPress 4.3.
	 * 					See: https://core.trac.wordpress.org/ticket/33308
	 *
	 * @param 	array	$columns	The current columns.
	 * @return 	array				The altered columns.
	 */
	function manage_wp_theatre_prod_posts_columns($columns) {
		$new_columns = array();
		foreach($columns as $key => $value) {
			switch ($key) {
				case 'date' :
					break;
				case 'title' :
					$new_columns[$key] = $value;
					$new_columns['thumbnail'] = __('Image','theatre');
					$new_columns['dates'] = __('Dates','theatre');
					break;
				default :
					$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	/**
	 * Outputs the HTML for the thumbnails and dates columns in the productions list table.
	 * 
	 * @since	0.?
	 * @param 	string	$column_name	The name of the column.
	 * @param 	string	$post_id		The ID of the current production.
	 */
	function manage_wp_theatre_prod_posts_custom_column($column_name, $post_id) {
		$production = new WPT_Production($post_id);
		switch($column_name) {
			case 'thumbnail':
				echo $production->thumbnail_html();
				break;
			case 'dates':
				echo $production->dates_html().'<br />';
				echo $production->cities();
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

	/**
	 * Admin setting.
	 */

	public function admin_page() {
        ?>
        <div class="wrap">
       		<h1><?php echo __('Theater','theatre').' '.__('Settings');?></h1>
            <h2 class="nav-tab-wrapper">
            <?php foreach ($this->tabs as $key=>$val) { ?>
            	<a class="nav-tab <?php echo $key==$this->tab?'nav-tab-active':'';?>" href="?page=wpt_admin&tab=<?php echo $key;?>">
            		<?php echo esc_html( $val );?>
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

    public function settings_field_stylesheet() {
    	global $wp_theatre;

		echo '<label>';
		echo '<input type="checkbox" name="wpt_style[stylesheet]"';
		if (!empty($wp_theatre->wpt_style_options['stylesheet'])) {
			echo ' checked="checked"';
		}
		echo '>'.__('Enable built-in Theatre stylesheet','theatre').'</option>';
		echo '</label>';
    }

	/**
	 * Outputs the tickets translation settings field.
	 *
	 * @since 	0.?
	 * @since	0.18.4	Properly escape the value attribute to prevent XSS exploits.
	 */
	function settings_field_language_tickets() {
		global $wp_theatre;
		echo '<input type="text" id="language_tickets" name="wpt_language[language_tickets]" value="'.esc_attr( $wp_theatre->wpt_language_options['language_tickets'] ).'" />';
		echo '<p class="description">'.__('Displayed on ticket buttons.','theatre').'</p>';
		echo '<p class="description">'.__('Can be overruled on a per-event basis.','theatre').'</p>';

	}

	/**
	 * Outputs the events translation settings field.
	 *
	 * @since 	0.?
	 * @since	0.18.4	Properly escape the value attribute to prevent XSS exploits.
	 */
	function settings_field_language_events() {
		global $wp_theatre;
		echo '<input type="text" id="language_events" name="wpt_language[language_events]" value="'.esc_attr( $wp_theatre->wpt_language_options['language_events'] ).'" />';
		echo '<p class="description">'.__('Displayed above event listings.','theatre').'</p>';

	}

	/**
	 * Outputs the categories translation settings field.
	 *
	 * @since 	0.?
	 * @since	0.18.4	Properly escape the value attribute to prevent XSS exploits.
	 */
	function settings_field_language_categories() {
		global $wp_theatre;
		echo '<input type="text" id="language_categories" name="wpt_language[language_categories]" value="'.esc_attr( $wp_theatre->wpt_language_options['language_categories'] ).'" />';
		echo '<p class="description">'.__('Displayed in category listings.','theatre').'</p>';

	}

	function settings_field_integrationtype() {
		global $wp_theatre;

		$options = array(
			'self' => __('same window','theatre'),
			'iframe' => __('iframe','theatre'),
			'_blank' => __('new window','theatre'),
			'lightbox' => __('lightbox','theatre')
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
		echo '<p class="description">'.__('Select the page that embeds all Ticketing iframes.','theatre').'</p>';
		echo '<p class="description">'.__('The page must contain the following shortcode:','theatre');
		echo '<pre>[wp_theatre_iframe]</pre>';
		echo '</p>';
	}

	/**
	 * Outputs the currency symbol settings field.
	 *
	 * @since 0.?
	 * @since	0.18.4	Properly escape the value attribute to prevent XSS exploits.
	 */
	function settings_field_currencysymbol() {
		global $wp_theatre;
		echo '<input type="text" id="currencysymbol" name="wpt_tickets[currencysymbol]"';
		if (!empty($wp_theatre->wpt_tickets_options['currencysymbol'])) {
			echo ' value="'.esc_attr( $wp_theatre->wpt_tickets_options['currencysymbol'] ).'"';

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

}


?>
