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

		$stock_quantity = $product->get_stock_quantity();

		if ( null === $stock_quantity || ! $product->managing_stock() ) {
			return;
		}

		$settings       = get_plugin_settings();
		$stock_quantity = max( 0, (int) $stock_quantity );
		$threshold      = (int) $settings['threshold'];

		if ( 0 === $stock_quantity && empty( $settings['show_on_empty'] ) ) {
			return;
		}

		if ( $threshold > 0 && $stock_quantity > $threshold ) {
			return;
		}

		$total_stock = max( 1, (int) $settings['total_stock'] );
		$percentage  = min( 100, max( 0, ( $stock_quantity / $total_stock ) * 100 ) );
		$message     = str_replace( '{stock}', (string) $stock_quantity, (string) $settings['message'] );

		?>
		<div class="hexcoda-ssb" style="<?php echo esc_attr( $this->build_css_variables( $settings, $percentage ) ); ?>">
			<div class="hexcoda-ssb__message"><?php echo esc_html( $message ); ?></div>
			<div class="hexcoda-ssb__track" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( (string) $total_stock ); ?>" aria-valuenow="<?php echo esc_attr( (string) $stock_quantity ); ?>" aria-label="<?php esc_attr_e( 'Available stock', 'hexcoda-smart-stock-bar' ); ?>">
				<span class="hexcoda-ssb__bar"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Register the selected display hook.
	 *
	 * @param string $position Display position.
	 * @return void
	 */
	private function register_position_hook( string $position ): void {
		switch ( $position ) {
			case 'before_add_to_cart':
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render' ), 10 );
				break;
			case 'after_add_to_cart':
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render' ), 10 );
				break;
			case 'after_price':
			default:
				add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 11 );
				break;
		}
	}

	/**
	 * Build safe CSS custom properties.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @param float                $percentage Bar percentage.
	 * @return string
	 */
	private function build_css_variables( array $settings, float $percentage ): string {
		$bar_color   = sanitize_hex_color( $settings['bar_color'] ?? '#16a34a' ) ?: '#16a34a';
		$track_color = sanitize_hex_color( $settings['track_color'] ?? '#e5e7eb' ) ?: '#e5e7eb';

		return sprintf(
			'--hexcoda-ssb-bar-color:%1$s;--hexcoda-ssb-track-color:%2$s;--hexcoda-ssb-percent:%3$s%%;',
			$bar_color,
			$track_color,
			round( $percentage, 2 )
		);
	}
}
