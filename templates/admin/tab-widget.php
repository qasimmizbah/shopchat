<?php
/**
 * Storefront Chat Widget Configuration Tab (Clean, Professional Minimalist Design)
 *
 * @package ShopChat\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$widget_on   = ! empty( $widget_settings['enable_widget'] );
$position    = $widget_settings['position'] ?? 'bottom-right';
$btn_text    = $widget_settings['button_text'] ?? 'Chat with us';
$agent_name  = $widget_settings['agent_name'] ?? 'Support Team';
$agent_status= $widget_settings['agent_status'] ?? 'Online now';
$welcome_msg = $widget_settings['welcome_message'] ?? 'Hello! How can we help you today?';
$inquiry_tpl = $widget_settings['product_inquiry_template'] ?? 'Hi! I have a question about {product_name}: {product_url}';
$hide_mobile = ! empty( $widget_settings['hide_on_mobile'] );
?>
<form method="post" action="" class="shopchat-form">
	<?php wp_nonce_field( 'shopchat_save_settings_nonce', 'shopchat_nonce' ); ?>
	<input type="hidden" name="shopchat_save_action" value="1">
	<input type="hidden" name="current_tab" value="widget">

	<div class="shopchat-two-columns">
		<!-- Left Column: Settings -->
		<div class="shopchat-card">
			<h3 class="shopchat-card-title"><?php esc_html_e( 'Storefront Chat Widget', 'shopchat' ); ?></h3>
			<p class="shopchat-card-description"><?php esc_html_e( 'Configure the floating WhatsApp button and greeting popup displayed to visitors.', 'shopchat' ); ?></p>

			<div class="shopchat-toggle-row">
				<div class="shopchat-toggle-info">
					<strong><?php esc_html_e( 'Enable Floating Chat Button', 'shopchat' ); ?></strong>
					<p><?php esc_html_e( 'Display floating WhatsApp trigger across storefront pages.', 'shopchat' ); ?></p>
				</div>
				<label class="shopchat-switch">
					<input type="checkbox" name="widget_settings[enable_widget]" value="1" <?php checked( $widget_on, true ); ?>>
					<span class="shopchat-slider"></span>
				</label>
			</div>

			<div class="shopchat-form-row">
				<label for="widget_pos"><?php esc_html_e( 'Screen Position', 'shopchat' ); ?></label>
				<select id="widget_pos" name="widget_settings[position]" style="width: 220px;">
					<option value="bottom-right" <?php selected( $position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right (Standard)', 'shopchat' ); ?></option>
					<option value="bottom-left" <?php selected( $position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'shopchat' ); ?></option>
				</select>
			</div>

			<div class="shopchat-form-row">
				<label for="widget_btn_text"><?php esc_html_e( 'Action Button Label', 'shopchat' ); ?></label>
				<input type="text" id="widget_btn_text" name="widget_settings[button_text]" value="<?php echo esc_attr( $btn_text ); ?>" class="regular-text" style="width: 100%;">
			</div>

			<div class="shopchat-form-row">
				<label for="widget_agent_name"><?php esc_html_e( 'Support Name', 'shopchat' ); ?></label>
				<input type="text" id="widget_agent_name" name="widget_settings[agent_name]" value="<?php echo esc_attr( $agent_name ); ?>" class="regular-text" style="width: 100%;">
			</div>

			<div class="shopchat-form-row">
				<label for="widget_agent_status"><?php esc_html_e( 'Status Subtitle', 'shopchat' ); ?></label>
				<input type="text" id="widget_agent_status" name="widget_settings[agent_status]" value="<?php echo esc_attr( $agent_status ); ?>" class="regular-text" style="width: 100%;">
			</div>

			<div class="shopchat-form-row">
				<label for="widget_welcome"><?php esc_html_e( 'Default Welcome Message', 'shopchat' ); ?></label>
				<textarea id="widget_welcome" name="widget_settings[welcome_message]" rows="3" class="regular-text" style="width: 100%;"><?php echo esc_textarea( $welcome_msg ); ?></textarea>
			</div>

			<div class="shopchat-form-row">
				<label for="widget_inquiry_tpl"><?php esc_html_e( 'Product Page Inquiry Template', 'shopchat' ); ?></label>
				<textarea id="widget_inquiry_tpl" name="widget_settings[product_inquiry_template]" rows="3" class="regular-text" style="width: 100%;"><?php echo esc_textarea( $inquiry_tpl ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Available merge tags: {product_name}, {product_url}', 'shopchat' ); ?></p>
			</div>

			<!-- Save Button -->
			<div class="shopchat-submit-row">
				<button type="submit" class="button button-primary button-large shopchat-save-btn">
					<?php esc_html_e( 'Save Changes', 'shopchat' ); ?>
				</button>
			</div>
		</div>

		<!-- Right Column: Widget Visual Preview -->
		<div class="shopchat-card">
			<h3 class="shopchat-card-title"><?php esc_html_e( 'Visual Preview', 'shopchat' ); ?></h3>
			<p class="shopchat-card-description"><?php esc_html_e( 'Appearance of the chat window as seen by storefront visitors.', 'shopchat' ); ?></p>

			<div class="shopchat-widget-preview-wrapper" style="position: relative; height: 380px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; display: flex; align-items: flex-end; justify-content: flex-end; padding: 20px; box-sizing: border-box;">
				<!-- Mini preview mockup -->
				<div style="width: 270px; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #e5e7eb;">
					<div style="background: #075E54; color: #ffffff; padding: 12px 14px; display: flex; align-items: center; gap: 10px;">
						<div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center;">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="#ffffff">
								<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
							</svg>
						</div>
						<div>
							<strong style="display: block; font-size: 13px; font-weight: 600;"><?php echo esc_html( $agent_name ); ?></strong>
							<span style="display: block; font-size: 11px; opacity: 0.85;"><?php echo esc_html( $agent_status ); ?></span>
						</div>
					</div>
					<div style="padding: 16px 14px; background: #efeae2; font-size: 12px; color: #1e293b;">
						<div style="background: #ffffff; padding: 10px 12px; border-radius: 0 8px 8px 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.06); line-height: 1.4;">
							<?php echo esc_html( $welcome_msg ); ?>
						</div>
					</div>
					<div style="padding: 12px 14px; background: #ffffff; border-top: 1px solid #f3f4f6;">
						<div style="background: #25D366; color: #ffffff; text-align: center; padding: 9px; border-radius: 6px; font-weight: 600; font-size: 13px;">
							<?php echo esc_html( $btn_text ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
