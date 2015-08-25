<?php
	
class WPT_Extensions_Promo {
	
	function __construct() {
		add_filter( 'plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2 );

	}
	
	function add_plugin_row_meta($links, $file) {

		if ( 'theatre/theater.php' != $file )
			return $links;
			
		$extensions_link = add_query_arg( 
			array(
				'utm_source'   => 'plugins-page',
				'utm_medium'   => 'plugin-row',
				'utm_campaign' => 'admin',
			), 'http://theater.slimndap.com/extensions/'
		);
		
		$links[] = '<a href="' . esc_url($extensions_link) . '">' . esc_html__( 'Extensions', 'wp_theatre' ) . '</a>';

		return $links;
	}
	
}