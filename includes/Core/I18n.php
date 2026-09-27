<?php
/**
 * Internationalization functionality
 *
 * @package ShopChat\Core
 */

namespace ShopChat\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class I18n {

	/**
	 * Load plugin text domain
	 */
	public function load_plugin_textdomain() {
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Backwards compatibility for local custom translations
		load_plugin_textdomain(
			'shopchat',
			false,
			dirname( SHOPCHAT_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
