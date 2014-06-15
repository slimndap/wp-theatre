<?php
	class WPT_Editor {
		function __construct() {
			add_action( 'admin_menu', array($this, 'admin_menu' ));
		}
		
		function admin_menu() {
			add_submenu_page(
				'theatre',
				__('Theater for WordPress','wp_theatre'),
				'WPT Editor',
				'manage_options', 
				'wpt_editor', 
				array($this, 'admin_page')
			);	
		}
		
		function admin_page() {
			// create event form
			echo $this->production_form();
			
			// filters
			
			// settings
			
			// productions
			echo $this->productions();
			
		}
		
		function production_form() {
			echo '<form id="wpt_editor_create_production_form">';
			
			echo '<input type="text" placeholder="'.__('Start typing to create a new event...','wp_theatre').'" />';
			
			echo '</form>';
		}
		
		function productions() {
			global $wp_theatre;
			
			
			$productions = $wp_theatre->productions->json();
			echo '<div class="wpt_productions">';
			
			echo $productions;
			
			echo '</div>';			
			
		}
	}
?>