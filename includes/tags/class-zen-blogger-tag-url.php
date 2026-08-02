<?php
/**
 * Dynamic tag: Url
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Permalink.
 */
class Zen_Blogger_Tag_Url extends \Elementor\Core\DynamicTags\Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-post-url';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post URL', 'zen-blogger' );
	}

	/**
	 * Control categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::URL_CATEGORY );
	}

	/**
	 * Output.
	 *
	 * @return void
	 */
	public function render() {
		echo esc_url( (string) get_permalink( $this->target_id() ) );
	}
}
