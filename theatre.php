<?php
/*
Plugin Name: Theatre
Plugin URI: http://wordpress.org/plugins/theatre/
Description: Turn your Wordpress website into a theatre website.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.1
Author URI: http://slimndap.com/
Text Domain: wp_theatre
*/

global $wp_theatre;

class WP_Theatre {
	function __construct() {
		add_action( 'admin_init', function() {
			wp_enqueue_script( 'wp_theatre_js', plugins_url( 'main.js', __FILE__ ), array('jquery') );
			wp_enqueue_style( 'wp_theatre_css', plugins_url( 'style.css', __FILE__ ) );
		});
		
		$this->season = new WP_Theatre_Season();
		$this->production = new WP_Theatre_Prod();
		$this->event = new WP_Theatre_Event();
	}
	
	
}


class WP_Theatre_Prod {
	function __construct() {
		add_action( 'init', array($this,'init'));
		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes'));
		add_filter('template_include', array($this, 'template_include')); 
	}
	
	function init() {
		register_post_type( 'wp_theatre_prod',
			array(
				'labels' => array(
					'name' => __( 'Productions' ),
					'singular_name' => __( 'Production' )
				),
			'public' => true,
			'has_archive' => true,
			'supports' => array('title', 'editor', 'excerpt', 'thumbnail')
			)
		);
		$this->post_type = get_post_type_object( 'wp_theatre_prod' );
	}	

	function add_meta_boxes() {
		global $wp_theatre;
		add_meta_box(
            'wp_theatre_events',
            $wp_theatre->event->post_type->labels->name,
            array($this,'render_meta_box_events'),
            $this->post_type->name,
            'side'
        ); 		
	}
	
	function render_meta_box_events($production) {
		global $wp_theatre;
		
		$production = get_the_id();
		
		if (get_post_status($production) == 'auto-draft') {
			echo __('You need to save this production before you can add events.');
		} else {
			
			$args = array(
				'post_type'=>'wp_theatre_event',
				'meta_key' => 'event_date',
				'order_by' => 'meta_value_num',
				'order' => 'ASC',
				'meta_query' => array(
					array(
						'key' => $this->post_type->name,
						'value' => get_the_ID(),
						'compare' => '=',
					),
					array(
						'key' => 'event_date', // Check the start date field
						'value' => date("Y-m-d"), // Set today's date (note the similar format)
						'compare' => '>=', // Return the ones greater than today's date
						'type' => 'NUMERIC,' // Let WordPress know we're working with numbers
					)
				),
			);
	
			$the_query = new WP_Query($args);
			if ( $the_query->have_posts() ) {
				echo '<ul>';
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					echo '<li>';
					edit_post_link( 
						strftime('%x %X',strtotime(get_post_meta(get_the_ID(),'event_date',true))), 
						'','',
						get_the_ID()
					);
					echo '<br />';
					echo get_post_meta(get_the_ID(),'venue',true).', '.get_post_meta(get_the_ID(),'city',true);
					echo '</li>';
					wp_reset_postdata();
				}
				echo '</ul>';
			} else {
				// no posts found
			}
			
			echo '<p><a href="'.get_bloginfo('url').'/wp-admin/post-new.php?post_type=event&'.$this->post_type->name.'='.$production.'" class="button button-primary">'.$wp_theatre->event->post_type->labels->new_item.'</a></p>';	
		}		
	}

	function template_include($template){	
		if ( is_singular($this->post_type->name) ) {
			$template_name = 'single-'.$this->post_type->name.'.php';
			$theme_template = locate_template(array($template_name), true);
			
			if(empty($theme_template)) {
				$template = plugin_dir_path(__FILE__).'templates/'.$template_name;
			}
		}
		return $template;	
	}
	
	function get_upcoming() {
		
	}
	
	function get_events() {
		
	}
 
 }

class WP_Theatre_Event {
	function __construct() {
		
		add_action( 'init', array($this,'init'));
		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'admin_init', function() {
			wp_enqueue_script( 'jquery-ui-timepicker', plugins_url( 'js/jquery-ui-timepicker-addon.js', __FILE__ ), array('jquery-ui-datepicker','jquery-ui-slider')  );
			wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		});
	}
	
	function init() {
		register_post_type( 'wp_theatre_event',
			array(
				'labels' => array(
					'name' => __( 'Events' ),
					'singular_name' => __( 'Event' ),
					'new_item' => __('New event'),
					'add_new_item' => __('Add new event'),
					'edit_item' => __('Edit event')

				),
			'public' => true,
			'has_archive' => true,
			'show_in_menu' => false,
			'supports' => array('')
			)
		);
		$this->post_type = get_post_type_object( 'wp_theatre_event' );
	}	

	function add_meta_boxes() {
		add_meta_box(
            'wp_theatre_event_data',
            __('Event data','wp_theatre'),
            array($this,'render_meta_box_event_data'),
            $this->post_type->name,
            'normal'
        ); 		
		add_meta_box(
            'wp_theatre_tickets',
            __('Tickets','wp_theatre'),
            array($this,'render_meta_box_tickets'),
            $this->post_type->name,
            'normal'
        ); 		
	}
	
	function render_meta_box_event_data($event) {
		global $wp_theatre;
		
		wp_nonce_field( $this->post_type->name, $this->post_type->name.'_nonce' );

		echo '<table class="form-table">';
		echo '<tbody>';

		echo '<tr class="form-field">';		
		echo '<th>';
		echo '<label>';
		echo $wp_theatre->production->post_type->labels->singular_name;
		echo '</label> ';
		echo '</th>';
		
		echo '<td>';

		if (isset($_GET[$wp_theatre->production->post_type->name])) {
			$current_production = (int) $_GET[$wp_theatre->production->post_type->name];
		} else {
			$current_production = get_post_meta($event->ID,$wp_theatre->production->post_type->name,true);
		}

		$args = array(
			'post_type'=>$wp_theatre->production->post_type->name,
		);
		if (is_numeric($current_production)) {
			$args['p'] = $current_production;
			$the_query = new WP_Query($args);
			if ( $the_query->have_posts() ) {
				$the_query->the_post();
				echo '<input type="hidden" name="'.$wp_theatre->production->post_type->name.'" value="'.$current_production.'" />';
				echo '<a href="'.get_bloginfo('url').'/wp-admin/post.php?post='.$current_production.'&action=edit">';
				the_title();
				echo '</a>';
			}
		} else {
			$the_query = new WP_Query($args);
			if ( $the_query->have_posts() ) {
				echo '<select name="'.$wp_theatre->production->post_type->name.'">';
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					echo '<option value="'.get_the_ID().'"';
					if ($current_production==get_the_ID()) {
						echo ' selected="selected"';
					}
					echo '>';
					the_title();
					echo '</option>';
				}
				echo '</select>';
			}
			
		}	
		wp_reset_postdata();	
		
		echo '</td>';
		echo '</tr>';
		
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Event date','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" class="wp_theatre_datepicker" name="event_date"';
        echo ' value="' . get_post_meta($event->ID,'event_date',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Venue','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" name="venue"';
        echo ' value="' . get_post_meta($event->ID,'venue',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
		echo '<tr class="form-field">';
		echo '<th><label>'.__('City','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="text" name="city"';
        echo ' value="' . get_post_meta($event->ID,'city',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
        echo '</tbody>';
        echo '</table>';		
	}
	
	function render_meta_box_tickets($event) {
		echo '<table class="form-table">';
		echo '<tbody>';
		
		echo '<tr class="form-field">';
		echo '<th><label>'.__('Tickets URL','wp_theatre').'</label></th>';	
		echo '<td>';
		echo '<input type="url" name="tickets_url"';
        echo ' value="' . get_post_meta($event->ID,'tickets_url',true) . '" />';
 		echo '</td>';
		echo '</tr>';
       
        echo '</tbody>';
        echo '</table>';		
	}
	
	public function save( $post_id ) {
		global $wp_theatre;
	
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST[$this->post_type->name.'_nonce'] ) )
			return $post_id;

		$nonce = $_POST[$this->post_type->name.'_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, $this->post_type->name ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$production = sanitize_text_field( $_POST[$wp_theatre->production->post_type->name] );
		$event_date = sanitize_text_field( $_POST['event_date'] );
		$venue = sanitize_text_field( $_POST['venue'] );
		$city = sanitize_text_field( $_POST['city'] );
		$tickets_url = sanitize_text_field( $_POST['tickets_url'] );

		// Update the meta field.
		update_post_meta( $post_id, $wp_theatre->production->post_type->name, $production );
		update_post_meta( $post_id, 'event_date', $event_date );
		update_post_meta( $post_id, 'venue', $venue );
		update_post_meta( $post_id, 'city', $city );
		update_post_meta( $post_id, 'tickets_url', $tickets_url );
	}
	
	function get_upcoming() {
		
	}
}


class WP_Theatre_Season {
	function __construct() {		
		add_action( 'init', array($this,'init'));
	}
	
	function init() {
		register_post_type( 'season',
			array(
				'labels' => array(
					'name' => __( 'Seasons' ),
					'singular_name' => __( 'Season' )
				),
			'public' => true,
			'has_archive' => true,
			)
		);
		
		$this->post_type = get_post_type( 'event' );
	}
	
}

$wp_theatre = new WP_Theatre();

?>
