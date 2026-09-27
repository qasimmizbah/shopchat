<?php
/**
 * Plugin Name:       ShopChat – Customer Chat, Order Alerts & Abandoned Cart Recovery for WooCommerce
 * Description:       Boost sales with WhatsApp Order Notifications, Abandoned Cart Recovery, Storefront Customer Chat, and Review Boosters for WooCommerce.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            CodenCraft
 * Author URI:        https://codencraft.in/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shopchat
 * Domain Path:       /languages
 * WC requires at least: 5.0
 * WC tested up to:      9.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Plugin Constants
define( 'SHOPCHAT_VERSION', '1.0.0' );
define( 'SHOPCHAT_PLUGIN_FILE', __FILE__ );
define( 'SHOPCHAT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SHOPCHAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHOPCHAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare High-Performance Order Storage (HPOS) Compatibility for WooCommerce
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SHOPCHAT_PLUGIN_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', SHOPCHAT_PLUGIN_FILE, true );
	}
} );

// Require Custom Autoloader
require_once SHOPCHAT_PLUGIN_DIR . 'includes/class-autoloader.php';

// Activation & Deactivation Hooks
register_activation_hook( __FILE__, array( 'ShopChat\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ShopChat\\Core\\Deactivator', 'deactivate' ) );

/**
 * Initialize and Run the Plugin
 */
function shopchat_run_plugin() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'shopchat_woocommerce_missing_notice' );
		return;
	}

	$plugin = ShopChat\Core\Plugin::get_instance();
	$plugin->init();
}
add_action( 'plugins_loaded', 'shopchat_run_plugin' );

/**
 * Notice if WooCommerce is missing
 */
function shopchat_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><strong><?php esc_html_e( 'ShopChat:', 'shopchat' ); ?></strong> <?php esc_html_e( 'WooCommerce must be installed and active for ShopChat to work.', 'shopchat' ); ?></p>
	</div>
	<?php
}
