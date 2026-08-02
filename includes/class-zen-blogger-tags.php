<?php
/**
 * Dynamic tags for building loop-item card templates.
 *
 * Elementor's own Dynamic Tags are a Pro feature, and free Elementor ships none
 * at all — so a "design your own card" template would have no way to read the
 * post it is rendering for. These tags fill exactly that gap, using the tag
 * categories that are available in free Elementor.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Zen Blogger dynamic tag group and its tags.
 */
final class Zen_Blogger_Tags {

	/**
	 * Tag group slug.
	 */
	const GROUP = 'zen-blogger';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register' ) );
	}

	/**
	 * Load the tag classes.
	 *
	 * Deferred to registration time: these extend Elementor base classes, so the
	 * files cannot be parsed before Elementor has loaded.
	 *
	 * @return void
	 */
	private static function load() {
		require_once ZENBLOG_PATH . 'includes/tags/trait-zen-blogger-tag-shared.php';

		foreach ( glob( ZENBLOG_PATH . 'includes/tags/class-zen-blogger-tag-*.php' ) as $file ) {
			require_once $file;
		}
	}

	/**
	 * Register the group and every tag.
	 *
	 * @param mixed $manager Elementor dynamic tags manager.
	 * @return void
	 */
	public static function register( $manager ) {
		if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
			return;
		}

		self::load();

		// The group must exist before any tag referencing it is registered.
		$manager->register_group(
			self::GROUP,
			array( 'title' => esc_html__( 'Zen Blogger', 'zen-blogger' ) )
		);

		foreach (
			array(
				'Zen_Blogger_Tag_Title',
				'Zen_Blogger_Tag_Excerpt',
				'Zen_Blogger_Tag_Date',
				'Zen_Blogger_Tag_Terms',
				'Zen_Blogger_Tag_Author',
				'Zen_Blogger_Tag_Reading_Time',
				'Zen_Blogger_Tag_Comments',
				'Zen_Blogger_Tag_Url',
				'Zen_Blogger_Tag_Image',
			) as $class
		) {
			if ( class_exists( $class ) ) {
				$manager->register( new $class() );
			}
		}
	}
}
