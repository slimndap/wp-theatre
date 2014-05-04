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
			}

			$this->options = get_option('wpt_listing_page');
	 	}
	 	
	 	function admin_init() {
	        register_setting(
	            'wpt_listing_page', // Option group
	            'wpt_listing_page' // Option name
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
	 	
	 	function shortcode($args) {
	 		global $wp_theatre;
	 	
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

		 	if ($args['listing_page_type']==WPT_Production::post_type_name) {
			 	$shortcode_args.= ' upcoming="1"';
				return '[wpt_productions'.$shortcode_args.'][/wpt_productions]';
 			} else {
				return '[wpt_events'.$shortcode_args.'][/wpt_events]';
 			}

	 	}
	 	
	 	function page() {
		 	if (!isset($this->page)) {
			 	if (!empty($this->options['listing_page_post_id'])) {
				 	$this->page = false;
				 	$page = get_post($this->options['listing_page_post_id']);
				 	if (!is_null($page)) {
				 		$this->page = $page;
				 	}
			 	}
		 	}		 	
		 	return $this->page;
	 	}
	 	
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
			echo '<p class="description">'.__('Select the page that shows your upcoming productions or events.','wp_theatre').'</p>';
	 	}
	 	
	    public function settings_field_wpt_listing_page_position() {
			$options = array(
				'above' => __('above content','wp_theatre'),
				'below' => __('below content','wp_theatre'),
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
	    
	    public function settings_field_wpt_listing_page_nav() {
			$options = array(
				'plain' => __('a plain list','wp_theatre'),
				'grouped' => __('a grouped list','wp_theatre'),
				'paginated' => __('a paginated list','wp_theatre')
			);
			
			foreach($options as $key=>$value) {
				echo '<label>';
				echo '<input type="radio" name="wpt_listing_page[listing_page_nav]" value="'.$key.'"';
				if (!empty($this->options['listing_page_nav']) && $key==$this->options['listing_page_nav']) {
					echo ' checked="checked"';
				}
				echo '>'.$value.'</option>';
				echo '</label>';
				echo '<br />';
			}
	    }
	    
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
	    
	 	function the_content($content) {
	 		global $wp_theatre;
	 		if ($this->page()) {
		 		if (is_page($this->page())) {
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
	 		}
		 	return $content;
	 	}
	 	
	 	function wpt_admin_page_tabs($tabs) {
			$tabs['wpt_listing_page'] = __('Upcoming events','wp_theatre');
			return $tabs;
	 	}
	 	
 	}
?>