<?php
/**
 * WooCommerce compatibility helpers.
 *
 * @package HexCodaSmartStockBar
 */

namespace HexCoda\SmartStockBar\Woo;

defined( 'ABSPATH' ) || exit;

/**
 * Checks WooCommerce availability.
 */
final class Compatibility {
	/**
	 * Determine whether WooCommerce is active and loaded.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' );
	}
}
