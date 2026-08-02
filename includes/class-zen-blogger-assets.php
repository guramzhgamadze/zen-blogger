<?php
/**
 * Front-end asset registration.
 *
 * Handles are only *registered* here. Elementor enqueues them on demand through the
 * widget's get_style_depends() / get_script_depends(), so a page without the widget
 * ships none of this CSS or JS.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's stylesheet and script.
 */
final class Zen_Blogger_Assets {

	/**
	 * Stylesheet handle.
	 */
	const STYLE_HANDLE = 'zen-blogger';

	/**
	 * Script handle.
	 */
	const SCRIPT_HANDLE = 'zen-blogger';

	/**
	 * Posts-widget script handle. Kept separate so a page with only a carousel
	 * never downloads the pagination code, and vice versa.
	 */
	const POSTS_SCRIPT_HANDLE = 'zen-blogger-posts';

	/**
	 * Hook asset registration into WordPress.
	 *
	 * Registration must also run inside the Elementor editor preview, which enqueues
	 * front-end assets through its own hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ) );
		add_action( 'elementor/preview/enqueue_styles', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the stylesheet and script.
	 *
	 * Elementor registers `swiper` (script + style) and `e-swiper` (style) itself.
	 * We depend on those rather than shipping a second copy of Swiper.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			wp_register_style(
				self::STYLE_HANDLE,
				ZENBLOG_URL . 'assets/css/zen-blogger.css',
				array(),
				ZENBLOG_VERSION
			);
		}

		if ( wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		$deps = array( 'elementor-frontend' );

		if ( wp_script_is( 'swiper', 'registered' ) ) {
			$deps[] = 'swiper';
		}

		// No 'defer' strategy: this script listens for elementor/frontend/init, and a
		// deferred script can execute after that event has already fired.
		wp_register_script(
			self::SCRIPT_HANDLE,
			ZENBLOG_URL . 'assets/js/zen-blogger.js',
			$deps,
			ZENBLOG_VERSION,
			array( 'in_footer' => true )
		);

		if ( ! wp_script_is( self::POSTS_SCRIPT_HANDLE, 'registered' ) ) {
			wp_register_script(
				self::POSTS_SCRIPT_HANDLE,
				ZENBLOG_URL . 'assets/js/zen-blogger-posts.js',
				array( 'elementor-frontend' ),
				ZENBLOG_VERSION,
				array( 'in_footer' => true )
			);
		}

		$strings = wp_json_encode(
			array(
				'prevSlide'  => __( 'Previous slide', 'zen-blogger' ),
				'nextSlide'  => __( 'Next slide', 'zen-blogger' ),
				'firstSlide' => __( 'This is the first slide', 'zen-blogger' ),
				'lastSlide'  => __( 'This is the last slide', 'zen-blogger' ),
				'goToSlide'  => __( 'Go to slide {{index}}', 'zen-blogger' ),
				'slideLabel' => __( 'Slide {{index}} of {{slidesLength}}', 'zen-blogger' ),
				'carousel'   => __( 'carousel', 'zen-blogger' ),
				'slide'      => __( 'slide', 'zen-blogger' ),
				'play'       => __( 'Start automatic slide show', 'zen-blogger' ),
				'pause'      => __( 'Stop automatic slide show', 'zen-blogger' ),
				'loadError'  => __( 'Could not load posts. Please try again.', 'zen-blogger' ),
			)
		);

		if ( $strings ) {
			foreach ( array( self::SCRIPT_HANDLE, self::POSTS_SCRIPT_HANDLE ) as $handle ) {
				wp_add_inline_script(
					$handle,
					'window.zenBloggerI18n = window.zenBloggerI18n || ' . $strings . ';',
					'before'
				);
			}
		}
	}

	/**
	 * Style handles the carousel widget depends on.
	 *
	 * @return string[]
	 */
	public static function style_depends() {
		$handles = array( self::STYLE_HANDLE );

		foreach ( array( 'swiper', 'e-swiper' ) as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) ) {
				$handles[] = $handle;
			}
		}

		return $handles;
	}

	/**
	 * Script handles the carousel widget depends on.
	 *
	 * @return string[]
	 */
	public static function script_depends() {
		return array( self::SCRIPT_HANDLE );
	}
}
