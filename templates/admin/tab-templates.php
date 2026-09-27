<?php
/**
 * Templates Editor & Live Phone Simulator Tab (Clean, Professional Minimalist Design)
 *
 * @package ShopChat\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$events = array(
	'processing'     => array(
		'label' => __( 'Order Processing', 'shopchat' ),
	),
	'completed'      => array(
		'label' => __( 'Order Completed', 'shopchat' ),
	),
	'on_hold'        => array(
		'label' => __( 'Order On Hold', 'shopchat' ),
	),
	'cancelled'      => array(
		'label' => __( 'Order Cancelled', 'shopchat' ),
	),
	'abandoned_cart' => array(
		'label' => __( 'Abandoned Cart', 'shopchat' ),
	),
	'review_request' => array(
		'label' => __( 'Review Request', 'shopchat' ),
	),
);
?>
<form method="post" action="" class="shopchat-form">
	<?php wp_nonce_field( 'shopchat_save_settings_nonce', 'shopchat_nonce' ); ?>
	<input type="hidden" name="shopchat_save_action" value="1">
	<input type="hidden" name="current_tab" value="templates">

	<div class="shopchat-split-layout">
		<!-- Left Pane: Template Configurator -->
		<div class="shopchat-editor-pane">
			<div class="shopchat-card">
				<h3 class="shopchat-card-title"><?php esc_html_e( 'Notification Templates', 'shopchat' ); ?></h3>
				<p class="shopchat-card-description"><?php esc_html_e( 'Select an order trigger below to configure its template. Merge tags will be replaced dynamically with order details.', 'shopchat' ); ?></p>

				<!-- Event Selector Segmented Control (Clean, no emojis) -->
				<div class="shopchat-event-pills">
					<?php
					$first = true;
					foreach ( $events as $event_key => $event_data ) :
						$is_active = $first;
						$first     = false;
						?>
						<button type="button" class="shopchat-event-pill <?php echo $is_active ? 'is-selected' : ''; ?>" data-event="<?php echo esc_attr( $event_key ); ?>">
							<?php echo esc_html( $event_data['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<!-- Available Merge Tag Chips -->
				<div class="shopchat-tags-section">
					<label class="shopchat-tags-heading"><?php esc_html_e( 'Insert Merge Tag:', 'shopchat' ); ?></label>
					<div class="shopchat-tag-chips">
						<?php foreach ( $available_tags as $tag => $label ) : ?>
							<button type="button" class="shopchat-tag-chip" data-tag="<?php echo esc_attr( $tag ); ?>" title="<?php echo esc_attr( $label ); ?>">
								<?php echo esc_html( $tag ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Template Textareas for each Event -->
				<div class="shopchat-templates-container">
					<?php
					$first = true;
					foreach ( $events as $event_key => $event_data ) :
						$is_active = $first;
						$first     = false;
						$tpl_data  = $templates[ $event_key ] ?? array();
						$enabled   = ! empty( $tpl_data['enabled'] );
						$msg       = $tpl_data['message'] ?? '';
						?>
						<div class="shopchat-event-block" id="event-block-<?php echo esc_attr( $event_key ); ?>" style="<?php echo $is_active ? '' : 'display: none;'; ?>">
							<div class="shopchat-toggle-row" style="margin-bottom: 14px; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
								<div class="shopchat-toggle-info">
									<strong><?php esc_html_e( 'Enable Notification for', 'shopchat' ); ?> "<?php echo esc_html( $event_data['label'] ); ?>"</strong>
								</div>
								<label class="shopchat-switch">
									<input type="checkbox" name="templates[<?php echo esc_attr( $event_key ); ?>][enabled]" value="1" <?php checked( $enabled, true ); ?>>
									<span class="shopchat-slider"></span>
								</label>
							</div>

							<div class="shopchat-textarea-wrap">
								<label for="template-<?php echo esc_attr( $event_key ); ?>" style="font-weight: 600; margin-bottom: 6px; display: block; font-size: 13px;">
									<?php esc_html_e( 'Message Template Content (Used for Direct wa.me & Open 24h Window)', 'shopchat' ); ?>
								</label>
								<textarea
									id="template-<?php echo esc_attr( $event_key ); ?>"
									name="templates[<?php echo esc_attr( $event_key ); ?>][message]"
									rows="6"
									class="shopchat-template-textarea"
									data-event="<?php echo esc_attr( $event_key ); ?>"
									placeholder="<?php esc_attr_e( 'Compose WhatsApp message...', 'shopchat' ); ?>"
								><?php echo esc_textarea( $msg ); ?></textarea>
							</div>

							<div class="shopchat-form-row" style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #e2e8f0;">
								<label for="meta-template-<?php echo esc_attr( $event_key ); ?>" style="font-weight: 600; font-size: 12.5px; color: var(--sc-slate-700); display: block; margin-bottom: 4px;">
									<?php esc_html_e( 'Meta Approved Template Name (For Automated Meta Cloud API outside 24h window):', 'shopchat' ); ?>
								</label>
								<input
									type="text"
									id="meta-template-<?php echo esc_attr( $event_key ); ?>"
									name="templates[<?php echo esc_attr( $event_key ); ?>][meta_template_name]"
									value="<?php echo esc_attr( $tpl_data['meta_template_name'] ?? '' ); ?>"
									class="regular-text"
									placeholder="e.g. jaspers_market_order_confirmation_v1 or hello_world"
									style="width: 100%; font-family: monospace; font-size: 12.5px;"
								>
								<p class="description" style="font-size: 11.5px; margin-top: 4px;">
									<?php esc_html_e( 'Pre-approved template name from your Meta WhatsApp Manager. Required by Meta to deliver messages to customers who have not messaged you in 24 hours.', 'shopchat' ); ?>
								</p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Save Button -->
				<div class="shopchat-submit-row" style="margin-top: 20px; padding-top: 14px; border-top: 1px solid #e5e7eb;">
					<button type="submit" class="button button-primary button-large shopchat-save-btn">
						<?php esc_html_e( 'Save Templates', 'shopchat' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Right Pane: Realistic WhatsApp Phone Simulator -->
		<div class="shopchat-simulator-pane">
			<div class="shopchat-phone-frame">
				<!-- Speaker Notch -->
				<div class="shopchat-phone-notch">
					<div class="shopchat-phone-speaker"></div>
					<div class="shopchat-phone-camera"></div>
				</div>

				<!-- WhatsApp App Interface -->
				<div class="shopchat-phone-screen">
					<!-- App Header -->
					<div class="shopchat-wa-header">
						<span class="shopchat-wa-back">←</span>
						<div class="shopchat-wa-avatar">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="#ffffff">
								<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
							</svg>
						</div>
						<div class="shopchat-wa-title">
							<span class="shopchat-wa-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
							<span class="shopchat-wa-sub"><?php esc_html_e( 'online', 'shopchat' ); ?></span>
						</div>
						<div class="shopchat-wa-icons">⋮</div>
					</div>

					<!-- Chat Canvas -->
					<div class="shopchat-wa-chat-area">
						<div class="shopchat-wa-date-pill"><?php esc_html_e( 'TODAY', 'shopchat' ); ?></div>

						<!-- Outgoing WhatsApp Bubble -->
						<div class="shopchat-wa-bubble">
							<div id="shopchat-live-preview-text" class="shopchat-bubble-content">
								<!-- Dynamically populated via JS -->
							</div>
							<div class="shopchat-bubble-meta">
								<span id="shopchat-live-time">10:42 AM</span>
								<span class="shopchat-wa-ticks">✓✓</span>
							</div>
						</div>
					</div>

					<!-- Fake Bottom Bar -->
					<div class="shopchat-wa-input-bar">
						<div class="shopchat-wa-fake-input"><?php esc_html_e( 'Message', 'shopchat' ); ?></div>
					</div>
				</div>
			</div>
			<p class="shopchat-simulator-hint"><?php esc_html_e( 'Live preview reflects current template content in real-time.', 'shopchat' ); ?></p>
		</div>
	</div>
</form>
