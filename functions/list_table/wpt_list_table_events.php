<?php

class WPT_List_Table_Events extends WPT_List_Table {

   function __construct(){
        parent::__construct( array(
            'singular'  => __('production','theatre'),
            'plural'    => __('productions', 'theatre'),
            'ajax'      => false,
        ) );
    }

	function column_categories($event) {
		$html = '';
	    if ( $production = $event->production() ) {
			$args = array(
				'html' => true,
			);
			$html.= $production->categories($args);
		}
		return $html;
	}

	function column_cb($event){
		$html ='';
		if ($production = $event->production()) {
			$html.= sprintf( 
				'<input type="checkbox" name="%1$s[]" value="%2$s" />', 
				$this->_args['singular'], 
				$production->ID
			); 
			
		}
		return $html;
	}

	function column_tickets($event) {
		return $event->tickets_html();
	}

    function column_title($event){
	    $html = '';
	    
	    $actions = array();
	    
	    if ( $production = $event->production() ) {
	        if ('trash' == get_post_status($production->ID) ) {
		        $restore_url = add_query_arg('action', 'untrash', get_edit_post_link( $production->ID ));
		        $restore_url = wp_nonce_url($restore_url, 'untrash-post_'.$production->ID);
		        $actions = array(
	    	        'restore' 	=> '<a href="'.$restore_url.'">'.__('Restore').'</a>',
					'delete' => '<a href="'.get_delete_post_link( $production->ID, '', true ).'">'.__('Delete Permanently').'</a>',
				);		    		        		        
	        } else {
		        $actions = array(
	    	        'edit' 	=> '<a href="'.get_edit_post_link( $production->ID ).'">'.__('Edit').'</a>',
					'delete' => '<a href="'.get_delete_post_link( $production->ID, '', false ).'">'.__('Trash').'</a>',
					'view' => '<a href="'.get_permalink($production->ID).'">'.__('View').'</a>',
				);		    		        
	        }
	    } else {		    
	        $actions = array(
				'delete' => '<a href="'.get_delete_post_link( $event->ID, '', true ).'">'.__('Delete').'</a>',
			);		    
	    }
        $event_args = array(
	    	'template' => '{{thumbnail}}{{title_in_list_table}}{{startdate}}{{location}}{{remark}}',  
        );
        $html.= sprintf('%1$s %2$s',
            $event->html($event_args),
            $this->row_actions($actions)
        );
		return $html;
    }
    
	function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Event',
            'tickets' => 'Tickets',
            'categories' => 'Categories',
        );
        return $columns;
    }

	function get_sortable_columns() { 
		$sortable_columns = array( 
			'title' => array('title',false),
		); 
		return $sortable_columns; 
	}
    
    function prepare_items() {
	    global $wp_theatre;

	    $per_page = 10;

	    $columns = $this->get_columns();
		$hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
	    
	    $events_args = array();
	    
	    $events_args['status'] = 'any';
	    
	    if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order'] && 'title' == $_REQUEST['orderby'])) {
		    $events_args['order'] = $_REQUEST['order'];
	    }
	    
	    if (!empty($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) {
		    $events_args['cat'] = $_REQUEST['cat'];
	    }
	    
	    if (!empty($_REQUEST['dates']) && 'upcoming' == $_REQUEST['dates']) {
		    $events_args['start'] = 'now';
	    }
	    
	    if (!empty($_REQUEST['post_status'])) {
		    $events_args['status'] = array($_REQUEST['post_status']);
	    }
	    
	    if (!empty($_REQUEST['s'])) {
		    $events_args['s'] = sanitize_text_field($_REQUEST['s']);
	    }
	    
	 	$events = $wp_theatre->events->get($events_args);

	    $current_page = $this->get_pagenum();
	    
	 	$total_items = count($events);
	 	
	 	$events = array_slice($events,(($current_page-1)*$per_page),$per_page);

	 	$this->items = $events;

	 	$this->set_pagination_args( 
	 		array( 
	 			'total_items' => $total_items, 
	 			'per_page' => $per_page, 
	 			'total_pages' => ceil($total_items/$per_page)
	 		)
	 	);
	 	
	}
	
}