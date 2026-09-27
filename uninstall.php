<?php
/**
 * Clean data removal on plugin deletion
 *
 * @package ShopChat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'shopchat_general_settings' );
delete_option( 'shopchat_templates' );
delete_option( 'shopchat_widget_settings' );

// Clean scheduled cron events
wp_clear_scheduled_hook( 'shopchat_abandoned_cart_cron' );
wp_clear_scheduled_hook( 'shopchat_review_reminder_cron' );

// Drop custom table safely
global $wpdb;
$shopchat_table_name = $wpdb->prefix . 'shopchat_abandoned_carts';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$wpdb->query( "DROP TABLE IF EXISTS `{$shopchat_table_name}`" );

