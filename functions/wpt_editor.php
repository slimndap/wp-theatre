<?php
	class WPT_Editor {
		function __construct() {
			add_action( 'admin_init', array($this, 'admin_init' ));
			add_action( 'admin_menu', array($this, 'admin_menu' ));
			add_action( 'admin_enqueue_scripts', array($this,'admin_enqueue_scripts'), 20);

			add_action( 'wp_ajax_productions',array($this,'ajax_productions'));
			add_action( 'wp_ajax_events',array($this,'ajax_events'));
			add_action( 'wp_ajax_save',array($this,'ajax_save'));
			add_action( 'wp_ajax_delete',array($this,'ajax_delete'));
			add_filter( 'admin_footer_text',array($this,'admin_footer_text'));
			add_filter( 'update_footer',array($this,'update_footer'),11);

			add_action( 'admin_notices', array($this,'admin_notices'));			
			$this->page = false;
			
			$this->notices = (array) get_option('wpt_editor_notices');
			
		}
		
		function admin_init() {			
			$this->catch_form_submit();
		}
		
		function admin_enqueue_scripts() {
			global $wp_theatre;
			if ($this->is_theater_admin()) {
				wp_localize_script(
					'wpt_admin',
					'wpt_editor_ajax',
					array(
						'wpt_nonce'=> wp_create_nonce('wpt_nonce'),
						'url' => admin_url( 'admin-ajax.php' ),
						'order_key' => $wp_theatre->order->meta_key,
						'confirm_message' => __('Are you sure you want to move \'%s\' to the Trash?','wp_theatre'),
						'start_typing' => __('Start typing to create a new event&hellip;','wp_theatre'),
						'start_typing_first' => __('Start typing to create your first event&hellip;','wp_theatre')
					) 
				);
			}
		}
		
		function admin_menu() {
			$this->page = add_submenu_page(
				'theatre',
				__('Theater','wp_theatre'),
				'WPT Editor',
				'manage_options', 
				'wpt_editor', 
				array($this, 'admin_page')
			);	
		}
		
		function admin_notices() {
			foreach($this->notices as $notice) {
				echo '<div class="'.$notice['class'].'">'.$notice['msg'].'</div>';
			}
			delete_option('wpt_editor_notices');
		}
		
		function admin_page() {
			global $wp_theatre;
			
			$args = array();
			$this->categories = get_categories($args);
			
			$args = array(
				'post_type'=>WPT_Season::post_type_name,
				'posts_per_page' => -1
			);
			$this->seasons = get_posts($args);

			$html = '';

			$html.= '<div class="wrap">';
			
			$html.= '<div class="wpt_editor_settings">';
			$html.= '<a href="'.admin_url('edit.php?post_status=trash&post_type='.WPT_Production::post_type_name).'" class="trash">'.__('Trash','wp_theatre').'</a>';
			$html.= '<a href="'.admin_url('admin.php?page=wpt_admin').'" class="settings">'.__('Settings','wp_theatre').'</a>';
			$html.= '</div>';

			echo '<div id="wpt_editor">';

    		// create event form
			$html.= '<div id="wpt_editor_production_form_create">'.$this->production_form().'</div>';
			
			
			$html.= '<div class="wpt_editor_list">';
			
			// sort
			$html.= '<div class="wpt_editor_sort"> Sort by: ';
			$html.= '<div class="spinner">'.__('Working...','wp_theatre').'</div>';
			$html.= '<span class="sort" href="#" data-sort="title">name</span>';
			$html.= '<span class="sort" href="#" data-sort="'.$wp_theatre->order->meta_key.'">date</span>';
			$html.= '</div>';

			// filters
			$html.= '<div class="wpt_editor_filters">';
			$html.= '<h3>'.__('Filters','wp_theatre').'</h3>';
			$html.= '<input type="text" class="wpt_editor_search"  placeholder="'.__('Search by keyword','wp_theatre').'" />';
			
			if (!empty($this->categories)) {
				$html.= '<div class="categories">';
				$html.= '<ul>';
				
				foreach ($this->categories as $category) {
					$html.= '<li><a href="#'.$category->slug.'">'.$category->name.'</a></li>';
				}
				
				$html.= '</ul>';
				$html.= '</div>';				
			}
			
			if (!empty($this->seasons)) {
				$html.= '<div class="seasons">';
				$html.= '<ul>';
				
				foreach ($this->seasons as $season) {
					$html.= '<li><a href="#'.$season->ID.'">'.$season->post_title.'</a></li>';
				}
				
				$html.= '</ul>';
				$html.= '</div>';				
			}

			// echo $wp_theatre->calendar->html();

			$html.= '</div>';
			
			// settings
			
			$html.= '<div class="wpt_editor_templates">';
			
			
			// production template
			$html.= '<div id="wpt_editor_production_template" class="production">';
			$html.= '<div class="hidden"><div class="ID"></div></div>';
			$html.= '<div class="actions"><div class="view_link"></div><div class="delete_link"></div><div class="edit_link"></div></div>';
			$html.= '<div class="meta"><div class="dates"></div><div class="cities"></div><div class="categories_html"></div><div class="season_html"></div></div>';
			$html.= '<div class="content"><div class="thumbnail"></div><div class="title"></div><div class="excerpt"></div></div>';
			$html.= '<div class="form"></div>';
			$html.= '</div>'; // .wpt_editor_production_template
			
			// event template
			$html.= '<div id="wpt_editor_event_template" class="event">';
			$html.= '<div class="hidden"><div class="ID"></div></div>';
			$html.= '<div class="content"><div class="datetime_html"></div><div class="location_html"></div><div class="tickets_html"></div></div>';
			$html.= '<div class="actions"><div class="delete_link"></div><div class="edit_link"></div></div>';
			$html.= '</div>'; // .wpt_editor_event_template
			
			// production form template
			$html.= '<div id="wpt_editor_production_form_template">';
			$html.= '<a class="close" href="#">Close</a>';
			$html.= $this->production_form();
			$html.= '<div id="wpt_editor_events"><div class="list">events</div></div>';
			$html.= '</div>';


			$html.= '</div>'; // .wpt_editor_templates
			
			// productions
			$html.= '<div id="wpt_editor_productions"><div class="list"></div></div>';
			
			$html.= '</div>';
			
			$html.= '</div>';
			
			$html.= '</div>';

			echo $html;
		}
		
		function ajax_delete() {
			check_ajax_referer('wpt_nonce', 'wpt_nonce');
			if (wp_trash_post($_POST['ID'])) {
				wp_send_json($_POST['ID']);			
			} else {
				die();
			}
		}
		
		function ajax_productions() {
			check_ajax_referer('wpt_nonce', 'wpt_nonce');
		
			global $wp_theatre;
			
			$args = array(
				'upcoming' => true
			);
			wp_send_json($wp_theatre->productions->to_array($args));
		}
		
		function ajax_events() {
			check_ajax_referer('wpt_nonce', 'wpt_nonce');
		
			global $wp_theatre;
			
			$args = array(
				'production' => $_POST['production']
			);
			wp_send_json($wp_theatre->events->to_array($args));
			
		}
		
		function ajax_save() {
			check_ajax_referer('wpt_nonce', 'wpt_nonce');

			if (!empty($_POST['ID'])) {
				$production = new WPT_Production($_POST['ID']);
			} else {
				$production = new WPT_Production();			
			}

			$production->title = $_POST['title'];
			$production->excerpt = empty($_POST['excerpt'])?'':$_POST['excerpt'];
			$production->categories = empty($_POST['categories'])?array():$_POST['categories'];
			$production->season = empty($_POST['season'])?'':$_POST['season'];

			if (!empty($_POST['events'])) {
				$event_data = $_POST['events'][0];
				$event = new WPT_Event();
				$event->datetime['event_date'] = strtotime($event_data['event_date']);
				$event->datetime['enddate'] = strtotime($event_data['enddate']);
				$event->venue = $event_data['venue'];
				$event->city = $event_data['city'];
				$event->tickets_url = $event_data['tickets_url'];
				$event->tickets_button = $event_data['tickets_button'];
				$event->prices = explode("\n",$event_data['prices']);			
				$production->events = array($event);
			}

			$production->save();
			
			wp_send_json($production->to_array());
		}
		
		function catch_form_submit() {
			// Bail if this is not a submit of our form
			if ( ! isset( $_POST['wpt_editor_submit'] ) ) {
				return;
			}
			
			// Bail if the nonce check fails
			if ( ! isset( $_POST['wpt_nonce'] ) || ! wp_verify_nonce( $_POST['wpt_nonce'], 'wpt_nonce' ) ) {
				$this->notice(__('Please try again.','wp_theatre'),'error');
				wp_redirect('admin.php?page=wpt_editor');
				die();
			}
						
			// Bail if production doesn't have a title
			if (empty($_POST['title'])) {
				$this->notice(__('Please give your event a title.','wp_theatre'),'error');
				wp_redirect('admin.php?page=wpt_editor');
				die();
			}
			
			if (!empty($_POST['ID'])) {
				$production = new WPT_Production($_POST['ID']);
			} else {
				$production = new WPT_Production();			
			}

			$production->title = $_POST['title'];
			$production->excerpt = empty($_POST['excerpt'])?'':$_POST['excerpt'];
			$production->categories = empty($_POST['categories'])?array():$_POST['categories'];
			$production->season = empty($_POST['season'])?'':$_POST['season'];

			$event = new WPT_Event();
			$event->datetime = strtotime($_POST['event_date_date'].' '.$_POST['event_date_time']);
			$event->venue = $_POST['venue'];
			$event->city = $_POST['city'];
			$event->tickets_url = $_POST['tickets_url'];
			$event->tickets_button = $_POST['tickets_button'];
			$event->prices = explode("\n",$_POST['prices']);			
			$production->events = array($event);

			$production->save();

		}
		
		function is_theater_admin() {
			$screen = get_current_screen();
			return $this->page && !empty($screen) && ($screen->id == $this->page);
		}
		
		function production_form() {
			$html = '';
		
			$html.= '<form class="wpt_editor_production_form" action="?page=wpt_editor" method="post">';
			$html.= wp_nonce_field('wpt_nonce','wpt_nonce', true, false);
			$html.= '<input type="hidden" name="ID" />';
			$html.= '<input type="text" name="title" id="wpt_editor_production_form_title" required placeholder="'.__('Title','wp_theatre').'" />';
			$html.= '<textarea name="excerpt" id="wpt_editor_production_form_excerpt" placeholder="'.__('Excerpt','wp_theatre').'"></textarea>';
			$html.= '<select id="wpt_editor_production_form_categories" name="categories[]" multiple>';
			if (!empty($this->categories)) {
				foreach ($this->categories as $category) {
					$html.= '<option value="'.$category->term_id.'">'.$category->name.'</option>';
				}
			}
			$html.= '</select>';
			
			$html.= '<select id="wpt_editor_production_form_season" name="season">';
			$html.= '<option value="">'.__('(season)','wp_theatre').'</option>';
			if (!empty($this->seasons)) {
				foreach ($this->seasons as $season) {
					$html.= '<option value="'.$season->ID.'">'.$season->post_title.'</option>';
				}
			}
			$html.= '</select>';
			
			$html.= $this->event_form();
			
			$html.= '<input type="submit" name="wpt_editor_submit" class="button button-primary" value="'.__('Save new event','wp_theatre').'" />';
			$html.= '<button type="reset" class="button">'.__('Cancel').'</button>';
			
			$html.= '</form>';
			
			return $html;
		}
		
		function event_form() {
			
			$html = '';
			
			$html.= '<div class="dates">';
			
			$default_date = strtotime('20:00');
			if ($default_date < time()) {
				$default_date = strtotime('tomorrow 20:00');
			}
			$default_date = apply_filters('wpt_editor_default_date',$default_date);
			
			$html.= '<input type="text" name="event_date_date" id="wpt_editor_event_form_event_date_date" placeholder="'.__('Start date','wp_theatre').'" value="'.date('Y-m-d',$default_date).'" />';
			$html.= '<input type="text" name="event_date_time" id="wpt_editor_event_form_event_date_time" placeholder="'.__('Start time','wp_theatre').'" value="'.date('H:i',$default_date).'" />';
			$html.= '<input type="text" name="enddate_date" id="wpt_editor_event_form_enddate_date" placeholder="'.__('End date','wp_theatre').'" />';
			$html.= '<input type="text" name="enddate_time" id="wpt_editor_event_form_enddate_time" placeholder="'.__('End time','wp_theatre').'" />';
			$html.= '</div>'; // .dates
			
			$html.= '<div class="location">';
			$html.= '<input type="text" name="venue" id="wpt_editor_event_form_venue" placeholder="'.__('Venue','wp_theatre').'" />';
			$html.= '<input type="text" name="city" id="wpt_editor_event_form_city" placeholder="'.__('City','wp_theatre').'" />';
			$html.= '</div>'; // .location
			
			$html.= '<div class="prices">';
			$html.= '<textarea name="prices" id="wpt_editor_production_form_pcires" placeholder="'.__('Prices','wp_theatre').'"></textarea>';
			$html.= '</div>'; // .prices
			
			$html.= '<div class="tickets">';
			$html.= '<input type="url" name="tickets_url" id="wpt_editor_event_form_tickets_url" placeholder="'.__('Tickets URL','wp_theatre').'" />';
			$html.= '<input type="text" name="tickets_button" id="wpt_editor_event_form_tickets_button" placeholder="'.__('Tickets text','wp_theatre').'" />';
			$html.= '</div>'; // .tickets
			
			$html.= '<div class="status">';
			$html.= '<select id="wpt_editor_production_form_tickets_status" name="status">';
			
			$statusses = array(
				WPT_Event::tickets_status_onsale => __('On sale','wp_theatre'),
				WPT_Event::tickets_status_soldout => __('Sold Out','wp_theatre'),
				WPT_Event::tickets_status_cancelled => __('Cancelled','wp_theatre'),
				WPT_Event::tickets_status_hidden => __('Hidden','wp_theatre'),	
				WPT_Event::tickets_status_other => __('Other&hellip;','wp_theatre'),	
			);
			
			foreach ($statusses as $value=>$label) {
				$html.= '<option value="'.$value.'">'.$label.'</option>';
			}

			$html.= '</select>';
			$html.= '</div>'; // .status
			
			$html = '<div class="wpt_editor_event_form">'.$html.'</div>';
			return $html;
		}

		function admin_footer_text ($text)
		{
			if ($this->is_theater_admin()) {
			    $text = '<span id="footer-thankyou">'.
			    		sprintf(__('Thank you for using <a href="%s">Theater for WordPress</a>.','wp_theatre'),'http://wordpress.org/plugins/theatre/').
			    		'</span>';			
			}
			return $text;
		}
	
		function notice($msg, $class='updated') {
			$this->notices[] = array(
				'msg' => $msg,
				'class' => $class
			);
			update_option('wpt_editor_notices',$this->notices);
		}
		
		function save_production($production) {
			$defaults = array(
				'post_title' => __('(Draft production)','wp_theatre'),
				'post_type' => WPT_Production::post_type_name,
				'post_status' => 'publish'
			);
			
			$post = wp_parse_args( $production, $defaults );
			$ID = wp_insert_post($post);
			if (!empty($production['season'])) {
				update_post_meta($ID, WPT_Season::post_type_name, $production['season']);
			}
			return new WPT_Production($ID);
		}
		
		function update_footer($text) {
			$text = __('Created by <a href="http://slimndap.com">Jeroen Schmit</a> of <a class="logo" href="http://slimndap.com">Slim &amp; Dapper</a>','wp_theatre');
		//echo $text;
//		exit;
			return $text;
			
		}
	}
