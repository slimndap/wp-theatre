<?php
class WPT_Permalinks {

	function __construct() {
		/*
		 * Permalink settings
		 */
		add_action( 'admin_init', array($this,'add_production_permalink_settings'));
		add_action( 'admin_init', array($this,'save_production_permalinks'));		 
		
		$this->options = get_option( 'wpt_permalinks' );
	}
		
	public function add_production_permalink_settings() {
		add_settings_section(
			'wpt_product_permalink', 
			__( 'Theater production permalinks', 'wp_theatre' ), 
			array( $this, 'settings_section_production_permalinks' ), 
			'permalink'
		);
		
	}
	
	public function get_production_permalink() {
		return empty( $this->options['production_base'] ) ? _x( 'production', 'slug', 'wp_theatre' ) : $this->options['production_base'];
	}
	
	public function settings_section_production_permalinks() {
		global $wp_theatre;
				var_dump($this->options);

		$html = '';
		
		$html.= wpautop( __( 'These settings control the permalinks used for Theater productions. These settings only apply when <strong>not using "default" permalinks above</strong>.', 'wp_theatre' ) );

		$permalinks = get_option( 'wpt_permalinks' );
		$production_permalink = $permalinks['production_base'];
		$production_base   = _x( 'production', 'default-slug', 'wp_theatre' );

		$html.= '<table class="form-table">';
		$html.= '<tbody>';
		
		$permalink_options = array(
			'default' => array(
				'structure' => '',
				'title' => __( 'Default', 'wp_theatre' ),
				'example' => home_url().'/?'.$production_base.'='.__('sample-production','wp_theatre'),
			),
			'production' => array(
				'structure' => '/' . $production_base,
				'title' => __( 'Production', 'wp_theatre' ),
				'example' => home_url().'/' . trailingslashit( $production_base ).__('sample-production','wp_theatre'),
			),
		);
		
		if ($page_id = $wp_theatre->listing_page->page()) {
			$permalink_options['listing_page'] = array(
				'structure' => '/' . get_page_uri( $page_id ),
				'title' => __( 'Listing page', 'wp_theatre' ),
				'example' => get_permalink( $page_id ).__('sample-production','wp_theatre'),
			);
		}
		
		$option_checked = false;
		
		foreach ($permalink_options as $name => $args) {
			$html.= '<tr>';
			$html.= '<th>';
			$html.= '<label>';
			$html.= '<input name="wpt_production_permalink" type="radio" value="'.$args['structure'].'"';
			$html.= ' '.checked( $args['structure'], $production_permalink, false).' />';
			$html.= ' '.$args['title'];
			$html.= '</label>';
			$html.= '</th>';
			$html.= '<td><code>'.$args['example'].'</code></td>';
			$html.= '</tr>';
			
			if ( $args['structure'] == $production_permalink) {
				$option_checked = true;
			}
		}
		
		$html.= '<tr>';
		$html.= '<th>';
		$html.= '<label>';
		$html.= '<input name="wpt_production_permalink" type="radio" value="custom" '.checked( $option_checked, false, false ).' />';
		$html.= __( 'Custom Base', 'wp_theatre' );
		$html.= '</label>';
		$html.= '</th>';
		$html.= '<td>';
		$html.= '<input name="wpt_production_permalink_structure" type="text" value="'.esc_attr( $production_permalink ).'" class="regular-text code"> <span class="description">'.__( 'Enter a custom base to use. A base <strong>must</strong> be set or WordPress will use default instead.', 'wp_theatre' ).'</span>';
		$html.= '</td>';
		$html.= '</tr>';

		$html.= '</tbody>';
		$html.= '</table>';

		echo $html;
	}

	public function save_production_permalinks() {
		
		if (!is_admin()) {
			return;
		}
		
		$permalinks = get_option( 'wpt_permalinks' );
		
		if ( isset( $_POST['wpt_production_permalink'] )) {
			$production_permalink = sanitize_text_field($_POST['wpt_production_permalink']);

			if ( $production_permalink == 'custom' ) {
				// Get permalink without slashes
				$production_permalink = trim( sanitize_text_field( $_POST['wpt_production_permalink_structure'] ), '/' );
				
				// Prepending slash
				$production_permalink = '/' . $production_permalink;
				
			} elseif ( empty( $production_permalink ) ) {
				$production_permalink = false;
			}
			
			$permalinks['production_base'] = untrailingslashit( $production_permalink );
			
			update_option( 'wpt_permalinks', $permalinks );
		}
	}

}