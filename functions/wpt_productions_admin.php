<?php

/**
 * WPT_Productions_Admin class.
 */
class WPT_Productions_Admin {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 );
		add_filter( 'wpt_production_title_in_list_table_html', array( $this, 'get_production_title_with_edit_link' ), 10, 2 );
		add_filter( 'wpt_event_title_in_list_table_html', array( $this, 'get_event_title_with_edit_link' ), 10, 3 );
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
			'compact' => _x( 'Compact', 'list table', 'wp_theatre' ),
			'list' => _x( 'List', 'list table', 'wp_theatre' ),
		);

		
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
		$html = '';

		$html .= '<div class="wp_theatre_prod_title">';

		if ( $production = $event->production() ) {
			$html .= '<a href="'.get_edit_post_link( $production->ID ).'">';
			$html .= $production->title();
			$html .= '</a>';

			ob_start();
			_post_states( $production->post() );
			$html .= ob_get_contents();
			ob_end_clean();
		} else {
			$html .= sprintf( '(%s)', __( 'no title','wp_theatre' ) );
		}

		$html .= '</div>';
		return $html;
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
		$html = '';

		$html .= '<div class="wp_theatre_prod_title">';
		$html .= '<a href="'.get_edit_post_link( $production->ID ).'">';
		$html .= $production->title();
		$html .= '</a>';

		ob_start();
		_post_states( $production->post() );
		$html .= ob_get_contents();
		ob_end_clean();

		$html .= '</div>';
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

	/**
	 * add_submenu function.
	 * 
	 * @access public
	 * @return void
	 */
	function add_submenu() {
		add_submenu_page(
			'theater-events',
			__( 'Events', 'wp_theatre' ),
			__( 'Events', 'wp_theatre' ),
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

		$html = '';

		$html .= '<div class="wrap">';

		$html .= '<h1>'.__( 'Events','wp_theatre' );

		$html .= '<a href="'.admin_url( 'post-new.php?post_type='.WPT_Production::post_type_name ).'" class="page-title-action">'.__( 'Add new' ).'</a>';

		$html .= $this->get_search_results_summary_html();

		$html .= '</h1>';

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
		$list_table->search_box( __( 'Search events', 'wp_theatre' ), WPT_Production::post_type_name );
		$html .= $list_table->views();
		$html .= ob_get_contents();
		ob_end_clean();

		$html .= '<form method="get">';

		$html .= '<input type="hidden" name="page" value="theater-events" />';

		$list_table->prepare_items();

		ob_start();
		$list_table->search_box( __( 'Search events', 'wp_theatre' ), WPT_Production::post_type_name );
		$list_table->display();
		$html .= ob_get_contents();
		ob_end_clean();

		$html .= '</form>';

		$html .= '</div>';

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