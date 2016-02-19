<?php

/**
 * WPT_Productions_Admin class.
 */
class WPT_Productions_Admin {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 );
		add_filter( 'wpt_production_title_in_list_table_html', array( $this, 'get_production_title_with_edit_link' ), 10, 2 );
		add_filter( 'wpt_event_title_in_list_table_html', array( $this, 'get_event_title_with_edit_link' ), 10, 3 );
        add_filter('wpt/production/thumbnail/html', array($this, 'add_production_thumbnail_placeholder'), 10, 4);
		add_action( 'init', array( $this, 'set_list_table_mode' ) );
	}

	/**
	 * get_list_table_mode function.
	 * 
	 * @access public
	 * @return void
	 */
	public function get_list_table_mode() {
		
		/**
		 * mode_default
		 * 
		 * (default value: apply_filters( 'wpt/production/admin/listing_table/mode/default', 'compact' ))
		 * 
		 * @var string
		 * @access public
		 */
		$mode_default = apply_filters( 'wpt/production/admin/listing_table/mode/default', 'compact' );

		$mode = get_user_setting( 'wpt_production_admin_listing_table_mode', $mode_default );
		
		/*
		 * Check if the mode is allowed for this list table.
		 * Otherwise use the first entry in the allowed modes.
		 */
		$allowed_modes = $this->get_list_table_modes();
		if (!array_key_exists($mode, $allowed_modes)) {
			$mode = key($allowed_modes); // See http://stackoverflow.com/a/1028677
		}
		
		return $mode;
	}

	/**
	 * get_list_table_modes function.
	 * 
	 * @access public
	 * @return void
	 */
	public function get_list_table_modes() {
		$modes = array(
			'compact' => _x( 'Compact', 'list table', 'theatre' ),
			'list' => _x( 'List', 'list table', 'theatre' ),
		);


		if (!empty($_GET['post_status']) && 'trash' == $_GET['post_status']) {
			unset($modes['list']);
		}
		
		/**
		 * modes
		 * 
		 * (default value: apply_filters( 'wpt/production/admin/list_table/modes', $modes ))
		 * 
		 * @var string
		 * @access public
		 */
		$modes = apply_filters( 'wpt/production/admin/list_table/modes', $modes );

		return $modes;
	}

	/**
	 * get_event_title_with_edit_link function.
	 * 
	 * @access public
	 * @param mixed $html
	 * @param mixed $field
	 * @param mixed $event
	 * @return void
	 */
	function get_event_title_with_edit_link($html, $field, $event) {
		ob_start();
		?><div class="wp_theatre_prod_title"><?php			
			if ( $production = $event->production() ) {
				if ('trash' == get_post_status($production->ID) ) {
					echo $production->title();
				} else {				
					?><a href="<?php echo get_edit_post_link( $production->ID ); ?>"><?php echo $production->title(); ?></a><?php
				}
				_post_states( $production->post() );
			} else {
				printf( '(%s)', __( 'no title','theatre' ) );
			}

		?></div><?php
		return ob_get_clean();
	}

	/**
	 * get_production_title_with_edit_link function.
	 * 
	 * @access public
	 * @param mixed $html
	 * @param mixed $production
	 * @return void
	 */
	function get_production_title_with_edit_link($html, $production) {
		ob_start();
		?><div class="wp_theatre_prod_title"><?php
			
			if ('trash' == get_post_status($production->ID) ) {
				echo $production->title();
			} else {				
				?><a href="<?php echo get_edit_post_link( $production->ID ); ?>"><?php echo $production->title(); ?></a><?php
			}
	
			_post_states( $production->post() );
			
		?></div><?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * get_search_results_summary_html function.
	 * 
	 * @access private
	 * @return void
	 */
	private function get_search_results_summary_html() {
		$html = '';

		if ( ! empty( $_REQUEST['s'] ) ) {
			$html .= sprintf( 
				' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', 
				sanitize_text_field( $_REQUEST['s'] ) 
			);
		}
		return $html;
	}

	function add_production_thumbnail_placeholder($html, $size, $filters, $production) {
		if (!is_admin()) {
			return $html;
		}
		
		if (empty($_GET['page'])) {
			return $html;
		}
		
		if ('theater-events' != $_GET['page']) {
			return $html;
		}
		
		
		if (empty($html)) {
			$html = '<figure class="placeholder"><span class="dashicons dashicons-tickets-alt"></span></figure>';
		}
		return $html;
	}

	/**
	 * add_submenu function.
	 * 
	 * @access public
	 * @return void
	 */
	function add_submenu() {
		add_submenu_page(
			'theater-events',
			__( 'Events', 'theatre' ),
			__( 'Events', 'theatre' ),
			'edit_posts',
			'theater-events',
			array(
				$this,
				'admin_html',
			)
		);
	}

	/**
	 * admin_html function.
	 * 
	 * @access public
	 * @return void
	 */
	public function admin_html() {
		global $mode;

		switch ( $this->get_list_table_mode() ) {
			case 'list' :
				$list_table = new WPT_List_Table_Events();
				break;
			default :
				$list_table = new WPT_List_Table_Productions();
		}
		
		/**
		 * list_table
		 * 
		 * (default value: apply_filters( 'wpt/production/admin/html/list_table', $list_table, $mode ))
		 * 
		 * @var string
		 * @access public
		 */
		$list_table = apply_filters( 'wpt/production/admin/html/list_table', $list_table, $mode );
		$list_table = apply_filters( 'wpt/production/admin/html/list_table?mode='.$mode, $list_table );

		ob_start();
		
		?><div class="wrap">
			<h1><?php _e( 'Events','theatre' ); ?>
				<a href="<?php echo admin_url( 'post-new.php?post_type='.WPT_Production::post_type_name );?>" class="page-title-action">
					<?php _e('Add new event', 'theatre'); ?>
				</a>
				<?php echo $this->get_search_results_summary_html(); ?>
			</h1><?php
		
			$list_table->views();
	
			?><form method="get">
				<input type="hidden" name="page" value="theater-events" /><?php
	
				$list_table->prepare_items();
				$list_table->search_box( __( 'Search events', 'theatre' ), WPT_Production::post_type_name );
				$list_table->display();
					
			?></form>
		</div><?php

		$html = ob_get_clean();

		echo $html;
	}

	/**
	 * set_list_table_mode function.
	 * 
	 * @access public
	 * @return void
	 */
	public function set_list_table_mode() {
		$modes_allowed = $this->get_list_table_modes();
		if ( ! empty( $_REQUEST['mode'] ) && in_array( $_REQUEST['mode'], array_keys( $modes_allowed ) ) ) {
			$mode = $_REQUEST['mode'];
			set_user_setting( 'wpt_production_admin_listing_table_mode', $mode );
		}
	}
}