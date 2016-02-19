<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}	

class WPT_List_Table extends WP_List_Table {

   function __construct($args = array()){
        global $status, $page, $mode;
	 	
		global $wp_theatre;

		$this->modes = $wp_theatre->productions_admin->get_list_table_modes();

        parent::__construct( $args );
                
    }

    protected function get_filter_categories_html() {

		$cat = '';
		if (!empty($_REQUEST['cat'])) {
			$cat = intval($_REQUEST['cat']);
		}

	    $html = '';
		$dropdown_options = array(
			'show_option_all' => __( 'All categories' ),
			'hide_empty' => 0,
			'hierarchical' => 1,
			'show_count' => 0,
			'orderby' => 'name',
			'selected' => $cat,
			'echo' => 0,
		);
		$html.= '<label class="screen-reader-text" for="cat">' . __( 'Filter by category' ) . '</label>';
		$html.= wp_dropdown_categories( $dropdown_options );		
		return $html;   
    }
    
    protected function get_filter_dates_html() {
	    $html = '';
		$html.= '<select name="dates">';
		
		$options = array(
			'all' => __('All dates'),
			'upcoming' => __('Upcoming'),
		);
		
		$dates = '';
		if (!empty($_REQUEST['dates'])) {
			$dates = $_REQUEST['dates'];
		}

		foreach ($options as $key=>$val) {
			$html.= sprintf('<option value="'.$key.'"%s>'.$val.'</option>', selected($key, $dates, false));
		}
		
		$html.= '</select>';
		return $html;
    }
    
    protected function get_mode_switcher_html() {
	    global $wp_theatre;
	    $html = '';
		ob_start();
		$this->view_switcher( $wp_theatre->productions_admin->get_list_table_mode() );			
		$html.= ob_get_contents();
		ob_end_clean();
		return $html;
    }
    
    function get_views() {
	    $num_posts = wp_count_posts(WPT_Production::post_type_name, 'readable');

		$view_current = '';
		if (!empty($_REQUEST['post_status'])) {
			$view_current = $_REQUEST['post_status'];
		}

		$views_available = array(
			'publish' => __('Published'),
			'draft' => __('Draft'),
			'trash' => __('Trash'),
		);

	    $views = array();
	    
	    $view_html = __('All');
	    $view_url = add_query_arg('post_status', '');
		$class='';
		if (empty($view_current)) {
			$class = ' class="current"';
		}
		$view_html = '<a href="'.$view_url.'"'.$class.'>'.$view_html.'</a>';
	    $views['all'] = $view_html;
	    
	    foreach ($views_available as $key=>$val) {
		    if (!empty($num_posts->{$key})) {
				$view_html = $val;
				$view_url = add_query_arg('post_status', $key);
				$class='';
				if ($key == $view_current) {
					$class = ' class="current"';
				}
				$view_html = '<a href="'.$view_url.'"'.$class.'>'.$view_html.'</a>';
				$views[$key] = $view_html;
		    } 
	    }
	    return $views;
    }
    
	function extra_tablenav( $which ) {
		$html = '';
		if ( 'top' == $which) {
			/*
			$html.= '<div class="alignleft actions">';
			
			$html.= $this->get_filter_dates_html();
			$html.= $this->get_filter_categories_html();
				
			$html.= get_submit_button( __( 'Filter' ), 'button', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
			
			$html.= '</div>';
			*/
			$html.= $this->get_mode_switcher_html();
		}
		
		echo $html;
	}
	
	function get_bulk_actions() {
		$actions = array();
		$actions['publish'] = __('Publish', 'theatre');
		$actions['draft'] = __('Save as draft', 'theatre');
		$actions['trash'] = __('Move to trash', 'theatre');
		return $actions;
	}
	
}