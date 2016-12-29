<?php
class Theater_Custom_CSS {
	
	static function init() {		
		if (self::is_custom_css_supported()) {
			add_action( 'wp_head', array( __CLASS__, 'migrate_custom_css' ), 5);		
		} else {
			add_action( 'wp_head', array( __CLASS__, 'add_custom_css_to_head' ) );
			add_action( 'admin_init', array( __CLASS__, 'add_settings_section' ), 20);			
		}
	}
	
	static function add_custom_css_to_head() {
		global $wp_theatre;
		
		if ( $css = self::get_custom_css() ) {
			?><!-- Custom Theater CSS -->
			<style><?php 
				echo $css; 
			?></style><?php			
		}
		
	}
	
	static function add_settings_section() {
		global $wp_theatre;
		
		if ( 'wpt_style' == $wp_theatre->admin->tab ) {
			
	        add_settings_field(
	            'css', // ID
	            __('Custom CSS','theatre'), // Title
	            array( __CLASS__, 'get_settings_field_html' ), // Callback
	            'wpt_style', // Page
	            'display_section_id' // Section
	        );						
		}
	}
	
	static function get_css_options() {
		return get_option('wpt_style');
	}
	
	static function get_custom_css() {
		$options = self::get_css_options();
		
		if (empty($options['custom_css'])) { 
			return false;
		}
		
		return $options['custom_css'];
	}
	
	static function get_settings_field_html() {
    	global $wp_theatre;

		$css = self::get_custom_css();

		?><p>
			<textarea id="wpt_custom_css" name="wpt_style[custom_css]"><?php
				if ( $css = self::get_custom_css() ) {
					echo esc_html( $css );				
				}
			?></textarea>
		</p><?php
    }
    
    static function is_custom_css_supported() {
	    return function_exists( 'wp_update_custom_css_post' );
    }
    
    static function migrate_custom_css() {
	    global $wp_theatre;
	    
		if ( $css = self::get_custom_css() ) {

	        $core_css = wp_get_custom_css(); // Preserve any CSS already added to the core option.
	        $return = wp_update_custom_css_post( $core_css . $css );
	        if ( ! is_wp_error( $return ) ) {	        
		        $css_options = self::get_css_options();
				unset($css_options['custom_css']);
				update_option( 'wpt_style', $css_options);
	        }
			
		}
	    
    }
}	
