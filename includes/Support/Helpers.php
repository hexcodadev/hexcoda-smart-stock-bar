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
		'enabled'                     => true,
		'total_stock'                 => 100,
		'threshold'                   => 10,
		'medium_threshold'            => 30,
		'low_label'                   => __( 'Only a few left', 'hexcoda-smart-stock-bar' ),
		'medium_label'                => __( 'In stock', 'hexcoda-smart-stock-bar' ),
		'high_label'                  => __( 'Plenty available', 'hexcoda-smart-stock-bar' ),
		'hide_variation_availability' => false,
		'show_on_empty'               => true,
		'position'                    => 'below_add_to_cart',
		'low_color'                   => '#ef3b1a',
		'medium_color'                => '#f6c21a',
		'high_color'                  => '#5a9b3f',
		'track_color'                 => '#d9dee5',
		'font_size'                   => 14,
		'bar_height'                  => 8,
		'spacing_top'                 => 12,
		'spacing_bottom'              => 12,

		// Legacy keys retained for compatibility with 0.1.x installs.
		'bar_color'                   => '#a7ca4f',
		'message'                     => __( 'Only {stock} left in stock', 'hexcoda-smart-stock-bar' ),
		'hide_default'                => false,
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

	$settings = wp_parse_args( $saved, default_settings() );

	if ( ! array_key_exists( 'low_label', $saved ) && ! empty( $saved['message'] ) ) {
		$settings['low_label'] = (string) $saved['message'];
	}

	if ( ! array_key_exists( 'high_color', $saved ) && ! empty( $saved['bar_color'] ) ) {
		$settings['high_color'] = (string) $saved['bar_color'];
	}

	return $settings;
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
