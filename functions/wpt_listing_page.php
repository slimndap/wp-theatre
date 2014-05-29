<?php

/*
 * Manages the dedicated listing page
 *
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

			add_action('wpt_rewrite_rules',array($this,'wpt_rewrite_rules'));

	 	}
	 	
	 	function admin_init() {

		 	/*
		 	 * Flush rewrite rules after option's values have been updated.
		 	 * @see sanitize_option_values()
		 	 */
		 	 
		 	if (delete_transient('wpt_listing_page_flush_rules')) {
		 		flush_rewrite_rules();
		 	}

	        register_setting(
	            'wpt_listing_page', // Option group
	            'wpt_listing_page', // The name of an option to sanitize and save.
	            array($this,'sanitize_option_values') // A callback function that sanitizes the option's value.
	        );
	        
	 		if (!empty($_GET['tab']) && $_GET['tab']=='wpt_listing_page') {    

		        add_settings_section(
		            'wpt_listing_page_page', // ID
		            __('Page'), // Title
		            '', // Callback
		            'wpt_listing_page' // Page
		        );  

		        add_settings_field(
		            'wpt_listing_page_post_id', // ID
		            __('Upcoming events page','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_post_id' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_page' // Section           
		        );

		        add_settings_section(
		            'wpt_listing_page_display', // ID
		            __('Display options','wp_theatre'), // Title
		            '', // Callback
		            'wpt_listing_page' // Page
		        );  

		        add_settings_field(
		            'wpt_listing_page_position', // ID
		            __('Insert listing','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_position' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_display' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_type', // ID
		            __('Show events as','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_type' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_display' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_nav', // ID
		            __('Arrange events as','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_nav' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_display' // Section           
		        );
		        
		        add_settings_field(
		            'wpt_listing_page_groupby', // ID
		            __('Group/paginate events by','wp_theatre'), // Title 
		            array( $this, 'settings_field_wpt_listing_page_groupby' ), // Callback
		            'wpt_listing_page', // Page
		            'wpt_listing_page_display' // Section           
		        );
		        
			}
		 	
	 	}
	 	
	 	function options() {
		 	return get_option('wpt_listing_page');
	 	}
	 	
	 	function wpt_rewrite_rules() {
		 	
		 	/*
		 	 * Update the rewrite rules for the listings pages.
		 	 * events
		 	 * events/today
		 	 * events/tomorrow
		 	 * events/yesterday
		 	 * events/2014/05
		 	 * events/2014/05/23
		 	 * events/comedy
		 	 * events/comedy/2014/05
		 	 * events/comedy/2014/05/23
		 	 */

			if ($this->page()) {
				$post_name = $this->page->post_name;
			
				add_rewrite_rule(
					$post_name.'/([0-9]{4})/([0-9]{2})$', 
					'index.php?pagename='.$post_name.'&wpt_month=$matches[1]-$matches[2]',
					'top'
				);
			
				add_rewrite_rule(
					$post_name.'/([0-9]{4})/([0-9]{2})/([0-9]{2})$', 
					'index.php?pagename='.$post_name.'&wpt_day=$matches[1]-$matches[2]-$matches[3]',
					'top'
				);	 	 

				add_rewrite_rule(
					$post_name.'/([a-z0-9-]+)$', 
					'index.php?pagename='.$post_name.'&wpt_category=$matches[1]',
					'top'
				);	 	 

				add_rewrite_rule(
					$post_name.'/([a-z0-9-]+)/([0-9]{4})/([0-9]{2})$', 
					'index.php?pagename='.$post_name.'&wpt_category=$matches[1]&wpt_month=$matches[2]-$matches[3]',
					'top'
				);
			}
			
			global $wp_rewrite;
			$wp_rewrite->flush_rules();

			
	 	}
	 	
	 	function shortcode($args) {
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

		 	if ($args['listing_page_type']==WPT_Production::post_type_name) {
			 	$shortcode_args.= ' upcoming="1"';
				return '[wpt_productions'.$shortcode_args.'][/wpt_productions]';
 			} else {
				return '[wpt_events'.$shortcode_args.'][/wpt_events]';
 			}

	 	}
	 	
	 	function page() {
		 	if (!isset($this->page)) {
			 	$this->page = false;
			 	$options = $this->options();
			 	if (!empty($options['listing_page_post_id'])) {
				 	$page = get_post($options['listing_page_post_id']);
				 	if (!is_null($page)) {
				 		$this->page = $page;
				 	}
			 	}
		 	}		 	
		 	return $this->page;
	 	}
	 	
	 	/*
	 	 * Set a transient every time the option's values are updated, 
	 	 * so the rewrite rules can be flushed on the next page load.
	 	 * @see admin_init()
	 	 */
	 	 
	 	function sanitize_option_values($input) {
		 	set_transient('wpt_listing_page_flush_rules');
		 	return $input;
	 	}
	 	
	 	function settings_field_wpt_listing_page_post_id() {
	 		global $wp_theatre;
			$pages = get_pages();
			$options = $this->options();

			echo '<select id="wpt_listing_page_post_id" name="wpt_listing_page[listing_page_post_id]">';
			echo '<option></option>';
			foreach($pages as $page) {
				echo '<option value="'.$page->ID.'"';
				if ($page->ID==$options['listing_page_post_id']) {
					echo ' selected="selected"';
				}
				echo '>'.$page->post_title.'</option>';
			}
			echo '</select>';
			echo '<p class="description">'.__('Select the page that shows your upcoming productions or events.','wp_theatre').'</p>';
	 	}
	 	
	    public function settings_field_wpt_listing_page_position() {
	    	$options = $this->options();
	    	
			$radio_options = array(
				'above' => __('above content','wp_theatre'),
				'below' => __('below content','wp_theatre'),
				'not' => __('manually, using <code>'.$this->shortcode($this->options()).'</code> shortcode','wp_theatre')
			);
			
			foreach($radio_options as $key=>$value) {
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_position]" value="'.$key.'"';
				if (!empty($options['listing_page_position']) && $key==$options['listing_page_position']) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
	    public function settings_field_wpt_listing_page_type() {
	    	$options = $this->options();

			$radio_options = array(
				WPT_Production::post_type_name => __('productions','wp_theatre'),
				WPT_Event::post_type_name => __('events','wp_theatre')
			);
			
			foreach($radio_options as $key=>$value) {
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_type]" value="'.$key.'"';
				if (!empty($options['listing_page_type']) && $key==$options['listing_page_type']) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
	    public function settings_field_wpt_listing_page_nav() {
	    	$options = $this->options();

			$radio_options = array(
				'plain' => __('a plain list','wp_theatre'),
				'grouped' => __('a grouped list','wp_theatre'),
				'paginated' => __('a paginated list','wp_theatre')
			);
			
			foreach($radio_options as $key=>$value) {
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_nav]" value="'.$key.'"';
				if (!empty($options['listing_page_nav']) && $key==$options['listing_page_nav']) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
	    public function settings_field_wpt_listing_page_groupby() {
	    	$options = $this->options();

			$radio_options = array(
				'month' => __('month','wp_theatre'),
				'category' => __('category','wp_theatre'),
				'season' => __('season','wp_theatre')
			);
			
			foreach($radio_options as $key=>$value) {
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_groupby]" value="'.$key.'"';
				if (!empty($options['listing_page_groupby']) && $key==$options['listing_page_groupby']) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
	 	function the_content($content) {
	 		global $wp_theatre;

	    	$options = $this->options();

	 		if ($this->page() && is_page($this->page->ID)) {
	 			if (!empty($options['listing_page_position'])) {
		 			switch($options['listing_page_position']) {
			 			case 'above':
			 				$content = $this->shortcode($options).$content;
			 				break;
			 			case 'below':
			 				$content.= $this->shortcode($options);
			 				break;
		 			}
	 			}
	 		}
		 	return $content;
	 	}
	 	
	 	/*
	 	 * Get the URL for the listing page
	 	 */
	 	
	 	function url($args) {
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
			 		$url.= substr($args['wpt_month'],0,4).'/'.substr($args['wpt_month'],5,6);
		 		}
		 		
		 		return $url;	 		
	 			
			} else {
				return false;
			}		 	
	 	}
	 	
	 	function wpt_admin_page_tabs($tabs) {
			$tabs['wpt_listing_page'] = __('Upcoming events','wp_theatre');
			return $tabs;
	 	}
	 	
	 	function wpt_listing_filter_pagination_url($url) {
	 		if (
	 			get_option('permalink_structure') &&
	 			$this->page() &&
	 			is_page($this->page->ID)
	 		) {
		 		$url_parts = parse_url($url);
		 		$url = $this->url($url_parts['query']);
	 		}
		 	return $url;
	 	}
	 	
	 	
 	}
?>