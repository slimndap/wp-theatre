<?php
/**
 * Custom CSS class.
 *
 * Add custom CSS to the front end.
 * @deprecated	0.15.16 because this is now natively supported in WP 4.7.
 */
class Theater_Custom_CSS {
	
	static function init() {	
		if (self::is_custom_css_supported()) {
			// Hooks if custom CSS is part of WP (WP 4.7+).
			add_action( 'wp_head', array( __CLASS__, 'migrate_custom_css' ), 5);		
		} else {
			// Hooks if custom CSS is not part of WP (WP 4.6-).
			add_action( 'wp_head', array( __CLASS__, 'add_custom_css_to_head' ) );
			add_action( 'admin_init', array( __CLASS__, 'add_settings_section' ), 20);			
		}
	}
	
	/**
	 * Adds the custom CSS to the head of the page.
	 * 
	 * @since	0.15.16	Moved from WPT_Frontend::wp_head().
	 * @return 	void
	 */
	static function add_custom_css_to_head() {
		global $wp_theatre;
		
		if ( $css = self::get_custom_css() ) {
			?><!-- Custom Theater CSS -->
			<style><?php 
				echo $css; 
			?></style><?php			
		}
		
	}
	
	/**
	 * Adds a custom CSS section to the 'Style' tab in Theater admin settings.
	 * 
	 * @since	0.15.16	Moved from WPT_Admin::admin_init().
	 * @return 	void
	 */
	static function add_settings_section() {
		global $wp_theatre;
		
		if ( 'wpt_style' == $wp_theatre->admin->tab ) {
			
	        add_settings_field(
	            'css', // ID
	            __('Custom CSS','theatre'), // Title
	            array( __CLASS__, 'settings_field_html' ), // Callback
	            'wpt_style', // Page
	            'display_section_id' // Section
	        );						
		}
	}
	
	/**
	 * Gets the Theater Style options.
	 * 
	 * @since	0.15.16
	 * @return	array
	 */
	static function get_css_options() {
		return get_option('wpt_style');
	}
	
	/**
	 * Gets the custom CSS from the Theater Style options.
	 * 
	 * @since	0.15.16
	 * @return	string|bool
	 */
	static function get_custom_css() {
		$options = self::get_css_options();
		
		if (empty($options['custom_css'])) { 
			return false;
		}
		
		return $options['custom_css'];
	}
	
	/**
	 * Outputs the HTML for the custom CSS field in the Theater admin settings.
	 * 
	 * @since	0.15.16	
	 * @since	0.15.16	Moved from WPT_Admin::settings_field_css().
	 * @return void
	 */
	static function settings_field_html() {
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
    
    /**
     * Checks if custom CSS is supported natively by WordPress.
     * 
     * @since	0.15.16
     * @return	bool
     */
    static function is_custom_css_supported() {
	    return function_exists( 'wp_update_custom_css_post' );
    }
    
    /**
     * Migrates deprecated custom CSS from Theater to WP.
     * 
     * @since	0.15.16
     * @return 	void
     */
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
