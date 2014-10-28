<?php
	class WPT_Test_Save_Methods extends WP_UnitTestCase {
		
		
		function setUp() {
		}

		function test_wpt_production_save() {
			global $wp_theatre;
			
			$production = new WPT_Production();
			$production->save();
			
			$this->assertCount(1,$wp_theatre->productions->load());			
		}

		function test_wpt_event_save() {
			global $wp_theatre;
			
			$event = new WPT_Event();
			$event->save();
			
			$this->assertCount(1,$wp_theatre->events->load());			
		}

	}