<?php

defined( 'ABSPATH' ) || exit;

final class LaneMint_Rate_Repository {
	const MAX_ROWS = 10000;

	public function import_csv( $partner_name, $file_path ) {
		global $wpdb;

		$partner_name = sanitize_text_field( $partner_name );
		if ( '' === $partner_name ) {
			return new WP_Error( 'missing_partner', __( 'Enter a delivery partner name.', 'lanemint' ) );
		}

		$handle = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			return new WP_Error( 'unreadable_csv', __( 'The CSV file could not be read.', 'lanemint' ) );
		}

		$header = fgetcsv( $handle );
		if ( ! is_array( $header ) ) {
			fclose( $handle );
			return new WP_Error( 'empty_csv', __( 'The CSV file is empty.', 'lanemint' ) );
		}

		$header = array_map( array( $this, 'canonical_header' ), $header );
		$required = array( 'service', 'price' );
		foreach ( $required as $column ) {
			if ( ! in_array( $column, $header, true ) ) {
				fclose( $handle );
				return new WP_Error( 'missing_column', sprintf( __( 'Missing required column: %s.', 'lanemint' ), $column ) );
			}
		}

		$rows = array();
		$line = 1;
		while ( ( $values = fgetcsv( $handle ) ) !== false ) {
			++$line;
			if ( count( $rows ) >= self::MAX_ROWS ) {
				fclose( $handle );
				return new WP_Error( 'too_many_rows', sprintf( __( 'A CSV may contain at most %d data rows.', 'lanemint' ), self::MAX_ROWS ) );
			}
			if ( 1 === count( $values ) && '' === trim( (string) $values[0] ) ) {
				continue;
			}
			$values = array_pad( $values, count( $header ), '' );
			$row    = array_combine( $header, array_slice( $values, 0, count( $header ) ) );
			$row    = $this->validate_row( $row, $line );
			if ( is_wp_error( $row ) ) {
				fclose( $handle );
				return $row;
			}
			$rows[] = $row;
		}
		fclose( $handle );

		if ( empty( $rows ) ) {
			return new WP_Error( 'no_rates', __( 'No valid rate rows were found.', 'lanemint' ) );
		}

		$partners_table = $wpdb->prefix . 'lanemint_partners';
		$rates_table    = $wpdb->prefix . 'lanemint_rates';
		$slug           = sanitize_title( $partner_name );
		$now            = current_time( 'mysql', true );

		$wpdb->query( 'START TRANSACTION' );
		$partner_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$partners_table} WHERE slug = %s", $slug ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $partner_id ) {
			$wpdb->update( $partners_table, array( 'name' => $partner_name, 'updated_at' => $now ), array( 'id' => $partner_id ), array( '%s', '%s' ), array( '%d' ) );
			$wpdb->delete( $rates_table, array( 'partner_id' => $partner_id ), array( '%d' ) );
		} else {
			$wpdb->insert(
				$partners_table,
				array( 'name' => $partner_name, 'slug' => $slug, 'enabled' => 1, 'created_at' => $now, 'updated_at' => $now ),
				array( '%s', '%s', '%d', '%s', '%s' )
			);
			$partner_id = (int) $wpdb->insert_id;
		}

		if ( ! $partner_id ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'partner_save_failed', __( 'The delivery partner could not be saved.', 'lanemint' ) );
		}

		foreach ( $rows as $row ) {
			$row['partner_id'] = $partner_id;
			$ok = $wpdb->insert(
				$rates_table,
				$row,
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%d', '%d', '%d' )
			);
			if ( false === $ok ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'rate_save_failed', __( 'A database error occurred while saving the rate card.', 'lanemint' ) );
			}
		}

		$wpdb->query( 'COMMIT' );
		return array( 'partner_id' => $partner_id, 'imported' => count( $rows ) );
	}

	public function get_matching_rates( array $destination, $weight ) {
		global $wpdb;
		$partners = $wpdb->prefix . 'lanemint_partners';
		$rates    = $wpdb->prefix . 'lanemint_rates';
		$country  = strtoupper( sanitize_text_field( $destination['country'] ?? '' ) );

		$sql = $wpdb->prepare(
			"SELECT r.*, p.name AS partner_name
			FROM {$rates} r
			INNER JOIN {$partners} p ON p.id = r.partner_id
			WHERE p.enabled = 1 AND r.active = 1
			AND (r.country = '' OR r.country = %s)
			AND r.min_weight <= %f AND r.max_weight >= %f
			ORDER BY r.priority DESC, r.price ASC, r.id ASC",
			$country,
			(float) $weight,
			(float) $weight
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$candidates = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$context    = $this->destination_context( $destination );
		$matched    = array();

		foreach ( $candidates as $rate ) {
			if ( ! $this->field_matches( $rate['state'], $context['state'] ) ||
				! $this->field_matches( $rate['lga'], $context['lga'] ) ||
				! $this->field_matches( $rate['city'], $context['city'] ) ||
				! $this->postcode_matches( $rate['postcode_pattern'], $context['postcode'] ) ) {
				continue;
			}

			$key = (int) $rate['partner_id'] . ':' . sanitize_key( $rate['service'] );
			if ( ! isset( $matched[ $key ] ) ) {
				$matched[ $key ] = $rate;
			}
		}

		return array_values( $matched );
	}

	public function list_partners() {
		global $wpdb;
		$partners = $wpdb->prefix . 'lanemint_partners';
		$rates    = $wpdb->prefix . 'lanemint_rates';
		return $wpdb->get_results( "SELECT p.*, COUNT(r.id) AS rate_count FROM {$partners} p LEFT JOIN {$rates} r ON r.partner_id=p.id GROUP BY p.id ORDER BY p.name", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function canonical_header( $value ) {
		$value = strtolower( trim( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', '_', $value );
		$aliases = array( 'carrier_service' => 'service', 'cost' => 'price', 'rate' => 'price', 'postcode' => 'postcode_pattern', 'zip' => 'postcode_pattern', 'max_kg' => 'max_weight', 'min_kg' => 'min_weight' );
		return $aliases[ $value ] ?? trim( $value, '_' );
	}

	private function validate_row( array $row, $line ) {
		$price = isset( $row['price'] ) ? wc_format_decimal( $row['price'] ) : '';
		if ( '' === $price || ! is_numeric( $price ) || (float) $price < 0 ) {
			return new WP_Error( 'invalid_price', sprintf( __( 'Line %d has an invalid price.', 'lanemint' ), $line ) );
		}

		$min = isset( $row['min_weight'] ) && '' !== trim( $row['min_weight'] ) ? (float) wc_format_decimal( $row['min_weight'] ) : 0;
		$max = isset( $row['max_weight'] ) && '' !== trim( $row['max_weight'] ) ? (float) wc_format_decimal( $row['max_weight'] ) : 999999;
		if ( $min < 0 || $max < $min ) {
			return new WP_Error( 'invalid_weight', sprintf( __( 'Line %d has an invalid weight band.', 'lanemint' ), $line ) );
		}

		return array(
			'service'          => sanitize_text_field( $row['service'] ?: 'Standard' ),
			'country'          => strtoupper( substr( sanitize_text_field( $row['country'] ?? 'NG' ), 0, 2 ) ),
			'state'            => sanitize_text_field( $row['state'] ?? '' ),
			'lga'              => sanitize_text_field( $row['lga'] ?? '' ),
			'city'             => sanitize_text_field( $row['city'] ?? '' ),
			'postcode_pattern' => strtoupper( sanitize_text_field( $row['postcode_pattern'] ?? '' ) ),
			'min_weight'       => $min,
			'max_weight'       => $max,
			'price'            => (float) $price,
			'currency'         => strtoupper( substr( sanitize_text_field( $row['currency'] ?? get_woocommerce_currency() ), 0, 3 ) ),
			'priority'         => (int) ( $row['priority'] ?? 0 ),
			'active'           => isset( $row['active'] ) && in_array( strtolower( trim( $row['active'] ) ), array( '0', 'no', 'false', 'off' ), true ) ? 0 : 1,
		);
	}

	private function destination_context( array $destination ) {
		$state = sanitize_text_field( $destination['state'] ?? '' );
		$lga   = '';
		if ( false !== strpos( $state, '-' ) ) {
			$parts = explode( '-', $state, 2 );
			$state = $parts[0];
			$lga   = $parts[1];
		}
		return array(
			'state'    => $this->normalize( $state ),
			'lga'      => $this->normalize( $destination['lga'] ?? $lga ),
			'city'     => $this->normalize( $destination['city'] ?? '' ),
			'postcode' => strtoupper( preg_replace( '/[^A-Z0-9]/i', '', (string) ( $destination['postcode'] ?? '' ) ) ),
		);
	}

	private function field_matches( $rule, $actual ) {
		$rule = $this->normalize( $rule );
		return '' === $rule || $rule === $actual || false !== strpos( ' ' . $actual . ' ', ' ' . $rule . ' ' );
	}

	private function postcode_matches( $pattern, $postcode ) {
		$pattern = strtoupper( preg_replace( '/[^A-Z0-9*]/i', '', (string) $pattern ) );
		if ( '' === $pattern ) {
			return true;
		}
		$regex = '/^' . str_replace( '\\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';
		return 1 === preg_match( $regex, $postcode );
	}

	private function normalize( $value ) {
		$value = remove_accents( strtolower( (string) $value ) );
		return trim( preg_replace( '/[^a-z0-9]+/', ' ', $value ) );
	}
}

