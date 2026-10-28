<?php
/**
 * Plugin Name: LaneMint
 * Plugin URI:  https://github.com/akinolaking/lanemint
 * Description: Import carrier rate cards from CSV and offer postcode-aware WooCommerce shipping rates.
 * Version:     0.1.0
 * Author:      Akinola Muyiwa, OneNet Servers
 * Author URI:  https://akinola.co.uk
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lanemint
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.2
 * WC tested up to: 11.2
 */

defined( 'ABSPATH' ) || exit;

define( 'LANEMINT_VERSION', '0.1.0' );
define( 'LANEMINT_FILE', __FILE__ );
define( 'LANEMINT_PATH', plugin_dir_path( __FILE__ ) );
define( 'LANEMINT_URL', plugin_dir_url( __FILE__ ) );

require_once LANEMINT_PATH . 'includes/class-lanemint-activator.php';
require_once LANEMINT_PATH . 'includes/class-lanemint-rate-repository.php';
require_once LANEMINT_PATH . 'includes/class-lanemint-nipost-client.php';
require_once LANEMINT_PATH . 'includes/class-lanemint-telemetry.php';
require_once LANEMINT_PATH . 'includes/class-lanemint-admin.php';
require_once LANEMINT_PATH . 'includes/class-lanemint-checkout.php';

register_activation_hook( __FILE__, array( 'LaneMint_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LaneMint_Activator', 'deactivate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( current_user_can( 'activate_plugins' ) ) {
						echo '<div class="notice notice-error"><p>' . esc_html__( 'LaneMint requires WooCommerce.', 'lanemint' ) . '</p></div>';
					}
				}
			);
			return;
		}

		LaneMint_Admin::init();
		LaneMint_Checkout::init();
		LaneMint_Telemetry::init();
	}
);

add_action(
	'woocommerce_shipping_init',
	static function () {
		require_once LANEMINT_PATH . 'includes/class-lanemint-shipping-method.php';
	}
);

add_filter(
	'woocommerce_shipping_methods',
	static function ( $methods ) {
		$methods['lanemint'] = 'LaneMint_Shipping_Method';
		return $methods;
	}
);

