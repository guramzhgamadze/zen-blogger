<?php
/**
 * Plugin Name:       Zen Blogger
 * Plugin URI:        https://wordpress.org/plugins/zen-blogger/
 * Description:       An accessible, fast blog post carousel widget for Elementor. Six skins, a deep query builder, Swiper-powered motion, WCAG 2.2 AA controls and zero extra libraries.
 * Version:           1.8.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  elementor
 * Author:            Guram Zgamadze
 * Author URI:        https://profiles.wordpress.org/guramzgamadze/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zen-blogger
 * Domain Path:       /languages
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

define( 'ZENBLOG_VERSION', '1.8.1' );
define( 'ZENBLOG_FILE', __FILE__ );
define( 'ZENBLOG_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZENBLOG_URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimum Elementor version the widget API in this plugin is verified against.
 */
define( 'ZENBLOG_MIN_ELEMENTOR', '3.26.0' );

/**
 * Boot the plugin once every other plugin has loaded.
 *
 * Elementor is detected at runtime rather than at file-load time: plugins load in
 * alphabetical order, so a file-scope check can run before Elementor defines anything.
 *
 * @return void
 */
function zenblog_bootstrap() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'zenblog_notice_missing_elementor' );
		return;
	}

	if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, ZENBLOG_MIN_ELEMENTOR, '<' ) ) {
		add_action( 'admin_notices', 'zenblog_notice_old_elementor' );
		return;
	}

	require_once ZENBLOG_PATH . 'includes/class-zen-blogger.php';
	Zen_Blogger::instance()->init();
}
add_action( 'plugins_loaded', 'zenblog_bootstrap' );

/**
 * Admin notice shown when Elementor is not active.
 *
 * @return void
 */
function zenblog_notice_missing_elementor() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Zen Blogger requires Elementor to be installed and active.', 'zen-blogger' );
	echo '</p></div>';
}

/**
 * Admin notice shown when the installed Elementor is older than the supported minimum.
 *
 * @return void
 */
function zenblog_notice_old_elementor() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	printf(
		/* translators: %s: minimum required Elementor version number. */
		esc_html__( 'Zen Blogger requires Elementor %s or newer.', 'zen-blogger' ),
		esc_html( ZENBLOG_MIN_ELEMENTOR )
	);
	echo '</p></div>';
}
