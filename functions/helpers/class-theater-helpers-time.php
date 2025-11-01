<?php
	
/**
 * Time-related helper functions.
 * @since	0.15.11
 * @package	Theater/Helpers
 */
class Theater_Helpers_Time {
	
	/**
	 * Gets the next day start time offset.
	 * 
	 * The next day start time offset is used to determine on which day an event should appear in lists.
	 * Eg. if the offset is '2 * HOUR_IN_SECONDS' then all events that start before 2 AM are treated as if 
	 * they start on the previous day.
	 *
	 * @since	0.15.11
	 * @return	int		The offset in seconds.
	 */
	static function get_next_day_start_time_offset() {

		$next_day_start_time_offset = 0;

		/**
		 * Filters the next day start time offset.
		 * 
		 * @since	0.15.11
		 * @since	0.15.14	Changed filtername to lowercase.
		 * 
		 * @var 	int		The offset in seconds.
		 */
		$next_day_start_time_offset = apply_filters('theater/helpers/time/next_day_start_time_offset', $next_day_start_time_offset);

		return $next_day_start_time_offset;
		
	}

	/**
	 * Gets the site's timezone as a DateTimeZone instance.
	 *
	 * Falls back to UTC when no timezone string is configured. This mirrors how WordPress core
	 * resolves the timezone for date utilities and keeps the math predictable for DST-aware code.
	 *
	 * @since 0.17.0
	 * @return DateTimeZone Site timezone.
	 */
	public static function get_timezone() {
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}

		$timezone_string = get_option( 'timezone_string' );

		if ( ! empty( $timezone_string ) ) {
			try {
				return new DateTimeZone( $timezone_string );
			} catch ( Exception $exception ) {
				// Fall through to the GMT offset fallback below when the stored value is invalid.
			}
		}

		/*
		 * Sites without a timezone string only store a numeric GMT offset, which
		 * can't express historical DST rules. We map the offset to a timezone
		 * abbreviation when possible and otherwise fall back to UTC.
		 */
		$offset = (float) get_option( 'gmt_offset', 0 );
		$seconds_offset = (int) round( $offset * HOUR_IN_SECONDS );
		$timezone_name  = timezone_name_from_abbr( '', $seconds_offset, 0 );

		if ( false !== $timezone_name ) {
			return new DateTimeZone( $timezone_name );
		}

		return new DateTimeZone( 'UTC' );
	}

	/**
	 * Converts a human-readable time string into a DateTimeImmutable object using the site timezone.
	 *
	 * The helper intentionally mirrors the old `strtotime( $string, current_time( 'timestamp' ) )`
	 * pattern that Theater used historically. We start from the supplied base timestamp (or "now")
	 * and rely on DateTimeImmutable::modify() so relative expressions like "next Tuesday" keep working.
	 *
	 * @since 0.17.0
	 *
	 * @param string     $time_string   Time to parse (e.g. "now", "+2 hours", "2025-10-31 20:00").
	 * @param int|false  $base_timestamp Optional Unix timestamp to treat as the reference point.
	 *                                   When omitted the current local time is used.
	 *
	 * @return DateTimeImmutable Parsed local datetime.
	 */
	public static function get_local_datetime_from_string( $time_string, $base_timestamp = false ) {
		$timezone = self::get_timezone();

		if ( false !== $base_timestamp && is_numeric( $base_timestamp ) ) {
			$base = new DateTimeImmutable( '@' . (int) $base_timestamp );
			$base = $base->setTimezone( $timezone );
		} else {
			$base = new DateTimeImmutable( 'now', $timezone );
		}

		if ( empty( $time_string ) ) {
			return $base;
		}

		/*
		 * DateTimeImmutable::modify( 'now' ) changes the reference to the real "now",
		 * whereas the legacy strtotime( 'now', $base ) kept the base timestamp intact.
		 * We guard that specific string so the legacy behaviour survives.
		 */
		if ( 'now' === strtolower( trim( $time_string ) ) ) {
			return $base;
		}

		$modified = $base->modify( $time_string );

		if ( false === $modified ) {
			return $base;
		}

		return $modified;
	}

	/**
	 * Converts a human-readable time string to a UTC timestamp.
	 *
	 * @since 0.17.0
	 *
	 * @param string     $time_string   Time to parse.
	 * @param int|false  $base_timestamp Optional base timestamp for relative expressions.
	 *
	 * @return int UTC timestamp.
	 */
	public static function get_utc_timestamp_from_local_string( $time_string, $base_timestamp = false ) {
		$datetime = self::get_local_datetime_from_string( $time_string, $base_timestamp );

		return $datetime
			->setTimezone( new DateTimeZone( 'UTC' ) )
			->getTimestamp();
	}

	/**
	 * Gets the timezone offset (in seconds) for the supplied UTC timestamp.
	 *
	 * Passing a timestamp makes the offset DST-aware, so future and past events receive
	 * the correct adjustment instead of a hardcoded `get_option( 'gmt_offset' )`.
	 *
	 * @since 0.17.0
	 *
	 * @param int|false $timestamp UTC timestamp. Defaults to "now" when omitted.
	 *
	 * @return int Offset in seconds.
	 */
	public static function get_timezone_offset_in_seconds( $timestamp = false ) {
		$timezone = self::get_timezone();

		if ( false === $timestamp ) {
			$datetime = new DateTimeImmutable( 'now', $timezone );
			return (int) $timezone->getOffset( $datetime );
		}

		if ( ! is_numeric( $timestamp ) ) {
			return 0;
		}

		$datetime = new DateTimeImmutable( '@' . (int) $timestamp );

		return (int) $timezone->getOffset( $datetime );
	}

	/**
	 * Converts a UTC timestamp to the local timestamp used by date() / date_i18n().
	 *
	 * @since 0.17.0
	 *
	 * @param int $utc_timestamp UTC timestamp.
	 *
	 * @return int Local timestamp.
	 */
	public static function get_local_timestamp_from_utc( $utc_timestamp ) {
		if ( ! is_numeric( $utc_timestamp ) ) {
			return (int) $utc_timestamp;
		}

		return (int) $utc_timestamp + self::get_timezone_offset_in_seconds( $utc_timestamp );
	}

	/**
	 * Gets the current UTC timestamp using the site's timezone rules.
	 *
	 * @since 0.17.0
	 *
	 * @return int UTC timestamp.
	 */
	public static function current_time_in_utc() {
		return self::get_utc_timestamp_from_local_string( 'now' );
	}
	
}
