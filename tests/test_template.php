<?php
class WPT_Test_Template extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();		
	}

	function create_event() {
		$event_args = array(
			'post_type' => WPT_Event::post_type_name,
		);
		return $this->factory->post->create( $event_args );
	}

	function create_event_for_production($production_id) {
		$event_id = $this->create_event();
		add_post_meta( $event_id, WPT_Production::post_type_name, $production_id, true );
		return $event_id;
	}

	function create_production() {
		$production_args = array(
			'post_type' => WPT_Production::post_type_name,
		);
		return $this->factory->post->create( $production_args );
	}

	function assume_role($role = 'author') {
		$user = new WP_User( $this->factory->user->create( array( 'role' => $role ) ) );
		wp_set_current_user( $user->ID );
		return $user;
	}

	function test_production_template() {
		$production_id = $this->create_production();		
		$production = new WPT_Production($production_id);
		$template = new WPT_Production_Template($production);
		
		$expected = ' <div class="wp_theatre_prod_title"><a href="'.$production->permalink().'">'.$production->title().'</a></div> <div class="wp_theatre_prod_dates"></div> <div class="wp_theatre_prod_cities"></div>';
		$actual = $template->get_merged();
		
		$this->assertEquals($expected, $actual);
	}
	
	function test_event_template() {
		$production_id = $this->create_production();	
		$event_id = $this->create_event_for_production($production_id)	;
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)));
		
		$event = new WPT_Event($event_id);
		$template = new WPT_Event_Template($event);
		
		$expected = ' <div class="wp_theatre_event_title"><a href="'.$event->permalink().'">'.$event->production()->title().'</a></div> <div class="wp_theatre_event_remark"></div> <div class="wp_theatre_event_datetime"><div class="wp_theatre_event_date wp_theatre_event_startdate">'.$event->startdate().'</div><div class="wp_theatre_event_time wp_theatre_event_starttime">'.$event->starttime().'</div></div> <div class="wp_theatre_event_location"><div class="wp_theatre_event_venue"></div><div class="wp_theatre_event_city"></div></div> <div class="wp_theatre_event_tickets"></div>';
		$actual = $template->get_merged();
		
		$this->assertEquals($expected, $actual);		
	}
	
	function test_template_custom() {
		$production_id = $this->create_production();	
		$event_id = $this->create_event_for_production($production_id)	;
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)));
		
		$event = new WPT_Event($event_id);
		$template = new WPT_Event_Template($event, '{{title}}');
		
		$expected = $event->title(array('html'=>true));
		$actual = $template->get_merged();
		
		$this->assertEquals($expected, $actual);		
	}
	
	function test_template_placeholder() {
		
	}
	
	function test_template_placeholder_thumbnail() {
		add_theme_support( 'post-thumbnails' );	
	    $this->assume_role( 'author' );
	
	    // create attachment
	    $filename = dirname(__FILE__).'/assets/thumbnail.jpg';
	    $contents = file_get_contents( $filename );
	    $upload = wp_upload_bits( $filename, null, $contents );
	    $this->assertTrue( empty( $upload['error'] ) );
	
	    $attachment = array(
	      'post_title' => 'Post Thumbnail',
	      'post_type' => 'attachment',
	      'post_mime_type' => 'image/jpeg',
	      'guid' => $upload['url']
	    );
	    $attachment_id = wp_insert_attachment( $attachment, $upload['file'] );
	
	    $post = array( 'post_title' => 'Post Thumbnail Test', 'post_thumbnail' => $attachment_id );
		$production_id = $this->create_production();	
		set_post_thumbnail($production_id, $attachment_id);
		
		$production = new WPT_Production($production_id);
		$template = new WPT_Production_Template($production, '{{thumbnail}}');
		
		$actual = $template->get_merged();
		
		$img_url = wp_get_attachment_url( $attachment_id );

		wp_delete_attachment($attachment_id, true);
		
		$expected = '<figure><img ';
		$this->assertContains($expected, $actual);
		
		$expected = 'src="'.$img_url.'" class="';
		$this->assertContains($expected, $actual);
		
	    remove_theme_support( 'post-thumbnails' );		
	}
	
	function test_template_placeholder_thumbnail_args() {
		add_theme_support( 'post-thumbnails' );	
	    $this->assume_role( 'author' );
	
	    // create attachment
	    $filename = dirname(__FILE__).'/assets/thumbnail.jpg';
	    $contents = file_get_contents( $filename );
	    $upload = wp_upload_bits( $filename, null, $contents );
	    $this->assertTrue( empty( $upload['error'] ) );
	
	    $attachment = array(
	      'post_title' => 'Post Thumbnail',
	      'post_type' => 'attachment',
	      'post_mime_type' => 'image/jpeg',
	      'guid' => $upload['url']
	    );
	    $attachment_id = wp_insert_attachment( $attachment, $upload['file'] );
	
	    $post = array( 'post_title' => 'Post Thumbnail Test', 'post_thumbnail' => $attachment_id );
		$production_id = $this->create_production();	
		set_post_thumbnail($production_id, $attachment_id);
		
		$production = new WPT_Production($production_id);
		$template = new WPT_Production_Template($production, '{{thumbnail(\'medium\')}}');
		
		$actual = $template->get_merged();
		$img_url = wp_get_attachment_url( $attachment_id );

		wp_delete_attachment($attachment_id, true);
		
		$expected = '<figure><img ';
		$this->assertContains($expected, $actual);
		
		$expected = 'src="'.$img_url.'" class="';
		$this->assertContains($expected, $actual);

	    remove_theme_support( 'post-thumbnails' );		
	}
	
	function test_template_placeholder_filter_permalink() {
		$production_id = $this->create_production();	
		$event_id = $this->create_event_for_production($production_id)	;
		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', time() + (2 * DAY_IN_SECONDS)));
		
		$event = new WPT_Event($event_id);
		$template = new WPT_Event_Template($event, '{{title|permalink}}');
		
		$expected = '<div class="wp_theatre_event_title"><a href="'.$event->production()->permalink().'">'.$event->production()->title().'</a></div>';
		$actual = $template->get_merged();
		
		$this->assertEquals($expected, $actual);			
	}

	function test_template_placeholder_filter_date() {
		$production_id = $this->create_production();	
		$event_id = $this->create_event_for_production($production_id);

		$startdate = time() + (2 * DAY_IN_SECONDS);
		$date_format = 'D d';

		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', $startdate));
		
		$event = new WPT_Event($event_id);
		$template = new WPT_Event_Template($event, '{{startdate|date(\''.$date_format.'\')}}');
		
		$expected = '<div class="wp_theatre_event_date wp_theatre_event_startdate">'.date($date_format,$startdate).'</div>';
		$actual = $template->get_merged();
		
		$this->assertEquals($expected, $actual);			
	}

	function test_template_placeholder_filter_tickets_url() {
		$production_id = $this->create_production();	
		$event_id = $this->create_event_for_production($production_id);

		$tickets_url = 'http://slimndap.com';
		add_post_meta($event_id, 'tickets_url', $tickets_url);
		
		$event = new WPT_Event($event_id);
		$template = new WPT_Event_Template($event, '{{title|tickets_url}}');
		
		$expected = '<div class="wp_theatre_event_title"><a href="'.$tickets_url.'" rel="nofollow" class="wp_theatre_event_tickets_url">Tickets</a></div>';
		$actual = $template->get_merged();
		
		$this->assertEquals($expected, $actual);			
	}

	function test_template_placeholder_filter_date_and_permalink() {
		$production_id = $this->create_production();	
		$event_id = $this->create_event_for_production($production_id);

		$startdate = time() + (2 * DAY_IN_SECONDS);
		$date_format = 'D d';

		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', $startdate));
		
		$event = new WPT_Event($event_id);
		$template = new WPT_Event_Template($event, '{{startdate|date(\''.$date_format.'\')|permalink}}');
		
		$expected = '<div class="wp_theatre_event_date wp_theatre_event_startdate"><a href="'.$event->production()->permalink().'">'.date($date_format,$startdate).'</a></div>';
		$actual = $template->get_merged();
		
		$this->assertEquals($expected, $actual);			
	}

	/**
	 * Test if datetime doesn't mess up timezone if used in conjunction with 'date' filter.
	 * See: https://github.com/slimndap/wp-theatre/issues/161
	 */
	function test_timezone_with_date_template_filter() {
		update_option('gmt_offset', - 5 );

		$production_id = $this->create_production();	
		$event_id = $this->create_event_for_production($production_id);

		$startdate = strtotime('Tomorrow 20:30');
		$date_format = 'H:i:s';

		add_post_meta($event_id, 'event_date', date('Y-m-d H:i:s', $startdate));
		
		$event = new WPT_Event($event_id);
		$template = new WPT_Event_Template($event, '{{datetime|date(\''.$date_format.'\')}}');
		
		$expected = '<div class="wp_theatre_event_datetime">'.date($date_format,$startdate).'</div>';
		$actual = $template->get_merged();
		
		$this->assertEquals($expected, $actual);			
	}
	


}