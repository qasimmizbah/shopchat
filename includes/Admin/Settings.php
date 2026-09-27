<?php
/**
 * Settings Handler and Admin Page Renderer
 *
 * @package ShopChat\Admin
 */

namespace ShopChat\Admin;

use ShopChat\Database\CartDb;
use ShopChat\Notifications\TemplateEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * Register hooks
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_shopchat_test_meta_api', array( $this, 'ajax_test_meta_api' ) );
	}

	/**
	 * Enqueue admin CSS and JS only on ShopChat pages
	 *
	 * @param string $hook_suffix Admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'shopchat' ) === false && strpos( $hook_suffix, 'shop_order' ) === false ) {
			return;
		}

		$css_file = SHOPCHAT_PLUGIN_DIR . 'assets/css/admin.css';
		$js_file  = SHOPCHAT_PLUGIN_DIR . 'assets/js/admin.js';
		$css_ver  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : SHOPCHAT_VERSION;
		$js_ver   = file_exists( $js_file ) ? (string) filemtime( $js_file ) : SHOPCHAT_VERSION;

		wp_enqueue_style(
			'shopchat-admin',
			SHOPCHAT_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'shopchat-admin',
			SHOPCHAT_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$js_ver,
			true
		);

		wp_localize_script(
			'shopchat-admin',
			'shopchat_admin_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'shopchat_admin_nonce' ),
			)
		);
	}

	/**
	 * Save plugin settings with strict security checks
	 */
	public function save_settings() {
		if ( ! isset( $_POST['shopchat_save_action'] ) ) {
			return;
		}

		$capability = current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options';
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'Unauthorized user action.', 'shopchat' ) );
		}

		check_admin_referer( 'shopchat_save_settings_nonce', 'shopchat_nonce' );

		$current_tab = isset( $_POST['current_tab'] ) ? sanitize_key( wp_unslash( $_POST['current_tab'] ) ) : 'general';

		// 1. General Settings Tab
		if ( 'general' === $current_tab && isset( $_POST['general_settings'] ) && is_array( $_POST['general_settings'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_general = wp_unslash( $_POST['general_settings'] );
			$sanitized   = array(
				'phone_number'         => sanitize_text_field( $raw_general['phone_number'] ?? '' ),
				'admin_alert_phone'    => sanitize_text_field( $raw_general['admin_alert_phone'] ?? '' ),
				'store_country_code'   => sanitize_text_field( $raw_general['store_country_code'] ?? '1' ),
				'default_country_code' => sanitize_text_field( $raw_general['default_country_code'] ?? '91' ),
				'messaging_mode'       => in_array( $raw_general['messaging_mode'] ?? 'wame', array( 'wame', 'meta_cloud' ), true ) ? sanitize_text_field( $raw_general['messaging_mode'] ) : 'wame',
				'meta_phone_number_id' => sanitize_text_field( $raw_general['meta_phone_number_id'] ?? '' ),
				'meta_access_token'    => sanitize_text_field( $raw_general['meta_access_token'] ?? '' ),
				'meta_waba_id'         => sanitize_text_field( $raw_general['meta_waba_id'] ?? '' ),
				'enable_order_notify'  => isset( $raw_general['enable_order_notify'] ) ? '1' : '0',
				'enable_cart_recovery' => isset( $raw_general['enable_cart_recovery'] ) ? '1' : '0',
				'cart_cutoff_minutes'  => absint( $raw_general['cart_cutoff_minutes'] ?? 20 ),
			);
			update_option( 'shopchat_general_settings', $sanitized );
		}

		// 2. Notification Templates Tab
		if ( 'templates' === $current_tab && isset( $_POST['templates'] ) && is_array( $_POST['templates'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_templates = wp_unslash( $_POST['templates'] );
			$sanitized     = array();

			foreach ( $raw_templates as $status_key => $data ) {
				$clean_key               = sanitize_key( $status_key );
				$sanitized[ $clean_key ] = array(
					'enabled'            => isset( $data['enabled'] ) ? '1' : '0',
					'message'            => sanitize_textarea_field( $data['message'] ?? '' ),
					'meta_template_name' => sanitize_text_field( $data['meta_template_name'] ?? '' ),
				);
			}

			update_option( 'shopchat_templates', $sanitized );
		}

		// 3. Storefront Chat Widget Tab
		if ( 'widget' === $current_tab && isset( $_POST['widget_settings'] ) && is_array( $_POST['widget_settings'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_widget = wp_unslash( $_POST['widget_settings'] );
			$sanitized  = array(
				'enable_widget'            => isset( $raw_widget['enable_widget'] ) ? '1' : '0',
				'position'                 => in_array( $raw_widget['position'] ?? 'bottom-right', array( 'bottom-right', 'bottom-left' ), true ) ? sanitize_text_field( $raw_widget['position'] ) : 'bottom-right',
				'button_text'              => sanitize_text_field( $raw_widget['button_text'] ?? 'Chat with us' ),
				'agent_name'               => sanitize_text_field( $raw_widget['agent_name'] ?? 'Support Team' ),
				'agent_status'             => sanitize_text_field( $raw_widget['agent_status'] ?? 'Online now' ),
				'welcome_message'          => sanitize_textarea_field( $raw_widget['welcome_message'] ?? 'Hello! How can we help you today?' ),
				'product_inquiry_template' => sanitize_textarea_field( $raw_widget['product_inquiry_template'] ?? 'Hi! I have a question about {product_name}: {product_url}' ),
				'hide_on_mobile'           => isset( $raw_widget['hide_on_mobile'] ) ? '1' : '0',
			);
			update_option( 'shopchat_widget_settings', $sanitized );
		}

		// Add notice and redirect
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'shopchat-settings',
					'tab'     => $current_tab,
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX endpoint to test Meta Cloud API dispatch directly from Admin
	 */
	public function ajax_test_meta_api() {
		check_ajax_referer( 'shopchat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'shopchat' ) ) );
		}

		$test_phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		if ( empty( $test_phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a recipient phone number.', 'shopchat' ) ) );
		}

		$gateway = new \ShopChat\Gateways\MetaCloudGateway();
		$result  = $gateway->send(
			$test_phone,
			'',
			array(
				'template_name' => 'hello_world',
				'language'      => 'en_US',
			)
		);

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success(
				array(
					'message'    => __( 'WhatsApp test message sent successfully!', 'shopchat' ),
					'message_id' => $result['message_id'] ?? '',
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => $result['message'] ?? __( 'Failed to dispatch test message.', 'shopchat' ),
				)
			);
		}
	}

	/**
	 * Render settings dashboard layout
	 */
	public function render() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		$general_settings = get_option( 'shopchat_general_settings', array() );
		$templates        = get_option( 'shopchat_templates', array() );
		$widget_settings  = get_option( 'shopchat_widget_settings', array() );
		$stats            = CartDb::get_stats();
		$available_tags   = TemplateEngine::get_available_tags();

		require SHOPCHAT_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}
}
