<?php
/**
 * Dynamic tag: Comments
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Comment count.
 */
class Zen_Blogger_Tag_Comments extends \Elementor\Core\DynamicTags\Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-comment-count';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Comment Count', 'zen-blogger' );
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
		$count = (int) get_comments_number( $this->target_id() );

		echo esc_html(
			sprintf(
				/* translators: %s: number of comments. */
				_n( '%s comment', '%s comments', $count, 'zen-blogger' ),
				number_format_i18n( $count )
			)
		);
	}
}
