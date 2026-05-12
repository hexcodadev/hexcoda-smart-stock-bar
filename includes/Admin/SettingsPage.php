<?php
/**
 * Admin settings page.
 *
 * @package HexCodaSmartStockBar
 */

namespace HexCoda\SmartStockBar\Admin;

use function HexCoda\SmartStockBar\Support\default_settings;
use function HexCoda\SmartStockBar\Support\get_plugin_settings;
use const HexCoda\SmartStockBar\Support\OPTION_NAME;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders plugin settings.
 */
final class SettingsPage {
	private const PAGE_SLUG = 'hexcoda-smart-stock-bar';
	private const GROUP     = 'hexcoda_ssb_settings_group';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . HEXCODA_SSB_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Add settings link to the plugin list.
	 *
	 * @param array<int, string> $links Existing links.
	 * @return array<int, string>
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Settings', 'hexcoda-smart-stock-bar' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add submenu page under WooCommerce.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'HexCoda Stock Bar', 'hexcoda-smart-stock-bar' ),
			__( 'HexCoda Stock Bar', 'hexcoda-smart-stock-bar' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => default_settings(),
			)
		);
	}

	/**
	 * Sanitize settings from the admin form.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = default_settings();
		$settings = array();

		$settings['enabled']       = ! empty( $input['enabled'] );
		$settings['threshold']     = isset( $input['threshold'] ) ? max( 0, absint( $input['threshold'] ) ) : $defaults['threshold'];
		$settings['total_stock']   = isset( $input['total_stock'] ) ? max( 1, absint( $input['total_stock'] ) ) : $defaults['total_stock'];
		$settings['position']      = $this->sanitize_position( $input['position'] ?? $defaults['position'] );
		$settings['bar_color']     = sanitize_hex_color( $input['bar_color'] ?? $defaults['bar_color'] ) ?: $defaults['bar_color'];
		$settings['track_color']   = sanitize_hex_color( $input['track_color'] ?? $defaults['track_color'] ) ?: $defaults['track_color'];
		$settings['message']       = sanitize_text_field( $input['message'] ?? $defaults['message'] );
		$settings['show_on_empty'] = ! empty( $input['show_on_empty'] );

		return $settings;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'woocommerce_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'hexcoda-ssb-admin',
			HEXCODA_SSB_URL . 'assets/admin/admin.css',
			array(),
			HEXCODA_SSB_VERSION
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings = get_plugin_settings();
		?>
		<div class="wrap hexcoda-admin">
			<div class="hexcoda-admin__header">
				<p class="hexcoda-admin__eyebrow"><?php esc_html_e( 'HexCoda for WooCommerce', 'hexcoda-smart-stock-bar' ); ?></p>
				<h1><?php esc_html_e( 'Smart Stock Bar', 'hexcoda-smart-stock-bar' ); ?></h1>
				<p><?php esc_html_e( 'Show a focused stock progress bar on product pages without adding heavy urgency features.', 'hexcoda-smart-stock-bar' ); ?></p>
			</div>

			<form action="options.php" method="post" class="hexcoda-panel">
				<?php settings_fields( self::GROUP ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable stock bar', 'hexcoda-smart-stock-bar' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_NAME ); ?>[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>>
								<?php esc_html_e( 'Show the stock bar on eligible product pages.', 'hexcoda-smart-stock-bar' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="hexcoda-ssb-threshold"><?php esc_html_e( 'Low stock threshold', 'hexcoda-smart-stock-bar' ); ?></label></th>
						<td>
							<input id="hexcoda-ssb-threshold" class="small-text" type="number" min="0" name="<?php echo esc_attr( OPTION_NAME ); ?>[threshold]" value="<?php echo esc_attr( (string) $settings['threshold'] ); ?>">
							<p class="description"><?php esc_html_e( 'Show the bar only when available stock is at or below this number. Use 0 to show whenever stock is managed.', 'hexcoda-smart-stock-bar' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="hexcoda-ssb-total-stock"><?php esc_html_e( 'Reference stock amount', 'hexcoda-smart-stock-bar' ); ?></label></th>
						<td>
							<input id="hexcoda-ssb-total-stock" class="small-text" type="number" min="1" name="<?php echo esc_attr( OPTION_NAME ); ?>[total_stock]" value="<?php echo esc_attr( (string) $settings['total_stock'] ); ?>">
							<p class="description"><?php esc_html_e( 'Used to calculate the bar percentage. Example: 5 left from a reference of 20 shows a 25% bar.', 'hexcoda-smart-stock-bar' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="hexcoda-ssb-position"><?php esc_html_e( 'Display position', 'hexcoda-smart-stock-bar' ); ?></label></th>
						<td>
							<select id="hexcoda-ssb-position" name="<?php echo esc_attr( OPTION_NAME ); ?>[position]">
								<option value="after_price" <?php selected( $settings['position'], 'after_price' ); ?>><?php esc_html_e( 'After price', 'hexcoda-smart-stock-bar' ); ?></option>
								<option value="before_add_to_cart" <?php selected( $settings['position'], 'before_add_to_cart' ); ?>><?php esc_html_e( 'Before add to cart', 'hexcoda-smart-stock-bar' ); ?></option>
								<option value="after_add_to_cart" <?php selected( $settings['position'], 'after_add_to_cart' ); ?>><?php esc_html_e( 'After add to cart', 'hexcoda-smart-stock-bar' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="hexcoda-ssb-message"><?php esc_html_e( 'Message', 'hexcoda-smart-stock-bar' ); ?></label></th>
						<td>
							<input id="hexcoda-ssb-message" class="regular-text" type="text" name="<?php echo esc_attr( OPTION_NAME ); ?>[message]" value="<?php echo esc_attr( $settings['message'] ); ?>">
							<p class="description"><?php esc_html_e( 'Use {stock} as the stock quantity placeholder.', 'hexcoda-smart-stock-bar' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Colors', 'hexcoda-smart-stock-bar' ); ?></th>
						<td class="hexcoda-color-row">
							<label>
								<span><?php esc_html_e( 'Bar', 'hexcoda-smart-stock-bar' ); ?></span>
								<input type="color" name="<?php echo esc_attr( OPTION_NAME ); ?>[bar_color]" value="<?php echo esc_attr( $settings['bar_color'] ); ?>">
							</label>
							<label>
								<span><?php esc_html_e( 'Track', 'hexcoda-smart-stock-bar' ); ?></span>
								<input type="color" name="<?php echo esc_attr( OPTION_NAME ); ?>[track_color]" value="<?php echo esc_attr( $settings['track_color'] ); ?>">
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Out of stock products', 'hexcoda-smart-stock-bar' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_NAME ); ?>[show_on_empty]" value="1" <?php checked( $settings['show_on_empty'] ); ?>>
								<?php esc_html_e( 'Show an empty bar when product stock is 0.', 'hexcoda-smart-stock-bar' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize the display position.
	 *
	 * @param mixed $position Submitted position.
	 * @return string
	 */
	private function sanitize_position( $position ): string {
		$allowed = array( 'after_price', 'before_add_to_cart', 'after_add_to_cart' );

		return in_array( $position, $allowed, true ) ? $position : 'after_price';
	}
}
