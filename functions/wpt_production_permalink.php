<?php
/**
 * The production permalink.
 *
 * Manages the permalink structure for the productions.
 * The permalink structure can be set on the Wordpress permalink settings page.
 *
 * @since 0.12
 */
class WPT_Production_Permalink {

	function __construct() {
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );

		$this->options = get_option( 'wpt/production/permalink' );
	}

	/**
	 * Adds the production permalink section to the permalink settings page.
	 *
	 * @since	0.12
	 * @return	void
	 */
	public function add_settings() {
		add_settings_section(
			'wpt_production_permalink',
			__( 'Theater permalinks', 'wp_theatre' ),
			array( $this, 'settings_section' ),
			'permalink'
		);
	}

	/**
	 * Gets the default permalink.
	 *
	 * @since 	0.12
	 * @return 	string	The default permalink.
	 */
	public function get_default() {
		$default = '/'._x( 'production', 'slug', 'wp_theatre' );

		/**
		 * Filter the default production permalink.
		 *
		 * @since 	0.12
		 * @param	string	$default	The default production permalink.
		 */
		$default = apply_filters( 'wpt/production/permalink/default', $default );

		return $default;
	}

	/**
	 * Gets the production permalink.
	 *
	 * @since	0.12
	 * @return 	string	The permalink.
	 */
	public function get_permalink() {
		$permalink = empty( $this->options['permalink'] ) ? $this->get_default() : $this->options['permalink'];

		/**
		 * Filter the production permalink.
		 *
		 * @since	0.12
		 * @param	string	$permalink	The production permalink.
		 */
		$permalink = apply_filters( 'wpt/production/permalink/', $permalink );

		return $permalink;
	}

	/**
	 * Save the production permalink.
	 *
	 * @since	0.12
	 * @param 	string	$permalink	The permalink.
	 * @return	void
	 */
	public function save_permalink($permalink = '') {
		if ( empty($permalink) ) {
			$permalink = $this->get_default();
		}

		$permalink = '/'.trim( $permalink, '/' );

		$this->options['permalink'] = $permalink;
		update_option( 'wpt/production/permalink', $this->options );
	}

	/**
	 * Save the production permalink settings.
	 *
	 * @since	0.12
	 * @return 	void
	 */
	public function save_settings() {

		if ( ! isset( $_POST['wpt_production_permalink'] ) ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		$permalink = sanitize_text_field( $_POST['wpt_production_permalink'] );

		if ( 'custom' == $permalink ) {
			$permalink = sanitize_text_field( $_POST['wpt_production_permalink_custom'] );
		}

		$this->save_permalink( $permalink );
	}

	/**
	 * Outputs the production permalink settings section.
	 *
	 * @since 	0.12
	 * @return 	void
	 */
	public function settings_section() {
		global $wp_theatre;

		$html = '';

		$html .= wpautop( __( 'These settings control the permalinks used for Theater productions. These settings only apply when <strong>not using "default" permalinks above</strong>.', 'wp_theatre' ) );

		$html .= '<table class="form-table">';
		$html .= '<tbody>';

		$permalink_options = array(
			'production' => array(
				'structure' => $this->get_default(),
				'title' => __( 'Production', 'wp_theatre' ),
				'example' => home_url().trailingslashit( $this->get_default() ).__( 'sample-production','wp_theatre' ),
			),
		);

		$option_checked = false;

		foreach ( $permalink_options as $name => $args ) {
			$html .= '<tr>';
			$html .= '<th>';
			$html .= '<label>';
			$html .= '<input name="wpt_production_permalink" type="radio" value="'.$args['structure'].'"';
			$html .= ' '.checked( $args['structure'], $this->get_permalink(), false ).' />';
			$html .= ' '.$args['title'];
			$html .= '</label>';
			$html .= '</th>';
			$html .= '<td><code>'.$args['example'].'</code></td>';
			$html .= '</tr>';

			if ( $args['structure'] == $this->get_permalink() ) {
				$option_checked = true;
			}
		}

		$html .= '<tr>';
		$html .= '<th>';
		$html .= '<label>';
		$html .= '<input name="wpt_production_permalink" type="radio" value="custom" '.checked( $option_checked, false, false ).' />';
		$html .= __( 'Custom Base', 'wp_theatre' );
		$html .= '</label>';
		$html .= '</th>';
		$html .= '<td>';
		$html .= '<code>'.untrailingslashit( home_url() ).'</code>';
		$html .= '<input name="wpt_production_permalink_custom" type="text" value="'.esc_attr( $this->get_permalink() ).'" class="regular-text code">';
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '</tbody>';
		$html .= '</table>';

		echo $html;
	}

}