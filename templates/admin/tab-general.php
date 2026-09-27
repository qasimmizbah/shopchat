<?php
/**
 * General Settings Tab (Clean, Professional Minimalist Design)
 *
 * @package ShopChat\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$phone              = $general_settings['phone_number'] ?? '';
$admin_alert_phone  = $general_settings['admin_alert_phone'] ?? '917791026797';
$store_country_code = $general_settings['store_country_code'] ?? ( $general_settings['default_country_code'] ?? '1' );
$base_calling_code  = ( function_exists( 'WC' ) && WC()->countries && WC()->countries->get_base_country() ) ? preg_replace( '/[^0-9]/', '', (string) WC()->countries->get_country_calling_code( WC()->countries->get_base_country() ) ) : '91';
$country_code       = $general_settings['default_country_code'] ?? $base_calling_code;
$mode               = $general_settings['messaging_mode'] ?? 'wame';
$notify_on          = ! empty( $general_settings['enable_order_notify'] );
$cart_on            = ! empty( $general_settings['enable_cart_recovery'] );
$cart_cutoff        = $general_settings['cart_cutoff_minutes'] ?? 20;
?>
<form method="post" action="" class="shopchat-form">
	<?php wp_nonce_field( 'shopchat_save_settings_nonce', 'shopchat_nonce' ); ?>
	<input type="hidden" name="shopchat_save_action" value="1">
	<input type="hidden" name="current_tab" value="general">

	<!-- Business WhatsApp Number -->
	<div class="shopchat-card">
		<h3 class="shopchat-card-title"><?php esc_html_e( 'WhatsApp Business Account', 'shopchat' ); ?></h3>
		<p class="shopchat-card-description"><?php esc_html_e( 'Specify your store support WhatsApp number and the store owner WhatsApp number for new order alerts.', 'shopchat' ); ?></p>

		<div class="shopchat-form-row shopchat-form-phone-group">
			<div class="shopchat-input-prefix">
				<label for="shopchat_store_country_code"><?php esc_html_e( 'Country Code', 'shopchat' ); ?></label>
				<div class="shopchat-prefix-box">
					<span>+</span>
					<input type="number" id="shopchat_store_country_code" name="general_settings[store_country_code]" value="<?php echo esc_attr( $store_country_code ); ?>" placeholder="1" style="width: 70px;">
				</div>
			</div>
			<div class="shopchat-input-main" style="flex-grow: 1;">
				<label for="shopchat_phone"><?php esc_html_e( 'Store Support WhatsApp Phone Number', 'shopchat' ); ?></label>
				<input type="text" id="shopchat_phone" name="general_settings[phone_number]" value="<?php echo esc_attr( $phone ); ?>" placeholder="e.g. 5551761659" class="regular-text" style="width: 100%;">
				<p class="description"><?php esc_html_e( 'Digits only. Used for storefront chat widget and customer product inquiries.', 'shopchat' ); ?></p>
			</div>
		</div>

		<div class="shopchat-form-row" style="margin-top: 16px; border-top: 1px solid var(--sc-slate-100); padding-top: 16px;">
			<label for="shopchat_admin_alert_phone" style="font-weight: 500; color: var(--sc-slate-800);"><?php esc_html_e( 'Store Owner / Admin WhatsApp Phone for New Order Alerts', 'shopchat' ); ?></label>
			<input type="text" id="shopchat_admin_alert_phone" name="general_settings[admin_alert_phone]" value="<?php echo esc_attr( $admin_alert_phone ); ?>" placeholder="e.g. 917791026797" class="regular-text" style="max-width: 320px; display: block; margin-top: 6px;">
			<p class="description"><?php esc_html_e( 'Enter the WhatsApp number where you want to receive instant new order alerts (e.g. 917791026797).', 'shopchat' ); ?></p>
		</div>

		<div class="shopchat-form-row" style="margin-top: 16px; border-top: 1px solid var(--sc-slate-100); padding-top: 16px;">
			<label for="shopchat_default_country_code" style="font-weight: 500; color: var(--sc-slate-800);"><?php esc_html_e( 'Default Customer Country Code', 'shopchat' ); ?></label>
			<div style="display: flex; align-items: center; gap: 8px; margin-top: 6px;">
				<div class="shopchat-prefix-box">
					<span>+</span>
					<input type="number" id="shopchat_default_country_code" name="general_settings[default_country_code]" value="<?php echo esc_attr( $country_code ); ?>" placeholder="91" style="width: 80px;">
				</div>
				<span style="font-size: 13px; color: var(--sc-slate-500);"><?php esc_html_e( '(Auto-prepended when local 10-digit phone numbers are entered at checkout)', 'shopchat' ); ?></span>
			</div>
			<p class="description"><?php esc_html_e( 'Auto-detected from WooCommerce store location or customer billing country (India: +91).', 'shopchat' ); ?></p>
		</div>
	</div>

	<!-- Messaging Engine Selection -->
	<div class="shopchat-card">
		<h3 class="shopchat-card-title"><?php esc_html_e( 'Messaging Dispatch Engine', 'shopchat' ); ?></h3>
		<p class="shopchat-card-description"><?php esc_html_e( 'Select your preferred message delivery method.', 'shopchat' ); ?></p>

		<div class="shopchat-mode-selection">
			<!-- Option 1: 100% Free Mode -->
			<label class="shopchat-mode-option <?php echo 'wame' === $mode ? 'is-selected' : ''; ?>">
				<input type="radio" name="general_settings[messaging_mode]" value="wame" <?php checked( $mode, 'wame' ); ?> class="shopchat-mode-radio">
				<div class="shopchat-mode-card">
					<div class="shopchat-mode-header">
						<h4><?php esc_html_e( 'Direct Send (wa.me)', 'shopchat' ); ?></h4>
						<span class="shopchat-mode-pill free-tag"><?php esc_html_e( 'Free', 'shopchat' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Generates one-click personalized messages directly via WhatsApp Web or app. No developer account or credit cards required.', 'shopchat' ); ?></p>
					<ul class="shopchat-card-bullets">
						<li><?php esc_html_e( 'Zero monthly subscription or per-message cost', 'shopchat' ); ?></li>
						<li><?php esc_html_e( 'Instant setup with any existing phone number', 'shopchat' ); ?></li>
						<li><?php esc_html_e( '1-Click send from WooCommerce order details', 'shopchat' ); ?></li>
					</ul>
				</div>
			</label>

			<!-- Option 2: Meta Cloud API -->
			<label class="shopchat-mode-option <?php echo 'meta_cloud' === $mode ? 'is-selected' : ''; ?>">
				<input type="radio" name="general_settings[messaging_mode]" value="meta_cloud" <?php checked( $mode, 'meta_cloud' ); ?> class="shopchat-mode-radio">
				<div class="shopchat-mode-card">
					<div class="shopchat-mode-header">
						<h4><?php esc_html_e( 'Official Meta Cloud API', 'shopchat' ); ?></h4>
						<span class="shopchat-mode-pill pro-tag"><?php esc_html_e( 'Automated', 'shopchat' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Background delivery via the WhatsApp Business Platform. Recommended for high-volume stores wanting automated notification delivery.', 'shopchat' ); ?></p>
					<ul class="shopchat-card-bullets">
						<li><?php esc_html_e( 'Fully automated background delivery', 'shopchat' ); ?></li>
						<li><?php esc_html_e( 'Official Meta Business Platform verification', 'shopchat' ); ?></li>
						<li><?php esc_html_e( 'Meta standard conversation pricing applies', 'shopchat' ); ?></li>
					</ul>
				</div>
			</label>
		</div>

		<!-- Collapsible Meta Cloud API Credentials -->
		<div id="shopchat-meta-credentials" class="shopchat-credentials-box" style="<?php echo 'meta_cloud' === $mode ? '' : 'display: none;'; ?>">
			<h4 style="margin-top: 0; color: #1e293b;"><?php esc_html_e( 'Meta Cloud API Credentials', 'shopchat' ); ?></h4>
			<p class="description" style="margin-bottom: 12px;">
				<?php esc_html_e( 'Obtain these credentials from your Meta Developers Console under WhatsApp > API Setup.', 'shopchat' ); ?>
			</p>

			<div class="shopchat-form-row">
				<label for="meta_phone_id"><?php esc_html_e( 'Phone Number ID', 'shopchat' ); ?></label>
				<input type="text" id="meta_phone_id" name="general_settings[meta_phone_number_id]" value="<?php echo esc_attr( $general_settings['meta_phone_number_id'] ?? '' ); ?>" class="regular-text" style="width: 100%;">
			</div>

			<div class="shopchat-form-row">
				<label for="meta_waba_id"><?php esc_html_e( 'WhatsApp Business Account (WABA) ID', 'shopchat' ); ?></label>
				<input type="text" id="meta_waba_id" name="general_settings[meta_waba_id]" value="<?php echo esc_attr( $general_settings['meta_waba_id'] ?? '' ); ?>" class="regular-text" style="width: 100%;">
			</div>

			<div class="shopchat-form-row">
				<label for="meta_token"><?php esc_html_e( 'Permanent Access Token', 'shopchat' ); ?></label>
				<input type="password" id="meta_token" name="general_settings[meta_access_token]" value="<?php echo esc_attr( $general_settings['meta_access_token'] ?? '' ); ?>" class="regular-text" style="width: 100%;">
			</div>

			<!-- Live Connection Test Box -->
			<div class="shopchat-api-test-box" style="margin-top: 20px; padding: 16px; background: #f8fafc; border: 1px solid var(--sc-slate-200); border-radius: var(--sc-radius-md);">
				<h4 style="margin: 0 0 6px 0; font-size: 13.5px; font-weight: 600; color: var(--sc-slate-800);"><?php esc_html_e( 'Test Meta API Connection', 'shopchat' ); ?></h4>
				<p style="margin: 0 0 12px 0; font-size: 12.5px; color: var(--sc-slate-500);"><?php esc_html_e( 'Send a live test message to verify your Meta credentials and allowed test recipient phone number.', 'shopchat' ); ?></p>
				<div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
					<input type="text" id="shopchat_test_phone" placeholder="e.g. 917791026797" value="917791026797" class="regular-text" style="max-width: 200px;">
					<button type="button" id="shopchat_test_meta_btn" class="button button-secondary">
						<?php esc_html_e( 'Send Test WhatsApp', 'shopchat' ); ?>
					</button>
					<span id="shopchat_test_status" style="font-size: 13px; margin-left: 6px; font-weight: 500;"></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Automation & Feature Toggles -->
	<div class="shopchat-card">
		<h3 class="shopchat-card-title"><?php esc_html_e( 'Automation Modules', 'shopchat' ); ?></h3>

		<div class="shopchat-toggle-row">
			<div class="shopchat-toggle-info">
				<strong><?php esc_html_e( 'Order Notifications', 'shopchat' ); ?></strong>
				<p><?php esc_html_e( 'Enable WhatsApp notification triggers for order status updates.', 'shopchat' ); ?></p>
			</div>
			<label class="shopchat-switch">
				<input type="checkbox" name="general_settings[enable_order_notify]" value="1" <?php checked( $notify_on, true ); ?>>
				<span class="shopchat-slider"></span>
			</label>
		</div>

		<div class="shopchat-toggle-row">
			<div class="shopchat-toggle-info">
				<strong><?php esc_html_e( 'Abandoned Cart Capture & Recovery', 'shopchat' ); ?></strong>
				<p><?php esc_html_e( 'Capture shopper details on checkout and enable cart recovery links.', 'shopchat' ); ?></p>
			</div>
			<label class="shopchat-switch">
				<input type="checkbox" name="general_settings[enable_cart_recovery]" value="1" <?php checked( $cart_on, true ); ?>>
				<span class="shopchat-slider"></span>
			</label>
		</div>

		<div class="shopchat-form-row" style="margin-top: 16px;">
			<label for="cart_cutoff"><?php esc_html_e( 'Cart Abandonment Idle Window (Minutes)', 'shopchat' ); ?></label>
			<input type="number" id="cart_cutoff" name="general_settings[cart_cutoff_minutes]" value="<?php echo esc_attr( $cart_cutoff ); ?>" min="5" max="1440" style="width: 100px;">
			<p class="description"><?php esc_html_e( 'Inactivity threshold before a cart session is classified as abandoned (Default: 20 minutes).', 'shopchat' ); ?></p>
		</div>
	</div>

	<!-- Save Button -->
	<div class="shopchat-submit-row">
		<button type="submit" class="button button-primary button-large shopchat-save-btn">
			<?php esc_html_e( 'Save Changes', 'shopchat' ); ?>
		</button>
	</div>
</form>
