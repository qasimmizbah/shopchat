<?php
/**
 * Frontend Abandoned Cart Tracker & Cart Restorer
 *
 * @package ShopChat\Frontend
 */

namespace ShopChat\Frontend;

use ShopChat\Database\CartDb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CartTracker {

	/**
	 * Register hooks
	 */
	public function init() {
		// Enqueue scripts specifically for checkout tracking
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_scripts' ) );

		// AJAX Cart capture listeners
		add_action( 'wp_ajax_shopchat_capture_cart', array( $this, 'ajax_capture_cart' ) );
		add_action( 'wp_ajax_nopriv_shopchat_capture_cart', array( $this, 'ajax_capture_cart' ) );

		// Auto-capture on checkout load for logged-in users with items in cart
		add_action( 'template_redirect', array( $this, 'auto_capture_logged_in_cart' ) );

		// Checkout & Order completion hooks (Classic Checkout, Gutenberg Blocks, & Store API)
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_order_placed' ), 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'on_store_api_order_placed' ), 10, 1 );
		add_action( 'woocommerce_new_order', array( $this, 'on_new_order' ), 10, 2 );
		add_action( 'woocommerce_thankyou', array( $this, 'on_thankyou' ), 10, 1 );

		// Cart restoration link listener
		add_action( 'template_redirect', array( $this, 'handle_cart_recovery_link' ) );

		// Register 5-minute cron interval & automated abandoned cart recovery worker
		add_filter( 'cron_schedules', array( $this, 'register_cron_intervals' ) );
		add_action( 'shopchat_abandoned_cart_cron', array( $this, 'process_abandoned_carts' ) );

		$recurrence = wp_get_schedule( 'shopchat_abandoned_cart_cron' );
		if ( 'shopchat_every_five_minutes' !== $recurrence ) {
			wp_clear_scheduled_hook( 'shopchat_abandoned_cart_cron' );
			wp_schedule_event( time(), 'shopchat_every_five_minutes', 'shopchat_abandoned_cart_cron' );
		}

		// AJAX instant recovery send from Admin Abandoned Carts Table
		add_action( 'wp_ajax_shopchat_send_cart_recovery', array( $this, 'ajax_send_cart_recovery' ) );
	}

	/**
	 * Ensure tracking scripts are enqueued on checkout and cart
	 */
	public function enqueue_checkout_scripts() {
		if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) ) {
			wp_enqueue_script(
				'shopchat-frontend',
				SHOPCHAT_PLUGIN_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				SHOPCHAT_VERSION,
				true
			);

			wp_localize_script(
				'shopchat-frontend',
				'shopchat_vars',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'shopchat_frontend_nonce' ),
				)
			);
		}
	}

	/**
	 * Auto-capture cart when a logged in user visits checkout
	 */
	public function auto_capture_logged_in_cart() {
		if ( ! is_user_logged_in() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$general_settings = get_option( 'shopchat_general_settings', array() );
		if ( empty( $general_settings['enable_cart_recovery'] ) ) {
			return;
		}

		$user  = wp_get_current_user();
		$phone = get_user_meta( $user->ID, 'billing_phone', true );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $user->ID, 'phone', true );
		}
		$name = trim( $user->first_name . ' ' . $user->last_name );
		if ( empty( $name ) ) {
			$name = $user->display_name;
		}

		$cart_items = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$product      = $item['data'] ?? null;
			$cart_items[] = array(
				'product_id'   => $item['product_id'] ?? 0,
				'variation_id' => $item['variation_id'] ?? 0,
				'quantity'     => $item['quantity'] ?? 1,
				'name'         => $product ? $product->get_name() : '',
				'price'        => $product ? $product->get_price() : 0,
			);
		}

		$session_id = ( WC()->session && method_exists( WC()->session, 'get_customer_id' ) ) ? WC()->session->get_customer_id() : wp_generate_password( 24, false );

		CartDb::save_cart(
			array(
				'session_id'     => $session_id,
				'customer_name'  => $name,
				'customer_phone' => $phone ? $phone : '',
				'customer_email' => $user->user_email,
				'cart_contents'  => $cart_items,
				'cart_total'     => WC()->cart->get_total( 'edit' ),
				'currency'       => get_woocommerce_currency(),
				'user_id'        => $user->ID,
			)
		);
	}


	/**
	 * Capture customer details & cart items on checkout input blur via AJAX
	 */
	public function ajax_capture_cart() {
		check_ajax_referer( 'shopchat_frontend_nonce', 'nonce' );

		$general_settings = get_option( 'shopchat_general_settings', array() );
		if ( empty( $general_settings['enable_cart_recovery'] ) ) {
			wp_send_json_error( array( 'message' => 'Cart recovery disabled' ) );
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			wp_send_json_error( array( 'message' => 'Cart is empty' ) );
		}

		$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

		// Must have at least a phone number or email to recover
		if ( empty( $phone ) && empty( $email ) ) {
			wp_send_json_error( array( 'message' => 'No contact info provided' ) );
		}

		// Collect cart items summary
		$cart_items = array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $item ) {
			$product      = $item['data'];
			$cart_items[] = array(
				'product_id'   => $item['product_id'],
				'variation_id' => $item['variation_id'] ?? 0,
				'quantity'     => $item['quantity'],
				'name'         => $product ? $product->get_name() : '',
				'price'        => $product ? $product->get_price() : 0,
			);
		}

		$session_id = WC()->session ? WC()->session->get_customer_id() : wp_generate_password( 24, false );

		$saved_id = CartDb::save_cart(
			array(
				'session_id'     => $session_id,
				'customer_name'  => $name,
				'customer_phone' => $phone,
				'customer_email' => $email,
				'cart_contents'  => $cart_items,
				'cart_total'     => WC()->cart->get_total( 'edit' ),
				'currency'       => get_woocommerce_currency(),
				'user_id'        => get_current_user_id(),
			)
		);

		wp_send_json_success( array( 'cart_id' => $saved_id ) );
	}

	/**
	 * Mark cart as converted/recovered when order is successfully placed (Classic Checkout)
	 *
	 * @param int       $order_id Order ID.
	 * @param array     $posted_data Form data.
	 * @param \WC_Order $order Order object.
	 */
	public function on_order_placed( $order_id, $posted_data, $order ) {
		CartDb::mark_order_recovered( $order ? $order : $order_id );
	}

	/**
	 * Mark cart as converted when Gutenberg Checkout Block processes order (Store API)
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function on_store_api_order_placed( $order ) {
		CartDb::mark_order_recovered( $order );
	}

	/**
	 * Mark cart as converted on any new order creation
	 *
	 * @param int            $order_id Order ID.
	 * @param \WC_Order|null $order Order object.
	 */
	public function on_new_order( $order_id, $order = null ) {
		CartDb::mark_order_recovered( $order ? $order : $order_id );
	}

	/**
	 * Mark cart as converted when customer hits thank you page
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_thankyou( $order_id ) {
		CartDb::mark_order_recovered( $order_id );
	}

	/**
	 * Restore cart if customer visits with recovery link (?shopchat_recover=TOKEN)
	 */
	public function handle_cart_recovery_link() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public recovery link from WhatsApp
		if ( empty( $_GET['shopchat_recover'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public recovery link from WhatsApp
		$token = sanitize_text_field( wp_unslash( $_GET['shopchat_recover'] ) );
		if ( empty( $token ) ) {
			return;
		}

		$cart_row = CartDb::get_by_token( $token );

		if ( ! $cart_row ) {
			return;
		}

		if ( function_exists( 'WC' ) && WC()->cart ) {
			$items = maybe_unserialize( $cart_row['cart_contents'] );

			if ( is_array( $items ) && ! empty( $items ) ) {
				WC()->cart->empty_cart();

				foreach ( $items as $item ) {
					$product_id   = absint( $item['product_id'] ?? 0 );
					$quantity     = absint( $item['quantity'] ?? 1 );
					$variation_id = absint( $item['variation_id'] ?? 0 );

					if ( $product_id > 0 ) {
						WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
					}
				}

				// Add notice
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Your saved cart has been restored! Please complete your checkout below.', 'shopchat' ), 'success' );
				}

				// Redirect to checkout cleanly without query params
				$redir_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
				wp_safe_redirect( $redir_url );
				exit;
			}
		}
	}

	/**
	 * Process abandoned carts for automated background dispatch
	 */
	public function process_abandoned_carts() {
		$general_settings = get_option( 'shopchat_general_settings', array() );
		if ( empty( $general_settings['enable_cart_recovery'] ) ) {
			return;
		}

		$mode = $general_settings['messaging_mode'] ?? 'wame';
		if ( 'meta_cloud' !== $mode ) {
			return; // Direct mode uses 1-click manual send
		}

		$cutoff_minutes   = absint( $general_settings['cart_cutoff_minutes'] ?? 20 );
		$cutoff_threshold = gmdate( 'Y-m-d H:i:s', (int) current_time( 'timestamp' ) - ( $cutoff_minutes * 60 ) );

		$carts = CartDb::get_abandoned_pending_recovery( $cutoff_threshold, 20 );

		if ( empty( $carts ) ) {
			return;
		}

		$templates = get_option( 'shopchat_templates', array() );
		$tpl_data  = $templates['abandoned_cart'] ?? array();
		if ( empty( $tpl_data['enabled'] ) || empty( $tpl_data['message'] ) ) {
			return;
		}

		$gateway            = new \ShopChat\Gateways\MetaCloudGateway();
		$meta_template_name = sanitize_text_field( $tpl_data['meta_template_name'] ?? 'jaspers_market_order_confirmation_v1' );

		foreach ( $carts as $cart ) {
			$message = \ShopChat\Notifications\TemplateEngine::parse_cart_template( $tpl_data['message'], $cart );
			$args    = array();

			if ( ! empty( $meta_template_name ) ) {
				$args['template_name'] = $meta_template_name;
				$args['language']      = 'en_US';
				$args['components']    = $this->build_template_components( $meta_template_name, $cart );
			}

			$res = $gateway->send( $cart['customer_phone'], $message, $args );
			if ( ! empty( $res['success'] ) ) {
				CartDb::mark_mail_sent( $cart['id'], true );
			}
		}
	}

	/**
	 * AJAX handler to dispatch cart recovery message via API from Admin table
	 */
	public function ajax_send_cart_recovery() {
		check_ajax_referer( 'shopchat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'shopchat' ) ) );
		}

		$cart_id = isset( $_POST['cart_id'] ) ? absint( wp_unslash( $_POST['cart_id'] ) ) : 0;
		if ( ! $cart_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid cart ID.', 'shopchat' ) ) );
		}

		$cart = CartDb::get_by_id( $cart_id );

		if ( ! $cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not found.', 'shopchat' ) ) );
		}

		$phone = $cart['customer_phone'];
		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'No phone number for this cart.', 'shopchat' ) ) );
		}

		$templates = get_option( 'shopchat_templates', array() );
		$tpl_data  = $templates['abandoned_cart'] ?? array();
		$message   = \ShopChat\Notifications\TemplateEngine::parse_cart_template( $tpl_data['message'] ?? '', $cart );

		$gateway            = new \ShopChat\Gateways\MetaCloudGateway();
		$meta_template_name = sanitize_text_field( $tpl_data['meta_template_name'] ?? 'jaspers_market_order_confirmation_v1' );
		$args               = array();

		if ( ! empty( $meta_template_name ) ) {
			$args['template_name'] = $meta_template_name;
			$args['language']      = 'en_US';
			$args['components']    = $this->build_template_components( $meta_template_name, $cart );
		}

		$res = $gateway->send( $phone, $message, $args );

		if ( ! empty( $res['success'] ) ) {
			CartDb::mark_mail_sent( $cart_id );
			wp_send_json_success( array( 'message' => __( 'Recovery message dispatched via Meta Cloud API successfully!', 'shopchat' ) ) );
		} else {
			wp_send_json_error( array( 'message' => $res['message'] ?? __( 'Failed to dispatch message.', 'shopchat' ) ) );
		}
	}

	/**
	 * Build components array for Meta Cloud API template
	 *
	 * @param string $meta_template_name Template name.
	 * @param array  $cart Cart data.
	 * @return array
	 */
	public function build_template_components( $meta_template_name, $cart ) {
		if ( empty( $meta_template_name ) || 'hello_world' === $meta_template_name || 'jaspers_market_plain_text_v1' === $meta_template_name ) {
			return array();
		}

		$customer_name  = ! empty( $cart['customer_name'] ) ? $cart['customer_name'] : 'Customer';
		$cart_total_str = function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( $cart['cart_total'] ?? 0 ) ), ENT_QUOTES, 'UTF-8' ) : (string) ( $cart['cart_total'] ?? 0 );
		$recovery_token = $cart['recovery_token'] ?? '';
		$recovery_url   = home_url( '/?shopchat_recover=' . $recovery_token );

		if ( 'jaspers_market_order_confirmation_v1' === $meta_template_name ) {
			return array(
				array(
					'type'       => 'body',
					'parameters' => array(
						array( 'type' => 'text', 'text' => $customer_name ),
						array( 'type' => 'text', 'text' => 'Cart Recovery' ),
						array( 'type' => 'text', 'text' => $cart_total_str ),
					),
				),
			);
		}

		// Custom Template Support: Passes Customer Name, Store Name, Cart Total, Recovery URL
		return array(
			array(
				'type'       => 'body',
				'parameters' => array(
					array( 'type' => 'text', 'text' => $customer_name ),
					array( 'type' => 'text', 'text' => get_bloginfo( 'name' ) ),
					array( 'type' => 'text', 'text' => $cart_total_str ),
					array( 'type' => 'text', 'text' => $recovery_url ),
				),
			),
		);
	}

	/**
	 * Register custom cron intervals
	 *
	 * @param array $schedules Available cron schedules.
	 * @return array
	 */
	public function register_cron_intervals( $schedules ) {
		$schedules['shopchat_every_five_minutes'] = array(
			'interval' => 300,
			'display'  => esc_html__( 'Every 5 Minutes', 'shopchat' ),
		);
		return $schedules;
	}
}
