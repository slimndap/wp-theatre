<?php
/**
 * Theater_Sync_Admin class.
 *
 * @author	Jeroen Schmit
 */
class Theater_Sync_Admin {

	static function init() {
		add_filter ( 'wpt/productions/admin/tabs', array( __CLASS__, 'add_tab' ) );
		add_action ( 'wpt/productions/admin/tab?tab=sync', array( __CLASS__, 'tab_html' ) );
		add_action ( 'wpt/productions/admin/tab/actions?tab=sync', array( __CLASS__, 'tab_actions_html' ) );
		
		add_filter ( 'admin_init', array( __CLASS__, 'create_🤖' ) );
		add_filter ( 'admin_init', array( __CLASS__, 'activate_license' ) );
	}
	
	
	static function activate_license() {
		
		if ( empty( $_GET['🤖'] ) ) {
			return;
		}
		
		
		if ( empty( $_POST[ 'license_key' ] ) ) {
			return;
		}
		
		if ( empty( $_POST['nonce' ] ) ) {
			return;
		}
		
		if ( ! wp_verify_nonce( $_POST['nonce' ], 'activate_license' ) ) {
			wp_die();
		}
			
		$🤖 = new Theater_Sync_🤖( $_GET['🤖'] );
		
		$provider = Theater_Sync_Data::get_provider( $🤖->get( 'provider' ) );
		
		$license = Theater_Sync_Mother::activate_license( $_POST[ 'license_key' ], $provider );
		
		$🤖->set( 'license_key', $_POST[ 'license_key' ] );
		$🤖->set( 'license', $license );
		$🤖->save();
		
	}
	
	static function add_tab( $tabs ) {
		$tabs['sync'] =  __( 'TicketSync', 'theatre' );

		return $tabs;
	}
	
	static function get_url( $args = array() ) {		
		$url = admin_url( 'admin.php?page=theater-events&tab=sync' );
		$url = add_query_arg( $args, $url );
		return $url;
	}
	
	static function tab_actions_html( $tab ) {
		?><a href="<?php echo self::get_url( array( 'action' => 'add🤖' ) ); ?>" class="page-title-action">
			<?php _e( 'Connect more providers', 'theatre' ); ?>
		</a><?php
	}
		
	static function tab_html( $tab ) {
		
		$action = empty( $_GET[ 'action' ] ) ? '' : $_GET[ 'action' ];

		switch( $_GET[ 'action' ] ) {
			
			case 'add🤖' :
				echo self::get_addnew_html();
				break;
			case 'activate🤖' :
				echo self::get_edit_html();
				break;
			default:
			
				$🤖🤖🤖 = Theater_Sync_Data::get_🤖🤖🤖();
				
				if ( empty( $🤖🤖🤖 ) ) {
					echo self::get_onboarding_html();						
				} else {
					echo self::get_🤖🤖🤖_html();											
				}
			
			
			
		}
		
	}
	
	static function create_🤖() {
		
		if ( empty( $_GET[ 'provider' ] ) ) {
			return;
		}
		
		if ( empty( $_GET['nonce' ] ) ) {
			return;
		}
		
		if ( ! wp_verify_nonce( $_GET['nonce' ], 'create🤖' ) ) {
			wp_die();
		}
		
		$🤖 = new Theater_Sync_🤖();
		$🤖->set( 'provider', $_GET[ 'provider' ] );
		$🤖 = $🤖->save();

		$args = array(
			'action' => 'activate🤖',
			'🤖' => $🤖->get('ID'),
		);

		wp_redirect( self::get_url( $args ) );
		exit;
	}
	
	static function get_addnew_html() {
		ob_start();
		?><h3><?php
			_e( 'Select your ticketing provider', 'theatre' );
		?></h3><?php
		
		?><div class="wp-list-table theater-sync-addnew widefat">
			<div id="the-list"><?php
			foreach( Theater_Sync_Data::get_providers() as $provider ) {

				$args = array(
					'action' => 'create🤖',
					'provider' => $provider->slug,
					'nonce' => wp_create_nonce( 'create🤖' ),
				);
				$url = self::get_url( $args );
				
				?><div class="plugin-card plugin-card-akismet">
					<div class="plugin-card-top">
						<div class="name column-name">
							<h3><?php 
								echo $provider->title; 
							?><img src="data:image/svg+xml;base64,<?php echo base64_encode( $provider->logo ); ?>" class="plugin-icon" alt="<?php printf( __( '%s logo', 'theatre' ), $provider->title ); ?>">
						</h3>
						</div>
						<div class="action-links">
							<ul class="plugin-action-buttons">
								<li>
									<a href="<?php echo $url; ?>" class="button"><?php
										_e( 'Connect', 'theatre' ); 
									?></a>
								</li>
								<li><?php
									printf( __( '&euro; %d/month', 'theatre' ), 40 );
								?></li>
							</ul>
						</div>
					</div>
				</div><?php
			}				
			?></div>
		</div>
		<div class="missing"><?php
			printf( __( 'Is your ticketing provider missing? <a href="%s">Let me know</a>, so I can add it.', 'theater' ), 'https://wp.theatre');
		?></div><?php
	}
	
	static function get_🤖🤖🤖_html() {
		
		ob_start();		
		?>
		<table class="theater-sync-🤖🤖🤖 wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th class="col-logos"></th>
					<th><?php _e( 'Ticketing provider', 'theatre' ); ?></th>
					<th><?php _e( 'Status', 'theatre' ); ?></th>
				</tr>
			</thead><?php
			foreach ( Theater_Sync_Data::get_🤖🤖🤖() as $🤖 ) {
				
				$args = array(
					'action' => 'activate🤖',
					'🤖' => $🤖->get('ID'),
				);
				
				$provider = Theater_Sync_Data::get_provider( $🤖->get('provider') );
				
				?>
				
				<tr>
					<td class="logos">
						<span class="dashicons dashicons-wordpress"></span>
						<div class="status status-connecting">
							<span>connecting</span>
						</div>
						<img src="data:image/svg+xml;base64,<?php echo base64_encode( $provider->logo ); ?>" alt="<?php printf( __( '%s logo', 'theatre' ), $provider->title ); ?>">
					</td>
					<td>
						<a href="<?php echo self::get_url( $args ); ?>"><?php 
							echo $provider->title; 
						?></a>
					</td>
					<td>Please enter activation key</td>
				</tr><?php
			}			
		?></table><?php
		return ob_get_clean();
		
	}
	
	static function get_edit_html() {
		
		if ( empty( $_GET[ '🤖' ] ) ) {
			wp_die();
		}
		
		$🤖 = new Theater_Sync_🤖( $_GET[ '🤖' ] );
		print_r($🤖);
		$provider = Theater_sync_Data::get_provider( $🤖->get( 'provider' ) );
		
		ob_start();
		?>
		<div class="theater-sync-edit">
			<h3>
				<img src="data:image/svg+xml;base64,<?php echo base64_encode( $provider->logo ); ?>" alt="<?php printf( __( '%s logo', 'theatre' ), $provider->title ); ?>"> <?php 
				printf( __( 'Setup %s sync', 'theatre' ), $provider->title ); ?>
			</h3>
			<ul class="steps">
				<li class="activate<?php if (THEATER_SYNC_BOT_STATUS_ACTIVATED > $🤖->get( 'status' ) ) { ?> step-active<?php } ?>"><?php
					_e( 'Activate', 'theatre' );				
				?></li>
				<li class="install<?php if (THEATER_SYNC_BOT_STATUS_CONFIGURING > $🤖->get( 'status' ) ) { ?> step-active<?php } ?>"><?php
					_e( 'Install', 'theatre' );				
				?></li>
				<li class="configure"><?php
					_e( 'Configure', 'theatre' );				
				?></li>
				<li class="ready"><?php
					_e( 'Ready', 'theatre' );				
				?></li>
			</ul>
			<div class="activate"><?php
				if ( THEATER_SYNC_BOT_STATUS_ACTIVATED > $🤖->get( 'status' ) ) {
					
					?><a href="<?php echo $provider->add_to_cart; ?>" class="button-primary"><?php 
						_e('Purchase activation key', 'theatre'); 
					?></a>
					<button class="button"><?php _e( 'Enter activation key', 'theatre' ); ?></button>
					<p>&euro; 40/month. You will be billed monthly and can cancel at any time. </p>
					
	
					<form method="post"><?php
						wp_nonce_field( 'activate_license', 'nonce', true, true );
						?><label><?php _e( 'Enter the activation key from the confirmation e-mail:', 'theatre' ); ?></label>
						<input type="text" name="license_key" class="regular-text" value="<?php echo $🤖->get('license_key' ); ?>" required />
						<input type="submit" value="Activate" class="button" diisabled />
					</form><?php
				}
					
			?></div>
			<div class="install">
				Please wait while we are installing your sync. This can take up to two minutes.
			</div>
			<div class="configure">
			</div>
			<div class="ready">
			</div>
		</div><?php
		return ob_get_clean();
	}
	
	static function get_onboarding_html() {
		ob_start();
		?><h3><?php
			_e( 'Tired of manually entering your events?', 'theatre' );
		?></h3>
		<p>You really need TicketSync!</p>
		<p><a href="<?php echo self::get_url( array( 'action' => 'add🤖' ) ); ?>" class="button-primary"><?php _e( 'Connect your tickets', 'theater' ); ?></a></p><?php
		return ob_get_clean();
	}

}
Theater_Sync_Admin::init();