<?php

/*
 * Manages events listings on the dedicated listing page and the production pages.
 * @since: 0.8 
 */

 	class WPT_Listing_Page {
	 	
	 	function __construct() {	
			if (is_admin()) {
				add_action('admin_init', array($this,'admin_init'));
				add_filter('wpt_admin_page_tabs', array($this,'wpt_admin_page_tabs'));
			} else {
				add_action('the_content', array($this, 'the_content'));
				add_filter('wpt_listing_filter_pagination_url', array($this,'wpt_listing_filter_pagination_url'));	
			}

			add_action('init',array($this,'init'));
			
			add_action( "add_option_wpt_listing_page", array($this,'reset'));
			add_action( "update_option_wpt_listing_page", array($this,'reset'));

			add_action('init',array($this,'deprecated'));

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
		            __('Upcoming events','wp_theatre'), // Title
		            '', // Callback
		            'wpt_listing_page' // Page
		        );  

		        add_settings_field(
		            'wpt_listing_page_post_id', // ID
		            __('Page to show upcoming events on','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_post_id' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );

		        add_settings_field(
		            'wpt_listing_page_position', // ID
		            __('Position on page','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_position' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_type', // ID
		            __('Show as','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_type' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_nav_events', // ID
		            __('Arrange the events','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_nav_events' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_nav_productions', // ID
		            __('Arrange the productions','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_nav_productions' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_template', // ID
		            __('Template','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_template' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );
		        
		        add_settings_section(
		            'wpt_listing_production_page', // ID
		            __('Events on production pages','wp_theatre'), // Title
		            '', // Callback
		            'wpt_listing_page' // Page
		        );  
		
		        add_settings_field(
		            'wpt_listing_page_position_on_production_page', // ID
		            __('Position on page','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_position_on_production_page' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_production_page' // Section           
		        );

		        add_settings_field(
		            'wpt_listing_page_template_on_production_page', // ID
		            __('Template','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_template_on_production_page' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_production_page' // Section           
		        );
		        
			}
		 	
	 	}
	 	

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

			if ($this->page()) {
				$post_name = $this->page->post_name;
			
				// <listing_page>/2014/05
				add_rewrite_rule(
					$post_name.'/([0-9]{4})/([0-9]{2})$', 
					'index.php?pagename='.$post_name.'&wpt_month=$matches[1]-$matches[2]',
					'top'
				);
				
				// <listing_page>/2014/05/06
				add_rewrite_rule(
					$post_name.'/([0-9]{4})/([0-9]{2})/([0-9]{2})$', 
					'index.php?pagename='.$post_name.'&wpt_day=$matches[1]-$matches[2]-$matches[3]',
					'top'
				);	 	 

				// <listing_page>/comedy
				add_rewrite_rule(
					$post_name.'/([a-z0-9-]+)$', 
					'index.php?pagename='.$post_name.'&wpt_category=$matches[1]',
					'top'
				);	 	 

				// <listing_page>/comedy/2014/05
				add_rewrite_rule(
					$post_name.'/([a-z0-9-]+)/([0-9]{4})/([0-9]{2})$', 
					'index.php?pagename='.$post_name.'&wpt_category=$matches[1]&wpt_month=$matches[2]-$matches[3]',
					'top'
				);

				// <listing_page>/comedy/2014/05/06
				add_rewrite_rule(
					$post_name.'/([a-z0-9-]+)/([0-9]{4})/([0-9]{2})/([0-9]{2})$', 
					'index.php?pagename='.$post_name.'&wpt_category=$matches[1]&wpt_day=$matches[2]-$matches[3]-$matches[4]',
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

		 	if (!empty($wp_query->query_vars['wpt_category'])) {
			 	$shortcode_args.= ' category="'.$wp_query->query_vars['wpt_category'].'"';
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
	 	 * Get the page with upcoming events.
	 	 * @return WP_Post Page if page is set and if page exists.
	 	 * @retrun false otherwise. 
	 	 */
	 	
	 	function page() {
		 	if (!isset($this->page)) {
			 	$this->page = false;
			 	if (!empty($this->options['listing_page_post_id'])) {
				 	$page = get_post($this->options['listing_page_post_id']);
				 	if (!is_null($page)) {
				 		$this->page = $page;
				 	}
			 	}
		 	}		 	
		 	return $this->page;
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
				'above' => __('show above content','wp_theatre'),
				'below' => __('show below content','wp_theatre'),
				'not' => __('manually, using <code>'.$this->shortcode($this->options).'</code> shortcode','wp_theatre')
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
				'above' => __('show above content','wp_theatre'),
				'below' => __('show below content','wp_theatre'),
				'' => __('manually, using <code>[wpt_production_events]</code> shortcode','wp_theatre')
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
				WPT_Production::post_type_name => __('productions','wp_theatre'),
				WPT_Event::post_type_name => __('events','wp_theatre')
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
				'day' => __('day','wp_theatre'),
				'month' => __('month','wp_theatre'),
				'category' => __('category','wp_theatre')
			);
			
			$options = array(
				'' => __('as a plain list','wp_theatre'),
				'grouped' => __('grouped by','wp_theatre'),
				'paginated' => __('paginate by','wp_theatre')
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
				'category' => __('category','wp_theatre')
			);
			
			$options = array(
				'' => __('as a plain list','wp_theatre'),
				'grouped' => __('grouped by','wp_theatre'),
				'paginated' => __('paginate by','wp_theatre')
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
	    
	 	/*
	 	 * Show input to set the field that is used for the navigation on the listing page.
	 	 */
	 	
	    public function settings_field_wpt_listing_page_groupby() {
			$options = array(
				'month' => __('month','wp_theatre'),
				'category' => __('category','wp_theatre'),
				'season' => __('season','wp_theatre')
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
				echo $this->options['listing_page_template'];
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
				echo $this->options['listing_page_template_on_production_page'];
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
	 		
	 		if ($this->page() && is_page($this->page->ID)) {
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
	 		
			if (is_singular(WPT_Production::post_type_name)) {
				if (
					isset( $this->options['listing_page_position_on_production_page'] ) &&
					in_array($this->options['listing_page_position_on_production_page'], array('above','below'))
				) {
					$production = new WPT_Production();			
					$events_html = '<h3>'.__('Events','wp_theatre').'</h3>';

					$template = '{{remark}} {{datetime}} {{location}} {{tickets}}';
					if (!empty($this->options['listing_page_template_on_production_page'])) {
						$template = $this->options['listing_page_template_on_production_page'];
					}
					$events_html.= '[wpt_production_events]'.$template.'[/wpt_production_events]';
					
					switch ($this->options['listing_page_position_on_production_page']) {
						case 'above' :
							$content = $events_html.$content;
							break;
						case 'below' :
							$content.= $events_html;
					}
				}
			}
		
		 	return $content;
	 	}
	 	
	 	/*
	 	 * Get the URL for the listing page.
	 	 * @since 0.8
	 	 * 
	     * @param array $args {
	     *     An array of arguments. Optional.
	     *
	     *     @type string $wpt_month     Month to filter on. No month-filter when set to <false>. 
	     *                                 Accepts <yyyy-mm> or <false>.
	     *                                 Default <false>.
	     *     @type integer $wpt_category Category to filter on. No category-filter when set to <false>.
	     *                                 Default <false>.
	     * }
	     * @return string URL.
	 	 */
	 	
	 	function url($args=array()) {
	 		if (
	 			get_option('permalink_structure') &&
	 			$this->page()
	 		) {
		 		$defaults = array(
		 			'wpt_month' => false,
		 			'wpt_day' => false,
		 			'wpt_category' => false
		 		);
		 		$args = wp_parse_args($args, $defaults);
		 		$url = trailingslashit(get_permalink($this->page->ID));

		 		if ($args['wpt_category']) {
		 			if ($category=get_category($args['wpt_category'])) {
				 		$url.= $category->slug.'/';
		 			}
		 		}
		 		
		 		if ($args['wpt_month']) {
			 		$url.= substr($args['wpt_month'],0,4).'/'.substr($args['wpt_month'],5,2);
		 		}
		 		
		 		if ($args['wpt_day']) {
			 		$url.= substr($args['wpt_day'],0,4).'/'.substr($args['wpt_day'],5,2).'/'.substr($args['wpt_day'],8,2);
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
	 		unset($this->page);
	 	}
	 	
	 	/*
	 	 * Add a new tab to the tabs navigation on the settings page.
	 	 *
	 	 * @see WPT_Admin::admin_init()
	 	 * @since 0.8
	 	 */
	 	
	 	function wpt_admin_page_tabs($tabs) {
			$tabs = array_merge(
				array('wpt_listing_page'=>__('Display','wp_theatre')),
				$tabs
			);
			return $tabs;
	 	}
	 	
	 	/*
	 	 * Turn the filter navigation links into pretty URLs when displayed on the listing page.
	 	 *
	 	 * @see WPT_Listing::filter_pagination()
	 	 */
	 	
	 	function wpt_listing_filter_pagination_url($url) {
	 		if (
	 			get_option('permalink_structure') &&
	 			$this->page() &&
	 			is_page($this->page->ID)
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
?>