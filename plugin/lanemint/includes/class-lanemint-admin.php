<?php

defined( 'ABSPATH' ) || exit;

final class LaneMint_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_lanemint_import_csv', array( __CLASS__, 'import_csv' ) );
		add_action( 'admin_post_lanemint_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_lanemint_test_nipost', array( __CLASS__, 'test_nipost' ) );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'LaneMint', 'lanemint' ),
			__( 'LaneMint', 'lanemint' ),
			'manage_woocommerce',
			'lanemint',
			array( __CLASS__, 'render' )
		);
	}

	public static function assets( $hook ) {
		if ( 'woocommerce_page_lanemint' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'lanemint-admin', LANEMINT_URL . 'assets/css/admin.css', array(), LANEMINT_VERSION );
		wp_enqueue_script( 'lanemint-admin', LANEMINT_URL . 'assets/js/admin.js', array(), LANEMINT_VERSION, true );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage LaneMint.', 'lanemint' ) );
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'rates';
		$tabs = array( 'rates' => __( 'Partners & rates', 'lanemint' ), 'nipost' => __( 'NIPOST', 'lanemint' ), 'diagnostics' => __( 'Diagnostics', 'lanemint' ) );
		?>
		<div class="wrap lanemint-wrap">
			<div class="lanemint-hero">
				<div><span class="lanemint-kicker"><?php esc_html_e( 'WooCommerce delivery rate manager', 'lanemint' ); ?></span><h1><?php esc_html_e( 'LaneMint', 'lanemint' ); ?></h1><p><?php esc_html_e( 'Turn carrier spreadsheets into dependable checkout choices.', 'lanemint' ); ?></p></div>
				<div class="lanemint-version"><?php echo esc_html( 'v' . LANEMINT_VERSION ); ?></div>
			</div>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'LaneMint settings', 'lanemint' ); ?>">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=lanemint&tab=' . $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<?php self::notice(); ?>
			<?php 'nipost' === $tab ? self::render_nipost() : ( 'diagnostics' === $tab ? self::render_diagnostics() : self::render_rates() ); ?>
		</div>
		<?php
	}

	private static function render_rates() {
		$partners = ( new LaneMint_Rate_Repository() )->list_partners();
		?>
		<div class="lanemint-grid">
			<section class="lanemint-card lanemint-card-primary">
				<div class="lanemint-step">1</div><h2><?php esc_html_e( 'Import a carrier rate card', 'lanemint' ); ?></h2>
				<p><?php esc_html_e( 'Upload one CSV per delivery partner. Re-importing the same partner replaces its previous rates safely.', 'lanemint' ); ?></p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="lanemint_import_csv">
					<?php wp_nonce_field( 'lanemint_import_csv', 'lanemint_nonce' ); ?>
					<label for="lanemint-partner"><?php esc_html_e( 'Delivery partner name', 'lanemint' ); ?></label>
					<input class="regular-text" id="lanemint-partner" name="partner_name" type="text" maxlength="191" required placeholder="<?php esc_attr_e( 'e.g. Acme Express', 'lanemint' ); ?>">
					<label class="lanemint-drop" for="lanemint-csv"><strong><?php esc_html_e( 'Choose a CSV file', 'lanemint' ); ?></strong><span><?php esc_html_e( 'Maximum 2 MB · up to 10,000 rows', 'lanemint' ); ?></span><input id="lanemint-csv" name="rate_card" type="file" accept=".csv,text/csv" required></label>
					<div class="lanemint-actions"><button class="button button-primary button-hero" type="submit"><?php esc_html_e( 'Validate & import', 'lanemint' ); ?></button><a class="button" href="<?php echo esc_url( LANEMINT_URL . 'sample-rate-card.csv' ); ?>" download><?php esc_html_e( 'Download template', 'lanemint' ); ?></a></div>
				</form>
			</section>
			<section class="lanemint-card">
				<div class="lanemint-step">2</div><h2><?php esc_html_e( 'Add LaneMint to a zone', 'lanemint' ); ?></h2>
				<p><?php esc_html_e( 'Open WooCommerce shipping zones, edit a zone, then add “LaneMint rate cards” as a shipping method.', 'lanemint' ); ?></p>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><?php esc_html_e( 'Open shipping zones', 'lanemint' ); ?></a>
			</section>
		</div>
		<section class="lanemint-card lanemint-table-card"><h2><?php esc_html_e( 'Imported partners', 'lanemint' ); ?></h2>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Partner', 'lanemint' ); ?></th><th><?php esc_html_e( 'Rates', 'lanemint' ); ?></th><th><?php esc_html_e( 'Status', 'lanemint' ); ?></th><th><?php esc_html_e( 'Last updated', 'lanemint' ); ?></th></tr></thead><tbody>
			<?php if ( empty( $partners ) ) : ?><tr><td colspan="4" class="lanemint-empty"><?php esc_html_e( 'No rate cards yet. Import your first CSV above.', 'lanemint' ); ?></td></tr><?php endif; ?>
			<?php foreach ( $partners as $partner ) : ?><tr><td><strong><?php echo esc_html( $partner['name'] ); ?></strong></td><td><?php echo esc_html( number_format_i18n( $partner['rate_count'] ) ); ?></td><td><span class="lanemint-pill"><?php echo $partner['enabled'] ? esc_html__( 'Active', 'lanemint' ) : esc_html__( 'Paused', 'lanemint' ); ?></span></td><td><?php echo esc_html( get_date_from_gmt( $partner['updated_at'], 'j M Y, H:i' ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table>
		</section>
		<?php
	}

	private static function render_nipost() {
		$mode  = get_option( 'lanemint_nipost_mode', 'format' );
		$level = (int) get_option( 'lanemint_nipost_lookup_level', 1 );
		$key   = get_option( 'lanemint_nipost_api_key', '' );
		?>
		<section class="lanemint-card lanemint-settings"><h2><?php esc_html_e( 'NIPOST Digital Postcode', 'lanemint' ); ?></h2><p><?php esc_html_e( 'API calls are made only from your WordPress server. Secret keys are never exposed to checkout browsers.', 'lanemint' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="lanemint_save_settings"><?php wp_nonce_field( 'lanemint_save_settings', 'lanemint_nonce' ); ?>
			<label for="lanemint-mode"><?php esc_html_e( 'Checkout validation', 'lanemint' ); ?></label><select id="lanemint-mode" name="nipost_mode"><option value="off" <?php selected( $mode, 'off' ); ?>><?php esc_html_e( 'Off', 'lanemint' ); ?></option><option value="format" <?php selected( $mode, 'format' ); ?>><?php esc_html_e( 'Check format locally', 'lanemint' ); ?></option><option value="api" <?php selected( $mode, 'api' ); ?>><?php esc_html_e( 'Verify with NIPOST API', 'lanemint' ); ?></option></select>
			<label for="lanemint-key"><?php esc_html_e( 'Secret API key', 'lanemint' ); ?></label><input class="regular-text" id="lanemint-key" name="nipost_api_key" type="password" autocomplete="new-password" placeholder="<?php echo $key ? esc_attr__( 'Saved — enter a new key to replace it', 'lanemint' ) : 'nipost_test_…'; ?>"><p class="description"><?php esc_html_e( 'For production, define LANEMINT_NIPOST_API_KEY in wp-config.php instead of storing the key in the database.', 'lanemint' ); ?></p>
			<label for="lanemint-level"><?php esc_html_e( 'Lookup level', 'lanemint' ); ?></label><select id="lanemint-level" name="nipost_lookup_level"><?php for ( $i = 1; $i <= 5; $i++ ) : ?><option value="<?php echo esc_attr( $i ); ?>" <?php selected( $level, $i ); ?>><?php echo esc_html( 'Level ' . $i ); ?></option><?php endfor; ?></select>
			<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Save NIPOST settings', 'lanemint' ); ?></button></p>
		</form></section>
		<?php
	}

	private static function render_diagnostics() {
		$enabled = get_option( 'lanemint_telemetry_enabled', 'no' );
		?>
		<section class="lanemint-card lanemint-settings"><h2><?php esc_html_e( 'Privacy-safe diagnostics', 'lanemint' ); ?></h2><p><?php esc_html_e( 'Optional diagnostics send only a random installation ID, software versions, lifecycle events and LaneMint error codes. No store URL, customer, order, address, postcode, product or price data is sent.', 'lanemint' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="lanemint_save_settings"><?php wp_nonce_field( 'lanemint_save_settings', 'lanemint_nonce' ); ?><label class="lanemint-toggle"><input type="checkbox" name="telemetry_enabled" value="yes" <?php checked( $enabled, 'yes' ); ?>><span><?php esc_html_e( 'Share anonymous diagnostics to help improve LaneMint', 'lanemint' ); ?></span></label><p><button class="button button-primary" type="submit"><?php esc_html_e( 'Save diagnostics preference', 'lanemint' ); ?></button></p></form>
		<hr><h3><?php esc_html_e( 'Environment', 'lanemint' ); ?></h3><dl class="lanemint-diagnostics"><dt>LaneMint</dt><dd><?php echo esc_html( LANEMINT_VERSION ); ?></dd><dt>WordPress</dt><dd><?php echo esc_html( get_bloginfo( 'version' ) ); ?></dd><dt>WooCommerce</dt><dd><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : '—' ); ?></dd><dt>PHP</dt><dd><?php echo esc_html( PHP_VERSION ); ?></dd></dl></section>
		<?php
	}

	public static function import_csv() {
		self::authorize( 'lanemint_import_csv' );
		if ( empty( $_FILES['rate_card'] ) || UPLOAD_ERR_OK !== (int) $_FILES['rate_card']['error'] ) {
			self::redirect( 'error', __( 'Choose a CSV file to import.', 'lanemint' ) );
		}
		if ( (int) $_FILES['rate_card']['size'] > 2 * MB_IN_BYTES ) {
			self::redirect( 'error', __( 'The CSV file exceeds the 2 MB limit.', 'lanemint' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload = wp_handle_upload( $_FILES['rate_card'], array( 'test_form' => false, 'mimes' => array( 'csv' => 'text/csv' ) ) );
		if ( isset( $upload['error'] ) || empty( $upload['file'] ) ) {
			self::redirect( 'error', isset( $upload['error'] ) ? sanitize_text_field( $upload['error'] ) : __( 'Upload failed.', 'lanemint' ) );
		}

		$repository = new LaneMint_Rate_Repository();
		$name       = isset( $_POST['partner_name'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_name'] ) ) : '';
		$result     = $repository->import_csv( $name, $upload['file'] );
		wp_delete_file( $upload['file'] );
		if ( is_wp_error( $result ) ) {
			LaneMint_Telemetry::event( 'error', array( 'code' => $result->get_error_code(), 'area' => 'csv_import' ) );
			self::redirect( 'error', $result->get_error_message() );
		}
		LaneMint_Telemetry::event( 'csv_import' );
		self::redirect( 'success', sprintf( __( 'Imported %d rates for %s.', 'lanemint' ), $result['imported'], $name ) );
	}

	public static function save_settings() {
		self::authorize( 'lanemint_save_settings' );
		if ( isset( $_POST['nipost_mode'] ) ) {
			$mode = sanitize_key( wp_unslash( $_POST['nipost_mode'] ) );
			update_option( 'lanemint_nipost_mode', in_array( $mode, array( 'off', 'format', 'api' ), true ) ? $mode : 'format', false );
			update_option( 'lanemint_nipost_lookup_level', min( 5, max( 1, absint( $_POST['nipost_lookup_level'] ?? 1 ) ) ), false );
			$key = isset( $_POST['nipost_api_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['nipost_api_key'] ) ) ) : '';
			if ( '' !== $key && preg_match( '/^nipost_(?:test|live)_[A-Za-z0-9_-]+$/', $key ) ) {
				update_option( 'lanemint_nipost_api_key', $key, false );
			}
		}
		$old = get_option( 'lanemint_telemetry_enabled', 'no' );
		$new = isset( $_POST['telemetry_enabled'] ) ? 'yes' : 'no';
		if ( isset( $_POST['telemetry_enabled'] ) || ! isset( $_POST['nipost_mode'] ) ) {
			update_option( 'lanemint_telemetry_enabled', $new, false );
			if ( 'yes' === $new && 'yes' !== $old ) {
				LaneMint_Telemetry::event( 'activation' );
			}
		}
		self::redirect( 'success', __( 'Settings saved.', 'lanemint' ), isset( $_POST['nipost_mode'] ) ? 'nipost' : 'diagnostics' );
	}

	public static function test_nipost() {}

	private static function authorize( $action ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'lanemint' ), esc_html__( 'Forbidden', 'lanemint' ), array( 'response' => 403 ) );
		}
		$nonce = isset( $_POST['lanemint_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['lanemint_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( esc_html__( 'The security check failed. Refresh the page and try again.', 'lanemint' ), esc_html__( 'Forbidden', 'lanemint' ), array( 'response' => 403 ) );
		}
	}

	private static function redirect( $type, $message, $tab = 'rates' ) {
		$url = add_query_arg( array( 'page' => 'lanemint', 'tab' => $tab, 'lanemint_notice' => $type, 'lanemint_message' => rawurlencode( $message ) ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	private static function notice() {
		if ( empty( $_GET['lanemint_notice'] ) || empty( $_GET['lanemint_message'] ) ) {
			return;
		}
		$type    = 'success' === sanitize_key( wp_unslash( $_GET['lanemint_notice'] ) ) ? 'success' : 'error';
		$message = sanitize_text_field( rawurldecode( wp_unslash( $_GET['lanemint_message'] ) ) );
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}

