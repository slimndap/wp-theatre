<?php
	class WPT_Integration_Jetpack_featured_content {
		function __construct() {
			// set priority high enough to load after add_theme_support in functions.php
			add_action('after_setup_theme',array($this,'after_setup_theme'),12);
		}
		
		function after_setup_theme() {
			global $_wp_theme_features;
			if (current_theme_supports('featured-content')) {
				$_wp_theme_features['featured-content'][0]['post_types'][] = WPT_Production::post_type_name;
			}
		}	
	}	
	new  WPT_Integration_Jetpack_featured_content();
?>