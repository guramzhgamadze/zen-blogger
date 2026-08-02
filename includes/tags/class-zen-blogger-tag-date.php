<?php
/**
 * Dynamic tag: Date
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post date.
 */
class Zen_Blogger_Tag_Date extends \Elementor\Core\DynamicTags\Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-post-date';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Date', 'zen-blogger' );
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
			'format',
			array(
				'label'   => esc_html__( 'Format', 'zen-blogger' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default'  => esc_html__( 'Site default', 'zen-blogger' ),
					'human'    => esc_html__( 'Relative (2 days ago)', 'zen-blogger' ),
					'modified' => esc_html__( 'Last modified', 'zen-blogger' ),
				),
			)
		);
	}

	/**
	 * Output.
	 *
	 * @return void
	 */
	public function render() {
		$id     = $this->target_id();
		$format = (string) $this->get_settings( 'format' );

		if ( 'human' === $format ) {
			echo esc_html(
				sprintf(
					/* translators: %s: human-readable time difference. */
					__( '%s ago', 'zen-blogger' ),
					human_time_diff( (int) get_post_time( 'U', true, $id ), time() )
				)
			);
			return;
		}

		echo esc_html( 'modified' === $format ? get_the_modified_date( '', $id ) : get_the_date( '', $id ) );
	}
}
