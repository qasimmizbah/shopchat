<?php
/**
 * Frontend Chat Widget Template
 *
 * @package ShopChat\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="shopchat-widget-root" class="shopchat-widget-wrapper shopchat-pos-<?php echo esc_attr( $position ); ?>">
	<!-- Chat Popup Box -->
	<div id="shopchat-popup-box" class="shopchat-popup-box" style="display: none;">
		<div class="shopchat-popup-header">
			<div class="shopchat-header-avatar">
				<div class="shopchat-avatar-placeholder">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
						<circle cx="12" cy="7" r="4"></circle>
					</svg>
				</div>
				<span class="shopchat-status-dot"></span>
			</div>
			<div class="shopchat-header-info">
				<h4 class="shopchat-agent-title"><?php echo esc_html( $agent_name ); ?></h4>
				<span class="shopchat-agent-subtitle"><?php echo esc_html( $agent_status ); ?></span>
			</div>
			<button type="button" id="shopchat-popup-close" class="shopchat-popup-close" aria-label="<?php esc_attr_e( 'Close chat popup', 'shopchat' ); ?>">
				&times;
			</button>
		</div>

		<div class="shopchat-popup-body">
			<div class="shopchat-message-bubble">
				<p><?php echo nl2br( esc_html( $welcome_msg ) ); ?></p>
				<span class="shopchat-bubble-time"><?php echo esc_html( date_i18n( get_option( 'time_format' ) ) ); ?></span>
			</div>
		</div>

		<div class="shopchat-popup-footer">
			<a href="<?php echo esc_attr( \ShopChat\Gateways\WaMeGateway::escape_url( $chat_url ) ); ?>" target="_blank" rel="noopener noreferrer" class="shopchat-start-btn">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
					<path d="M17.472 14.382c-.301-.15-1.78-.879-2.056-.98-.276-.1-.477-.15-.678.15-.2.3-.777.98-.952 1.18-.176.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.895-.798-1.5-1.784-1.675-2.085-.176-.3-.019-.463.132-.612.136-.135.301-.351.452-.527.15-.175.2-.3.301-.5.1-.2.05-.376-.025-.526-.075-.15-.678-1.634-.928-2.238-.244-.588-.492-.508-.678-.517-.176-.009-.376-.01-.577-.01s-.527.076-.803.376c-.276.3-1.054 1.03-1.054 2.511s1.079 2.91 1.23 3.111c.15.2 2.124 3.243 5.146 4.549.719.311 1.28.497 1.718.636.723.23 1.381.197 1.902.12.58-.088 1.78-.727 2.031-1.429.251-.702.251-1.304.176-1.429-.076-.126-.276-.201-.577-.352z"/>
					<path d="M12 0C5.373 0 0 5.373 0 12c0 2.118.553 4.108 1.517 5.839L.085 23.414a.75.75 0 00.916.929l5.772-1.396A11.95 11.95 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 01-5.068-1.382l-.364-.216-3.774.913.935-3.67-.236-.376A9.944 9.944 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/>
				</svg>
				<span><?php echo esc_html( $button_text ); ?></span>
			</a>
		</div>
	</div>

	<!-- Floating Bubble Trigger Button -->
	<button type="button" id="shopchat-widget-button" class="shopchat-widget-button" aria-label="<?php esc_attr_e( 'Open WhatsApp chat', 'shopchat' ); ?>">
		<span class="shopchat-pulse-ring"></span>
		<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
			<path d="M17.472 14.382c-.301-.15-1.78-.879-2.056-.98-.276-.1-.477-.15-.678.15-.2.3-.777.98-.952 1.18-.176.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.895-.798-1.5-1.784-1.675-2.085-.176-.3-.019-.463.132-.612.136-.135.301-.351.452-.527.15-.175.2-.3.301-.5.1-.2.05-.376-.025-.526-.075-.15-.678-1.634-.928-2.238-.244-.588-.492-.508-.678-.517-.176-.009-.376-.01-.577-.01s-.527.076-.803.376c-.276.3-1.054 1.03-1.054 2.511s1.079 2.91 1.23 3.111c.15.2 2.124 3.243 5.146 4.549.719.311 1.28.497 1.718.636.723.23 1.381.197 1.902.12.58-.088 1.78-.727 2.031-1.429.251-.702.251-1.304.176-1.429-.076-.126-.276-.201-.577-.352z"/>
			<path d="M12 0C5.373 0 0 5.373 0 12c0 2.118.553 4.108 1.517 5.839L.085 23.414a.75.75 0 00.916.929l5.772-1.396A11.95 11.95 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 01-5.068-1.382l-.364-.216-3.774.913.935-3.67-.236-.376A9.944 9.944 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/>
		</svg>
	</button>
</div>
