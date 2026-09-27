<?php
/**
 * Storefront Floating WhatsApp Chat Widget
 *
 * @package ShopChat\Frontend
 */

namespace ShopChat\Frontend;

use ShopChat\Gateways\WaMeGateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChatWidget {

	/**
	 * Register hooks
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_widget' ) );
	}

	/**
	 * Enqueue frontend widget styles and scripts
	 */
	public function enqueue_assets() {
		$settings = get_option( 'shopchat_widget_settings', array() );

		if ( empty( $settings['enable_widget'] ) ) {
			return;
		}

		wp_enqueue_style(
			'shopchat-frontend',
			SHOPCHAT_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			SHOPCHAT_VERSION
		);

		wp_enqueue_script(
			'shopchat-frontend',
			SHOPCHAT_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			SHOPCHAT_VERSION,
			true
		);

		// Localize script for checkout tracking or widget triggers
		wp_localize_script(
			'shopchat-frontend',
			'shopchat_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'shopchat_frontend_nonce' ),
			)
		);
	}

	/**
	 * Render floating WhatsApp chat widget HTML
	 */
	public function render_widget() {
		$widget_settings  = get_option( 'shopchat_widget_settings', array() );
		$general_settings = get_option( 'shopchat_general_settings', array() );

		if ( empty( $widget_settings['enable_widget'] ) ) {
			return;
		}

		$phone = $general_settings['phone_number'] ?? '';
		if ( empty( $phone ) ) {
			return;
		}

		$position     = sanitize_html_class( $widget_settings['position'] ?? 'bottom-right' );
		$button_text  = esc_html( $widget_settings['button_text'] ?? __( 'Chat with us', 'shopchat' ) );
		$agent_name   = esc_html( $widget_settings['agent_name'] ?? __( 'Support Team', 'shopchat' ) );
		$agent_status = esc_html( $widget_settings['agent_status'] ?? __( 'Online now', 'shopchat' ) );
		$welcome_msg  = esc_html( $widget_settings['welcome_message'] ?? __( 'Hello! How can we help you today?', 'shopchat' ) );

		// Check if on single product page to prepare prefilled product inquiry
		$default_msg = 'Hello!';
		if ( function_exists( 'is_product' ) && is_product() ) {
			global $product;
			if ( $product instanceof \WC_Product ) {
				$template = $widget_settings['product_inquiry_template'] ?? 'Hi! I have a question about {product_name}: {product_url}';
				$default_msg = str_replace(
					array( '{product_name}', '{product_url}' ),
					array( $product->get_name(), get_permalink( $product->get_id() ) ),
					$template
				);
			}
		}

		$gateway  = new WaMeGateway();
		$chat_url = $gateway->get_chat_url( $phone, $default_msg );

		require SHOPCHAT_PLUGIN_DIR . 'templates/frontend/chat-widget.php';
	}
}
