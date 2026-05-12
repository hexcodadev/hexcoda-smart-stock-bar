<?php
/**
 * Main plugin coordinator.
 *
 * @package HexCodaSmartStockBar
 */

namespace HexCoda\SmartStockBar;

use HexCoda\SmartStockBar\Admin\Notices;
use HexCoda\SmartStockBar\Admin\SettingsPage;
use HexCoda\SmartStockBar\Frontend\StockBar;
use HexCoda\SmartStockBar\Woo\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Wires admin, frontend, and WooCommerce integration.
 */
final class Plugin {
	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot(): void {
		$compatibility = new Compatibility();
		$notices       = new Notices( $compatibility );

		$notices->register();

		if ( ! $compatibility->is_woocommerce_active() ) {
			return;
		}

		if ( is_admin() ) {
			( new SettingsPage() )->register();
		}

		( new StockBar() )->register();
	}
}
