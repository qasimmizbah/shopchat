<?php
/**
 * Overview & Metrics Tab (Clean, Professional Minimalist Design)
 *
 * @package ShopChat\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$phone_configured = ! empty( $general_settings['phone_number'] );
$notify_enabled   = ! empty( $general_settings['enable_order_notify'] );
$widget_enabled   = ! empty( $widget_settings['enable_widget'] );
?>
<div class="shopchat-overview-grid">
	<!-- Top Metric Cards (Stripe-inspired clean typography) -->
	<div class="shopchat-metrics-row">
		<div class="shopchat-metric-card">
			<div class="shopchat-metric-header">
				<span class="shopchat-metric-label"><?php esc_html_e( 'Carts Abandoned', 'shopchat' ); ?></span>
			</div>
			<div class="shopchat-metric-value"><?php echo esc_html( $stats['abandoned_count'] ?? 0 ); ?></div>
			<div class="shopchat-metric-subtext"><?php esc_html_e( 'Shoppers pending checkout', 'shopchat' ); ?></div>
		</div>

		<div class="shopchat-metric-card">
			<div class="shopchat-metric-header">
				<span class="shopchat-metric-label"><?php esc_html_e( 'Recovered Revenue', 'shopchat' ); ?></span>
			</div>
			<div class="shopchat-metric-value">
				<?php
				$recovered_rev = $stats['recovered_revenue'] ?? 0;
				echo function_exists( 'wc_price' ) ? wp_kses_post( wc_price( $recovered_rev ) ) : esc_html( '$' . number_format( (float) $recovered_rev, 2 ) );
				?>
			</div>
			<div class="shopchat-metric-subtext"><?php esc_html_e( 'Rescued through WhatsApp', 'shopchat' ); ?></div>
		</div>

		<div class="shopchat-metric-card">
			<div class="shopchat-metric-header">
				<span class="shopchat-metric-label"><?php esc_html_e( 'Carts Recovered', 'shopchat' ); ?></span>
			</div>
			<div class="shopchat-metric-value"><?php echo esc_html( $stats['recovered_count'] ?? 0 ); ?></div>
			<div class="shopchat-metric-subtext"><?php esc_html_e( 'Completed checkout orders', 'shopchat' ); ?></div>
		</div>

		<div class="shopchat-metric-card">
			<div class="shopchat-metric-header">
				<span class="shopchat-metric-label"><?php esc_html_e( 'Messaging Mode', 'shopchat' ); ?></span>
			</div>
			<div class="shopchat-metric-value shopchat-metric-text">
				<?php echo ( 'wame' === ( $general_settings['messaging_mode'] ?? 'wame' ) ) ? esc_html__( 'Free Direct', 'shopchat' ) : esc_html__( 'Meta API', 'shopchat' ); ?>
			</div>
			<div class="shopchat-metric-subtext"><?php echo ( 'wame' === ( $general_settings['messaging_mode'] ?? 'wame' ) ) ? esc_html__( 'Zero API fees', 'shopchat' ) : esc_html__( 'Cloud API connected', 'shopchat' ); ?></div>
		</div>
	</div>

	<!-- Setup Checklist & Best Practices -->
	<div class="shopchat-two-columns">
		<div class="shopchat-card">
			<div class="shopchat-card-header">
				<h3 class="shopchat-card-title"><?php esc_html_e( 'Setup Checklist', 'shopchat' ); ?></h3>
				<span class="shopchat-badge-count">
					<?php
					$done = ( $phone_configured ? 1 : 0 ) + ( $notify_enabled ? 1 : 0 ) + ( $widget_enabled ? 1 : 0 );
					echo esc_html( $done ) . ' ' . esc_html__( 'of 3 completed', 'shopchat' );
					?>
				</span>
			</div>
			<p class="shopchat-card-description"><?php esc_html_e( 'Follow these configuration steps to start engaging shoppers on WhatsApp.', 'shopchat' ); ?></p>

			<div class="shopchat-checklist">
				<div class="shopchat-check-item <?php echo $phone_configured ? 'is-complete' : 'is-pending'; ?>">
					<div class="shopchat-check-indicator">
						<?php echo $phone_configured ? '✓' : '1'; ?>
					</div>
					<div class="shopchat-check-text">
						<strong><?php esc_html_e( 'Connect WhatsApp Number', 'shopchat' ); ?></strong>
						<p><?php esc_html_e( 'Specify your store support phone number with country code.', 'shopchat' ); ?></p>
					</div>
					<div class="shopchat-check-action">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=general' ) ); ?>" class="button button-secondary">
							<?php echo $phone_configured ? esc_html__( 'Edit', 'shopchat' ) : esc_html__( 'Configure', 'shopchat' ); ?>
						</a>
					</div>
				</div>

				<div class="shopchat-check-item <?php echo $notify_enabled ? 'is-complete' : 'is-pending'; ?>">
					<div class="shopchat-check-indicator">
						<?php echo $notify_enabled ? '✓' : '2'; ?>
					</div>
					<div class="shopchat-check-text">
						<strong><?php esc_html_e( 'Configure Notification Templates', 'shopchat' ); ?></strong>
						<p><?php esc_html_e( 'Review and personalize order status messages.', 'shopchat' ); ?></p>
					</div>
					<div class="shopchat-check-action">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=templates' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Manage', 'shopchat' ); ?>
						</a>
					</div>
				</div>

				<div class="shopchat-check-item <?php echo $widget_enabled ? 'is-complete' : 'is-pending'; ?>">
					<div class="shopchat-check-indicator">
						<?php echo $widget_enabled ? '✓' : '3'; ?>
					</div>
					<div class="shopchat-check-text">
						<strong><?php esc_html_e( 'Storefront Floating Widget', 'shopchat' ); ?></strong>
						<p><?php esc_html_e( 'Enable direct product inquiries from storefront pages.', 'shopchat' ); ?></p>
					</div>
					<div class="shopchat-check-action">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=widget' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Settings', 'shopchat' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>

		<div class="shopchat-card">
			<div class="shopchat-card-header">
				<h3 class="shopchat-card-title"><?php esc_html_e( 'Recommendations', 'shopchat' ); ?></h3>
			</div>
			<p class="shopchat-card-description"><?php esc_html_e( 'Proven strategies to maximize conversions and customer engagement.', 'shopchat' ); ?></p>

			<div class="shopchat-recommendation-list">
				<div class="shopchat-rec-item">
					<strong><?php esc_html_e( 'Abandoned Cart Recovery Window', 'shopchat' ); ?></strong>
					<p><?php esc_html_e( 'Dispatching a cart recovery message between 20 to 60 minutes after checkout abandonment yields the highest conversion return before customer intent cools down.', 'shopchat' ); ?></p>
				</div>

				<div class="shopchat-rec-item">
					<strong><?php esc_html_e( 'Post-Purchase Review Timing', 'shopchat' ); ?></strong>
					<p><?php esc_html_e( 'Trigger review requests 2 to 4 days after order delivery. WhatsApp conversion for review collection is up to 4x higher than standard email notifications.', 'shopchat' ); ?></p>
				</div>

				<div class="shopchat-rec-item">
					<strong><?php esc_html_e( 'Direct Send vs Automated API', 'shopchat' ); ?></strong>
					<p><?php esc_html_e( 'Free Direct Send (wa.me) has zero setup requirements and zero per-message charges. Switch to Meta Cloud API only when your store reaches high daily order volumes.', 'shopchat' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
