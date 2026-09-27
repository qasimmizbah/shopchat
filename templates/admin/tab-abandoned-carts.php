<?php
/**
 * Abandoned Carts Table View (Clean, Professional Minimalist Design)
 *
 * @package ShopChat\Templates\Admin
 */

use ShopChat\Database\CartDb;
use ShopChat\Gateways\WaMeGateway;
use ShopChat\Notifications\TemplateEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// 1. Automatically sync recent orders to mark matching carts as recovered
CartDb::sync_recent_orders();

// 2. Automatically process and dispatch any abandoned carts that crossed idle window
if ( class_exists( '\ShopChat\Frontend\CartTracker' ) ) {
	( new \ShopChat\Frontend\CartTracker() )->process_abandoned_carts();
}

// 2. Handle manual single cart deletion
if ( isset( $_GET['action'], $_GET['cart_id'], $_GET['_wpnonce'] ) && 'delete_cart' === $_GET['action'] ) {
	$cart_id = absint( wp_unslash( $_GET['cart_id'] ) );
	$nonce   = sanitize_key( wp_unslash( $_GET['_wpnonce'] ) );
	if ( current_user_can( 'manage_woocommerce' ) && wp_verify_nonce( $nonce, 'shopchat_delete_cart_' . $cart_id ) ) {
		CartDb::delete_cart( $cart_id );
		wp_safe_redirect( admin_url( 'admin.php?page=shopchat-settings&tab=abandoned-carts' ) );
		exit;
	}
}

// 3. Filter status: default to 'abandoned' (active pending only)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cart_filter      = isset( $_GET['cart_status'] ) ? sanitize_key( wp_unslash( $_GET['cart_status'] ) ) : 'abandoned';
$count_abandoned  = CartDb::get_count( 'abandoned' );
$count_recovered  = CartDb::get_count( 'recovered' );
$count_all        = CartDb::get_count();

$db_status_filter = ( 'all' === $cart_filter ) ? '' : ( 'recovered' === $cart_filter ? 'recovered' : 'abandoned' );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page_num         = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;
$carts            = CartDb::get_carts( 25, $page_num, $db_status_filter );
$templates        = get_option( 'shopchat_templates', array() );
$tpl              = $templates['abandoned_cart']['message'] ?? '';
$general_settings = get_option( 'shopchat_general_settings', array() );
$mode             = $general_settings['messaging_mode'] ?? 'wame';
$gateway          = new WaMeGateway();
?>
<div class="shopchat-card">
	<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
		<div>
			<h3 class="shopchat-card-title" style="margin-bottom: 4px;"><?php esc_html_e( 'Abandoned Carts', 'shopchat' ); ?></h3>
			<p class="shopchat-card-description" style="margin-bottom: 0;"><?php esc_html_e( 'Shoppers who started checkout without completing payment. Use 1-click WhatsApp links to recover orders.', 'shopchat' ); ?></p>
		</div>
	</div>

	<!-- Status Filter Buttons -->
	<div class="shopchat-filter-pills" style="margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=abandoned-carts&cart_status=abandoned' ) ); ?>" class="button <?php echo 'abandoned' === $cart_filter ? 'button-primary' : 'button-secondary'; ?>">
			<?php esc_html_e( 'Active Abandoned', 'shopchat' ); ?> <span class="count">(<?php echo esc_html( $count_abandoned ); ?>)</span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=abandoned-carts&cart_status=recovered' ) ); ?>" class="button <?php echo 'recovered' === $cart_filter ? 'button-primary' : 'button-secondary'; ?>">
			<?php esc_html_e( 'Recovered', 'shopchat' ); ?> <span class="count">(<?php echo esc_html( $count_recovered ); ?>)</span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=abandoned-carts&cart_status=all' ) ); ?>" class="button <?php echo 'all' === $cart_filter ? 'button-primary' : 'button-secondary'; ?>">
			<?php esc_html_e( 'All Sessions', 'shopchat' ); ?> <span class="count">(<?php echo esc_html( $count_all ); ?>)</span>
		</a>
	</div>

	<?php if ( empty( $carts ) ) : ?>
		<div class="shopchat-empty-state">
			<?php if ( 'abandoned' === $cart_filter ) : ?>
				<h4><?php esc_html_e( 'No Pending Abandoned Carts', 'shopchat' ); ?></h4>
				<p><?php esc_html_e( 'All recent shopper carts have been completed or converted into orders!', 'shopchat' ); ?></p>
			<?php elseif ( 'recovered' === $cart_filter ) : ?>
				<h4><?php esc_html_e( 'No Recovered Carts Yet', 'shopchat' ); ?></h4>
				<p><?php esc_html_e( 'When customers complete their orders after receiving a recovery link, they will appear here.', 'shopchat' ); ?></p>
			<?php else : ?>
				<h4><?php esc_html_e( 'No Cart Sessions Captured', 'shopchat' ); ?></h4>
				<p><?php esc_html_e( 'When customers start entering their contact details at checkout, sessions will be captured here automatically.', 'shopchat' ); ?></p>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="shopchat-table-responsive">
			<table class="wp-list-table widefat striped table-view-list shopchat-table">
				<thead>
					<tr>
						<th class="col-customer"><?php esc_html_e( 'Customer', 'shopchat' ); ?></th>
						<th class="col-phone"><?php esc_html_e( 'Phone Number', 'shopchat' ); ?></th>
						<th class="col-items"><?php esc_html_e( 'Cart Items', 'shopchat' ); ?></th>
						<th class="col-total"><?php esc_html_e( 'Total', 'shopchat' ); ?></th>
						<th class="col-status"><?php esc_html_e( 'Status', 'shopchat' ); ?></th>
						<th class="col-activity"><?php esc_html_e( 'Last Activity', 'shopchat' ); ?></th>
						<th class="col-action"><?php esc_html_e( 'Action', 'shopchat' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $carts as $cart ) :
						$phone      = $cart['customer_phone'];
						$name       = ! empty( $cart['customer_name'] ) ? $cart['customer_name'] : __( 'Guest Customer', 'shopchat' );
						$items      = maybe_unserialize( $cart['cart_contents'] );
						$status     = $cart['status'];
						$message    = TemplateEngine::parse_cart_template( $tpl, $cart );
						$wa_url     = $gateway->get_chat_url( $phone, $message );
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'page'        => 'shopchat-settings',
									'tab'         => 'abandoned-carts',
									'cart_status' => $cart_filter,
									'action'      => 'delete_cart',
									'cart_id'     => $cart['id'],
								),
								admin_url( 'admin.php' )
							),
							'shopchat_delete_cart_' . $cart['id']
						);
						?>
						<tr>
							<td class="col-customer">
								<strong><?php echo esc_html( $name ); ?></strong>
								<?php if ( ! empty( $cart['customer_email'] ) ) : ?>
									<br><small style="color: #64748b;"><?php echo esc_html( $cart['customer_email'] ); ?></small>
								<?php endif; ?>
							</td>
							<td class="col-phone">
								<span style="font-family: monospace; font-size: 13px;"><?php echo esc_html( $phone ? $phone : '—' ); ?></span>
							</td>
							<td class="col-items">
								<?php
								if ( is_array( $items ) && ! empty( $items ) ) {
									$names = array();
									foreach ( $items as $it ) {
										$names[] = esc_html( $it['name'] ?? '' ) . ' (x' . absint( $it['quantity'] ?? 1 ) . ')';
									}
									echo esc_html( implode( ', ', $names ) );
								} else {
									echo '—';
								}
								?>
							</td>
							<td class="col-total">
								<strong>
									<?php
									$c_total = $cart['cart_total'] ?? 0;
									echo function_exists( 'wc_price' ) ? wp_kses_post( wc_price( $c_total, array( 'currency' => $cart['currency'] ?? 'USD' ) ) ) : esc_html( '$' . number_format( (float) $c_total, 2 ) );
									?>
								</strong>
							</td>
							<td class="col-status">
								<?php if ( 'recovered' === $status ) : ?>
									<span class="shopchat-status-pill pill-recovered"><?php esc_html_e( 'Recovered', 'shopchat' ); ?></span>
								<?php else : ?>
									<span class="shopchat-status-pill pill-abandoned"><?php esc_html_e( 'Abandoned', 'shopchat' ); ?></span>
									<?php if ( ! empty( $cart['mail_sent'] ) ) : ?>
										<br><small style="color: #16a34a; font-weight: 600; font-size: 11px;">✓ <?php esc_html_e( 'Recovery Sent', 'shopchat' ); ?></small>
									<?php else : ?>
										<br><small style="color: #94a3b8; font-size: 11px;"><?php esc_html_e( 'Pending Dispatch', 'shopchat' ); ?></small>
									<?php endif; ?>
								<?php endif; ?>
							</td>
							<td class="col-activity">
								<span title="<?php echo esc_attr( $cart['updated_at'] ); ?>">
									<?php echo esc_html( human_time_diff( strtotime( $cart['updated_at'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'shopchat' ) ); ?>
								</span>
							</td>
							<td class="col-action">
								<?php if ( 'recovered' !== $status && ! empty( $phone ) ) : ?>
									<?php if ( 'meta_cloud' === $mode ) : ?>
										<button type="button" class="button button-small shopchat-send-api-btn" data-cart-id="<?php echo esc_attr( $cart['id'] ); ?>" style="margin-right: 6px; background: #2563eb; color: #fff; border-color: #1d4ed8; font-weight: 500;">
											<?php echo ! empty( $cart['mail_sent'] ) ? esc_html__( 'Resend API', 'shopchat' ) : esc_html__( 'Send API', 'shopchat' ); ?>
										</button>
										<a href="<?php echo esc_attr( WaMeGateway::escape_url( $wa_url ) ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small shopchat-table-btn" style="margin-right: 6px;" title="<?php esc_attr_e( 'Open in WhatsApp Web/App', 'shopchat' ); ?>">
											wa.me
										</a>
									<?php else : ?>
										<a href="<?php echo esc_attr( WaMeGateway::escape_url( $wa_url ) ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small shopchat-table-btn" style="margin-right: 6px;">
											<?php esc_html_e( 'Send WhatsApp', 'shopchat' ); ?>
										</a>
									<?php endif; ?>
								<?php endif; ?>

								<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small" style="color: #dc2626;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this cart record?', 'shopchat' ); ?>');" title="<?php esc_attr_e( 'Delete record', 'shopchat' ); ?>">
									<?php esc_html_e( 'Delete', 'shopchat' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
