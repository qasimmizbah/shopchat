<?php
/**
 * Main Plugin Orchestrator (Singleton)
 *
 * @package ShopChat\Core
 */

namespace ShopChat\Core;

use ShopChat\Admin\AdminMenu;
use ShopChat\Admin\Settings;
use ShopChat\Admin\OrderMetaBox;
use ShopChat\Frontend\ChatWidget;
use ShopChat\Frontend\CartTracker;
use ShopChat\Frontend\CheckoutFields;
use ShopChat\Notifications\OrderNotifier;
use ShopChat\Notifications\ReviewReminder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	/**
	 * Singleton instance
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {}

	/**
	 * Initialize plugin components
	 */
	public function init() {
		// 1. Load Translations
		$i18n = new I18n();
		add_action( 'init', array( $i18n, 'load_plugin_textdomain' ) );

		// 2. Load Admin Components
		if ( is_admin() ) {
			$admin_menu = new AdminMenu();
			$admin_menu->init();

			$settings = new Settings();
			$settings->init();

			$order_metabox = new OrderMetaBox();
			$order_metabox->init();
		}

		// 3. Load Notifications
		$notifier = new OrderNotifier();
		$notifier->init();

		$review_reminder = new ReviewReminder();
		$review_reminder->init();

		// 4. Load Checkout Fields & Enforcement
		$checkout_fields = new CheckoutFields();
		$checkout_fields->init();

		// 5. Load Cart Tracker (Frontend, Admin AJAX, Background Cron)
		$cart_tracker = new CartTracker();
		$cart_tracker->init();

		// 6. Load Frontend Chat Widget
		if ( ! is_admin() ) {
			$chat_widget = new ChatWidget();
			$chat_widget->init();
		}
	}
}
