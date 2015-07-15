<?php
class WPT_Test_Production_Permalink extends WP_UnitTestCase {

	function setUp() {
		global $wp_theatre;

		unset($wp_theatre->production_permalink->options);
		
		parent::setUp();
		
	}

	function activate_pretty_permalinks() {
		/* 
		 * Make sure permalink structure is consistent when running query tests.
		 * @see: https://core.trac.wordpress.org/ticket/27704#comment:7
		 * @see: https://core.trac.wordpress.org/changeset/28967
		 * @see: https://github.com/slimndap/wp-theatre/issues/48
		 */
		global $wp_rewrite;
		$wp_rewrite->init(); 
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies(); 
		$wp_rewrite->flush_rules();		

		global $wp_theatre;
		$wp_theatre->setup->register_post_types();
	}
	
	function create_production() {
		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		return $this->factory->post->create( $production_args );
	}

	function test_production_permalink_is_off() {
		global $wp_rewrite;
		$wp_rewrite->init(); 
		$wp_rewrite->set_permalink_structure( '' );
		create_initial_taxonomies(); 
		$wp_rewrite->flush_rules();		

		$production_id = $this->create_production();
		$production = get_post($production_id);

		$expected = trailingslashit(home_url().'/?'.WPT_Production::post_type_name.'='.$production->post_name);
		$returned = trailingslashit(get_permalink($production_id));

		$this->assertEquals($expected, $returned);
	}
	
	function test_production_permalink_default() {
		$this->activate_pretty_permalinks();
		
		$production_id = $this->create_production();
		$production = get_post($production_id);

		$expected = trailingslashit(home_url().'/production/'.$production->post_name);
		$returned = trailingslashit(get_permalink($production_id));

		$this->assertEquals($expected, $returned);
	}
	
	function test_production_permalink_is_set() {
		global $wp_theatre;
		
		$base = 'hallo123';
		$wp_theatre->production_permalink->save_base($base);

		$options = get_option( 'wpt/production/permalink' );

		$expected = $options['base'];
		$returned = '/'.$base;

		$this->assertEquals($expected, $returned);
	}
	
	function test_production_permalink_is_retrieved() {
		global $wp_theatre;

		$base = $wp_theatre->production_permalink->get_base();

		$expected = '/production';
		$returned = $base;

		$this->assertEquals($expected, $returned);
	}
	
	function test_production_permalink_custom() {
		global $wp_theatre;
		
		$base = 'hallo456';
		$wp_theatre->production_permalink->save_base($base);

		$this->activate_pretty_permalinks();
		
		$production_id = $this->create_production();
		$production = get_post($production_id);

		$expected = trailingslashit(home_url().'/'.$base.'/'.$production->post_name);
		$returned = trailingslashit(get_permalink($production_id));

		$this->assertEquals($expected, $returned);		
	}
	
}