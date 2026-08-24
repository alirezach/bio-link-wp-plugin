<?php
/**
 * Bio Link - Logger
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bio_Link_Logger {

	private static $log_file = 'bio-link-debug.log';

	public static function is_enabled() {
		return (bool) get_option( 'bio_link_debug_enabled', 1 );
	}

	public static function enable( $enable = true ) {
		update_option( 'bio_link_debug_enabled', $enable ? 1 : 0 );
	}

	public static function log( $level, $message ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$log = get_option( 'bio_link_debug_log', array() );
		$log[] = array(
			'time'    => current_time( 'mysql' ),
			'level'   => $level,
			'message' => $message,
		);

		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}

		update_option( 'bio_link_debug_log', $log );
	}

	public static function get_log() {
		return get_option( 'bio_link_debug_log', array() );
	}

	public static function clear() {
		delete_option( 'bio_link_debug_log' );
	}

	public static function to_file( $level, $message ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$upload_dir = wp_upload_dir();
		$path = $upload_dir['basedir'] . '/bio-link-debug.log';
		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$entry = "[{$timestamp}] [{$level}] {$message}\n";
		@file_put_contents( $path, $entry, FILE_APPEND | LOCK_EX );
	}
}
