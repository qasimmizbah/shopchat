<?php
/**
 * Autoloader for ShopChat for WhatsApp & WooCommerce
 *
 * @package ShopChat
 */

namespace ShopChat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Autoloader {

	/**
	 * Register autoloader
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload classes based on namespace
	 *
	 * @param string $class Class name.
	 */
	public static function autoload( $class ) {
		// Only autoload classes in the ShopChat namespace
		$prefix = 'ShopChat\\';
		$len    = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		// Convert namespace separators to directory separators
		$file = SHOPCHAT_PLUGIN_DIR . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}

Autoloader::register();
