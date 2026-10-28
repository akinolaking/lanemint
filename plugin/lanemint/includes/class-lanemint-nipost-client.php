<?php

defined( 'ABSPATH' ) || exit;

final class LaneMint_NIPOST_Client {
	const BASE_URL = 'https://api.postcode.gov.ng';

	public function lookup( $postcode, $level = 1 ) {
		$postcode = strtoupper( sanitize_text_field( $postcode ) );
		$level    = min( 5, max( 1, absint( $level ) ) );
		$key      = $this->api_key();
		if ( '' === $key ) {
			return new WP_Error( 'missing_api_key', __( 'Add a NIPOST Postcode API key in LaneMint settings.', 'lanemint' ) );
		}

		$cache_key = 'lanemint_pc_' . md5( $postcode . ':' . $level );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$url = add_query_arg( array( 'code' => $postcode, 'level' => $level ), self::BASE_URL . '/v1/lookup' );
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 0,
				'headers'     => array( 'X-API-Key' => $key, 'Accept' => 'application/json' ),
				'user-agent'  => 'LaneMint/' . LANEMINT_VERSION . '; WordPress/' . get_bloginfo( 'version' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'nipost_unreachable', __( 'The NIPOST Postcode service could not be reached.', 'lanemint' ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $status || ! is_array( $body ) ) {
			$code = isset( $body['error']['code'] ) ? sanitize_key( $body['error']['code'] ) : 'http_' . absint( $status );
			return new WP_Error( $code, $this->safe_error_message( $status ) );
		}

		$data = isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : array();
		set_transient( $cache_key, $data, HOUR_IN_SECONDS );
		return $data;
	}

	public static function valid_format( $postcode ) {
		return 1 === preg_match( '/^[A-Z]{2}-?\d{2}-?[A-Z0-9]{3}-?[A-Z]{2}-?\d{2}$/i', trim( (string) $postcode ) );
	}

	private function api_key() {
		if ( defined( 'LANEMINT_NIPOST_API_KEY' ) && is_string( LANEMINT_NIPOST_API_KEY ) ) {
			return trim( LANEMINT_NIPOST_API_KEY );
		}
		return trim( (string) get_option( 'lanemint_nipost_api_key', '' ) );
	}

	private function safe_error_message( $status ) {
		$messages = array(
			401 => __( 'The NIPOST API key is missing or invalid.', 'lanemint' ),
			402 => __( 'The NIPOST account does not have enough credits.', 'lanemint' ),
			403 => __( 'The NIPOST API key does not have the required lookup access.', 'lanemint' ),
			404 => __( 'That digital postcode was not found.', 'lanemint' ),
			429 => __( 'The NIPOST API rate limit was reached. Please try again shortly.', 'lanemint' ),
		);
		return $messages[ $status ] ?? __( 'The NIPOST Postcode service returned an unexpected response.', 'lanemint' );
	}
}

