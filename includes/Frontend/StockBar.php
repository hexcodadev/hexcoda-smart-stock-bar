<?php
/**
 * Frontend stock bar output.
 *
 * @package HexCodaSmartStockBar
 */

namespace HexCoda\SmartStockBar\Frontend;

use WC_Product;

use function HexCoda\SmartStockBar\Support\get_plugin_settings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the stock bar on product pages.
 */
final class StockBar {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$settings = get_plugin_settings();

		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		if ( ! empty( $settings['hide_default'] ) ) {
			add_filter( 'woocommerce_get_stock_html', array( $this, 'maybe_hide_default_stock_html' ), 10, 2 );
		}

		if ( ! empty( $settings['hide_variation_availability'] ) ) {
			add_filter( 'woocommerce_available_variation', array( $this, 'maybe_hide_variation_availability' ), 10, 3 );
		}

		$this->register_position_hook( (string) $settings['position'] );
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			'hexcoda-ssb-frontend',
			HEXCODA_SSB_URL . 'assets/frontend/stock-bar.css',
			array(),
			HEXCODA_SSB_VERSION
		);
	}

	/**
	 * Render stock bar markup.
	 *
	 * @return void
	 */
	public function render(): void {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( ! $this->is_product_eligible( $product ) ) {
			return;
		}

		$settings       = get_plugin_settings();
		$settings       = $this->normalize_threshold_settings( $settings );
		$stock_quantity = max( 0, (int) $product->get_stock_quantity() );
		$total_stock    = max( 1, (int) $settings['total_stock'] );
		$percentage     = min( 100, max( 0, ( $stock_quantity / $total_stock ) * 100 ) );
		$state          = $this->get_stock_state( $stock_quantity, $settings );
		$message        = $this->get_state_label( $state, $stock_quantity, $settings );

		?>
		<div class="hexcoda-ssb hexcoda-ssb--<?php echo esc_attr( $state ); ?>" style="<?php echo esc_attr( $this->build_css_variables( $settings, $state, $percentage ) ); ?>">
			<div class="hexcoda-ssb__message"><?php echo esc_html( $message ); ?></div>
			<div class="hexcoda-ssb__track" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( (string) $total_stock ); ?>" aria-valuenow="<?php echo esc_attr( (string) $stock_quantity ); ?>" aria-label="<?php esc_attr_e( 'Available stock', 'hexcoda-smart-stock-bar' ); ?>">
				<span class="hexcoda-ssb__bar"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Hide WooCommerce's default stock text for products where this plugin can display a bar.
	 *
	 * @param string     $html    Default stock availability HTML.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public function maybe_hide_default_stock_html( string $html, WC_Product $product ): string {
		if ( $this->is_product_eligible( $product ) ) {
			return '';
		}

		return $html;
	}

	/**
	 * Hide WooCommerce variation stock availability text when enabled.
	 *
	 * @param array<string, mixed> $variation_data Variation data.
	 * @param mixed                $product Variation parent product.
	 * @param mixed                $variation Variation product.
	 * @return array<string, mixed>
	 */
	public function maybe_hide_variation_availability( array $variation_data, $product = null, $variation = null ): array {
		$variation_data['availability_html'] = '';

		return $variation_data;
	}

	/**
	 * Register the selected display hook.
	 *
	 * @param string $position Display position.
	 * @return void
	 */
	private function register_position_hook( string $position ): void {
		switch ( $this->normalize_position( $position ) ) {
			case 'above_add_to_cart':
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render' ), 10 );
				break;
			case 'after_meta':
				add_action( 'woocommerce_product_meta_end', array( $this, 'render' ), 10 );
				break;
			case 'below_add_to_cart':
			default:
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render' ), 10 );
				break;
		}
	}

	/**
	 * Determine whether a product is eligible for the stock bar.
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	private function is_product_eligible( WC_Product $product ): bool {
		$stock_quantity = $product->get_stock_quantity();

		if ( null === $stock_quantity || ! $product->managing_stock() ) {
			return false;
		}

		$settings       = get_plugin_settings();
		$settings       = $this->normalize_threshold_settings( $settings );
		$stock_quantity = max( 0, (int) $stock_quantity );

		if ( 0 === $stock_quantity && empty( $settings['show_on_empty'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get stock state from thresholds.
	 *
	 * @param int                  $stock_quantity Stock quantity.
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return string
	 */
	private function get_stock_state( int $stock_quantity, array $settings ): string {
		$low_threshold    = max( 0, (int) $settings['threshold'] );
		$medium_threshold = max( $low_threshold, (int) $settings['medium_threshold'] );

		if ( $stock_quantity <= $low_threshold ) {
			return 'low';
		}

		if ( $stock_quantity <= $medium_threshold ) {
			return 'medium';
		}

		return 'high';
	}

	/**
	 * Normalize threshold relationships.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return array<string, mixed>
	 */
	private function normalize_threshold_settings( array $settings ): array {
		$settings['threshold']        = max( 0, (int) $settings['threshold'] );
		$settings['medium_threshold'] = max( $settings['threshold'], (int) $settings['medium_threshold'] );
		$settings['total_stock']      = max( 1, (int) $settings['total_stock'], $settings['medium_threshold'] );

		return $settings;
	}

	/**
	 * Get label for the current state.
	 *
	 * @param string               $state Stock state.
	 * @param int                  $stock_quantity Stock quantity.
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return string
	 */
	private function get_state_label( string $state, int $stock_quantity, array $settings ): string {
		$key   = $state . '_label';
		$label = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';

		if ( '' === $label ) {
			$label = (string) ( $settings['medium_label'] ?? __( 'In stock', 'hexcoda-smart-stock-bar' ) );
		}

		return str_replace( '{stock}', (string) $stock_quantity, $label );
	}

	/**
	 * Build safe CSS custom properties.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @param string               $state Stock state.
	 * @param float                $percentage Bar percentage.
	 * @return string
	 */
	private function build_css_variables( array $settings, string $state, float $percentage ): string {
		$color_key    = $state . '_color';
		$bar_color    = sanitize_hex_color( $settings[ $color_key ] ?? '#5a9b3f' ) ?: '#5a9b3f';
		$track_color  = sanitize_hex_color( $settings['track_color'] ?? '#d9dee5' ) ?: '#d9dee5';
		$font_size    = max( 1, (int) $settings['font_size'] );
		$bar_height   = max( 1, (int) $settings['bar_height'] );
		$spacing_top  = max( 0, (int) $settings['spacing_top'] );
		$spacing_bottom = max( 0, (int) $settings['spacing_bottom'] );

		return sprintf(
			'--hexcoda-ssb-bar-color:%1$s;--hexcoda-ssb-track-color:%2$s;--hexcoda-ssb-percent:%3$s%%;--hexcoda-ssb-font-size:%4$dpx;--hexcoda-ssb-bar-height:%5$dpx;--hexcoda-ssb-spacing-top:%6$dpx;--hexcoda-ssb-spacing-bottom:%7$dpx;',
			$bar_color,
			$track_color,
			round( $percentage, 2 ),
			$font_size,
			$bar_height,
			$spacing_top,
			$spacing_bottom
		);
	}

	/**
	 * Normalize old and new display position values.
	 *
	 * @param string $position Display position.
	 * @return string
	 */
	private function normalize_position( string $position ): string {
		$map = array(
			'after_price'        => 'above_add_to_cart',
			'before_add_to_cart' => 'above_add_to_cart',
			'after_add_to_cart'  => 'below_add_to_cart',
		);

		if ( isset( $map[ $position ] ) ) {
			return $map[ $position ];
		}

		$allowed = array( 'above_add_to_cart', 'below_add_to_cart', 'after_meta' );

		return in_array( $position, $allowed, true ) ? $position : 'below_add_to_cart';
	}
}
