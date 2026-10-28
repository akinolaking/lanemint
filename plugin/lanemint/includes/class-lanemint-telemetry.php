<?php

defined( 'ABSPATH' ) || exit;

final class LaneMint_Telemetry {
	const DEFAULT_ENDPOINT = 'https://lanemint-telemetry.vercel.app';

	public static function init() {
		add_action( 'lanemint_telemetry_heartbeat', array( __CLASS__, 'heartbeat' ) );
		if ( 'yes' === get_option( 'lanemint_telemetry_enabled', 'no' ) && ! wp_next_scheduled( 'lanemint_telemetry_heartbeat' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'weekly', 'lanemint_telemetry_heartbeat' );
		}
	}

	public static function heartbeat() {
		self::event( 'heartbeat' );
	}

	public static function event( $event, array $context = array() ) {
		if ( 'yes' !== get_option( 'lanemint_telemetry_enabled', 'no' ) ) {
			return;
		}

		$allowed = array( 'activation', 'deactivation', 'heartbeat', 'error', 'csv_import' );
		$event   = sanitize_key( $event );
		if ( ! in_array( $event, $allowed, true ) ) {
			return;
		}

		$endpoint = untrailingslashit( esc_url_raw( apply_filters( 'lanemint_telemetry_endpoint', self::DEFAULT_ENDPOINT ) ) );
		$token    = get_option( 'lanemint_telemetry_token', '' );
		if ( '' === $token ) {
			$token = self::register_install( $endpoint );
			if ( '' === $token ) {
				return;
			}
		}

		$payload = array(
			'install_id'     => get_option( 'lanemint_install_id' ),
			'event'          => $event,
			'plugin_version' => LANEMINT_VERSION,
			'wordpress'      => get_bloginfo( 'version' ),
			'woocommerce'    => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'php'            => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
			'code'           => sanitize_key( $context['code'] ?? '' ),
			'area'           => sanitize_key( $context['area'] ?? '' ),
			'occurred_at'    => gmdate( 'c' ),
		);

		wp_safe_remote_post(
			$endpoint . '/api/v1/events',
			array(
				'timeout'     => 3,
				'blocking'    => false,
				'redirection' => 0,
				'headers'     => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( $payload ),
			)
		);
	}

	private static function register_install( $endpoint ) {
		$response = wp_safe_remote_post(
			$endpoint . '/api/v1/register',
			array(
				'timeout'     => 5,
				'redirection' => 0,
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( array( 'install_id' => get_option( 'lanemint_install_id' ), 'plugin_version' => LANEMINT_VERSION ) ),
			)
		);
		if ( is_wp_error( $response ) || 201 !== wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}
		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$token = isset( $body['token'] ) ? sanitize_text_field( $body['token'] ) : '';
		if ( '' !== $token ) {
			update_option( 'lanemint_telemetry_token', $token, false );
		}
		return $token;
	}
}

