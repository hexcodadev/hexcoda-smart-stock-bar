<?php
/**
 * Shared helper functions.
 *
 * Keep these small and reusable across future HexCoda plugins.
 *
 * @package HexCodaSmartStockBar
 */

namespace HexCoda\SmartStockBar\Support;

defined( 'ABSPATH' ) || exit;

const OPTION_NAME = 'hexcoda_ssb_settings';

/**
 * Get default plugin settings.
 *
 * @return array<string, mixed>
 */
function default_settings(): array {
	return array(
		'enabled'       => true,
		'threshold'     => 20,
		'total_stock'   => 20,
		'position'      => 'after_price',
		'bar_color'     => '#a7ca4f',
		'track_color'   => '#e7eadf',
		'message'       => __( 'Only {stock} left in stock', 'hexcoda-smart-stock-bar' ),
		'show_on_empty' => false,
		'hide_default'  => false,
	);
}

/**
 * Get merged plugin settings.
 *
 * @return array<string, mixed>
 */
function get_plugin_settings(): array {
	$saved = get_option( OPTION_NAME, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return wp_parse_args( $saved, default_settings() );
}

/**
 * Convert a setting value to a boolean.
 *
 * @param mixed $value Value to cast.
 * @return bool
 */
function to_bool( $value ): bool {
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Build a plugin asset URL.
 *
 * @param string $path Relative asset path.
 * @return string
 */
function asset_url( string $path ): string {
	return HEXCODA_SSB_URL . ltrim( $path, '/' );
}
