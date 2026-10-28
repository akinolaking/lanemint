<?php

defined( 'ABSPATH' ) || exit;

final class LaneMint_Checkout {
	public static function init() {
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'label_postcode' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_postcode' ), 10, 2 );
	}

	public static function label_postcode( $fields ) {
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			$key = $group . '_postcode';
			if ( isset( $fields[ $group ][ $key ] ) ) {
				$fields[ $group ][ $key ]['label']       = __( 'Postcode / NIPOST Digital Postcode', 'lanemint' );
				$fields[ $group ][ $key ]['placeholder'] = __( 'e.g. FC-01-A01-KP-27', 'lanemint' );
			}
		}
		return $fields;
	}

	public static function validate_postcode( $data, $errors ) {
		$mode = get_option( 'lanemint_nipost_mode', 'format' );
		if ( 'off' === $mode ) {
			return;
		}

		$prefix   = ! empty( $data['ship_to_different_address'] ) ? 'shipping_' : 'billing_';
		$country  = strtoupper( (string) ( $data[ $prefix . 'country' ] ?? '' ) );
		$postcode = trim( (string) ( $data[ $prefix . 'postcode' ] ?? '' ) );
		if ( 'NG' !== $country || '' === $postcode ) {
			return;
		}

		if ( ! LaneMint_NIPOST_Client::valid_format( $postcode ) ) {
			$errors->add( 'lanemint_postcode_format', __( 'Enter a valid NIPOST Digital Postcode, for example FC-01-A01-KP-27.', 'lanemint' ) );
			return;
		}

		if ( 'api' === $mode ) {
			$client = new LaneMint_NIPOST_Client();
			$result = $client->lookup( $postcode, (int) get_option( 'lanemint_nipost_lookup_level', 1 ) );
			if ( is_wp_error( $result ) ) {
				$errors->add( 'lanemint_postcode_lookup', $result->get_error_message() );
				LaneMint_Telemetry::event( 'error', array( 'code' => $result->get_error_code(), 'area' => 'nipost_lookup' ) );
			}
		}
	}
}

