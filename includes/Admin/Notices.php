<?php
/**
 * Admin notices.
 *
 * @package HexCodaSmartStockBar
 */

namespace HexCoda\SmartStockBar\Admin;

use HexCoda\SmartStockBar\Woo\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Displays admin notices.
 */
final class Notices {
	/**
	 * WooCommerce compatibility checker.
	 *
	 * @var Compatibility
	 */
	private $compatibility;

	/**
	 * Constructor.
	 *
	 * @param Compatibility $compatibility WooCommerce compatibility checker.
	 */
	public function __construct( Compatibility $compatibility ) {
		$this->compatibility = $compatibility;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
	}

	/**
	 * Show a dependency notice when WooCommerce is unavailable.
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice(): void {
		if ( $this->compatibility->is_woocommerce_active() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'HexCoda Smart Stock Bar requires WooCommerce to be installed and active.', 'hexcoda-smart-stock-bar' )
		);
	}
}
