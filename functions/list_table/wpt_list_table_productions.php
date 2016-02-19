<?php

class WPT_List_Table_Productions extends WPT_List_Table {

   	function __construct(){
	   
        parent::__construct( array(
            'singular'  => __('production','theatre'),
            'plural'    => __('productions', 'theatre'),
            'ajax'      => false,
        ) );
    }

	function column_categories($production) {
		$args = array(
			'html' => true,
		);
		return $production->categories($args);
	}

	function column_cb($production){ 
		return sprintf( 
			'<input type="checkbox" name="%1$s[]" value="%2$s" />', 
			$this->_args['singular'], 
			$production->ID
		); 
	}

    function column_title($production){
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
        $production_args = array(
	    	'template' => '{{thumbnail}}{{title_in_list_table}}{{dates}}{{cities}}',  
        );
        
        return sprintf('%1$s %2$s',
            $production->html($production_args),
            $this->row_actions($actions)
        );
    }
    
	function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Event', 'theatre'),
            'categories' => __('Categories', 'theatre'),
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
	    
	    $production_args = array();
	    
	    $production_args['status'] = 'any';
	    
	    if (!empty($_REQUEST['orderby']) && !empty($_REQUEST['order']) && 'title' == $_REQUEST['orderby']) {
		    $production_args['order'] = $_REQUEST['order'];
	    }
	    
	    if (!empty($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) {
		    $production_args['cat'] = $_REQUEST['cat'];
	    }
	    
	    if (!empty($_REQUEST['dates']) && 'upcoming' == $_REQUEST['dates']) {
		    $production_args['start'] = 'now';
	    }
	    
	    if (!empty($_REQUEST['post_status'])) {
		    $production_args['status'] = array($_REQUEST['post_status']);
	    }
	    
	    if (!empty($_REQUEST['s'])) {
		    $production_args['s'] = sanitize_text_field($_REQUEST['s']);
	    }
	    
	 	$productions = $wp_theatre->productions->get($production_args);

	    $current_page = $this->get_pagenum();
	    
	 	$total_items = count($productions);
	 	
	 	$productions = array_slice($productions,(($current_page-1)*$per_page),$per_page);

	 	$this->items = $productions;
	 	
	 	$this->set_pagination_args( 
	 		array( 
	 			'total_items' => $total_items, 
	 			'per_page' => $per_page, 
	 			'total_pages' => ceil($total_items/$per_page)
	 		)
	 	);
	 	
	}
	
}