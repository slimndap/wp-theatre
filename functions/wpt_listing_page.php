<?php

/*
 * Manages events listings on the dedicated listing page and the production pages.
 * @since: 0.8 
 */

 	class WPT_Listing_Page {
	 	
		public $options;

		function __construct() {	
			if (is_admin()) {
				add_action('admin_init', array($this,'admin_init'));
				add_filter('wpt_admin_page_tabs', array($this,'wpt_admin_page_tabs'));
				add_filter('display_post_states', array($this, 'set_display_post_state'), 10 , 2);
			} else {
				add_action('the_content', array($this, 'the_content'));
				add_filter('wpt_production_page_content_before', array($this, 'wpt_production_page_content_before'));
				add_filter('wpt_production_page_content_after', array($this, 'wpt_production_page_content_after'));
				add_filter('wpt_listing_filter_pagination_url', array($this,'wpt_listing_filter_pagination_url'));	
			}

			add_action('init',array($this,'init'));
			
			add_action( "add_option_wpt_listing_page", array($this,'reset'));
			add_action( "update_option_wpt_listing_page", array($this,'reset'));

			add_action('init',array($this,'deprecated'));

			add_action( 'widgets_init', array($this,'widgets_init'));

			$this->options = get_option('wpt_listing_page');
			

			
	 	}
	 	
	 	function admin_init() {
	 		global $wp_theatre;

		 	/*
		 	 * Flush rewrite rules after option's values have been updated.
		 	 * @see sanitize_option_values()
		 	 */
		 	 
		 	if (delete_transient('wpt_listing_page_flush_rules')) {
		 		flush_rewrite_rules();
		 	}

		 	/*
		 	 * Create a new tab on the settings page.
		 	 */

	        register_setting(
	            'wpt_listing_page', // Option group
	            'wpt_listing_page', // The name of an option to sanitize and save.
	            array($this,'sanitize_option_values') // A callback function that sanitizes the option's value.
	        );
	        
	 		if ($wp_theatre->admin->tab=='wpt_listing_page') {    

		        add_settings_section(
		            'wpt_listing_page_page', // ID
		            __('Upcoming events','theatre'), // Title
		            '', // Callback
		            'wpt_listing_page' // Page
		        );  

		        add_settings_field(
		            'wpt_listing_page_post_id', // ID
		            __('Page to show upcoming events on','theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_post_id' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );

		        add_settings_field(
		            'wpt_listing_page_position', // ID
		            __('Position on page','theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_position' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_nav_events', // ID
		            __('Arrange the events','theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_nav_events' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_template', // ID
		            __('Template','theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_template' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );
		        
		        add_settings_section(
		            'wpt_listing_production_page', // ID
		            __('Dates on event pages','theatre'), // Title
		            '', // Callback
		            'wpt_listing_page' // Page
		        );  
		
		        add_settings_field(
		            'wpt_listing_page_position_on_production_page', // ID
		            __('Position on page','theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_position_on_production_page' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_production_page' // Section           
		        );

		        add_settings_field(
		            'wpt_listing_page_template_on_production_page', // ID
		            __('Template','theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_template_on_production_page' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_production_page' // Section           
		        );
		        
			}
		 	
	 	}
	 	

	 	/**
	 	 * init function.
	 	 * 
	 	 * @since	0.?
	 	 * @since	0.15.33		Added support for a listing page with a parent page.
	 	 *						Fixes #237.
	 	 * @since	0.16		Added support for tags.
	 	 * @return 	void
	 	 */
	 	function init() {
		 	
			/*
			 * Add custom querystring variables and rewrite rules.
			 * @todo: is this really necessary?
			 */

			add_rewrite_tag('%wpt_month%', '.*');
			add_rewrite_tag('%wpt_day%', '.*');
			add_rewrite_tag('%wpt_category%', '.*');

		 	/*
		 	 * Update the rewrite rules for the listings pages.
		 	 * {listing_page}/today 
		 	 * {listing_page}/tomorrow
		 	 * {listing_page}/yesterday
		 	 */

			if ( $path = parse_url( $this->url(), PHP_URL_PATH ) ) {
				
				$path = untrailingslashit( substr( $path, 1 ) );

				// <listing_page>/2014/05
				add_rewrite_rule(
					$path.'/([0-9]{4})/([0-9]{2})$', 
					'index.php?pagename='.$path.'&wpt_month=$matches[1]-$matches[2]',
					'top'
				);
				
				// <listing_page>/2014/05/06
				add_rewrite_rule(
					$path.'/([0-9]{4})/([0-9]{2})/([0-9]{2})$', 
					'index.php?pagename='.$path.'&wpt_day=$matches[1]-$matches[2]-$matches[3]',
					'top'
				);	 	 

				// <listing_page>/comedy
				add_rewrite_rule(
					$path.'/([a-z0-9-]+)$', 
					'index.php?pagename='.$path.'&wpt_category=$matches[1]',
					'top'
				);	 	 

				// <listing_page>/comedy/2014/05
				add_rewrite_rule(
					$path.'/([a-z0-9-]+)/([0-9]{4})/([0-9]{2})$', 
					'index.php?pagename='.$path.'&wpt_category=$matches[1]&wpt_month=$matches[2]-$matches[3]',
					'top'
				);

				// <listing_page>/comedy/2014/05/06
				add_rewrite_rule(
					$path.'/([a-z0-9-]+)/([0-9]{4})/([0-9]{2})/([0-9]{2})$', 
					'index.php?pagename='.$path.'&wpt_category=$matches[1]&wpt_day=$matches[2]-$matches[3]-$matches[4]',
					'top'
				);	 	 

				// <listing_page>/tag/circus
				add_rewrite_rule(
					$path.'/tag/([a-z0-9-]+)$', 
					'index.php?pagename='.$path.'&wpt_tag=$matches[1]',
					'top'
				);	 	 

			}
	 	}
	 	
	 	/*
	 	 * Generate a shortcode for upcoming events.
	 	 * @since 0.8
	 	 * 
	     * @param array $args {
	     *     An array of arguments. Optional.
	     *
	     *     @type string $listing_page_type    Show events as production or a seperate events? 
	     *                                        Accepts <WPT_Production::post_type_name>, <WPT_Eventn::post_type_name>.
	     *                                        Default <WPT_Production::post_type_name>.
	     *     @type string $listing_page_nav     Show a plain, grouped or paginated list?
	     *                                        Accepts <plain>, <grouped>, <paginated>.
	     *                                        Default <plain>.
	     *     @type string $listing_page_groupby Field to group/paginate the list by.
	     *                                        If set to <false> then $listing_page_nav is ignored.
	     *                                        Default <false>.
	     * }
	     * @return string Shortcode
	 	 * 
	 	 * 
	 	 */
	 	
	 	function shortcode($args=array()) {
	 		global $wp_theatre;
	 		global $wp_query;
	 	
 			$defaults = array(
 				'listing_page_type' => WPT_Production::post_type_name,
 				'listing_page_nav' => 'plain',
				'listing_page_groupby' => false	
 			);
		 	$args = wp_parse_args($args, $defaults);

		 	$shortcode_args = '';
		 	if (!empty($args['listing_page_groupby'])) {
			 	if ($args['listing_page_nav']=='grouped') {
				 	$shortcode_args.= ' groupby="'.$args['listing_page_groupby'].'"';
			 	}
			 	if ($args['listing_page_nav']=='paginated') {
				 	$shortcode_args.= ' paginateby="'.$args['listing_page_groupby'].'"';
			 	}
		 	}

		 	$template = '';
			if (!empty($this->options['listing_page_template'])) {
				$template = $this->options['listing_page_template'];
			}

		 	if ($args['listing_page_type']==WPT_Production::post_type_name) {
			 	$shortcode_args.= ' upcoming="1"';
				return '[wpt_productions'.$shortcode_args.']'.$template.'[/wpt_productions]';
 			} else {
				return '[wpt_events'.$shortcode_args.']'.$template.'[/wpt_events]';
 			}
	 	}
	 	
	 	/*
	 	 * Gets the page with upcoming events.
	 	 *
	 	 * @since 	0.8
	 	 * @since 	0.12		No more caching of $page.
	 	 *						Rely on internal caching of Wordpress instead.
	 	 *
	 	 * @return WP_Post|bool	Page if page is set and if page exists.
	 	 * 						<false> otherwise. 
	 	 */
	 	function page() {
		 	$page = false;
		 	if (!empty($this->options['listing_page_post_id'])) {
			 	$post = get_post($this->options['listing_page_post_id']);
			 	if (!is_null($post)) {
			 		$page = $post;
			 	}
		 	}
		 	return $page;
	 	}
	 	
	 	 
	 	function sanitize_option_values($input) {

		 	/*
		 	 * Set a transient every time the option's values are updated, 
		 	 * so the rewrite rules can be flushed on the next page load.
		 	 * @see admin_init()
		 	 */
		 	set_transient('wpt_listing_page_flush_rules', true);
		 	

		 	/*
		 	 * Set the options that are used to generate the shortcodes.
		 	 * Based on the options set in the settings form.
		 	 */

		 	if (!empty($input['listing_page_type']) && $input['listing_page_type']==WPT_Production::post_type_name) {
			 	// Show as productions
			 	$valid = false;
			 	
			 	if (
			 		!empty($input['listing_page_nav_productions']) &&
			 		$input['listing_page_nav_productions'] == 'grouped' &&
			 		!empty($input['listing_page_nav_productions_grouped'])
			 	) {
				 	$input['listing_page_nav'] = 'grouped';
				 	$input['listing_page_groupby'] = $input['listing_page_nav_productions_grouped'];
				 	$valid = true;
			 	}
			 	
			 	if (
			 		!empty($input['listing_page_nav_productions']) &&
			 		$input['listing_page_nav_productions'] == 'paginated' &&
			 		!empty($input['listing_page_nav_productions_paginated'])
			 	) {
				 	$input['listing_page_nav'] = 'paginated';
				 	$input['listing_page_groupby'] = $input['listing_page_nav_productions_paginated'];
				 	$valid = true;
			 	}
			 	
			 	if (!$valid) {
				 	unset($input['listing_page_nav_productions']);
				 	unset($input['listing_page_nav_productions_grouped']);
				 	unset($input['listing_page_nav_productions_paginated']);
				}
			 	
		 	} else {
			 	// Show as events
			 	$input['listing_page_type'] = WPT_Event::post_type_name;
			 	
			 	$valid = false;
			 	
			 	if (
			 		!empty($input['listing_page_nav_events']) &&
			 		$input['listing_page_nav_events'] == 'grouped' &&
			 		!empty($input['listing_page_nav_events_grouped'])
			 	) {
				 	$input['listing_page_nav'] = 'grouped';
				 	$input['listing_page_groupby'] = $input['listing_page_nav_events_grouped'];
				 	$valid = true;
			 	}
			 	
			 	if (
			 		!empty($input['listing_page_nav_events']) &&
			 		$input['listing_page_nav_events'] == 'paginated' &&
			 		!empty($input['listing_page_nav_events_paginated'])
			 	) {
				 	$input['listing_page_nav'] = 'paginated';
				 	$input['listing_page_groupby'] = $input['listing_page_nav_events_paginated'];
				 	$valid = true;
			 	}
			 	
			 	if (!$valid) {
				 	unset($input['listing_page_nav_events']);
				 	unset($input['listing_page_nav_events_grouped']);
				 	unset($input['listing_page_nav_events_paginated']);
				}
		 	}

		 	return $input;
	 	}
	 	
	 	/*
	 	 * Show input to select listing page in settings form.
	 	 */
	 	
	 	function settings_field_wpt_listing_page_post_id() {
	 		global $wp_theatre;
			$pages = get_pages();

			echo '<select id="wpt_listing_page_post_id" name="wpt_listing_page[listing_page_post_id]">';
			echo '<option></option>';
			foreach($pages as $page) {
				echo '<option value="'.$page->ID.'"';
				if ($page->ID==$this->options['listing_page_post_id']) {
					echo ' selected="selected"';
				}
				echo '>'.$page->post_title.'</option>';
			}
			echo '</select>';
	 	}
	 	
	 	/*
	 	 * Show input to set position of upcoming events on listing page.
	 	 */
	 	
	    public function settings_field_wpt_listing_page_position() {
			$options = array(
				'above' => __('show above content','theatre'),
				'below' => __('show below content','theatre'),
				'not' => __('manually, using <code>'.esc_html($this->shortcode($this->options)).'</code> shortcode','theatre')
			);
			
			foreach($options as $key=>$value) {
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_position]" value="'.$key.'"';
				if (!empty($this->options['listing_page_position']) && $key==$this->options['listing_page_position']) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
	 	/*
	 	 * Show input to set position of upcoming events on production page.
	 	 */
	 	
	    public function settings_field_wpt_listing_page_position_on_production_page() {
			$options = array(
				'above' => __('show above content','theatre'),
				'below' => __('show below content','theatre'),
				'' => __('manually, using <code>[wpt_production_events]</code> shortcode','theatre')
			);
			
			foreach($options as $key=>$value) {
				$checked = 
					(!empty($this->options['listing_page_position_on_production_page']) && $key==$this->options['listing_page_position_on_production_page']) ||
					(empty($this->options['listing_page_position_on_production_page']) && empty($key));
			
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_position_on_production_page]" value="'.$key.'"';
				if ($checked) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
	 	/*
	 	 * Show input to choose whether to show events or productions on the listing page..
	 	 */
	 	
	    public function settings_field_wpt_listing_page_type() {
			$options = array(
				WPT_Production::post_type_name => __('productions','theatre'),
				WPT_Event::post_type_name => __('events','theatre')
			);
			
			foreach($options as $key=>$value) {
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_type]" value="'.$key.'"';
				if (!empty($this->options['listing_page_type']) && $key==$this->options['listing_page_type']) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
	 	/*
	 	 * Show input to set the type of navigation used on the listing page.
	 	 */
	 	
	    public function settings_field_wpt_listing_page_nav_events() {
			$options_groupby = array(
				'day' => __('day','theatre'),
				'month' => __('month','theatre'),
				'year' => __('year','theatre'),
				'category' => __('category','theatre')
			);
			
			$options = array(
				'' => __('as a plain list','theatre'),
				'grouped' => __('grouped by','theatre'),
				'paginated' => __('paginate by','theatre')
			);
			
			echo '<div id="listing_page_nav_events" class="wpt_settings_radio_with_selects">';
			
			foreach($options as $key=>$value) {

				$checked = 
					(!empty($this->options['listing_page_nav_events']) && $key==$this->options['listing_page_nav_events']) ||
					(empty($this->options['listing_page_nav_events']) && empty($key));
				
				
				echo '<input type="radio" id="listing_page_nav_events_'.$key.'" name="wpt_listing_page[listing_page_nav_events]" value="'.$key.'"';
				if ($checked) {
					echo ' checked="checked"';
				}
				echo '>';
				
				echo '<label for="listing_page_nav_events_'.$key.'">'.$value.'</label>';
				if (!empty($key)) {
					echo ' <select name="wpt_listing_page[listing_page_nav_events_'.$key.']"><option />';
					foreach($options_groupby as $groupby_key=>$groupby_value) {
						echo '<option value="'.$groupby_key.'"';
						if ($checked) {
							if (!empty($this->options['listing_page_groupby']) && $groupby_key==$this->options['listing_page_groupby']) {
								echo ' selected="selected"';
							}
						}
						echo '>';
						echo $groupby_value;
						echo '</option>';
					}
					echo '</select>';
				}
				echo '<br />';
			}
			
			echo '</div>';
	    }
	    
	 	/*
	 	 * Show input to set the type of navigation used on a production page.
	 	 */
	 	
	    public function settings_field_wpt_listing_page_nav_productions() {
			$options_groupby = array(
				'category' => __('category','theatre')
			);
			
			$options = array(
				'' => __('as a plain list','theatre'),
				'grouped' => __('grouped by','theatre'),
				'paginated' => __('paginate by','theatre')
			);
			
			echo '<div id="listing_page_nav_productions" class="wpt_settings_radio_with_selects">';

			foreach($options as $key=>$value) {

				$checked = 
					(!empty($this->options['listing_page_nav_productions']) && $key==$this->options['listing_page_nav_productions']) ||
					(empty($this->options['listing_page_nav_productions']) && empty($key));
				
				
				echo '<input type="radio" id="listing_page_nav_productions_'.$key.'" name="wpt_listing_page[listing_page_nav_productions]" value="'.$key.'"';
				if ($checked) {
					echo ' checked="checked"';
				}
				echo '>';
				
				echo '<label for="listing_page_nav_productions_'.$key.'">'.$value.'</label>';
				if (!empty($key)) {
					echo ' <select name="wpt_listing_page[listing_page_nav_productions_'.$key.']"><option />';
					foreach($options_groupby as $groupby_key=>$groupby_value) {
						echo '<option value="'.$groupby_key.'"';
						if ($checked) {
							if (!empty($this->options['listing_page_groupby']) && $groupby_key==$this->options['listing_page_groupby']) {
								echo ' selected="selected"';
							}
						}
						echo '>';
						echo $groupby_value;
						echo '</option>';
					}
					echo '</select>';
				}
				echo '<br />';
			}
			echo '</div>';
	    }
	    
	    /**
	     * Adds the 'Events page' display state to the listing page in the pages list tabel.
	     * 
	     * @since	0.13
	     * @since	0.14.3	Bugfix: Avoid PHP errors when no listing page is set.
	     *					Fixes #181.
	     * @param 	array 	$post_states	An array of post display states.
	     * @param 	int 	$post			The post ID.
	     * @return	array					The new array of post display states.
	     */
	    public function set_display_post_state($post_states, $post) { 
			if ( ($listing_page = $this->page()) && $post->ID == $listing_page->ID ) {
			    $post_states['wpt_listing_page'] = __('Events page', 'theatre');
		    }
		    return $post_states;
	    }
	    
	 	/*
	 	 * Show input to set the field that is used for the navigation on the listing page.
	 	 */
	 	
	    public function settings_field_wpt_listing_page_groupby() {
			$options = array(
				'day' => __('day','theatre'),
				'month' => __('month','theatre'),
				'year' => __('year','theatre'),
				'category' => __('category','theatre'),
				'season' => __('season','theatre')
			);
			
			foreach($options as $key=>$value) {
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_groupby]" value="'.$key.'"';
				if (!empty($this->options['listing_page_groupby']) && $key==$this->options['listing_page_groupby']) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
	 	/*
	 	 * Show input to enter a template for upcoming events on the listing page.
	 	 */
	 	
	    public function settings_field_wpt_listing_template() {
			echo '<p>';
			echo '<textarea id="wpt_custom_css" name="wpt_listing_page[listing_page_template]">';
			if (!empty($this->options['listing_page_template'])) {
				echo esc_html($this->options['listing_page_template']);
			}
			echo '</textarea>';
			echo '</p>';
			echo '<p class="description">Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes">documentation</a>.</p>';
	    }

	 	/*
	 	 * Show input to enter a template for upcoming events on a production page.
	 	 */
	 	 
	    public function settings_field_wpt_listing_template_on_production_page() {
			echo '<p>';
			echo '<textarea id="wpt_custom_css" name="wpt_listing_page[listing_page_template_on_production_page]">';
			if (!empty($this->options['listing_page_template_on_production_page'])) {
				echo esc_html($this->options['listing_page_template_on_production_page']);
			}
			echo '</textarea>';
			echo '</p>';
			echo '<p class="description">Optional, see <a href="https://github.com/slimndap/wp-theatre/wiki/Shortcodes">documentation</a>.</p>';
	    }


		/*
		 * Add a listing of upcoming events to the listing page and to the production pages.
		 * @see WPT_Page_Listing::shortcode()
		 */

	 	function the_content($content) {
	 		global $wp_theatre;
	 		
	 		if ($this->page() && is_page($this->page()->ID)) {
	 			if (!empty($this->options['listing_page_position'])) {
		 			switch($this->options['listing_page_position']) {
			 			case 'above':
			 				$content = $this->shortcode($this->options).$content;
			 				break;
			 			case 'below':
			 				$content.= $this->shortcode($this->options);
			 				break;
		 			}
	 			}
	 		}
	 				
		 	return $content;
	 	}
	 	
	 	/*
	 	 * Get the URL for the listing page.
	 	 *
	 	 * @since 	0.8
	 	 * @since 	0.10.2	Category now uses slug instead of term_id.
	 	 * @since	0.12.1	Trailingslashit to avoid an extra redirect.
	 	 * @since	0.13.1	Pagination now work when listing page is same as front page.
	 	 *					Fixes #98.
	 	 * @since	0.16	Added support for tags.
	 	 * 
	     * @param array $args {
	     *     An array of arguments. Optional.
	     *
	     *     @type string $wpt_day      	Day to filter on. No day-filter when set to <false>. 
	     *                                 	Accepts <yyyy-mm-dd> or <false>.
	     *                                 	Default <false>.
	     *     @type string $wpt_month      Month to filter on. No month-filter when set to <false>. 
	     *                                 	Accepts <yyyy-mm> or <false>.
	     *                                 	Default <false>.
	     *     @type string $wpt_category 	Category slug to filter on. No category-filter when set to <false>.
	     *                                 	Default <false>.
	     * }
	     * @return string URL.
	 	 */	
	 	function url($args=array()) {
	 		if ($page = $this->page()) {
		 		$url = get_permalink($page->ID);
		 		$defaults = array(
		 			'wpt_month' => false,
		 			'wpt_day' => false,
		 			'wpt_category' => false,
		 			'wpt_tag' => false
		 		);
		 		$args = wp_parse_args($args, $defaults);

	 			if (get_option('permalink_structure') && get_option('page_on_front') != $page->ID) {
		 			$url = trailingslashit($url);
			 		if ($args['wpt_category']) {
			 			if ($category=get_category_by_slug($args['wpt_category'])) {
					 		$url.= $category->slug.'/';
			 			}
			 		}
			 		if ($args['wpt_month']) {
				 		$url.= substr($args['wpt_month'],0,4).'/'.substr($args['wpt_month'],5,2);
			 		}
			 		if ($args['wpt_day']) {
				 		$url.= substr($args['wpt_day'],0,4).'/'.substr($args['wpt_day'],5,2).'/'.substr($args['wpt_day'],8,2);
			 		}
			 		if ($args['wpt_tag']) {
			 			if ( $tag = get_term_by( 'slug', $args['wpt_tag'], 'post_tag' ) ) {
					 		$url.= 'tag/'.$tag->slug.'/';
			 			}
			 		}
			 		$url = trailingslashit($url);
	 			} else {
		 			if (get_option('page_on_front') == $page->ID) {
			 			$url = add_query_arg('pagename', $page->post_name, $url);
		 			}
			 		if ($args['wpt_category']) {
			 			$url = add_query_arg('wpt_category', $args['wpt_category'], $url);
			 		}
			 		if ($args['wpt_month']) {
			 			$url = add_query_arg('wpt_month', $args['wpt_month'], $url);
			 		}
			 		if ($args['wpt_day']) {
			 			$url = add_query_arg('wpt_day', $args['wpt_day'], $url);
			 		}
			 		if ($args['wpt_tag']) {
			 			$url = add_query_arg('wpt_tag', $args['wpt_tag'], $url);
			 		}
	 			}
	 			return $url;
			} else {
				return false;
			}		 	
	 	}
	 	
	 	/*
	 	 * Reset the options and the page.
	 	 * Needed for automatic tests that update the 'wpt_listing_page' option during runtime.
	 	 *
	 	 * @see tests/test_listing_page.php
	 	 * @since 0.8
	 	 */
	 	
	 	function reset() {
	 		$this->options = get_option('wpt_listing_page');
	 	}
	 	
	 	/*
	 	 * Register the Theater Categories widget (if a listing page is set).
	 	 * 
	 	 * @see WPT_Categories_Widget
	 	 * @see WPT_Listing_page::page()
	 	 *
	 	 * @since 0.8
	 	 */

		function widgets_init() {
			if ($this->page()) {
				register_widget( 'WPT_Categories_Widget' );			
			}	
		}

		/*
		 * Add a new tab to the tabs navigation on the settings page.
	 	 *
	 	 * @see WPT_Admin::admin_init()
	 	 * @since 0.8
	 	 */
	 	
	 	function wpt_admin_page_tabs($tabs) {
			$tabs = array_merge(
				array('wpt_listing_page'=>__('Display','theatre')),
				$tabs
			);
			return $tabs;
	 	}
	 	
	 	/*
	 	 * Turn the filter navigation links into pretty URLs when displayed on the listing page.
	 	 *
	 	 * @see WPT_Listing::filter_pagination()
	 	 * @since	?.?
	 	 */
	 	function wpt_listing_filter_pagination_url($url) {
	 		if (
	 			$this->page() &&
	 			is_page($this->page()->ID)
	 		) {
		 		$url_parts = parse_url($url);
		 		if (empty($url_parts['query'])) {
			 		$url = $this->url();		 		
		 		} else {
			 		$url = $this->url($url_parts['query']);		 		
		 		}
	 		}
		 	return $url;
	 	}
	 	

	 	/**
	 	 * Adds an event listing before the content of a production page.
	 	 * 
	 	 * @since 0.9.5
	 	 * @see: WPT_Listing_Page::wpt_production_events_content
	 	 * @param string $content_above The old content before the content of the production.
	 	 * @return string The new content before the content of the production.
	 	 */
	 	function wpt_production_page_content_before($content_before) {
		 	if (
		 		!empty($this->options['listing_page_position_on_production_page']) &&
		 		(in_array($this->options['listing_page_position_on_production_page'], array('before','above')))
		 	) {
			 	$content_before.= $this->wpt_production_page_events_content();
		 	}
		 	return $content_before;
		}

	 	/**
	 	 * Adds an event listing after the content of a production page.
	 	 * 
	 	 * @since 0.9.5
	 	 * @see: WPT_Listing_Page::wpt_production_events_content
	 	 * @param string $content_below The old content after the content of the production.
	 	 * @return string The new content after the content of the production.
	 	 */
	 	function wpt_production_page_content_after($content_after) {
		 	if (
		 		!empty($this->options['listing_page_position_on_production_page']) &&
		 		(in_array($this->options['listing_page_position_on_production_page'], array('after','below')))
		 	) {
			 	$content_after.= $this->wpt_production_page_events_content();
		 	}
		 	
		 	return $content_after;
		}

	 	/**
	 	 * Gets the HTML for an event listing for use on a production page.
	 	 * 
	 	 * @since 	0.9.5
	 	 * @since	0.15.2	Added support for the header translation that you can set
	 	 *					in the Theater settings.
	 	 *
	 	 * @access 	private
	 	 * @return 	string	The HTML for an event listing for use on a production page.
	 	 */
	 	private function wpt_production_page_events_content() {
		 	global $wp_theatre;
		 	
			$production = new WPT_Production();			

			$template = '';
			if (!empty($this->options['listing_page_template_on_production_page'])) {
				$template = $this->options['listing_page_template_on_production_page'];
			}
			$events_html = do_shortcode('[wpt_production_events]'.$template.'[/wpt_production_events]');

			if (!empty($events_html)) {
				
				$events_header = __('Events','theatre');
				
				if (!empty($wp_theatre->wpt_language_options['language_events'])) {
					$events_header = $wp_theatre->wpt_language_options['language_events'];
				}
				
				$events_header_html = '<h3>'.esc_html($events_header).'</h3>';
				
				/**
				 * Filter the HTML of the header for an event listing on a production page.
				 * 
				 * @since	0.9.5
				 * @param	string	$events_header_html		The HTML of the header for an event 
				 *											listing on a production page.
				 */
				$events_header_html = apply_filters('wpt_production_page_events_header',$events_header_html);
				
				$events_html = $events_header_html.$events_html;
			}
			
		 	return $events_html;
		 	
	 	}
	 	
	 	/*
	 	 * For backward compatibility purposes
	 	 * Use old 'show_events' setting to display events on prodcution pages.
	 	 * As of v0.8 'listing_page_position_on_production_page' is used.
	 	 */
	 	
	 	function deprecated() {
		 	global $wp_theatre;
		 	
		 	if (empty($this->options['listing_page_position_on_production_page']) && !empty($wp_theatre->options['show_events'])) {
			 	$this->options['listing_page_position_on_production_page'] = $wp_theatre->options['show_events'];			 	
		 	}
	 	}
	 	
 	}

	/*
	 * The Theater Categories widget.
	 *
	 * Display a list of all categories with upcoming events.
	 *
	 * @since 0.8
	 */

	class WPT_Categories_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'wpt_categories_widget',
				__('Theater Categories','theatre'), // Name
				array( 'description' => __( 'Categories with upcoming events', 'theatre' ), ) // Args
			);
		}
		
		/**
		 * Outputs the widget HTML.
		 * 
		 * @since	0.8
		 * @since	0.15.8	Fixed a typo in the closing <ul>.
		 * @since	0.15.28	Use the new Theater_Transient class.
		 *
		 * @uses 	Theater_Transient() to store the widget output in a transient.
		 *
		 * @param 	mixed 	$args
		 * @param 	mixed 	$instance
		 */
		public function widget($args,$instance) {
			global $wp_theatre;
			
			echo $args['before_widget'];
			
			if ( ! empty( $instance['title'] ) ) {			
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base  );
				echo $args['before_title'] . $title . $args['after_title'];
			}
				
			$cat_args = array(
				'upcoming' => true
			);
			
			$transient = new Theater_Transient( 'cat', $cat_args );
			
			if ( ! ( $html = $transient->get() ) ) {
	
				$categories = $wp_theatre->events->get_categories($cat_args);
				
				$html = '';
				foreach($categories as $id=>$name) {
					$url = htmlentities($wp_theatre->listing_page->url(array('wpt_category'=>$id)));
				
					$html.= '<li class="'.sanitize_title($name).'">';
					$html.= '<a href="'.$url.'">';
					$html.= $name;
					$html.='</a>';
					$html.= '</li>';
				}
				$html = '<ul class="wpt_categories">'.$html.'</ul>';

				$transient->set( $html );
				
			}

			echo $html;

			echo $args['after_widget'];
		}
		
		public function form($instance) {
			$defaults = array(
				'title' => __( 'Categories', 'theatre' )
			);
			$values = wp_parse_args( $instance, $defaults );

			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $values['title'] ); ?>">
			</p>
			<?php 
			
		}
	}
?>
