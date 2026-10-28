<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LaneMint_Shipping_Method' ) ) {
	final class LaneMint_Shipping_Method extends WC_Shipping_Method {
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'lanemint';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'LaneMint rate cards', 'lanemint' );
			$this->method_description = __( 'Offer delivery-partner rates imported from CSV and matched by zone, postcode and weight.', 'lanemint' );
			$this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
			$this->init();
		}

		public function init() {
			$this->init_instance_form_fields();
			$this->init_settings();
			$this->enabled       = $this->get_option( 'enabled', 'yes' );
			$this->title         = $this->get_option( 'title', __( 'Delivery partners', 'lanemint' ) );
			$this->fallback_weight = max( 0.001, (float) $this->get_option( 'fallback_weight', '0.3' ) );

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		public function init_instance_form_fields() {
			$this->instance_form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'lanemint' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable LaneMint rates for this shipping zone', 'lanemint' ),
					'default' => 'yes',
				),
				'title' => array(
					'title'       => __( 'Checkout title', 'lanemint' ),
					'type'        => 'text',
					'default'     => __( 'Delivery partners', 'lanemint' ),
					'description' => __( 'Carrier and service names from the imported rate cards are shown as individual choices.', 'lanemint' ),
				),
				'fallback_weight' => array(
					'title'             => __( 'Fallback item weight (kg)', 'lanemint' ),
					'type'              => 'number',
					'default'           => '0.3',
					'custom_attributes' => array( 'min' => '0.001', 'step' => '0.001' ),
					'description'       => __( 'Used only when a product has no WooCommerce weight.', 'lanemint' ),
				),
			);
		}

		public function calculate_shipping( $package = array() ) {
			if ( 'yes' !== $this->enabled ) {
				return;
			}

			$weight = 0.0;
			foreach ( (array) ( $package['contents'] ?? array() ) as $item ) {
				$quantity = max( 0, (int) ( $item['quantity'] ?? 0 ) );
				$product  = $item['data'] ?? null;
				$item_weight = $product instanceof WC_Product ? (float) wc_get_weight( $product->get_weight(), 'kg' ) : 0.0;
				$weight += ( $item_weight > 0 ? $item_weight : $this->fallback_weight ) * $quantity;
			}

			if ( $weight <= 0 ) {
				return;
			}

			$repository = new LaneMint_Rate_Repository();
			$rates      = $repository->get_matching_rates( (array) ( $package['destination'] ?? array() ), round( $weight, 3 ) );

			foreach ( $rates as $rate ) {
				$label = sprintf( '%s — %s', $rate['partner_name'], $rate['service'] );
				$this->add_rate(
					array(
						'id'        => $this->get_rate_id( (int) $rate['id'] ),
						'label'     => $label,
						'cost'      => (float) $rate['price'],
						'taxes'     => false,
						'meta_data' => array(
							'LaneMint partner' => $rate['partner_name'],
							'LaneMint service' => $rate['service'],
							'Package weight'   => number_format( $weight, 3 ) . ' kg',
						),
					)
				);
			}
		}
	}
}

