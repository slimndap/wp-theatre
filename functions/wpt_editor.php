<?php
	class WPT_Editor {
		function __construct() {
			add_action( 'admin_menu', array($this, 'admin_menu' ));
			add_action( 'admin_enqueue_scripts', array($this,'admin_enqueue_scripts'), 20);

			add_action( 'wp_ajax_productions',array($this,'ajax_productions'));
			add_filter( 'admin_footer_text',array($this,'admin_footer_text'));
			
			$this->page = false;
		}
		
		function admin_enqueue_scripts() {
			global $wp_theatre;
			if ($this->is_theater_admin()) {
				wp_localize_script(
					'wpt_admin',
					'wpt_editor_ajax',
					array( 
						'url' => admin_url( 'admin-ajax.php' ),
						'order_key' => $wp_theatre->order->meta_key
					) 
				);
			}
		}
		
		function admin_menu() {
			$this->page = add_submenu_page(
				'theatre',
				__('Theater for WordPress','wp_theatre'),
				'WPT Editor',
				'manage_options', 
				'wpt_editor', 
				array($this, 'admin_page')
			);	
		}
		
		function admin_page() {
			global $wp_theatre;
			echo '<div id="wpt_editor">';
			
			echo '<div class="spinner">'.__('Working...','wp_theatre').'</div>';
		
    		// create event form
			echo $this->production_form();
			
			// sort
			echo '<div class="wpt_editor_sort"> Sort by:';
			echo '<span class="sort" href="#" data-sort="title">name</span>';
			echo '<span class="sort" href="#" data-sort="'.$wp_theatre->order->meta_key.'">date</span>';
			echo '</div>';

			// filters
			echo '<div class="wpt_editor_filters">';
			echo '<input class="search" />';
			echo '</div>';
			
			// settings
			
			echo '<div class="wpt_editor_templates">';
			
			echo '<div id="wpt_editor_production_template" class="production">';
	
			echo '<div class="actions">';
			echo '<div class="view_link"></div>';
			echo '<div class="delete_link"></div>';
			echo '<div class="edit_link"></div>';
			echo '</div>';
			
			echo '<div class="meta">';
			echo '<div class="dates"></div><div class="cities"></div><div class="categories"></div><div class="season"></div>';
			echo '</div>';
			
			echo '<div class="content">';
			echo '<div class="thumbnail"></div>';
			echo '<h2 class="title"></h2>';
			echo '<div class="excerpt"></div>';
			echo '<p></p>';
			echo '</div>';
			
			echo '</div>';
			echo '</div>';
			
			// productions
			echo '<div class="wpt_editor_productions"></div>';
			
			echo '</div>';
			
		}
		
		function ajax_productions() {
			global $wp_theatre;
			
			$args = array(
				'upcoming' => true
			);
			wp_send_json($wp_theatre->productions->to_array($args));
		}
		
		function is_theater_admin() {
			$screen = get_current_screen();
			return $this->page && !empty($screen) && ($screen->id == $this->page);
		}
		
		function production_form() {
			echo '<form id="wpt_editor_create_production_form">';
			
			echo '<input type="text" placeholder="'.__('Start typing to create a new event...','wp_theatre').'" />';
			
			echo '</form>';
		}

		function admin_footer_text ($text)
		{
			if ($this->is_theater_admin()) {
			    $text = '<span id="footer-thankyou">'.
			    		sprintf(__('Thank you for using <a href="%s">Theater for WordPress</a>.','wp_theatre'),'http://wordpress.org/plugins/theatre/').
			    		'</span>';			
			}
			return $text;
		}
		
	}
?>