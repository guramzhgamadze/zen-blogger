<?php
/**
 * Dynamic tag: Reading Time
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Estimated reading time.
 */
class Zen_Blogger_Tag_Reading_Time extends \Elementor\Core\DynamicTags\Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-reading-time';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Reading Time', 'zen-blogger' );
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
		$id      = $this->target_id();
		$content = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $id ) ) );

		// Unicode letter runs, so non-Latin scripts are counted too.
		$words = preg_match_all( '/[\p{L}\p{N}\p{M}]+/u', $content );

		if ( false === $words ) {
			$words = (int) str_word_count( $content );
		}

		$cjk = preg_match_all( '/[\x{3040}-\x{30FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{AC00}-\x{D7AF}]/u', $content );

		if ( $cjk ) {
			$words += (int) ceil( $cjk / 2 );
		}

		/** This filter is documented in includes/trait-zen-blogger-card.php */
		$wpm     = max( 1, (int) apply_filters( 'zenblog_reading_speed', 200, $id ) );
		$minutes = max( 1, (int) ceil( max( 1, (int) $words ) / $wpm ) );

		echo esc_html(
			sprintf(
				/* translators: %s: number of minutes. */
				_n( '%s min read', '%s min read', $minutes, 'zen-blogger' ),
				number_format_i18n( $minutes )
			)
		);
	}
}
