<?php
class Theater_Sync {
	
	static function init() {

		self::define_constants();

		require_once( THEATER_PLUGIN_PATH . '/functions/sync/class-theater-sync-admin.php' );
		require_once( THEATER_PLUGIN_PATH . '/functions/sync/class-theater-sync-mother.php' );
		require_once( THEATER_PLUGIN_PATH . '/functions/sync/class-theater-sync-bot.php' );
		require_once( THEATER_PLUGIN_PATH . '/functions/sync/class-theater-sync-data.php' );

	}
	
	static function define_constants() {
		define( 'THEATER_SYNC_MOTHER_URL', 'https://wp.theater' );
				
		define( 'THEATER_SYNC_BOT_STATUS_NEW', 20 );
		define( 'THEATER_SYNC_BOT_STATUS_ACTIVATED', 40 );
		define( 'THEATER_SYNC_BOT_STATUS_PROVISIONING', 60 );
		define( 'THEATER_SYNC_BOT_STATUS_PROVISIONED', 80 );
		define( 'THEATER_SYNC_BOT_STATUS_CONFIGURING', 100 );
		define( 'THEATER_SYNC_BOT_STATUS_READY', 120 );
		define( 'THEATER_SYNC_BOT_STATUS_WORKING', 140 );
	}
	
	
}

Theater_Sync::init();