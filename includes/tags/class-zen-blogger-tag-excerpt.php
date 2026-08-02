<?php
/**
 * Dynamic tag: Excerpt
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post excerpt, with an optional word limit.
 */
class Zen_Blogger_Tag_Excerpt extends \Elementor\Core\DynamicTags\Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-post-excerpt';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Excerpt', 'zen-blogger' );
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
	 * Tag settings.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'words',
			array(
				'label'   => esc_html__( 'Word Limit', 'zen-blogger' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 200,
				'default' => 20,
			)
		);
	}

	/**
	 * Output.
	 *
	 * @return void
	 */
	public function render() {
		$id    = $this->target_id();
		$words = (int) $this->get_settings( 'words' );
		$words = $words > 0 ? $words : 20;

		$raw = has_excerpt( $id )
			? get_the_excerpt( $id )
			: wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $id ) ) );

		echo esc_html( wp_trim_words( $raw, $words, '…' ) );
	}
}
