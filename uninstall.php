<?php
/**
 * Uninstall cleanup.
 *
 * @package HexCodaSmartStockBar
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'hexcoda_ssb_settings' );
