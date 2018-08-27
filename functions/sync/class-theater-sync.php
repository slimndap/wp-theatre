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
		define( 'THEATER_SYNC_BOT_STATUS_CONFIGURING', 80 );
		define( 'THEATER_SYNC_BOT_STATUS_READY', 100 );
	}
	
	
}

Theater_Sync::init();