<?php
/**
 * Dynamic tag: Terms
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post terms from a chosen taxonomy.
 */
class Zen_Blogger_Tag_Terms extends \Elementor\Core\DynamicTags\Tag {

	use Zen_Blogger_Tag_Shared;

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'zenblog-post-terms';
	}

	/**
	 * Panel label.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Terms', 'zen-blogger' );
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
		$options = array();

		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
			$options[ $taxonomy->name ] = $taxonomy->label;
		}

		$this->add_control(
			'taxonomy',
			array(
				'label'   => esc_html__( 'Taxonomy', 'zen-blogger' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'category',
				'options' => $options,
			)
		);

		$this->add_control(
			'separator_text',
			array(
				'label'   => esc_html__( 'Separator', 'zen-blogger' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => ', ',
			)
		);

		$this->add_control(
			'max',
			array(
				'label'   => esc_html__( 'Max Terms', 'zen-blogger' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 20,
				'default' => 3,
			)
		);
	}

	/**
	 * Output.
	 *
	 * @return void
	 */
	public function render() {
		$taxonomy = sanitize_key( (string) $this->get_settings( 'taxonomy' ) );

		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$terms = get_the_terms( $this->target_id(), $taxonomy );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$max   = (int) $this->get_settings( 'max' );
		$max   = $max > 0 ? $max : 3;
		$terms = array_slice( $terms, 0, $max );
		$names = wp_list_pluck( $terms, 'name' );
		$glue  = (string) $this->get_settings( 'separator_text' );

		echo esc_html( implode( '' !== $glue ? $glue : ', ', $names ) );
	}
}
