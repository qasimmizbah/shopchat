<?php
/**
 * Dynamic Template Engine for replacing merge tags
 *
 * @package ShopChat\Notifications
 */

namespace ShopChat\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TemplateEngine {

	/**
	 * Get list of all available merge tags with friendly descriptions
	 *
	 * @return array
	 */
	public static function get_available_tags() {
		return array(
			'{customer_name}' => __( 'Customer Full Name', 'shopchat' ),
			'{order_id}'      => __( 'Order ID / Number', 'shopchat' ),
			'{order_total}'   => __( 'Formatted Total Price', 'shopchat' ),
			'{order_status}'  => __( 'Current Order Status', 'shopchat' ),
			'{items_list}'    => __( 'List of items purchased', 'shopchat' ),
			'{order_url}'     => __( 'Order tracking / view URL', 'shopchat' ),
			'{site_title}'    => __( 'Your Store / Website Name', 'shopchat' ),
			'{recovery_url}'  => __( '1-Click Cart Restoration Link', 'shopchat' ),
			'{review_url}'    => __( 'Product Review Link', 'shopchat' ),
		);
	}

	/**
	 * Replace merge tags in a template for a WooCommerce Order
	 *
	 * @param string          $template Template content.
	 * @param \WC_Order|int   $order WooCommerce Order object or ID.
	 * @return string
	 */
	public static function parse_order_template( $template, $order ) {
		if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof \WC_Order ) {
			return $template;
		}

		$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		if ( empty( $customer_name ) ) {
			$customer_name = __( 'Customer', 'shopchat' );
		}

		// Prepare items list
		$items_summary = array();
		foreach ( $order->get_items() as $item ) {
			$items_summary[] = sprintf( '• %s (x%d)', $item->get_name(), $item->get_quantity() );
		}
		$items_list_str = implode( "\n", $items_summary );

		// Product review URL (first physical item or order received URL)
		$review_url = $order->get_view_order_url();
		$first_item = reset( $order->get_items() );
		if ( $first_item && $first_item->get_product_id() ) {
			$review_url = get_permalink( $first_item->get_product_id() ) . '#reviews';
		}

		$order_total_str  = function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' ) : (string) $order->get_total();
		$order_status_str = function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $order->get_status() ) : ucfirst( (string) $order->get_status() );

		$order_url = method_exists( $order, 'get_checkout_order_received_url' ) ? $order->get_checkout_order_received_url() : $order->get_view_order_url();
		if ( empty( $order_url ) ) {
			$order_url = $order->get_view_order_url();
		}

		$replacements = array(
			'{customer_name}' => $customer_name,
			'{order_id}'      => $order->get_order_number(),
			'{order_total}'   => $order_total_str,
			'{order_status}'  => $order_status_str,
			'{items_list}'    => $items_list_str,
			'{order_items}'   => $items_list_str,
			'{order_url}'     => esc_url_raw( $order_url ),
			'{site_title}'    => get_bloginfo( 'name' ),
			'{review_url}'    => esc_url_raw( $review_url ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/**
	 * Replace merge tags for Abandoned Cart recovery
	 *
	 * @param string $template Template string.
	 * @param array  $cart_row Cart row from database.
	 * @return string
	 */
	public static function parse_cart_template( $template, $cart_row ) {
		$customer_name = ! empty( $cart_row['customer_name'] ) ? $cart_row['customer_name'] : __( 'Customer', 'shopchat' );
		$total_price   = function_exists( 'wc_price' ) ? wc_price( $cart_row['cart_total'] ?? 0 ) : '$' . number_format( (float) ( $cart_row['cart_total'] ?? 0 ), 2 );
		$total_clean   = html_entity_decode( wp_strip_all_tags( $total_price ), ENT_QUOTES, 'UTF-8' );

		// Build 1-click cart recovery link
		$recovery_token = $cart_row['recovery_token'] ?? '';
		$checkout_url   = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
		$recovery_url   = add_query_arg(
			array(
				'shopchat_recover' => $recovery_token,
			),
			$checkout_url
		);

		$replacements = array(
			'{customer_name}' => $customer_name,
			'{order_total}'   => $total_clean,
			'{recovery_url}'  => esc_url_raw( $recovery_url ),
			'{site_title}'    => get_bloginfo( 'name' ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}
}
