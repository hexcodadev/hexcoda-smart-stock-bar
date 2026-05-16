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
		$current  = get_plugin_settings();
		$settings = array();

		$settings['enabled']                     = ! empty( $input['enabled'] );
		$settings['total_stock']                 = isset( $input['total_stock'] ) ? max( 1, absint( $input['total_stock'] ) ) : $defaults['total_stock'];
		$settings['threshold']                   = isset( $input['threshold'] ) ? max( 0, absint( $input['threshold'] ) ) : $defaults['threshold'];
		$settings['medium_threshold']            = isset( $input['medium_threshold'] ) ? max( 0, absint( $input['medium_threshold'] ) ) : $defaults['medium_threshold'];
		$settings['medium_threshold']            = max( $settings['threshold'], $settings['medium_threshold'] );
		$settings['total_stock']                 = max( $settings['total_stock'], $settings['medium_threshold'] );
		$settings['low_label']                   = $this->sanitize_label( $input['low_label'] ?? $defaults['low_label'] );
		$settings['medium_label']                = $this->sanitize_label( $input['medium_label'] ?? $defaults['medium_label'] );
		$settings['high_label']                  = $this->sanitize_label( $input['high_label'] ?? $defaults['high_label'] );
		$settings['hide_variation_availability'] = ! empty( $input['hide_variation_availability'] );
		$settings['show_on_empty']               = ! empty( $input['show_on_empty'] );
		$settings['position']                    = $this->sanitize_position( $input['position'] ?? $defaults['position'] );
		$settings['low_color']                   = sanitize_hex_color( $input['low_color'] ?? $defaults['low_color'] ) ?: $defaults['low_color'];
		$settings['medium_color']                = sanitize_hex_color( $input['medium_color'] ?? $defaults['medium_color'] ) ?: $defaults['medium_color'];
		$settings['high_color']                  = sanitize_hex_color( $input['high_color'] ?? $defaults['high_color'] ) ?: $defaults['high_color'];
		$settings['track_color']                 = sanitize_hex_color( $input['track_color'] ?? $defaults['track_color'] ) ?: $defaults['track_color'];
		$settings['font_size']                   = isset( $input['font_size'] ) ? max( 1, absint( $input['font_size'] ) ) : $defaults['font_size'];
		$settings['bar_height']                  = isset( $input['bar_height'] ) ? max( 1, absint( $input['bar_height'] ) ) : $defaults['bar_height'];
		$settings['spacing_top']                 = isset( $input['spacing_top'] ) ? max( 0, absint( $input['spacing_top'] ) ) : $defaults['spacing_top'];
		$settings['spacing_bottom']              = isset( $input['spacing_bottom'] ) ? max( 0, absint( $input['spacing_bottom'] ) ) : $defaults['spacing_bottom'];

		// Legacy keys retained so older installs are not abruptly reset.
		$settings['bar_color']    = $settings['high_color'];
		$settings['message']      = $settings['low_label'];
		$settings['hide_default'] = ! empty( $current['hide_default'] );

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

		$settings        = get_plugin_settings();
		$settings        = $this->normalize_threshold_settings( $settings );
		$low_color       = sanitize_hex_color( $settings['low_color'] ?? '#ef3b1a' ) ?: '#ef3b1a';
		$medium_color    = sanitize_hex_color( $settings['medium_color'] ?? '#f6c21a' ) ?: '#f6c21a';
		$high_color      = sanitize_hex_color( $settings['high_color'] ?? '#5a9b3f' ) ?: '#5a9b3f';
		$track_color     = sanitize_hex_color( $settings['track_color'] ?? '#d9dee5' ) ?: '#d9dee5';
		$font_size       = max( 1, (int) $settings['font_size'] );
		$bar_height      = max( 1, (int) $settings['bar_height'] );
		$spacing_top     = max( 0, (int) $settings['spacing_top'] );
		$spacing_bottom  = max( 0, (int) $settings['spacing_bottom'] );
		$preview_percent = 54;
		?>
		<div class="wrap hexcoda-admin">
			<h1 class="screen-reader-text"><?php esc_html_e( 'HexCoda Smart Stock Bar for WooCommerce', 'hexcoda-smart-stock-bar' ); ?></h1>
			<form action="options.php" method="post" class="hexcoda-app">
				<?php settings_fields( self::GROUP ); ?>

				<header class="hexcoda-topbar">
					<div class="hexcoda-topbar__identity">
						<div>
							<div class="hexcoda-title-row">
								<div class="hexcoda-product-title"><?php esc_html_e( 'HexCoda Smart Stock Bar for WooCommerce', 'hexcoda-smart-stock-bar' ); ?></div>
							</div>
							<p><?php esc_html_e( 'Visual stock urgency for better conversions', 'hexcoda-smart-stock-bar' ); ?></p>
						</div>
					</div>
					<div class="hexcoda-topbar__actions">
						<a class="hexcoda-button hexcoda-button--secondary" href="https://hexcoda.com/docs/smart-stock-bar/" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-book"></span>
							<?php esc_html_e( 'Documentation', 'hexcoda-smart-stock-bar' ); ?>
						</a>
						<button class="hexcoda-button hexcoda-button--primary" type="submit">
							<span class="dashicons dashicons-saved"></span>
							<?php esc_html_e( 'Save Changes', 'hexcoda-smart-stock-bar' ); ?>
						</button>
					</div>
				</header>

				<div class="hexcoda-grid">
					<section id="hexcoda-general" class="hexcoda-card hexcoda-card--general">
						<h2><?php esc_html_e( 'General Settings', 'hexcoda-smart-stock-bar' ); ?></h2>

						<?php $this->render_toggle_field( 'enabled', __( 'Enable Stock Bar', 'hexcoda-smart-stock-bar' ), __( 'Enable or disable the stock bar on product pages.', 'hexcoda-smart-stock-bar' ), ! empty( $settings['enabled'] ) ); ?>
						<?php $this->render_number_field( 'total_stock', __( 'Maximum Stock Cap', 'hexcoda-smart-stock-bar' ), __( 'The stock bar will treat any stock above this value as 100%.', 'hexcoda-smart-stock-bar' ), (int) $settings['total_stock'], 1 ); ?>
						<?php $this->render_number_field( 'medium_threshold', __( 'Medium Stock Threshold', 'hexcoda-smart-stock-bar' ), __( 'Stock above this value is considered medium stock.', 'hexcoda-smart-stock-bar' ), (int) $settings['medium_threshold'], 0 ); ?>
						<?php $this->render_number_field( 'threshold', __( 'Low Stock Threshold', 'hexcoda-smart-stock-bar' ), __( 'Stock at or below this value is considered low stock.', 'hexcoda-smart-stock-bar' ), (int) $settings['threshold'], 0 ); ?>
						<?php $this->render_text_field( 'low_label', __( 'Low Stock Label', 'hexcoda-smart-stock-bar' ), __( 'Label text to display when stock is low.', 'hexcoda-smart-stock-bar' ), (string) $settings['low_label'] ); ?>
						<?php $this->render_text_field( 'medium_label', __( 'Medium Stock Label', 'hexcoda-smart-stock-bar' ), __( 'Label text to display when stock is medium.', 'hexcoda-smart-stock-bar' ), (string) $settings['medium_label'] ); ?>
						<?php $this->render_text_field( 'high_label', __( 'High Stock Label', 'hexcoda-smart-stock-bar' ), __( 'Label text to display when stock is high.', 'hexcoda-smart-stock-bar' ), (string) $settings['high_label'] ); ?>
						<?php $this->render_toggle_field( 'hide_variation_availability', __( 'Hide Woo Variation Availability', 'hexcoda-smart-stock-bar' ), __( 'Hide the default WooCommerce variation stock text.', 'hexcoda-smart-stock-bar' ), ! empty( $settings['hide_variation_availability'] ) ); ?>
						<?php $this->render_toggle_field( 'show_on_empty', __( 'Show Empty Bar for Out of Stock Products', 'hexcoda-smart-stock-bar' ), __( 'Show an empty stock bar with the low stock label when out of stock.', 'hexcoda-smart-stock-bar' ), ! empty( $settings['show_on_empty'] ) ); ?>

						<div class="hexcoda-field">
							<label class="hexcoda-field__label" for="hexcoda-ssb-position">
								<?php esc_html_e( 'Stock Bar Position', 'hexcoda-smart-stock-bar' ); ?>
								<span class="hexcoda-help" title="<?php esc_attr_e( 'Choose where to display the stock bar on the product page.', 'hexcoda-smart-stock-bar' ); ?>">?</span>
							</label>
							<div class="hexcoda-field__control">
								<select id="hexcoda-ssb-position" class="hexcoda-input hexcoda-input--select" name="<?php echo esc_attr( OPTION_NAME ); ?>[position]">
									<option value="above_add_to_cart" <?php selected( $this->normalize_position( (string) $settings['position'] ), 'above_add_to_cart' ); ?>><?php esc_html_e( 'Above add to cart', 'hexcoda-smart-stock-bar' ); ?></option>
									<option value="below_add_to_cart" <?php selected( $this->normalize_position( (string) $settings['position'] ), 'below_add_to_cart' ); ?>><?php esc_html_e( 'Below add to cart', 'hexcoda-smart-stock-bar' ); ?></option>
									<option value="after_meta" <?php selected( $this->normalize_position( (string) $settings['position'] ), 'after_meta' ); ?>><?php esc_html_e( 'After product meta', 'hexcoda-smart-stock-bar' ); ?></option>
								</select>
								<p><?php esc_html_e( 'Choose where to display the stock bar on the product page.', 'hexcoda-smart-stock-bar' ); ?></p>
							</div>
						</div>
					</section>

					<aside class="hexcoda-side">
						<section class="hexcoda-card hexcoda-preview">
							<h2><?php esc_html_e( 'Live Preview', 'hexcoda-smart-stock-bar' ); ?></h2>
							<div class="hexcoda-preview__product">
								<div class="hexcoda-preview__image" aria-hidden="true">
									<div class="hexcoda-product-mock">
										<span class="hexcoda-product-mock__hood"></span>
										<span class="hexcoda-product-mock__body"></span>
										<span class="hexcoda-product-mock__pocket"></span>
									</div>
								</div>
								<div class="hexcoda-preview__content">
									<h3><?php esc_html_e( 'Premium Hoodie', 'hexcoda-smart-stock-bar' ); ?></h3>
									<strong><?php esc_html_e( '$49.00', 'hexcoda-smart-stock-bar' ); ?></strong>
									<p><?php esc_html_e( 'This is a simple product example to demonstrate the stock bar.', 'hexcoda-smart-stock-bar' ); ?></p>
									<hr>
									<div class="hexcoda-preview__status">
										<?php esc_html_e( 'Stock Status:', 'hexcoda-smart-stock-bar' ); ?>
										<span><?php echo esc_html( (string) $settings['medium_label'] ); ?></span>
									</div>
									<div class="hexcoda-preview__bar" style="<?php echo esc_attr( '--low:' . $low_color . ';--medium:' . $medium_color . ';--high:' . $high_color . ';--track:' . $track_color . ';--indicator:' . $preview_percent . '%;--height:' . $bar_height . 'px;--top:' . $spacing_top . 'px;--bottom:' . $spacing_bottom . 'px;' ); ?>">
										<span></span>
									</div>
									<div class="hexcoda-preview__labels">
										<span><?php esc_html_e( 'Low stock', 'hexcoda-smart-stock-bar' ); ?></span>
										<span><?php esc_html_e( 'In stock', 'hexcoda-smart-stock-bar' ); ?></span>
										<span><?php esc_html_e( 'High stock', 'hexcoda-smart-stock-bar' ); ?></span>
									</div>
									<div class="hexcoda-preview__cart">
										<input class="hexcoda-input" type="number" min="1" value="1" readonly>
										<button type="button"><?php esc_html_e( 'Add to cart', 'hexcoda-smart-stock-bar' ); ?></button>
									</div>
								</div>
							</div>
						</section>

						<section id="hexcoda-style" class="hexcoda-card hexcoda-style-card">
							<h2>
								<?php esc_html_e( 'Style', 'hexcoda-smart-stock-bar' ); ?>
								<span><?php esc_html_e( 'Quick Preview', 'hexcoda-smart-stock-bar' ); ?></span>
							</h2>
							<div class="hexcoda-style-grid">
								<?php $this->render_color_field( 'low_color', __( 'Low Stock Color', 'hexcoda-smart-stock-bar' ), $low_color ); ?>
								<?php $this->render_color_field( 'medium_color', __( 'Medium Stock Color', 'hexcoda-smart-stock-bar' ), $medium_color ); ?>
								<?php $this->render_color_field( 'high_color', __( 'High Stock Color', 'hexcoda-smart-stock-bar' ), $high_color ); ?>
								<?php $this->render_color_field( 'track_color', __( 'Bar Background', 'hexcoda-smart-stock-bar' ), $track_color ); ?>
								<?php $this->render_compact_number_field( 'font_size', __( 'Font Size (px)', 'hexcoda-smart-stock-bar' ), $font_size, 1 ); ?>
								<?php $this->render_compact_number_field( 'bar_height', __( 'Bar Height (px)', 'hexcoda-smart-stock-bar' ), $bar_height, 1 ); ?>
								<?php $this->render_compact_number_field( 'spacing_top', __( 'Top Spacing (px)', 'hexcoda-smart-stock-bar' ), $spacing_top, 0 ); ?>
								<?php $this->render_compact_number_field( 'spacing_bottom', __( 'Bottom Spacing (px)', 'hexcoda-smart-stock-bar' ), $spacing_bottom, 0 ); ?>
							</div>
						</section>

						<section class="hexcoda-card hexcoda-features-card">
							<h2><?php esc_html_e( 'Main Plugin Features', 'hexcoda-smart-stock-bar' ); ?></h2>
							<ul>
								<li><?php esc_html_e( 'Uses existing WooCommerce stock data', 'hexcoda-smart-stock-bar' ); ?></li>
								<li><?php esc_html_e( 'Focused frontend output', 'hexcoda-smart-stock-bar' ); ?></li>
								<li><?php esc_html_e( 'Lightweight configuration', 'hexcoda-smart-stock-bar' ); ?></li>
								<li><?php esc_html_e( 'Designed for performance and practical use', 'hexcoda-smart-stock-bar' ); ?></li>
							</ul>
						</section>
					</aside>
				</div>

				<footer class="hexcoda-footer">
					<button class="hexcoda-button hexcoda-button--primary" type="submit">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Changes', 'hexcoda-smart-stock-bar' ); ?>
					</button>
					<p>
						<?php esc_html_e( 'Built by', 'hexcoda-smart-stock-bar' ); ?>
						<a href="https://hexcoda.com/" target="_blank" rel="noopener noreferrer">HexCoda</a>
						<span><?php esc_html_e( 'Premium WordPress and WooCommerce plugins for cleaner online stores.', 'hexcoda-smart-stock-bar' ); ?></span>
					</p>
				</footer>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a toggle field.
	 *
	 * @param string $key Setting key.
	 * @param string $label Field label.
	 * @param string $description Field helper text.
	 * @param bool   $checked Whether the toggle is checked.
	 * @return void
	 */
	private function render_toggle_field( string $key, string $label, string $description, bool $checked ): void {
		$field_id = 'hexcoda-ssb-' . str_replace( '_', '-', $key );
		?>
		<div class="hexcoda-field">
			<label class="hexcoda-field__label" for="<?php echo esc_attr( $field_id ); ?>">
				<?php echo esc_html( $label ); ?>
				<span class="hexcoda-help" title="<?php echo esc_attr( $description ); ?>">?</span>
			</label>
			<div class="hexcoda-field__control">
				<label class="hexcoda-toggle">
					<input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" name="<?php echo esc_attr( OPTION_NAME . '[' . $key . ']' ); ?>" value="1" <?php checked( $checked ); ?>>
					<span></span>
				</label>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a number field.
	 *
	 * @param string $key Setting key.
	 * @param string $label Field label.
	 * @param string $description Field helper text.
	 * @param int    $value Current value.
	 * @param int    $min Minimum value.
	 * @return void
	 */
	private function render_number_field( string $key, string $label, string $description, int $value, int $min ): void {
		$field_id = 'hexcoda-ssb-' . str_replace( '_', '-', $key );
		?>
		<div class="hexcoda-field">
			<label class="hexcoda-field__label" for="<?php echo esc_attr( $field_id ); ?>">
				<?php echo esc_html( $label ); ?>
				<span class="hexcoda-help" title="<?php echo esc_attr( $description ); ?>">?</span>
			</label>
			<div class="hexcoda-field__control">
				<input id="<?php echo esc_attr( $field_id ); ?>" class="hexcoda-input hexcoda-input--number" type="number" min="<?php echo esc_attr( (string) $min ); ?>" name="<?php echo esc_attr( OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
				<p><?php echo esc_html( $description ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a text field.
	 *
	 * @param string $key Setting key.
	 * @param string $label Field label.
	 * @param string $description Field helper text.
	 * @param string $value Current value.
	 * @return void
	 */
	private function render_text_field( string $key, string $label, string $description, string $value ): void {
		$field_id = 'hexcoda-ssb-' . str_replace( '_', '-', $key );
		?>
		<div class="hexcoda-field">
			<label class="hexcoda-field__label" for="<?php echo esc_attr( $field_id ); ?>">
				<?php echo esc_html( $label ); ?>
				<span class="hexcoda-help" title="<?php echo esc_attr( $description ); ?>">?</span>
			</label>
			<div class="hexcoda-field__control">
				<input id="<?php echo esc_attr( $field_id ); ?>" class="hexcoda-input hexcoda-input--text" type="text" name="<?php echo esc_attr( OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<p><?php echo esc_html( $description ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a compact color field.
	 *
	 * @param string $key Setting key.
	 * @param string $label Field label.
	 * @param string $value Current value.
	 * @return void
	 */
	private function render_color_field( string $key, string $label, string $value ): void {
		$field_id = 'hexcoda-ssb-' . str_replace( '_', '-', $key );
		$value    = sanitize_hex_color( $value ) ?: '#000000';
		?>
		<label class="hexcoda-style-field hexcoda-style-field--color" for="<?php echo esc_attr( $field_id ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<span class="hexcoda-color-control">
				<input id="<?php echo esc_attr( $field_id ); ?>" type="color" name="<?php echo esc_attr( OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<em><?php esc_html_e( 'Select Color', 'hexcoda-smart-stock-bar' ); ?></em>
			</span>
		</label>
		<?php
	}

	/**
	 * Render a compact number field.
	 *
	 * @param string $key Setting key.
	 * @param string $label Field label.
	 * @param int    $value Current value.
	 * @param int    $min Minimum value.
	 * @return void
	 */
	private function render_compact_number_field( string $key, string $label, int $value, int $min ): void {
		$field_id = 'hexcoda-ssb-' . str_replace( '_', '-', $key );
		?>
		<label class="hexcoda-style-field" for="<?php echo esc_attr( $field_id ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<input id="<?php echo esc_attr( $field_id ); ?>" class="hexcoda-input hexcoda-input--small" type="number" min="<?php echo esc_attr( (string) $min ); ?>" name="<?php echo esc_attr( OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
		</label>
		<?php
	}

	/**
	 * Sanitize a label while keeping placeholders such as {stock}.
	 *
	 * @param mixed $label Submitted label.
	 * @return string
	 */
	private function sanitize_label( $label ): string {
		return sanitize_text_field( (string) $label );
	}

	/**
	 * Normalize threshold relationships for display.
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
	 * Sanitize the display position.
	 *
	 * @param mixed $position Submitted position.
	 * @return string
	 */
	private function sanitize_position( $position ): string {
		return $this->normalize_position( (string) $position );
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
