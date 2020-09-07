<?php
/**
 * Test Gutenberg support.
 *
 * @group 	gutenberg
 * @since	0.18.3
 */

class WPT_Test_Gutenberg extends WPT_UnitTestCase {

	function test_gutenberg_support() {
		
		global $wp_theatre;
		
		$actual = $wp_theatre->gutenberg->support_gutenberg();
		
		$this->assertTrue( $actual );
		
	}
	
	function test_gutenberg_support_filter() {
		
		add_filter( 'theater/gutenberg/support', '__return_false' )	;
		
		global $wp_theatre;
		
		$actual = $wp_theatre->gutenberg->support_gutenberg();
		
		$this->assertFalse( $actual );
		
	}
	
	

}