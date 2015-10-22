<?php

class WPT_Test_Extensions_Updater extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;
		$this->wp_theatre = $wp_theatre;

		parent::setUp();
	}
	
	function test_extension_is_added_with_filter() {

		$slug = 'test-extension';

		$func = create_function(
			'$extensions',
			'$extensions[] = array("slug"=>"'.$slug.'", "name"=>"Test Extension", "versions"=>"1.0", "plugin_file"=>__FILE__, "author"=>"Jeroen Schmit"); return $extensions;'
		);

		add_filter( 'wpt/extensions/updater/extensions', $func );
		
		$extensions = $this->wp_theatre->extensions_updater->get_extensions();
		
		$expected = $slug;
		$returned = $extensions[0]['slug'];
		
		$this->assertEquals($expected, $returned);
	}

}
