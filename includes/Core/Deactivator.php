<?php
/**
 * Plugin deactivation handler
 *
 * @package ShopChat\Core
 */

namespace ShopChat\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivator {

	/**
	 * Run deactivation tasks
	 */
	public static function deactivate() {
		// Clear any scheduled cron tasks
		wp_clear_scheduled_hook( 'shopchat_abandoned_cart_cron' );
		wp_clear_scheduled_hook( 'shopchat_review_reminder_cron' );
	}
}
