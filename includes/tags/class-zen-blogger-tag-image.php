<?php
/**
 * Dynamic tag: Image
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Featured image.
 *
 * Extends Data_Tag, not Tag. Tag::get_content_type() is final and returns 'ui',
 * so Elementor output-buffers render() and never calls get_value() — an image tag
 * written as a Tag silently produces nothing at all, with no error anywhere.
 */
class Zen_Blogger_Tag_Image extends \Elementor\Core\DynamicTags\Data_Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-featured-image';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Featured Image', 'zen-blogger' );
	}

	/**
	 * Control categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY );
	}

	/**
	 * Tag settings.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'fallback',
			array(
				'label'       => esc_html__( 'Fallback', 'zen-blogger' ),
				'description' => esc_html__( 'Used only when the post has no featured image. Leave empty to render nothing.', 'zen-blogger' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
			)
		);
	}

	/**
	 * Resolve the image.
	 *
	 * Returns an EMPTY url when there is nothing to show — Elementor's image widget
	 * opens with `if ( empty( $settings['image']['url'] ) ) return;`, so empty is
	 * precisely what makes it render nothing. Returning a placeholder instead would
	 * paint a grey box on every post without a thumbnail.
	 *
	 * @param array $options Unused.
	 * @return array
	 */
	protected function get_value( array $options = array() ) {
		$image = array(
			'id'  => null,
			'url' => '',
		);

		$thumb = (int) get_post_thumbnail_id( $this->target_id() );

		if ( ! $thumb ) {
			$fallback = $this->get_settings( 'fallback' );

			if ( ! empty( $fallback['id'] ) ) {
				$thumb = (int) $fallback['id'];
			} elseif ( ! empty( $fallback['url'] ) ) {
				return array(
					'id'  => null,
					'url' => $fallback['url'],
				);
			}
		}

		if ( ! $thumb ) {
			return $image;
		}

		$src = wp_get_attachment_image_url( $thumb, 'full' );

		if ( ! $src ) {
			return $image;
		}

		return array(
			'id'  => $thumb,
			'url' => $src,
		);
	}
}
