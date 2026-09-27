<?php
/**
 * Plugin activation handler
 *
 * @package ShopChat\Core
 */

namespace ShopChat\Core;

use ShopChat\Database\CartDb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	/**
	 * Run activation tasks
	 */
	public static function activate() {
		// 0. Verify WooCommerce dependency
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( SHOPCHAT_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'ShopChat requires WooCommerce to be installed and active before it can be activated.', 'shopchat' ),
				esc_html__( 'Plugin Activation Error', 'shopchat' ),
				array( 'back_link' => true )
			);
		}

		// 1. Create custom DB tables
		CartDb::create_tables();

		// 2. Set default General settings
		if ( ! get_option( 'shopchat_general_settings' ) ) {
			update_option(
				'shopchat_general_settings',
				array(
					'phone_number'          => '',
					'default_country_code'  => '91',
					'messaging_mode'        => 'wame', // 'wame' (Free 1-Click) or 'meta_cloud' (Automated)
					'meta_phone_number_id'  => '',
					'meta_access_token'     => '',
					'meta_waba_id'          => '',
					'enable_order_notify'   => '1',
					'enable_cart_recovery'  => '1',
					'cart_cutoff_minutes'   => 20,
				)
			);
		}

		// 3. Set default Message Templates
		if ( ! get_option( 'shopchat_templates' ) ) {
			update_option(
				'shopchat_templates',
				array(
					'processing' => array(
						'enabled' => '1',
						'message' => "Hi {customer_name},\n\nThank you for shopping at {site_title}.\nYour order #{order_id} for {order_total} has been received and is being processed.\n\nOrder Details:\n{order_url}\n\nNeed help? Feel free to reply directly to this message.",
					),
					'completed' => array(
						'enabled' => '1',
						'message' => "Hi {customer_name},\n\nYour order #{order_id} from {site_title} has been completed and shipped.\n\nTrack your order:\n{order_url}\n\nThank you for choosing {site_title}.",
					),
					'on_hold' => array(
						'enabled' => '0',
						'message' => "Hi {customer_name},\n\nYour order #{order_id} is currently on hold. If you have questions regarding payment or delivery, feel free to reply to this message.",
					),
					'cancelled' => array(
						'enabled' => '0',
						'message' => "Hello {customer_name},\n\nYour order #{order_id} has been cancelled. If this was a mistake, let us know and we will assist you right away.",
					),
					'abandoned_cart' => array(
						'enabled' => '1',
						'message' => "Hi {customer_name},\n\nYou left items in your shopping cart at {site_title}.\n\nYour cart is saved. You can complete your order directly here:\n{recovery_url}\n\nIf you need assistance, reply to this message.",
					),
					'review_request' => array(
						'enabled' => '1',
						'message' => "Hi {customer_name},\n\nThank you for your recent purchase from {site_title}.\n\nCould you please take a moment to share your review?\n{review_url}\n\nThank you for your feedback.",
					),
				)
			);
		}

		// 4. Set default Storefront Widget settings
		if ( ! get_option( 'shopchat_widget_settings' ) ) {
			update_option(
				'shopchat_widget_settings',
				array(
					'enable_widget'              => '1',
					'position'                   => 'bottom-right',
					'button_text'                => 'Chat with us',
					'agent_name'                 => 'Support Team',
					'agent_status'               => 'Online now',
					'agent_avatar'               => '',
					'welcome_message'            => 'Hello! How can we help you today?',
					'product_inquiry_template'   => 'Hi! I have a question about {product_name} ({product_url})',
					'hide_on_mobile'             => '0',
					'primary_color'              => '#25D366',
				)
			);
		}

		// 5. Ensure WooCommerce Checkout Phone Field is enabled and required
		if ( get_option( 'woocommerce_checkout_phone_field' ) !== 'required' ) {
			update_option( 'woocommerce_checkout_phone_field', 'required' );
		}
	}
}
