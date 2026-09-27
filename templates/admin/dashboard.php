<?php
/**
 * Main Admin Dashboard Wrapper
 *
 * @package ShopChat\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
$mode       = $general_settings['messaging_mode'] ?? 'wame';
?>
<div class="wrap shopchat-admin-wrap">
	<!-- Header Bar -->
	<header class="shopchat-top-header">
		<div class="shopchat-header-brand">
			<div class="shopchat-header-icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
					<path d="M17.472 14.382c-.301-.15-1.78-.879-2.056-.98-.276-.1-.477-.15-.678.15-.2.3-.777.98-.952 1.18-.176.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.895-.798-1.5-1.784-1.675-2.085-.176-.3-.019-.463.132-.612.136-.135.301-.351.452-.527.15-.175.2-.3.301-.5.1-.2.05-.376-.025-.526-.075-.15-.678-1.634-.928-2.238-.244-.588-.492-.508-.678-.517-.176-.009-.376-.01-.577-.01s-.527.076-.803.376c-.276.3-1.054 1.03-1.054 2.511s1.079 2.91 1.23 3.111c.15.2 2.124 3.243 5.146 4.549.719.311 1.28.497 1.718.636.723.23 1.381.197 1.902.12.58-.088 1.78-.727 2.031-1.429.251-.702.251-1.304.176-1.429-.076-.126-.276-.201-.577-.352z"/>
					<path d="M12 0C5.373 0 0 5.373 0 12c0 2.118.553 4.108 1.517 5.839L.085 23.414a.75.75 0 00.916.929l5.772-1.396A11.95 11.95 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 01-5.068-1.382l-.364-.216-3.774.913.935-3.67-.236-.376A9.944 9.944 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/>
				</svg>
			</div>
			<div>
				<h1 class="shopchat-title">
					<?php esc_html_e( 'ShopChat – Customer Chat & Cart Recovery for WooCommerce', 'shopchat' ); ?>
					<span class="shopchat-version-tag"><?php echo esc_html( SHOPCHAT_VERSION ); ?></span>
				</h1>
				<p class="shopchat-subtitle"><?php esc_html_e( 'Order notifications, customer chat, and abandoned cart recovery for WooCommerce', 'shopchat' ); ?></p>
			</div>
		</div>

		<div class="shopchat-header-actions">
			<span class="shopchat-status-badge shopchat-status-<?php echo esc_attr( $mode ); ?>">
				<span class="shopchat-dot"></span>
				<?php if ( 'wame' === $mode ) : ?>
					<?php esc_html_e( 'Free Direct Send (wa.me)', 'shopchat' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Meta Cloud API', 'shopchat' ); ?>
				<?php endif; ?>
			</span>
		</div>
	</header>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['updated'] ) ) :
		?>
		<div class="notice notice-success is-dismissible shopchat-notice">
			<p><?php esc_html_e( 'Settings updated successfully.', 'shopchat' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Professional Nav Tabs (Clean, no emojis) -->
	<nav class="shopchat-nav-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=overview' ) ); ?>" class="shopchat-tab-link <?php echo 'overview' === $active_tab ? 'is-active' : ''; ?>">
			<?php esc_html_e( 'Overview', 'shopchat' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=general' ) ); ?>" class="shopchat-tab-link <?php echo 'general' === $active_tab ? 'is-active' : ''; ?>">
			<?php esc_html_e( 'General Settings', 'shopchat' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=templates' ) ); ?>" class="shopchat-tab-link <?php echo 'templates' === $active_tab ? 'is-active' : ''; ?>">
			<?php esc_html_e( 'Order Templates', 'shopchat' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=abandoned-carts' ) ); ?>" class="shopchat-tab-link <?php echo 'abandoned-carts' === $active_tab ? 'is-active' : ''; ?>">
			<?php esc_html_e( 'Abandoned Carts', 'shopchat' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=shopchat-settings&tab=widget' ) ); ?>" class="shopchat-tab-link <?php echo 'widget' === $active_tab ? 'is-active' : ''; ?>">
			<?php esc_html_e( 'Storefront Chat Widget', 'shopchat' ); ?>
		</a>
	</nav>

	<!-- Tab Content Body -->
	<main class="shopchat-tab-content">
		<?php
		switch ( $active_tab ) {
			case 'general':
				require SHOPCHAT_PLUGIN_DIR . 'templates/admin/tab-general.php';
				break;
			case 'templates':
				require SHOPCHAT_PLUGIN_DIR . 'templates/admin/tab-templates.php';
				break;
			case 'abandoned-carts':
				require SHOPCHAT_PLUGIN_DIR . 'templates/admin/tab-abandoned-carts.php';
				break;
			case 'widget':
				require SHOPCHAT_PLUGIN_DIR . 'templates/admin/tab-widget.php';
				break;
			case 'overview':
			default:
				require SHOPCHAT_PLUGIN_DIR . 'templates/admin/tab-overview.php';
				break;
		}
		?>
	</main>
</div>
