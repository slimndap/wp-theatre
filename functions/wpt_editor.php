<?php
	class WPT_Editor {
		function __construct() {
			add_action( 'admin_menu', array($this, 'admin_menu' ));
			add_action( 'admin_enqueue_scripts', array($this,'admin_enqueue_scripts'), 20);

			add_action( 'wp_ajax_productions',array($this,'ajax_productions'));

		}
		
		function admin_enqueue_scripts() {
			global $wp_theatre;
			wp_localize_script(
				'wpt_admin',
				'wpt_editor_ajax',
				array( 
					'url' => admin_url( 'admin-ajax.php' ),
					'order_key' => $wp_theatre->order->meta_key
				) 
			);
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
		
			echo '<div class="wpt_editor">';
		
			// create event form
			echo $this->production_form();
			
			// filters
			
			// settings
			
			// productions
			echo '<div class="wpt_editor_productions"></div>';
			
			echo '</div>';
			
		}
		
		function ajax_productions() {
			global $wp_theatre;
			wp_send_json($wp_theatre->productions->to_array());
		}
		
		function production_form() {
			echo '<form id="wpt_editor_create_production_form">';
			
			echo '<input type="text" placeholder="'.__('Start typing to create a new event...','wp_theatre').'" />';
			
			echo '</form>';
		}
		
	}
?>