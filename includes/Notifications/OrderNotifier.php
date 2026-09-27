<?php
/**
 * Order Status Change Listener & Notifier
 *
 * @package ShopChat\Notifications
 */

namespace ShopChat\Notifications;

use ShopChat\Gateways\WaMeGateway;
use ShopChat\Gateways\MetaCloudGateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrderNotifier {

	/**
	 * Register hooks
	 */
	public function init() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'notify_admin_new_order' ), 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'notify_admin_new_order_from_store_api' ), 10, 1 );

		// Add custom WhatsApp column to WooCommerce Orders screen (Classic & HPOS)
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_column' ), 10, 2 );

		// HPOS Order List hooks
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_order_column' ) );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_hpos_order_column' ), 10, 2 );
	}

	/**
	 * Get required parameter count for a Meta Cloud message template
	 *
	 * @param string $template_name Template name.
	 * @return int
	 */
	public function get_template_param_count( $template_name ) {
		$template_name = trim( (string) $template_name );
		if ( empty( $template_name ) ) {
			return 0;
		}

		// Known Meta sample templates
		if ( in_array( $template_name, array( 'hello_world', 'jaspers_market_plain_text_v1' ), true ) ) {
			return 0;
		}
		if ( 'jaspers_market_order_confirmation_v1' === $template_name ) {
			return 3;
		}

		// Check cached count in transient
		$cache_key = 'shopchat_tpl_params_' . md5( $template_name );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$general_settings = get_option( 'shopchat_general_settings', array() );
		$waba_id          = $general_settings['meta_waba_id'] ?? '';
		$token            = $general_settings['meta_access_token'] ?? '';

		if ( empty( $waba_id ) || empty( $token ) ) {
			return 3;
		}

		$url      = sprintf( 'https://graph.facebook.com/v19.0/%s/message_templates?name=%s', $waba_id, rawurlencode( $template_name ) );
		$response = wp_remote_get(
			$url,
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
				'timeout' => 10,
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $body['data'][0]['components'] ) ) {
				foreach ( $body['data'][0]['components'] as $comp ) {
					if ( ( $comp['type'] ?? '' ) === 'BODY' && ! empty( $comp['text'] ) ) {
						preg_match_all( '/\{\{(\d+)\}\}/', $comp['text'], $matches );
						$count = ! empty( $matches[1] ) ? max( array_map( 'intval', $matches[1] ) ) : 0;
						set_transient( $cache_key, $count, DAY_IN_SECONDS );
						return $count;
					}
				}
			}
		}

		return 3;
	}

	/**
	 * Handle WooCommerce order status transition
	 *
	 * @param int            $order_id Order ID.
	 * @param string         $old_status Previous status.
	 * @param string         $new_status New status.
	 * @param \WC_Order|null $order Order instance.
	 */
	public function handle_order_status_change( $order_id, $old_status, $new_status, $order = null ) {
		if ( ! $order instanceof \WC_Order && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		$general_settings = get_option( 'shopchat_general_settings', array() );

		// Check if notifications are enabled
		if ( empty( $general_settings['enable_order_notify'] ) ) {
			return;
		}

		$templates = get_option( 'shopchat_templates', array() );

		// Check if template exists and is enabled for this status
		if ( empty( $templates[ $new_status ]['enabled'] ) || empty( $templates[ $new_status ]['message'] ) ) {
			return;
		}

		// Prevent duplicate customer notifications for the exact same status
		$notified_key = '_shopchat_customer_notified_' . $new_status;
		if ( get_post_meta( $order_id, $notified_key, true ) ) {
			return;
		}

		$phone   = $order->get_billing_phone();
		$country = $order->get_billing_country();
		if ( empty( $phone ) ) {
			return;
		}

		$message = TemplateEngine::parse_order_template( $templates[ $new_status ]['message'], $order );

		// If Automated Meta Cloud API mode is chosen
		$mode = $general_settings['messaging_mode'] ?? 'wame';

		if ( 'meta_cloud' === $mode ) {
			$meta_template_name = sanitize_text_field( $templates[ $new_status ]['meta_template_name'] ?? '' );
			$args               = array( 'country' => $country );

			if ( ! empty( $meta_template_name ) ) {
				$args['template_name'] = $meta_template_name;
				$args['language']      = 'en_US';

				$param_count = $this->get_template_param_count( $meta_template_name );

				if ( $param_count > 0 ) {
					$customer_name = $order->get_billing_first_name() ? ( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) : 'Valued Customer';
					$order_id_str  = (string) $order->get_id();
					$total_str     = html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );

					$item_names = array();
					foreach ( $order->get_items() as $item ) {
						$item_names[] = $item->get_name() . ' (x' . $item->get_quantity() . ')';
					}
					$items_summary = ! empty( $item_names ) ? implode( ', ', $item_names ) : 'Order Items';
					$site_title    = get_bloginfo( 'name' );

					$candidate_params = array(
						array( 'type' => 'text', 'text' => $customer_name ),
						array( 'type' => 'text', 'text' => $order_id_str ),
						array( 'type' => 'text', 'text' => $total_str ),
						array( 'type' => 'text', 'text' => $items_summary ),
						array( 'type' => 'text', 'text' => $site_title ),
					);

					$args['components'] = array(
						array(
							'type'       => 'body',
							'parameters' => array_slice( $candidate_params, 0, $param_count ),
						),
					);
				} else {
					$args['components'] = array();
				}
			}

			$gateway = new MetaCloudGateway();
			$result  = $gateway->send( $phone, $message, $args );

			if ( ! empty( $result['success'] ) ) {
				update_post_meta( $order_id, $notified_key, current_time( 'mysql' ) );
				$order->add_order_note(
					sprintf(
						/* translators: %s: status name */
						__( 'ShopChat: Automated WhatsApp notification sent for status "%s".', 'shopchat' ),
						$new_status
					)
				);
			} else {
				$order->add_order_note(
					sprintf(
						/* translators: 1: status name, 2: error message */
						__( 'ShopChat: Failed to dispatch WhatsApp notification for status "%1$s". Error: %2$s', 'shopchat' ),
						$new_status,
						$result['message'] ?? ''
					)
				);
			}
		} else {
			// In Click-to-Chat Free Mode, we log the ready message in order meta so admin can send it with 1 click
			$wa_gateway = new WaMeGateway();
			$chat_url   = $wa_gateway->get_chat_url( $phone, $message, $country );

			update_post_meta( $order_id, '_shopchat_last_message', $message );
			update_post_meta( $order_id, '_shopchat_last_url', $chat_url );
			update_post_meta( $order_id, $notified_key, current_time( 'mysql' ) );
		}
	}

	/**
	 * Notify Store Owner (Admin) when a new order is placed
	 *
	 * @param int       $order_id Order ID.
	 * @param array     $posted_data Form post data.
	 * @param \WC_Order $order Order object.
	 */
	public function notify_admin_new_order( $order_id, $posted_data = array(), $order = null ) {
		if ( ! $order instanceof \WC_Order && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		$general_settings = get_option( 'shopchat_general_settings', array() );

		// Check if notifications are enabled
		if ( empty( $general_settings['enable_order_notify'] ) ) {
			return;
		}

		// Also verify customer notification triggered for initial status
		$status       = $order->get_status();
		$notified_key = '_shopchat_customer_notified_' . $status;
		if ( ! get_post_meta( $order_id, $notified_key, true ) ) {
			$this->handle_order_status_change( $order_id, '', $status, $order );
		}

		// Prevent duplicate admin alerts for the same order
		if ( get_post_meta( $order_id, '_shopchat_admin_notified', true ) ) {
			return;
		}

		$admin_phone = ! empty( $general_settings['admin_alert_phone'] ) ? $general_settings['admin_alert_phone'] : ( $general_settings['phone_number'] ?? '' );

		if ( empty( $admin_phone ) ) {
			return;
		}

		$store_country_code = $general_settings['store_country_code'] ?? '1';

		$customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$customer_phone = $order->get_billing_phone() ? $order->get_billing_phone() : __( 'Not provided', 'shopchat' );
		$order_total    = function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' ) : (string) $order->get_total();

		// Build items list
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = sprintf( '• %s (x%d)', $item->get_name(), $item->get_quantity() );
		}
		$items_str = implode( "\n", $items );

		$admin_msg = sprintf(
			/* translators: 1: site title, 2: order ID, 3: customer name, 4: customer phone, 5: total price, 6: items, 7: order url */
			__( "New Order Alert for %1\$s!\nOrder: #%2\$s\nCustomer: %3\$s\nPhone: %4\$s\nTotal: %5\$s\nItems:\n%6\$s\nView: %7\$s", 'shopchat' ),
			get_bloginfo( 'name' ),
			$order->get_order_number(),
			$customer_name ? $customer_name : __( 'Guest', 'shopchat' ),
			$customer_phone,
			$order_total,
			$items_str,
			admin_url( 'post.php?post=' . $order_id . '&action=edit' )
		);

		$mode = $general_settings['messaging_mode'] ?? 'wame';

		if ( 'meta_cloud' === $mode ) {
			$gateway = new MetaCloudGateway();
			$args    = array( 'country' => $store_country_code );
			$result  = $gateway->send( $admin_phone, $admin_msg, $args );

			// If text fails (e.g. 24-hr window policy), fallback to approved template
			if ( empty( $result['success'] ) ) {
				$template_args = array(
					'country'       => $store_country_code,
					'template_name' => 'jaspers_market_order_confirmation_v1',
					'language'      => 'en_US',
					'components'    => array(
						array(
							'type'       => 'body',
							'parameters' => array(
								array( 'type' => 'text', 'text' => 'Store Admin' ),
								array( 'type' => 'text', 'text' => '#' . $order->get_order_number() ),
								array( 'type' => 'text', 'text' => $order_total ),
							),
						),
					),
				);
				$result = $gateway->send( $admin_phone, '', $template_args );
			}

			if ( ! empty( $result['success'] ) ) {
				update_post_meta( $order_id, '_shopchat_admin_notified', current_time( 'mysql' ) );
				$order->add_order_note( __( 'ShopChat: Admin WhatsApp new order alert dispatched.', 'shopchat' ) );
			} else {
				$order->add_order_note(
					sprintf(
						/* translators: %s: error message */
						__( 'ShopChat: Admin WhatsApp alert failed. Error: %s', 'shopchat' ),
						$result['message'] ?? ''
					)
				);
			}
		} else {
			$gateway   = new WaMeGateway();
			$admin_url = $gateway->get_chat_url( $admin_phone, $admin_msg, $store_country_code );
			update_post_meta( $order_id, '_shopchat_admin_alert_url', $admin_url );
			update_post_meta( $order_id, '_shopchat_admin_notified', current_time( 'mysql' ) );
		}
	}

	/**
	 * Notify admin when order is processed via WooCommerce Store API (Block Checkout)
	 *
	 * @param \WC_Order $order Order instance.
	 */
	public function notify_admin_new_order_from_store_api( $order ) {
		if ( $order instanceof \WC_Order ) {
			$this->notify_admin_new_order( $order->get_id(), array(), $order );
		}
	}

	/**
	 * Add custom WhatsApp column to Orders list
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_order_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'order_status' === $key || 'status' === $key ) {
				$new_columns['shopchat_actions'] = __( 'WhatsApp', 'shopchat' );
			}
		}
		if ( ! isset( $new_columns['shopchat_actions'] ) ) {
			$new_columns['shopchat_actions'] = __( 'WhatsApp', 'shopchat' );
		}
		return $new_columns;
	}

	/**
	 * Render WhatsApp action button in Classic Orders column
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Order ID.
	 */
	public function render_order_column( $column, $post_id ) {
		if ( 'shopchat_actions' === $column && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $post_id );
			if ( $order ) {
				$this->render_column_button( $order );
			}
		}
	}

	/**
	 * Render WhatsApp action button in HPOS Orders column
	 *
	 * @param string    $column Column name.
	 * @param \WC_Order $order Order instance.
	 */
	public function render_hpos_order_column( $column, $order ) {
		if ( 'shopchat_actions' === $column && $order instanceof \WC_Order ) {
			$this->render_column_button( $order );
		}
	}

	/**
	 * Render clean 1-click WhatsApp button in table
	 *
	 * @param \WC_Order $order Order instance.
	 */
	protected function render_column_button( $order ) {
		$phone = $order->get_billing_phone();

		if ( empty( $phone ) ) {
			echo '<span style="color: #94a3b8; font-size: 11px;">' . esc_html__( 'No phone', 'shopchat' ) . '</span>';
			return;
		}

		$templates = get_option( 'shopchat_templates', array() );
		$status    = $order->get_status();
		$template  = $templates[ $status ]['message'] ?? ( $templates['processing']['message'] ?? '' );
		$message   = TemplateEngine::parse_order_template( $template, $order );

		$gateway  = new WaMeGateway();
		$chat_url = $gateway->get_chat_url( $phone, $message, $order->get_billing_country() );
		?>
		<a href="<?php echo esc_attr( WaMeGateway::escape_url( $chat_url ) ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small" style="background: #128C7E; color: #fff; border-color: #075E54; font-weight: 500; padding: 2px 8px; line-height: 1.6;" title="<?php esc_attr_e( 'Open WhatsApp Chat with customer', 'shopchat' ); ?>">
			WhatsApp
		</a>
		<?php
	}
}
