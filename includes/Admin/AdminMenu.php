<?php
/**
 * Admin Navigation & Menu Registrations
 *
 * @package ShopChat\Admin
 */

namespace ShopChat\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu {

	/**
	 * Register hooks
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_notices', array( $this, 'check_woocommerce_dependency' ) );
		add_filter( 'submenu_file', array( $this, 'highlight_active_submenu' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_legacy_page_redirect' ) );
	}

	/**
	 * Redirect legacy submenu page to main tabbed settings
	 */
	public function handle_legacy_page_redirect() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'shopchat-abandoned-carts' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=shopchat-settings&tab=abandoned-carts' ) );
			exit;
		}
	}

	/**
	 * Highlight corresponding submenu item when viewing ShopChat tabs
	 *
	 * @param string|null $submenu_file Current submenu file.
	 * @param string      $parent_file  Current parent file.
	 * @return string|null
	 */
	public function highlight_active_submenu( $submenu_file, $parent_file ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'shopchat-settings' === $parent_file && isset( $_GET['tab'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			if ( in_array( $tab, array( 'general', 'abandoned-carts', 'templates', 'widget' ), true ) ) {
				return 'admin.php?page=shopchat-settings&tab=' . $tab;
			}
		}
		return $submenu_file;
	}

	/**
	 * Show friendly admin notice if WooCommerce is not installed or active
	 */
	public function check_woocommerce_dependency() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'ShopChat for WhatsApp & WooCommerce:', 'shopchat' ); ?></strong>
					<?php esc_html_e( 'WooCommerce is not currently active. While customer storefront chat works, WooCommerce is required for Order Notifications and Cart Recovery.', 'shopchat' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Register top-level and submenu items
	 */
	public function register_admin_menus() {
		$capability = 'manage_woocommerce';

		// Fallback capability if WooCommerce is not active
		if ( ! current_user_can( $capability ) && current_user_can( 'manage_options' ) ) {
			$capability = 'manage_options';
		}

		// Top Level Menu
		add_menu_page(
			__( 'ShopChat WhatsApp Suite', 'shopchat' ),
			__( 'ShopChat', 'shopchat' ),
			$capability,
			'shopchat-settings',
			array( $this, 'render_dashboard_page' ),
			'dashicons-format-chat',
			56
		);

		// Submenu 1: Overview
		add_submenu_page(
			'shopchat-settings',
			__( 'Overview Dashboard', 'shopchat' ),
			__( 'Overview', 'shopchat' ),
			$capability,
			'shopchat-settings',
			array( $this, 'render_dashboard_page' )
		);

		// Submenu 2: General Settings
		add_submenu_page(
			'shopchat-settings',
			__( 'General Settings', 'shopchat' ),
			__( 'General Settings', 'shopchat' ),
			$capability,
			'admin.php?page=shopchat-settings&tab=general'
		);

		// Submenu 3: Abandoned Carts
		add_submenu_page(
			'shopchat-settings',
			__( 'Abandoned Carts', 'shopchat' ),
			__( 'Abandoned Carts', 'shopchat' ),
			$capability,
			'admin.php?page=shopchat-settings&tab=abandoned-carts'
		);

		// Submenu 4: Order Templates
		add_submenu_page(
			'shopchat-settings',
			__( 'Order Templates', 'shopchat' ),
			__( 'Order Templates', 'shopchat' ),
			$capability,
			'admin.php?page=shopchat-settings&tab=templates'
		);

		// Submenu 5: Storefront Chat Widget
		add_submenu_page(
			'shopchat-settings',
			__( 'Storefront Chat Widget', 'shopchat' ),
			__( 'Chat Widget', 'shopchat' ),
			$capability,
			'admin.php?page=shopchat-settings&tab=widget'
		);
	}

	/**
	 * Render main settings & dashboard page
	 */
	public function render_dashboard_page() {
		$settings_handler = new Settings();
		$settings_handler->render();
	}
}
