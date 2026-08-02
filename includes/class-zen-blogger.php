<?php
/**
 * Plugin bootstrap / service container.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Singleton that wires the plugin into WordPress and Elementor.
 */
final class Zen_Blogger {

	/**
	 * Singleton instance.
	 *
	 * @var Zen_Blogger|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Zen_Blogger
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register every hook the plugin needs.
	 *
	 * @return void
	 */
	public function init() {
		require_once ZENBLOG_PATH . 'includes/class-zen-blogger-assets.php';
		require_once ZENBLOG_PATH . 'includes/class-zen-blogger-query.php';
		require_once ZENBLOG_PATH . 'includes/trait-zen-blogger-card.php';
		require_once ZENBLOG_PATH . 'includes/trait-zen-blogger-filter.php';
		require_once ZENBLOG_PATH . 'includes/class-zen-blogger-rest.php';
		require_once ZENBLOG_PATH . 'includes/class-zen-blogger-tags.php';

		Zen_Blogger_Assets::register_hooks();
		Zen_Blogger_Rest::register_hooks();
		Zen_Blogger_Tags::register_hooks();

		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register the "Zen Blogger" panel category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'zen-blogger',
			array(
				'title' => esc_html__( 'Zen Blogger', 'zen-blogger' ),
				'icon'  => 'eicon-posts-carousel',
			)
		);
	}

	/**
	 * Register every widget this plugin ships.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once ZENBLOG_PATH . 'includes/widgets/class-zen-blogger-carousel.php';
		require_once ZENBLOG_PATH . 'includes/widgets/class-zen-blogger-posts.php';

		$widgets_manager->register( new Zen_Blogger_Carousel() );
		$widgets_manager->register( new Zen_Blogger_Posts() );
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {}

	/**
	 * Block cloning of the singleton.
	 *
	 * @return void
	 */
	private function __clone() {}
}
