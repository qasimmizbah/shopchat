<?php
/**
 * WooCommerce Order Details WhatsApp Meta Box (HPOS Compatible)
 *
 * @package ShopChat\Admin
 */

namespace ShopChat\Admin;

use ShopChat\Gateways\WaMeGateway;
use ShopChat\Notifications\TemplateEngine;
use ShopChat\Notifications\ReviewReminder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrderMetaBox {

	/**
	 * Register hooks
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Register order meta box for both Classic and HPOS screens
	 */
	public function register_meta_box() {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

		add_meta_box(
			'shopchat_order_metabox',
			__( 'ShopChat: WhatsApp Actions', 'shopchat' ),
			array( $this, 'render_meta_box' ),
			$screen,
			'side',
			'high'
		);

		// Fallback for classic shop_order post type
		if ( 'shop_order' !== $screen ) {
			add_meta_box(
				'shopchat_order_metabox',
				__( 'ShopChat: WhatsApp Actions', 'shopchat' ),
				array( $this, 'render_meta_box' ),
				'shop_order',
				'side',
				'high'
			);
		}
	}

	/**
	 * Render the WhatsApp meta box
	 *
	 * @param \WP_Post|\WC_Order $post_or_order Post or Order object depending on screen.
	 */
	public function render_meta_box( $post_or_order ) {
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : ( function_exists( 'wc_get_order' ) ? wc_get_order( $post_or_order->ID ?? 0 ) : null );

		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Order data not found.', 'shopchat' ) . '</p>';
			return;
		}

		$phone = $order->get_billing_phone();

		if ( empty( $phone ) ) {
			?>
			<div class="shopchat-metabox-notice">
				<p><?php esc_html_e( 'No customer phone number found on this order.', 'shopchat' ); ?></p>
			</div>
			<?php
			return;
		}

		$templates = get_option( 'shopchat_templates', array() );
		$status    = $order->get_status();
		$template  = $templates[ $status ]['message'] ?? ( $templates['processing']['message'] ?? '' );
		$message   = TemplateEngine::parse_order_template( $template, $order );

		$gateway   = new WaMeGateway();
		$order_url = $gateway->get_chat_url( $phone, $message, $order->get_billing_country() );
		$rev_url   = ReviewReminder::get_whatsapp_review_url( $order );
		?>
		<div class="shopchat-order-metabox-content">
			<div class="shopchat-phone-badge">
				<span class="dashicons dashicons-phone"></span>
				<strong><?php echo esc_html( $phone ); ?></strong>
			</div>

			<div class="shopchat-action-group" style="margin-top: 12px;">
				<a href="<?php echo esc_attr( WaMeGateway::escape_url( $order_url ) ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary shopchat-wa-btn" style="width: 100%; text-align: center; background: #128C7E; border-color: #075E54; color: #fff; font-weight: 600; padding: 6px 12px; margin-bottom: 8px;">
					<?php esc_html_e( 'Send Status Update', 'shopchat' ); ?>
				</a>

				<?php if ( ! empty( $rev_url ) ) : ?>
					<a href="<?php echo esc_attr( WaMeGateway::escape_url( $rev_url ) ); ?>" target="_blank" rel="noopener noreferrer" class="button shopchat-review-btn" style="width: 100%; text-align: center; margin-bottom: 8px;">
						<?php esc_html_e( 'Request Review', 'shopchat' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<hr style="margin: 12px 0; border: 0; border-top: 1px solid #e2e8f0;">

			<details>
				<summary style="cursor: pointer; font-size: 12px; color: #64748b; margin-bottom: 6px;">
					<?php esc_html_e( 'Preview Prepared Message', 'shopchat' ); ?>
				</summary>
				<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; font-size: 11px; white-space: pre-wrap; color: #334155; max-height: 120px; overflow-y: auto;">
					<?php echo esc_html( $message ); ?>
				</div>
			</details>
		</div>
		<?php
	}
}
