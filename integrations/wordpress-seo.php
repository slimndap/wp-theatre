<?php
	class WPT_Integration_Wpseo {
	
		function __construct() {
			add_filter('wpseo_metadesc',array($this,'wpseo_metadesc'));
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10000 ,2);
		}
		
		/**
		 * Remove the WordPress SEO meta box from Event admin pages.
		 */
		 
		function add_meta_boxes($post_type, $post) {
			remove_meta_box('wpseo_meta', WPT_Event::post_type_name, 'normal');			
		}
		
		/**
		 * Use the production summary as the metadesc for a production.
		 * Only if no metadesc is set using the WordPress SEO meta box.
		 */
		
		function wpseo_metadesc($metadesc) {
			if (!is_admin() && empty($metadesc)) {
				if (is_singular(WPT_Production::post_type_name)) {
					$production = new WPT_Production();
					$metadesc = $production->summary();					
				}
			}
			return $metadesc;	
		}
		
	}
	new WPT_Integration_Wpseo();
?>