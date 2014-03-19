<?php
	class WPT_Integration_Wordpress_seo {
		function __construct() {
			add_filter('wpseo_metadesc',array($this,'wpseo_metadesc'));
		}
		
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
	new WPT_Integration_Wordpress_seo();
?>