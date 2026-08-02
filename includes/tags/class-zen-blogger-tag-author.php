<?php
/**
 * Dynamic tag: Author
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post author display name.
 */
class Zen_Blogger_Tag_Author extends \Elementor\Core\DynamicTags\Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-post-author';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Author', 'zen-blogger' );
	}

	/**
	 * Control categories.
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
		$author = (int) get_post_field( 'post_author', $this->target_id() );

		if ( $author ) {
			echo esc_html( get_the_author_meta( 'display_name', $author ) );
		}
	}
}
