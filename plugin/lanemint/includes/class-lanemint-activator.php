<?php

defined( 'ABSPATH' ) || exit;

final class LaneMint_Activator {
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$partners = $wpdb->prefix . 'lanemint_partners';
		$rates    = $wpdb->prefix . 'lanemint_rates';

		dbDelta(
			"CREATE TABLE {$partners} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(191) NOT NULL,
				slug varchar(191) NOT NULL,
				enabled tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY slug (slug)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$rates} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				partner_id bigint(20) unsigned NOT NULL,
				service varchar(191) NOT NULL DEFAULT 'Standard',
				country char(2) NOT NULL DEFAULT 'NG',
				state varchar(191) NOT NULL DEFAULT '',
				lga varchar(191) NOT NULL DEFAULT '',
				city varchar(191) NOT NULL DEFAULT '',
				postcode_pattern varchar(64) NOT NULL DEFAULT '',
				min_weight decimal(10,3) NOT NULL DEFAULT 0,
				max_weight decimal(10,3) NOT NULL DEFAULT 999999,
				price decimal(12,2) NOT NULL,
				currency char(3) NOT NULL DEFAULT 'NGN',
				priority int(11) NOT NULL DEFAULT 0,
				active tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (id),
				KEY partner_id (partner_id),
				KEY destination (country,state,lga,city),
				KEY weight_band (min_weight,max_weight)
			) {$charset};"
		);

		add_option( 'lanemint_db_version', LANEMINT_VERSION, '', false );
		add_option( 'lanemint_nipost_mode', 'format', '', false );
		add_option( 'lanemint_nipost_lookup_level', 1, '', false );
		add_option( 'lanemint_telemetry_enabled', 'no', '', false );
		add_option( 'lanemint_install_id', wp_generate_uuid4(), '', false );
		add_option( 'lanemint_needs_zone_notice', 'yes', '', false );

		if ( 'yes' === get_option( 'lanemint_telemetry_enabled', 'no' ) ) {
			LaneMint_Telemetry::event( 'activation' );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'lanemint_telemetry_heartbeat' );
		if ( class_exists( 'LaneMint_Telemetry' ) && 'yes' === get_option( 'lanemint_telemetry_enabled', 'no' ) ) {
			LaneMint_Telemetry::event( 'deactivation' );
		}
	}
}

