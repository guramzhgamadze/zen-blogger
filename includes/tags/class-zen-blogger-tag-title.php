<?php
/**
 * Dynamic tag: Title
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post title.
 */
class Zen_Blogger_Tag_Title extends \Elementor\Core\DynamicTags\Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-post-title';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Title', 'zen-blogger' );
	}

	/**
	 * Control categories this tag can fill.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	/**
	 * Output.
	 *
	 * @return void
	 */
	public function render() {
		echo esc_html( get_the_title( $this->target_id() ) );
	}
}
