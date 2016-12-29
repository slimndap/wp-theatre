<?php
/**
 * Test custom CSS.
 *
 * @group 	css
 * @since	0.15.16
 */

class WPT_Test_CSS extends WPT_UnitTestCase {

	function test_css_is_loaded() {
		
		global $wp_theatre;
		
		$options = array(
			'custom_css' => '.wpt-test { background-color: red; }',
		);
		
		add_option( 'wpt_style', $options );

		ob_start();
		do_action('wp_head');

		$actual = ob_get_clean();
		$expected = '.wpt-test { background-color: red; }';
		
		$this->assertContains($expected, $actual);

	}
	
	function test_css_is_migrated_in_wp_47_and_up() {

		global $wp_theatre;
		
		$options = array(
			'custom_css' => '.wpt-test { background-color: red; }',
		);
		
		add_option( 'wpt_style', $options );

		ob_start();
		do_action('wp_head');
		ob_get_clean();

		$actual = get_option( 'wpt_style' );
		
		$this->assertEmpty($actual);
		
	}

	function test_css_admin_field_is_loaded_wp_46_and_before() {
		global $wp_theatre;
		
		if (function_exists( 'wp_update_custom_css_post' )) {
			return;
		}
		
		do_action('admin_init');
		
		ob_start();
		do_settings_fields( 'wpt_style', 'display_section_id' ); 

		$actual = ob_get_clean();
		$expected = '<textarea id="wpt_custom_css" name="wpt_style[custom_css]">.wpt-test { background-color: red; }</textarea>';	

		$this->assertContains($expected, $actual);

	}
	
	function test_css_admin_field_is_not_loaded_wp_47_and_up() {
		global $wp_theatre;
		
		do_action('admin_init');
		
		ob_start();
		do_settings_fields( 'wpt_style', 'display_section_id' ); 
		$actual = ob_get_clean();
		$expected = 'custom_css';

		$this->assertNotContains($expected, $actual);
	}
	
	

}
