<?php
/**
 * Database handler for Abandoned Carts
 *
 * @package ShopChat\Database
 */

namespace ShopChat\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

class CartDb {

	/**
	 * Get table name with WordPress prefix
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return esc_sql( $wpdb->prefix . 'shopchat_abandoned_carts' );
	}

	/**
	 * Create or update custom database tables using dbDelta
	 */
	public static function create_tables() {
		global $wpdb;

		$table_name      = esc_sql( self::get_table_name() );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT 0,
			session_id varchar(191) NOT NULL DEFAULT '',
			customer_name varchar(191) DEFAULT '',
			customer_phone varchar(50) NOT NULL DEFAULT '',
			customer_email varchar(191) DEFAULT '',
			cart_contents longtext NOT NULL,
			cart_total decimal(10,2) NOT NULL DEFAULT 0.00,
			currency varchar(10) NOT NULL DEFAULT 'USD',
			recovery_token varchar(64) NOT NULL DEFAULT '',
			status varchar(30) NOT NULL DEFAULT 'in-cart',
			mail_sent tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY session_id (session_id),
			KEY customer_phone (customer_phone),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Save or update cart session
	 *
	 * @param array $data Cart session data.
	 * @return int|false
	 */
	public static function save_cart( $data ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );

		$session_id     = sanitize_text_field( $data['session_id'] ?? '' );
		$phone          = sanitize_text_field( $data['customer_phone'] ?? '' );
		$name           = sanitize_text_field( $data['customer_name'] ?? '' );
		$email          = sanitize_email( $data['customer_email'] ?? '' );
		$cart_contents  = maybe_serialize( $data['cart_contents'] ?? array() );
		$cart_total     = floatval( $data['cart_total'] ?? 0 );
		$currency       = sanitize_text_field( $data['currency'] ?? get_woocommerce_currency() );
		$recovery_token = sanitize_text_field( $data['recovery_token'] ?? wp_generate_password( 32, false ) );
		$user_id        = absint( $data['user_id'] ?? get_current_user_id() );
		$now            = current_time( 'mysql' );

		// Check if record exists for this session or phone (only match unrecovered active sessions)
		$existing = null;
		if ( ! empty( $session_id ) && '1' !== $session_id && ! empty( $phone ) ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
					"SELECT id FROM {$table_name} WHERE (session_id = %s OR customer_phone = %s) AND status != 'recovered' ORDER BY id DESC LIMIT 1",
					$session_id,
					$phone
				)
			);
		} elseif ( ! empty( $session_id ) && '1' !== $session_id ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
					"SELECT id FROM {$table_name} WHERE session_id = %s AND status != 'recovered' ORDER BY id DESC LIMIT 1",
					$session_id
				)
			);
		} elseif ( ! empty( $phone ) ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
					"SELECT id FROM {$table_name} WHERE customer_phone = %s AND status != 'recovered' ORDER BY id DESC LIMIT 1",
					$phone
				)
			);
		}

		if ( $existing ) {
			$wpdb->update(
				$table_name,
				array(
					'customer_name'  => $name,
					'customer_phone' => $phone,
					'customer_email' => $email,
					'cart_contents'  => $cart_contents,
					'cart_total'     => $cart_total,
					'currency'       => $currency,
					'status'         => 'abandoned',
					'updated_at'     => $now,
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' ),
				array( '%d' )
			);
			return $existing->id;
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'user_id'        => $user_id,
				'session_id'     => $session_id,
				'customer_name'  => $name,
				'customer_phone' => $phone,
				'customer_email' => $email,
				'cart_contents'  => $cart_contents,
				'cart_total'     => $cart_total,
				'currency'       => $currency,
				'recovery_token' => $recovery_token,
				'status'         => 'abandoned',
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s' )
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Mark abandoned cart as recovered when matching WooCommerce order is placed
	 *
	 * @param \WC_Order|int $order Order instance or order ID.
	 * @return bool
	 */
	public static function mark_order_recovered( $order ) {
		global $wpdb;
		if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		$table_name = esc_sql( self::get_table_name() );
		$now        = current_time( 'mysql' );

		// 1. Direct match if specific cart ID is saved in order meta
		$specific_cart_id = $order->get_meta( '_shopchat_recovered_cart_id' );
		if ( ! empty( $specific_cart_id ) ) {
			return (bool) $wpdb->update(
				$table_name,
				array(
					'status'     => 'recovered',
					'updated_at' => $now,
				),
				array( 'id' => absint( $specific_cart_id ) ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		// 2. Match phone or email, strictly ensuring the cart was created BEFORE or DURING order placement
		$phone   = preg_replace( '/[^0-9]/', '', (string) $order->get_billing_phone() );
		$last_10 = strlen( $phone ) >= 10 ? substr( $phone, -10 ) : $phone;
		$email   = sanitize_email( $order->get_billing_email() );

		if ( empty( $last_10 ) && empty( $email ) ) {
			return false;
		}

		// Determine order creation time with 120s buffer for checkout processing latency
		$order_date = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : current_time( 'mysql' );
		$order_ts   = strtotime( $order_date );
		$max_cart   = gmdate( 'Y-m-d H:i:s', $order_ts + 120 );

		$cart_id = 0;
		if ( ! empty( $last_10 ) && ! empty( $email ) ) {
			$cart_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
					"SELECT id FROM {$table_name} WHERE (customer_phone LIKE %s OR customer_email = %s) AND status = 'abandoned' AND created_at <= %s ORDER BY id DESC LIMIT 1",
					'%' . $wpdb->esc_like( $last_10 ),
					$email,
					$max_cart
				)
			);
		} elseif ( ! empty( $last_10 ) ) {
			$cart_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
					"SELECT id FROM {$table_name} WHERE customer_phone LIKE %s AND status = 'abandoned' AND created_at <= %s ORDER BY id DESC LIMIT 1",
					'%' . $wpdb->esc_like( $last_10 ),
					$max_cart
				)
			);
		} elseif ( ! empty( $email ) ) {
			$cart_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
					"SELECT id FROM {$table_name} WHERE customer_email = %s AND status = 'abandoned' AND created_at <= %s ORDER BY id DESC LIMIT 1",
					$email,
					$max_cart
				)
			);
		}

		if ( $cart_id > 0 ) {
			return (bool) $wpdb->update(
				$table_name,
				array(
					'status'     => 'recovered',
					'updated_at' => $now,
				),
				array( 'id' => $cart_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		return false;
	}

	/**
	 * Mark cart as recovered by token, session, or phone
	 *
	 * @param string $token_or_session Recovery token, session ID, or phone.
	 * @return bool
	 */
	public static function mark_recovered( $token_or_session ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );
		$clean      = sanitize_text_field( $token_or_session );

		return (bool) $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				"UPDATE {$table_name} SET status = 'recovered', updated_at = %s WHERE recovery_token = %s OR session_id = %s OR customer_phone = %s OR customer_email = %s",
				current_time( 'mysql' ),
				$clean,
				$clean,
				$clean,
				$clean
			)
		);
	}

	/**
	 * Automatically sync recovered carts against recent WooCommerce orders
	 */
	public static function sync_recent_orders() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		$recent_orders = wc_get_orders(
			array(
				'limit'   => 50,
				'status'  => array( 'processing', 'completed', 'on-hold' ),
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		foreach ( $recent_orders as $order ) {
			self::mark_order_recovered( $order );
		}
	}

	/**
	 * Delete a single cart record
	 *
	 * @param int $cart_id Cart ID.
	 * @return bool
	 */
	public static function delete_cart( $cart_id ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );
		return (bool) $wpdb->delete( $table_name, array( 'id' => absint( $cart_id ) ), array( '%d' ) );
	}

	/**
	 * Fetch abandoned carts for Admin List Table
	 *
	 * @param int    $per_page Items per page.
	 * @param int    $page_number Page number.
	 * @param string $status Filter by status.
	 * @return array
	 */
	public static function get_carts( $per_page = 20, $page_number = 1, $status = '' ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );
		$offset     = ( $page_number - 1 ) * $per_page;

		if ( ! empty( $status ) ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
					"SELECT * FROM {$table_name} WHERE status = %s ORDER BY updated_at DESC LIMIT %d OFFSET %d",
					$status,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				"SELECT * FROM {$table_name} ORDER BY updated_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);
	}

	/**
	 * Total carts count
	 *
	 * @param string $status Filter by status.
	 * @return int
	 */
	public static function get_count( $status = '' ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );

		if ( ! empty( $status ) ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
					"SELECT COUNT(*) FROM {$table_name} WHERE status = %s",
					$status
				)
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
	}

	/**
	 * Get stats for dashboard
	 *
	 * @return array
	 */
	public static function get_stats() {
		self::sync_recent_orders();

		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );

		// Total abandoned
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$abandoned_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'abandoned'" );

		// Total recovered
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$recovered_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'recovered'" );

		// Total recovered revenue
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$recovered_revenue = (float) $wpdb->get_var( "SELECT SUM(cart_total) FROM {$table_name} WHERE status = 'recovered'" );

		return array(
			'abandoned_count'   => $abandoned_count,
			'recovered_count'   => $recovered_count,
			'recovered_revenue' => $recovered_revenue,
		);
	}

	/**
	 * Get cart by recovery token
	 *
	 * @param string $token Recovery token.
	 * @return array|null
	 */
	public static function get_by_token( $token ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );
		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				"SELECT * FROM {$table_name} WHERE recovery_token = %s LIMIT 1",
				$token
			),
			ARRAY_A
		);
	}

	/**
	 * Get abandoned carts pending recovery notification
	 *
	 * @param string $cutoff_threshold Date string.
	 * @param int    $limit Limit.
	 * @return array
	 */
	public static function get_abandoned_pending_recovery( $cutoff_threshold, $limit = 20 ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				"SELECT * FROM {$table_name} WHERE status = 'abandoned' AND mail_sent = 0 AND updated_at <= %s AND customer_phone != '' ORDER BY id ASC LIMIT %d",
				$cutoff_threshold,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get cart by ID
	 *
	 * @param int $cart_id Cart ID.
	 * @return array|null
	 */
	public static function get_by_id( $cart_id ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );
		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				"SELECT * FROM {$table_name} WHERE id = %d LIMIT 1",
				$cart_id
			),
			ARRAY_A
		);
	}

	/**
	 * Mark mail as sent for a cart
	 *
	 * @param int  $cart_id Cart ID.
	 * @param bool $touch_timestamp Whether to update updated_at.
	 * @return bool
	 */
	public static function mark_mail_sent( $cart_id, $touch_timestamp = false ) {
		global $wpdb;
		$table_name = esc_sql( self::get_table_name() );
		$data       = array( 'mail_sent' => 1 );
		$format     = array( '%d' );

		if ( $touch_timestamp ) {
			$data['updated_at'] = current_time( 'mysql' );
			$format[]           = '%s';
		}

		return (bool) $wpdb->update(
			$table_name,
			$data,
			array( 'id' => absint( $cart_id ) ),
			$format,
			array( '%d' )
		);
	}
}
