<?php
/**
 * Plugin Name: HexCoda Smart Stock Bar for WooCommerce
 * Plugin URI: https://hexcoda.com/
 * Description: Add a lightweight stock progress bar to WooCommerce product pages.
 * Version: 0.2.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: HexCoda
 * Author URI: https://hexcoda.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hexcoda-smart-stock-bar
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 *
 * @package HexCodaSmartStockBar
 */

defined( 'ABSPATH' ) || exit;

define( 'HEXCODA_SSB_VERSION', '0.2.0' );
define( 'HEXCODA_SSB_FILE', __FILE__ );
define( 'HEXCODA_SSB_PATH', plugin_dir_path( __FILE__ ) );
define( 'HEXCODA_SSB_URL', plugin_dir_url( __FILE__ ) );
define( 'HEXCODA_SSB_BASENAME', plugin_basename( __FILE__ ) );

require_once HEXCODA_SSB_PATH . 'includes/Support/Helpers.php';
require_once HEXCODA_SSB_PATH . 'includes/Woo/Compatibility.php';
require_once HEXCODA_SSB_PATH . 'includes/Admin/Notices.php';
require_once HEXCODA_SSB_PATH . 'includes/Admin/SettingsPage.php';
require_once HEXCODA_SSB_PATH . 'includes/Frontend/StockBar.php';
require_once HEXCODA_SSB_PATH . 'includes/Plugin.php';

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		$plugin = new \HexCoda\SmartStockBar\Plugin();
		$plugin->boot();
	}
);
