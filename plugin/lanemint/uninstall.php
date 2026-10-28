<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( 'yes' !== get_option( 'lanemint_remove_data_on_uninstall', 'no' ) ) {
	return;
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lanemint_rates" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lanemint_partners" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

foreach ( array( 'lanemint_db_version', 'lanemint_nipost_mode', 'lanemint_nipost_lookup_level', 'lanemint_nipost_api_key', 'lanemint_telemetry_enabled', 'lanemint_telemetry_token', 'lanemint_install_id', 'lanemint_needs_zone_notice', 'lanemint_remove_data_on_uninstall' ) as $option ) {
	delete_option( $option );
}

