<?php

namespace No3x\WPML;

use WordPress\ORM\Model\WPML_Mail as Mail;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Log rotation for database.
 * @author No3x
 * @since 1.4
 */
class WPML_LogRotation extends WPML_Plugin {

	const WPML_LOGROTATION_SCHEDULE_HOOK = 'wpml_log_rotation';
	const WPML_LOGROTATION_SCHEDULE = 'LogRotationSchedule';

	public static function init() {
		add_action( self::WPML_LOGROTATION_SCHEDULE_HOOK , array( __NAMESPACE__ . '\\WPML_LogRotation', self::WPML_LOGROTATION_SCHEDULE) );
		$logRotation = new WPML_LogRotation();
		register_deactivation_hook( plugin_dir_path( __FILE__ ). $logRotation->getMainPluginFileName(), array( $logRotation, 'unschedule' ) );
	}

	public function __construct() {
		global $wpml_settings;

		if ( isset( $wpml_settings ) ) {
			if ( $wpml_settings['log-rotation-limit-amout'] == '1' || $wpml_settings['log-rotation-delete-time'] == '1' ) {
				$this->schedule();
			} else {
				$this->unschedule();
			}
		}
	}

	/**
	 * Schedules an event.
	 * @since 1.4
	 */
	function schedule() {
		if ( ! wp_next_scheduled( self::WPML_LOGROTATION_SCHEDULE_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::WPML_LOGROTATION_SCHEDULE_HOOK );
		}
	}

	/**
	 * Unschedules an event.
	 * @since 1.4
	 */
	function unschedule() {
		wp_clear_scheduled_hook( self::WPML_LOGROTATION_SCHEDULE_HOOK );
	}

	/**
	 * The LogRotation supports the limitation of stored mails by amount.
	 * @since 1.6.0.
	 */
	static function limitNumberOfMailsByAmount() {
		global $wpml_settings, $wpdb;
		$tableName = WPML_Plugin::getTablename( 'mails' );
		
		if ( $wpml_settings['log-rotation-limit-amout'] == '1') {
			$keep = $wpml_settings['log-rotation-limit-amout-keep'];
			if ( $keep > 0 ) {
				$wpdb->query(
						"DELETE p
						FROM
						$tableName AS p
						JOIN
						( SELECT mail_id
						FROM $tableName
						ORDER BY mail_id DESC
						LIMIT 1 OFFSET $keep
				) AS lim
						ON p.mail_id <= lim.mail_id;"
				);
			}
		}
	}

	/**
	 * The LogRotation supports the limitation of stored mails by date.
	 * @since 1.6.0.
	 */
	static function limitNumberOfMailsByTime() {
		global $wpml_settings, $wpdb;
		$tableName = WPML_Plugin::getTablename( 'mails' );
		
		if ( $wpml_settings['log-rotation-delete-time'] == '1') {
			$days = $wpml_settings['log-rotation-delete-time-days'];
			if ( $days > 0 ) {
				$wpdb->query( "DELETE FROM `$tableName` WHERE DATEDIFF( NOW(), `timestamp`) >= $days" );
			}
		}
	}
	
	/**
	 * Executes log rotation periodically.
	 * @since 1.4
	 */
	static function LogRotationSchedule() {
		self::limitNumberOfMailsByAmount();
		self::limitNumberOfMailsByTime();
	}
}