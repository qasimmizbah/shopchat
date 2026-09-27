<?php
/**
 * Review Request Booster
 *
 * @package ShopChat\Notifications
 */

namespace ShopChat\Notifications;

use ShopChat\Gateways\WaMeGateway;
use ShopChat\Gateways\MetaCloudGateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewReminder {

	/**
	 * Register hooks
	 */
	public function init() {
		// Hook into cron event for scheduled review checks if needed
		add_action( 'shopchat_send_review_reminder', array( $this, 'dispatch_review_reminder' ), 10, 1 );
	}

	/**
	 * Generate WhatsApp Review URL for an order
	 *
	 * @param \WC_Order|int $order Order object or ID.
	 * @return string
	 */
	public static function get_whatsapp_review_url( $order ) {
		if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof \WC_Order ) {
			return '';
		}

		$templates = get_option( 'shopchat_templates', array() );
		$template  = $templates['review_request']['message'] ?? '';

		if ( empty( $template ) ) {
			return '';
		}

		$phone   = $order->get_billing_phone();
		$message = TemplateEngine::parse_order_template( $template, $order );

		$gateway = new WaMeGateway();
		return $gateway->get_chat_url( $phone, $message, $order->get_billing_country() );
	}

	/**
	 * Dispatch automated review reminder via cron
	 *
	 * @param int $order_id Order ID.
	 */
	public function dispatch_review_reminder( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || 'completed' !== $order->get_status() ) {
			return;
		}

		$general_settings = get_option( 'shopchat_general_settings', array() );
		$mode             = $general_settings['messaging_mode'] ?? 'wame';

		if ( 'meta_cloud' === $mode ) {
			$templates = get_option( 'shopchat_templates', array() );
			$template  = $templates['review_request']['message'] ?? '';
			$phone     = $order->get_billing_phone();

			if ( ! empty( $template ) && ! empty( $phone ) ) {
				$message = TemplateEngine::parse_order_template( $template, $order );
				$gateway = new MetaCloudGateway();
				$gateway->send( $phone, $message, array( 'country' => $order->get_billing_country() ) );

				$order->add_order_note( __( 'ShopChat: Review reminder WhatsApp message dispatched to customer.', 'shopchat' ) );
			}
		}
	}
}
