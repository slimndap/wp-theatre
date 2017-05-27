<?php
/**
 * WPT_Test_Transients class.
 * 
 * @extends WPT_UnitTestCase
 * @group	widgets
 */
class WPT_Test_Widgets extends WPT_UnitTestCase {


	function test_widget_wpt_productions() {
		$this->setup_test_data();

		$widget = new WPT_Productions_Widget();

		$args = array(
			'before_title'  => '<h2>',
			'after_title'   => "</h2>\n",
			'before_widget' => '<section>',
			'after_widget'  => "</section>\n",
		);
		$instance = array(
			'title'  => 'Foo',
			'limit' => 4,
		);
	
		ob_start();
		$widget->widget( $args, $instance );
		$html = ob_get_clean();
		
		$actual = substr_count($html,  '"wp_theatre_prod"');		
		$expected = 3; // All productions with upcoming events. Sticky production with historic event is not present when limit is set.
		
		$this->assertEquals($expected, $actual, $html );
	}
	
	function test_widget_wpt_production_events() {
		$this->setup_test_data();

		$production_permalink = get_permalink( $this->production_with_upcoming_events );
		$production_title = get_the_title( $this->production_with_upcoming_events );

		$this->go_to( $production_permalink );

		$widget = new WPT_Production_Events_Widget();

		$args = array(
			'before_title'  => '<h2>',
			'after_title'   => "</h2>\n",
			'before_widget' => '<section>',
			'after_widget'  => "</section>\n",
		);
		$instance = array(
			'title'  => 'Foo',
		);
	
		ob_start();
		$widget->widget( $args, $instance );
		$html = ob_get_clean();
		
		// Only two events.
		$actual = substr_count($html, '"wp_theatre_event"');
		$expected = 2;		
		$this->assertEquals($expected, $actual, $html );

		// Only events from the current production.
		$actual = substr_count($html, '<div class="wp_theatre_event_title"><a href="'.$production_permalink.'">'.$production_title.'</a></div>');
		$expected = 2;		
		$this->assertEquals($expected, $actual, $html );

	}
	

}