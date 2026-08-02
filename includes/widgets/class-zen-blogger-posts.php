<?php
/**
 * Zen Blogger — Posts widget.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * A grid / masonry / list of posts with accessible AJAX pagination and filtering.
 */
class Zen_Blogger_Posts extends Widget_Base {

	use Zen_Blogger_Card_Trait;
	use Zen_Blogger_Filter_Trait;

	/**
	 * Widget name stored in _elementor_data. Never rename this.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'zen-blogger-posts';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Posts', 'zen-blogger' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	/**
	 * Panel categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( 'zen-blogger' );
	}

	/**
	 * Panel search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array( 'posts', 'blog', 'grid', 'masonry', 'list', 'archive', 'loop', 'filter', 'zen' );
	}

	/**
	 * Stylesheet dependencies.
	 *
	 * Settings are never read here — Elementor calls this on widget types too.
	 * See the note in the carousel widget.
	 *
	 * @return string[]
	 */
	public function get_style_depends(): array {
		return array( Zen_Blogger_Assets::STYLE_HANDLE );
	}

	/**
	 * Script dependencies.
	 *
	 * @return string[]
	 */
	public function get_script_depends(): array {
		return array( Zen_Blogger_Assets::POSTS_SCRIPT_HANDLE );
	}

	/**
	 * Skip the legacy inner wrapper.
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * Output depends on live query results.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/*
	 * -------------------------------------------------------------------
	 * Controls
	 * -------------------------------------------------------------------
	 */

	/**
	 * Register every control.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_grid_controls();
		$this->register_layout_controls();
		$this->register_query_controls();
		$this->register_filter_controls();
		$this->register_pagination_controls();
		$this->register_posts_a11y_controls();
		$this->register_seo_controls();

		$this->register_card_style();
		$this->register_image_style();
		$this->register_badge_style();
		$this->register_terms_style();
		$this->register_title_style();
		$this->register_meta_style();
		$this->register_excerpt_style();
		$this->register_button_style();
		$this->register_filter_style();
		$this->register_nav_style();
	}

	/**
	 * Content → Grid.
	 *
	 * @return void
	 */
	private function register_grid_controls() {
		$this->start_controls_section(
			'zenblog_section_grid',
			array(
				'label' => esc_html__( 'Grid', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'zenblog_layout',
			array(
				'label'   => esc_html__( 'Layout', 'zen-blogger' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'    => esc_html__( 'Grid — equal rows', 'zen-blogger' ),
					'masonry' => esc_html__( 'Masonry — natural heights', 'zen-blogger' ),
					'list'    => esc_html__( 'List — one per row', 'zen-blogger' ),
					'feature' => esc_html__( 'Feature — first post larger', 'zen-blogger' ),
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_columns',
			array(
				'label'          => esc_html__( 'Columns', 'zen-blogger' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'condition'      => array( 'zenblog_layout!' => 'list' ),

				/*
				 * A control that only has `selectors` makes Elementor refresh the
				 * generated CSS without re-rendering the widget. That is normally
				 * what you want, but the Feature layout caps its span against the
				 * column count in PHP and writes it as an inline custom property —
				 * so without a re-render the span stays stale in the editor until
				 * some other control happens to force one.
				 */
				'render_type'    => 'template',
				'selectors'      => array(
					'{{WRAPPER}} .zenblog__grid' => '--zenblog-columns: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_column_gap',
			array(
				'label'      => esc_html__( 'Column Gap', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 120,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__grid' => 'column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_row_gap',
			array(
				'label'      => esc_html__( 'Row Gap', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 120,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array(
					// Masonry is a CSS-columns layout, where row-gap has no effect —
					// the same value is published as a custom property so the masonry
					// rule can apply it as an item margin instead.
					'{{WRAPPER}} .zenblog__grid' => 'row-gap: {{SIZE}}{{UNIT}}; --zenblog-row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'zenblog_feature_span',
			array(
				'label'       => esc_html__( 'Feature Spans', 'zen-blogger' ),
				'description' => esc_html__( 'How many columns the first post occupies.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '2',
				'options'     => array(
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'condition'   => array( 'zenblog_layout' => 'feature' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content → Pagination.
	 *
	 * @return void
	 */
	private function register_pagination_controls() {
		$this->start_controls_section(
			'zenblog_section_nav',
			array(
				'label' => esc_html__( 'Pagination', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'zenblog_nav',
			array(
				'label'   => esc_html__( 'Type', 'zen-blogger' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''          => esc_html__( 'None', 'zen-blogger' ),
					'numbers'   => esc_html__( 'Page numbers', 'zen-blogger' ),
					'prev_next' => esc_html__( 'Previous / Next', 'zen-blogger' ),
					'load_more' => esc_html__( 'Load More button (AJAX)', 'zen-blogger' ),
					'infinite'  => esc_html__( 'Infinite scroll (AJAX)', 'zen-blogger' ),
				),
			)
		);

		$this->add_control(
			'zenblog_nav_ajax',
			array(
				'label'       => esc_html__( 'Update Without Reloading', 'zen-blogger' ),
				'description' => esc_html__( 'Page numbers and Previous/Next stay real links; this only upgrades them to fetch in place. The address bar is kept in step so Back and sharing still work.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'zenblog_nav' => array( 'numbers', 'prev_next' ) ),
			)
		);

		$this->add_control(
			'zenblog_load_more_text',
			array(
				'label'       => esc_html__( 'Button Text', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Load more posts', 'zen-blogger' ),
				'dynamic'     => array( 'active' => true ),
				// Infinite scroll renders the same button as its fallback, so the
				// text has to be editable there too — it was previously left
				// showing an uneditable default.
				'condition'   => array( 'zenblog_nav' => array( 'load_more', 'infinite' ) ),
			)
		);

		$this->add_control(
			'zenblog_prev_text',
			array(
				'label'     => esc_html__( 'Previous Text', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Previous', 'zen-blogger' ),
				'condition' => array( 'zenblog_nav' => 'prev_next' ),
			)
		);

		$this->add_control(
			'zenblog_next_text',
			array(
				'label'     => esc_html__( 'Next Text', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Next', 'zen-blogger' ),
				'condition' => array( 'zenblog_nav' => 'prev_next' ),
			)
		);

		$this->add_control(
			'zenblog_infinite_max',
			array(
				'label'       => esc_html__( 'Auto-Load Limit', 'zen-blogger' ),
				'description' => esc_html__( 'How many pages load automatically before the button must be pressed again. A feed that never stops growing puts the footer permanently out of reach.', 'zen-blogger' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 20,
				'default'     => 5,
				'condition'   => array( 'zenblog_nav' => 'infinite' ),
			)
		);

		$this->add_control(
			'zenblog_infinite_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Infinite scroll still renders a real Load More button as its fallback, and stops loading after the visitor presses it — an endless feed traps keyboard users before they can reach anything below it.', 'zen-blogger' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array( 'zenblog_nav' => 'infinite' ),
			)
		);

		$this->add_control(
			'zenblog_nav_current_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'With the "Current page query" source, pagination stays as real page links. The archive query only exists on the page itself, so it cannot be rebuilt for a background request — fetching in place would quietly return the wrong posts.', 'zen-blogger' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array(
					'zenblog_source' => 'current',
					'zenblog_nav!'   => '',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content → Accessibility.
	 *
	 * @return void
	 */
	private function register_posts_a11y_controls() {
		$this->start_controls_section(
			'zenblog_section_posts_a11y',
			array(
				'label' => esc_html__( 'Accessibility', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'zenblog_region_label',
			array(
				'label'       => esc_html__( 'List Label', 'zen-blogger' ),
				'description' => esc_html__( 'Announced by screen readers. Give each list on a page a different label.', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Posts', 'zen-blogger' ),
			)
		);

		$this->add_control(
			'zenblog_a11y_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'After every filter or page change the result count is announced politely, the grid is marked busy while loading, and focus moves to the first new post so keyboard users are not dropped back to the top of the page.', 'zen-blogger' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'zenblog_reduced_motion',
			array(
				'label'       => esc_html__( 'Respect "Reduce Motion"', 'zen-blogger' ),
				'description' => esc_html__( 'Visitors whose system asks for reduced motion get no card lift, zoom or transitions.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'zenblog_lazy_images',
			array(
				'label'       => esc_html__( 'Lazy-Load Below the Fold', 'zen-blogger' ),
				'description' => esc_html__( 'Images in the first row load eagerly with high priority; the rest load lazily.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Filter Bar.
	 *
	 * @return void
	 */
	private function register_filter_style() {
		$this->start_controls_section(
			'zenblog_style_filter',
			array(
				'label'     => esc_html__( 'Filter Bar', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_filter_container_heading',
			array(
				'label' => esc_html__( 'Container', 'zen-blogger' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_filter_container_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__filters',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'zenblog_filter_container_border',
				'selector' => '{{WRAPPER}} .zenblog__filters',
			)
		);

		$this->add_responsive_control(
			'zenblog_filter_container_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__filters' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_filter_container_padding',
			array(
				'label'      => esc_html__( 'Padding', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__filters' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_filter_row_gap',
			array(
				'label'      => esc_html__( 'Space Between Rows', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__filters' => '--zenblog-filter-row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'zenblog_filter_controls_heading',
			array(
				'label'     => esc_html__( 'Controls', 'zen-blogger' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'zenblog_filter_typography',
				'selector' => '{{WRAPPER}} .zenblog__check label, {{WRAPPER}} .zenblog__select, {{WRAPPER}} .zenblog__search-input, {{WRAPPER}} .zenblog__filter-submit, {{WRAPPER}} .zenblog__filter-reset',
			)
		);

		$this->add_control(
			'zenblog_filter_color',
			array(
				'label'     => esc_html__( 'Text Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__check label, {{WRAPPER}} .zenblog__select, {{WRAPPER}} .zenblog__search-input, {{WRAPPER}} .zenblog__filter-submit, {{WRAPPER}} .zenblog__filter-reset' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_filter_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__check label, {{WRAPPER}} .zenblog__select, {{WRAPPER}} .zenblog__search-input, {{WRAPPER}} .zenblog__filter-submit, {{WRAPPER}} .zenblog__filter-reset',
			)
		);

		$this->add_control(
			'zenblog_filter_color_active',
			array(
				'label'     => esc_html__( 'Selected Text Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__check input:checked + label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_filter_bg_active',
			array(
				'label'     => esc_html__( 'Selected Background', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__check input:checked + label' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'zenblog_filter_border',
				'selector' => '{{WRAPPER}} .zenblog__check label, {{WRAPPER}} .zenblog__select, {{WRAPPER}} .zenblog__search-input, {{WRAPPER}} .zenblog__filter-submit, {{WRAPPER}} .zenblog__filter-reset',
			)
		);

		$this->add_responsive_control(
			'zenblog_filter_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__check label, {{WRAPPER}} .zenblog__select, {{WRAPPER}} .zenblog__search-input, {{WRAPPER}} .zenblog__filter-submit, {{WRAPPER}} .zenblog__filter-reset' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_filter_padding',
			array(
				'label'      => esc_html__( 'Padding', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__check label, {{WRAPPER}} .zenblog__select, {{WRAPPER}} .zenblog__search-input, {{WRAPPER}} .zenblog__filter-submit, {{WRAPPER}} .zenblog__filter-reset' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_filter_gap',
			array(
				'label'      => esc_html__( 'Gap', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__filter-row' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_filter_spacing',
			array(
				'label'      => esc_html__( 'Space Below', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 120,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 32,
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__filters' => 'margin-block-end: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'zenblog_count_heading',
			array(
				'label'     => esc_html__( 'Result Count', 'zen-blogger' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'zenblog_show_count' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'zenblog_count_typography',
				'selector'  => '{{WRAPPER}} .zenblog__result-count',
				'condition' => array( 'zenblog_show_count' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_count_color',
			array(
				'label'     => esc_html__( 'Text Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					// The stylesheet dims it to 0.8; a chosen colour should be the
					// colour, not a faded version of it.
					'{{WRAPPER}} .zenblog__result-count' => 'color: {{VALUE}}; opacity: 1;',
				),
				'condition' => array( 'zenblog_show_count' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'zenblog_count_align',
			array(
				'label'     => esc_html__( 'Alignment', 'zen-blogger' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__result-count' => 'text-align: {{VALUE}};',
				),
				'condition' => array( 'zenblog_show_count' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'zenblog_count_spacing',
			array(
				'label'      => esc_html__( 'Space Above', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__result-count' => 'margin-block-start: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'zenblog_show_count' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Pagination.
	 *
	 * @return void
	 */
	private function register_nav_style() {
		$this->start_controls_section(
			'zenblog_style_nav',
			array(
				'label'     => esc_html__( 'Pagination', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'zenblog_nav!' => '' ),
			)
		);

		$this->add_responsive_control(
			'zenblog_nav_align',
			array(
				'label'     => esc_html__( 'Alignment', 'zen-blogger' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => esc_html__( 'Left', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => esc_html__( 'Center', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => esc_html__( 'Right', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .zenblog__nav' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_nav_full_width',
			array(
				'label'     => esc_html__( 'Full Width Button', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'selectors' => array(
					// Its own axis, not a fourth alignment: the row keeps whatever
					// alignment was chosen, the button just fills it.
					'{{WRAPPER}} .zenblog__more-btn' => 'flex: 1 1 100%;',
				),
				'condition' => array( 'zenblog_nav' => array( 'load_more', 'infinite' ) ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'zenblog_nav_typography',
				'selector' => '{{WRAPPER}} .zenblog__page, {{WRAPPER}} .zenblog__more-btn',
			)
		);

		$this->add_control(
			'zenblog_nav_color',
			array(
				'label'     => esc_html__( 'Text Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__page, {{WRAPPER}} .zenblog__more-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_nav_bg',
			array(
				'label'     => esc_html__( 'Background', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__page, {{WRAPPER}} .zenblog__more-btn' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_nav_color_current',
			array(
				'label'     => esc_html__( 'Current Page Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__page[aria-current="page"]' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_nav_bg_current',
			array(
				'label'     => esc_html__( 'Current Page Background', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__page[aria-current="page"]' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'zenblog_nav_border',
				'selector' => '{{WRAPPER}} .zenblog__page, {{WRAPPER}} .zenblog__more-btn',
			)
		);

		$this->add_responsive_control(
			'zenblog_nav_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__page, {{WRAPPER}} .zenblog__more-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_nav_padding',
			array(
				'label'      => esc_html__( 'Padding', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__page, {{WRAPPER}} .zenblog__more-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_nav_spacing',
			array(
				'label'      => esc_html__( 'Space Above', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 120,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 40,
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__nav' => 'margin-block-start: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/*
	 * -------------------------------------------------------------------
	 * Render
	 * -------------------------------------------------------------------
	 */

	/**
	 * Render the widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$uid      = 'zenblog-' . $this->get_id();
		$paged    = $this->current_page( $settings );
		$state    = $this->filter_state( $settings, $uid );

		$query = $this->run_query( $settings, $paged, $state );

		$layout  = ! empty( $settings['zenblog_layout'] ) ? sanitize_html_class( $settings['zenblog_layout'] ) : 'grid';
		$nav     = isset( $settings['zenblog_nav'] ) ? $settings['zenblog_nav'] : '';
		$label   = isset( $settings['zenblog_region_label'] ) ? trim( $settings['zenblog_region_label'] ) : '';
		$total   = (int) $query->found_posts;
		$maxpage = (int) $query->max_num_pages;

		/*
		 * The skin class drives every card style rule in the shared stylesheet.
		 * Without it a Posts widget silently renders every skin as Classic — the
		 * markup differs, but overlay/overlap/side/editorial all need their CSS.
		 */
		$skin = ! empty( $settings['zenblog_skin'] ) ? sanitize_html_class( $settings['zenblog_skin'] ) : 'classic';

		$classes = array(
			'zenblog',
			'zenblog-posts',
			'zenblog--layout-' . $layout,
			'zenblog--skin-' . $skin,
		);

		if ( 'side' === $skin && 'end' === ( isset( $settings['zenblog_side_media_position'] ) ? $settings['zenblog_side_media_position'] : 'start' ) ) {
			$classes[] = 'zenblog--media-end';
		}

		if ( 'overlay' === $skin && 'yes' === ( isset( $settings['zenblog_scrim'] ) ? $settings['zenblog_scrim'] : 'yes' ) ) {
			$classes[] = 'zenblog--scrim';
		}

		if ( 'yes' === ( isset( $settings['zenblog_reduced_motion'] ) ? $settings['zenblog_reduced_motion'] : 'yes' ) ) {
			$classes[] = 'zenblog--respect-motion';
		}

		/*
		 * A feature card that spans every column is just a full-width card — the
		 * point of the layout is that something sits beside it. The span is
		 * therefore capped at one less than the column count, per breakpoint,
		 * and disappears entirely at a single column. This has to be computed in
		 * PHP: {{WRAPPER}} selectors cannot read another control's value, and CSS
		 * cannot clamp a `grid-column: span N` against a custom property.
		 */
		if ( 'feature' === $layout ) {
			$span = isset( $settings['zenblog_feature_span'] ) ? (int) $settings['zenblog_feature_span'] : 2;
			$vars = array();

			/*
			 * Each device falls back to that device's own control default, NOT to
			 * the desktop value. Elementor's generated CSS uses tablet_default /
			 * mobile_default when those controls are untouched, so inheriting
			 * desktop here would compute a span against a column count that is not
			 * the one actually rendering.
			 */
			$fallbacks = array(
				'desktop' => 3,
				'tablet'  => 2,
				'mobile'  => 1,
			);

			foreach ( $fallbacks as $device => $fallback ) {
				$key = ( 'desktop' === $device ) ? 'zenblog_columns' : 'zenblog_columns_' . $device;
				$n   = ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) ? (int) $settings[ $key ] : $fallback;
				$n   = max( 1, $n );

				$vars[ $device ] = ( $n > 1 ) ? max( 1, min( $span, $n - 1 ) ) : 1;
			}

			$this->add_render_attribute(
				'zenblog',
				'style',
				sprintf(
					'--zenblog-fspan-d:%d;--zenblog-fspan-t:%d;--zenblog-fspan-m:%d;',
					$vars['desktop'],
					$vars['tablet'],
					$vars['mobile']
				)
			);
		}

		$this->add_render_attribute(
			'zenblog',
			array(
				'class'              => $classes,
				'id'                 => $uid,
				'data-zenblog-posts' => wp_json_encode(
					array(
						'restUrl'   => rest_url( Zen_Blogger_Rest::NS . '/posts' ),

						/*
						 * The document the widget was PLACED in, which on a Theme
						 * Builder template is not the post being viewed. See
						 * document_id() — sending the queried ID here made every
						 * AJAX page 404 on every theme template.
						 */
						'postId'    => $this->document_id(),
						'contextId' => is_singular() ? (int) get_queried_object_id() : 0,
						'pageUrl'   => $this->page_url(),
						'elementId' => $this->get_id(),
						'nav'       => $nav,
						'ajax'      => $this->ajax_enabled( $settings ),
						'paged'     => $paged,
						'maxPages'  => $maxpage,
						'total'     => $total,
						'state'     => $state,
						'perPage'   => $this->per_page( $settings ),
						'autoMax'   => isset( $settings['zenblog_infinite_max'] ) ? max( 1, (int) $settings['zenblog_infinite_max'] ) : 5,
					)
				),
			)
		);

		if ( '' !== $label ) {
			$this->add_render_attribute(
				'zenblog',
				array(
					'role'       => 'region',
					'aria-label' => $label,
				)
			);
		}
		?>
		<div <?php $this->print_render_attribute_string( 'zenblog' ); ?>>
			<?php $this->render_filter_bar( $settings, $state, $uid, $total ); ?>

			<?php
			/*
			 * A polite status line, not an alert: filter and page changes are
			 * user-initiated, so announcing the new result count without
			 * interrupting is the right level of noise.
			 */
			?>
			<p class="zenblog__status zenblog__sr" role="status" aria-live="polite"></p>

			<div class="zenblog__grid" id="<?php echo esc_attr( $uid ); ?>-grid" aria-busy="false">
				<?php
				if ( ! $query->have_posts() ) {
					$this->render_empty_state( $settings );
				} else {
					$this->render_items( $settings, $query, $paged );
				}
				?>
			</div>

			<?php $this->render_nav( $settings, $paged, $maxpage, $uid, $state ); ?>

			<?php if ( 'infinite' === $nav ) : ?>
				<?php
				/*
				 * Infinite scroll watches this, not the paginator. The paginator is
				 * replaced wholesale on every AJAX page — observing it meant the
				 * IntersectionObserver was left watching a detached node after the
				 * first load, and auto-loading silently stopped. This element is
				 * never re-rendered, so the observer stays attached.
				 */
				?>
				<div class="zenblog__sentinel" aria-hidden="true"></div>
			<?php endif; ?>
		</div>
		<?php
		wp_reset_postdata();
	}

	/**
	 * Render the cards for one page of results.
	 *
	 * @param array    $settings Widget settings.
	 * @param WP_Query $query    Query.
	 * @param int      $paged    Current page.
	 * @return void
	 */
	private function render_items( array $settings, $query, $paged ) {
		$index  = 0;
		$eager  = ( $paged > 1 ) ? 0 : $this->eager_count( $settings );
		$shown  = array();
		$schema = array();
		$offset = ( $paged - 1 ) * $this->per_page( $settings );

		while ( $query->have_posts() ) {
			$query->the_post();
			++$index;
			$shown[]  = get_the_ID();
			$schema[] = $this->schema_item( $settings, $offset + $index );

			$this->render_item( $settings, $index <= $eager, $offset + $index );
		}

		Zen_Blogger_Query::mark_rendered( $shown );

		// Only the first page carries the structured data: an AJAX page is an
		// update to the same list, not a second list.
		if ( 1 === (int) $paged ) {
			$this->render_schema( $settings, $schema );
		}
	}

	/**
	 * Render a single card wrapper.
	 *
	 * @param array $settings Widget settings.
	 * @param bool  $eager    Load the image eagerly.
	 * @param int   $position Overall position in the list.
	 * @return void
	 */
	private function render_item( array $settings, $eager, $position ) {
		$skin       = isset( $settings['zenblog_skin'] ) ? $settings['zenblog_skin'] : 'classic';
		$show_image = 'minimal' !== $skin && 'yes' === ( isset( $settings['zenblog_show_image'] ) ? $settings['zenblog_show_image'] : '' );
		$terms_on   = 'yes' === ( isset( $settings['zenblog_show_terms'] ) ? $settings['zenblog_show_terms'] : '' );
		$terms_pos  = isset( $settings['zenblog_terms_position'] ) ? $settings['zenblog_terms_position'] : 'body';
		$stretched  = 'yes' === ( isset( $settings['zenblog_link_whole_card'] ) ? $settings['zenblog_link_whole_card'] : '' );

		if ( 'overlay' === $skin ) {
			$terms_pos = 'body';
		}
		?>
		<article class="zenblog__item" data-zenblog-position="<?php echo esc_attr( (string) $position ); ?>" tabindex="-1">
			<?php
			// A user-designed template replaces the built-in card entirely; the item
			// wrapper stays so focus management after an AJAX load still works.
			if ( $this->render_loop_template( $settings, $position ) ) {
				echo '</article>';
				return;
			}
			?>
			<div class="zenblog__card<?php echo $stretched ? ' zenblog__card--stretched' : ''; ?>">
				<?php
				if ( $show_image ) {
					$this->render_media( $settings, $eager, $terms_on && 'media' === $terms_pos, $position );
				}
				?>
				<div class="zenblog__body">
					<?php $this->render_card_body( $settings, $stretched, $position ); ?>
				</div>
			</div>
		</article>
		<?php
	}

	/**
	 * Render pagination.
	 *
	 * @param array  $settings Widget settings.
	 * @param int    $paged    Current page.
	 * @param int    $maxpage  Total pages.
	 * @param string $uid      Widget DOM id.
	 * @param array  $state    Active filter state.
	 * @return void
	 */
	private function render_nav( array $settings, $paged, $maxpage, $uid, array $state ) {
		$nav = isset( $settings['zenblog_nav'] ) ? $settings['zenblog_nav'] : '';

		if ( '' === $nav || $maxpage < 2 ) {
			return;
		}

		/*
		 * Load More and infinite scroll have nothing left to offer on the final
		 * page, so the whole paginator goes. It used to keep rendering a button
		 * that pointed one page past the end; pressing it fetched an empty
		 * result and appended the "no posts found" notice under the cards —
		 * which reads as a stray line of unstyled text below the grid.
		 */
		if ( ( 'load_more' === $nav || 'infinite' === $nav ) && $paged >= $maxpage ) {
			return;
		}

		/*
		 * Built from the page's own URL, not the current request. When this runs
		 * inside the REST callback the current request is /wp-json/..., and the
		 * no-JavaScript fallback links would point visitors at raw JSON.
		 */
		$base = $this->nav_base_url
			? remove_query_arg( self::page_arg( $uid ), $this->nav_base_url )
			: remove_query_arg( self::page_arg( $uid ) );

		if ( '' !== $state['search'] ) {
			$base = add_query_arg( $this->search_arg( $uid ), rawurlencode( $state['search'] ), $base );
		}

		if ( '' !== $state['sort'] ) {
			$base = add_query_arg( $this->sort_arg( $uid ), $state['sort'], $base );
		}

		foreach ( $state['terms'] as $taxonomy => $ids ) {
			$base = add_query_arg( $this->tax_arg( $uid, $taxonomy ), implode( ',', array_map( 'intval', $ids ) ), $base );
		}

		$page_url = function ( $n ) use ( $base, $uid ) {
			return add_query_arg( self::page_arg( $uid ), (int) $n, $base );
		};

		echo '<nav class="zenblog__nav" aria-label="' . esc_attr__( 'Posts pagination', 'zen-blogger' ) . '">';

		if ( 'load_more' === $nav || 'infinite' === $nav ) {
			// Even infinite scroll renders a real button: it is the no-JS fallback,
			// the keyboard path, and the stop control once auto-loading pauses.
			$text = isset( $settings['zenblog_load_more_text'] ) ? trim( $settings['zenblog_load_more_text'] ) : '';
			$text = '' !== $text ? $text : __( 'Load more posts', 'zen-blogger' );

			printf(
				'<a class="zenblog__more-btn" href="%1$s" aria-controls="%2$s-grid" data-zenblog-more="1">%3$s</a>',
				esc_url( $page_url( $paged + 1 ) ),
				esc_attr( $uid ),
				esc_html( $text )
			);

			echo '</nav>';
			return;
		}

		if ( 'prev_next' === $nav ) {
			$prev = isset( $settings['zenblog_prev_text'] ) ? $settings['zenblog_prev_text'] : __( 'Previous', 'zen-blogger' );
			$next = isset( $settings['zenblog_next_text'] ) ? $settings['zenblog_next_text'] : __( 'Next', 'zen-blogger' );

			if ( $paged > 1 ) {
				printf(
					'<a class="zenblog__page zenblog__page--prev" href="%1$s" data-zenblog-page="%2$d" rel="prev">%3$s</a>',
					esc_url( $page_url( $paged - 1 ) ),
					(int) ( $paged - 1 ),
					esc_html( $prev )
				);
			}

			if ( $paged < $maxpage ) {
				printf(
					'<a class="zenblog__page zenblog__page--next" href="%1$s" data-zenblog-page="%2$d" rel="next">%3$s</a>',
					esc_url( $page_url( $paged + 1 ) ),
					(int) ( $paged + 1 ),
					esc_html( $next )
				);
			}

			echo '</nav>';
			return;
		}

		/*
		 * Numbered pages, windowed around the current one — but the first and last
		 * page are always reachable. Without them a long archive is a trap: the
		 * window only ever moves two steps at a time, so page 29 of 29 cannot be
		 * reached at all from page 1.
		 */
		$window = 2;
		$from   = max( 1, $paged - $window );
		$to     = min( $maxpage, $paged + $window );

		$link = function ( $n ) use ( $page_url, $paged ) {
			printf(
				'<a class="zenblog__page" href="%1$s" data-zenblog-page="%2$d"%3$s>%4$s</a>',
				esc_url( $page_url( $n ) ),
				(int) $n,
				( (int) $n === (int) $paged ) ? ' aria-current="page"' : '',
				esc_html( number_format_i18n( $n ) )
			);
		};

		$gap = function () {
			// Presentational: a screen reader should hear the page numbers, not an
			// ellipsis between them.
			echo '<span class="zenblog__page-gap" aria-hidden="true">&hellip;</span>';
		};

		if ( $from > 1 ) {
			$link( 1 );
			if ( $from > 2 ) {
				$gap();
			}
		}

		for ( $n = $from; $n <= $to; $n++ ) {
			$link( $n );
		}

		if ( $to < $maxpage ) {
			if ( $to < $maxpage - 1 ) {
				$gap();
			}
			$link( $maxpage );
		}

		echo '</nav>';
	}

	/*
	 * -------------------------------------------------------------------
	 * AJAX
	 * -------------------------------------------------------------------
	 */

	/**
	 * Render one page of results for the REST endpoint.
	 *
	 * @param int    $paged    Page number.
	 * @param array  $state    Validated filter state.
	 * @param string $page_url URL the widget's page lives at.
	 * @return array
	 */
	public function render_ajax_page( $paged, array $state, $page_url = '' ) {
		$settings           = $this->get_settings_for_display();
		$this->nav_base_url = $page_url ? $page_url : '';
		$query              = $this->run_query( $settings, $paged, $state );

		ob_start();

		if ( $query->have_posts() ) {
			$this->render_items( $settings, $query, $paged );
		} elseif ( $paged < 2 ) {
			/*
			 * "No posts found" answers "your filters matched nothing". Past page
			 * one it would instead be answering "you asked for a page that does
			 * not exist" — and in append mode that notice lands underneath a grid
			 * full of results, saying the opposite of what the visitor can see.
			 */
			$this->render_empty_state( $settings );
		}

		$html = ob_get_clean();

		// The paginator itself has to come back too: its window moves with the
		// current page, so leaving the old markup in place strands the visitor on
		// whatever range was rendered first.
		ob_start();
		$this->render_nav( $settings, $paged, (int) $query->max_num_pages, 'zenblog-' . $this->get_id(), $state );
		$nav = ob_get_clean();

		wp_reset_postdata();

		$total    = (int) $query->found_posts;
		$maxpages = (int) $query->max_num_pages;
		$shown    = min( $total, $paged * $this->per_page( $settings ) );

		return array(
			'html'       => $html,
			'nav'        => $nav,
			'paged'      => (int) $paged,
			'maxPages'   => $maxpages,
			'total'      => $total,
			'count'      => (int) $query->post_count,
			/* translators: 1: number of posts shown so far, 2: total number of posts. */
			'message'    => sprintf( __( 'Showing %1$s of %2$s posts', 'zen-blogger' ), number_format_i18n( $shown ), number_format_i18n( $total ) ),
			'countLabel' => $this->count_label( $settings, $total ),
		);
	}

	/*
	 * -------------------------------------------------------------------
	 * Helpers
	 * -------------------------------------------------------------------
	 */

	/**
	 * Build and run the query for a page.
	 *
	 * @param array $settings Widget settings.
	 * @param int   $paged    Page number.
	 * @param array $state    Validated filter state.
	 * @return WP_Query
	 */
	private function run_query( array $settings, $paged, array $state ) {
		$paged = max( 1, (int) $paged );

		$nav      = isset( $settings['zenblog_nav'] ) ? $settings['zenblog_nav'] : '';
		$paginate = '' !== $nav;

		add_filter( 'zenblog_query_args', array( $this, 'apply_paging' ), 10, 1 );

		$this->paging = array(
			'paged'    => $paged,
			'state'    => $state,
			'relation' => ( isset( $settings['zenblog_filter_relation'] ) && 'AND' === $settings['zenblog_filter_relation'] ) ? 'AND' : 'IN',
			'paginate' => $paginate,
		);

		$query = Zen_Blogger_Query::run( $settings, $this->get_id() );

		// Request-scoped: render() runs on every control change in the editor, and
		// a filter left attached would stack up across re-renders.
		remove_filter( 'zenblog_query_args', array( $this, 'apply_paging' ), 10 );

		return $query;
	}

	/**
	 * Paging state for the current run_query() call.
	 *
	 * @var array
	 */
	private $paging = array();

	/**
	 * URL paging links are built against, when it is not the current request.
	 *
	 * @var string
	 */
	private $nav_base_url = '';

	/**
	 * Inject paging and the active term filter into the query args.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function apply_paging( $args ) {
		$p = $this->paging;

		if ( empty( $p ) ) {
			return $args;
		}

		if ( ! empty( $p['paginate'] ) ) {
			$args['paged']         = (int) $p['paged'];
			$args['no_found_rows'] = false;
			unset( $args['offset'] ); // offset and paged are mutually exclusive in WP_Query.
		}

		$state = isset( $p['state'] ) ? $p['state'] : array();

		if ( ! empty( $state['search'] ) ) {
			$args['s'] = $state['search'];
		}

		if ( ! empty( $state['terms'] ) ) {
			$clauses = array();

			foreach ( $state['terms'] as $taxonomy => $ids ) {
				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				$clauses[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => array_map( 'intval', $ids ),
					'operator' => ( 'AND' === $p['relation'] ) ? 'AND' : 'IN',
				);
			}

			if ( $clauses ) {
				$existing = ! empty( $args['tax_query'] ) ? $args['tax_query'] : array();
				unset( $existing['relation'] );

				// Different taxonomies must all match; terms within one obey the
				// user's Any/All choice, set per clause above.
				$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $existing, $clauses ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filtering by taxonomy is the feature.
			}
		}

		if ( ! empty( $state['sort'] ) ) {
			$args = $this->apply_sort( $args, $state['sort'] );
		}

		return $args;
	}

	/**
	 * Translate a sort key from the URL into query arguments.
	 *
	 * @param array  $args Query args.
	 * @param string $sort Sort key, already validated against sort_options().
	 * @return array
	 */
	private function apply_sort( array $args, $sort ) {
		unset( $args['meta_key'] );

		switch ( $sort ) {
			case 'oldest':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			case 'title_desc':
				$args['orderby'] = 'title';
				$args['order']   = 'DESC';
				break;
			case 'comments':
				$args['orderby'] = 'comment_count';
				$args['order']   = 'DESC';
				break;
			case 'modified':
				$args['orderby'] = 'modified';
				$args['order']   = 'DESC';
				break;
			case 'rand':
				$args['orderby'] = 'rand';
				unset( $args['order'] );
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		return $args;
	}

	/**
	 * Posts per page for this widget.
	 *
	 * @param array $settings Widget settings.
	 * @return int
	 */
	private function per_page( array $settings ) {
		$n = isset( $settings['zenblog_per_page'] ) ? (int) $settings['zenblog_per_page'] : 9;

		return max( 1, min( 100, $n ) );
	}

	/**
	 * Whether AJAX updates apply to this pagination type.
	 *
	 * @param array $settings Widget settings.
	 * @return bool
	 */
	private function ajax_enabled( array $settings ) {
		$nav = isset( $settings['zenblog_nav'] ) ? $settings['zenblog_nav'] : '';

		/*
		 * "Current page query" inherits the main query's vars, and the main query
		 * for an archive simply does not exist inside a REST request — it would
		 * silently inherit an empty one and return site-wide latest posts instead
		 * of the archive. Real page links reload the archive properly, so that is
		 * what this source gets.
		 */
		if ( 'current' === ( isset( $settings['zenblog_source'] ) ? $settings['zenblog_source'] : '' ) ) {
			return false;
		}

		if ( 'load_more' === $nav || 'infinite' === $nav ) {
			return true;
		}

		return in_array( $nav, array( 'numbers', 'prev_next' ), true )
			&& 'yes' === ( isset( $settings['zenblog_nav_ajax'] ) ? $settings['zenblog_nav_ajax'] : '' );
	}

	/**
	 * ID of the Elementor document this widget was placed in.
	 *
	 * This is deliberately NOT get_queried_object_id(). On a Theme Builder
	 * template the widget's settings live in the template post, while the
	 * queried object is whichever post the template happens to be rendering —
	 * so looking the element up by the queried ID finds nothing and every AJAX
	 * page returns 404. Elementor switches the current document before it
	 * renders elements (Frontend::get_builder_content) precisely so a widget
	 * can ask which document it belongs to.
	 *
	 * @return int
	 */
	private function document_id() {
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) ) {
			$document = \Elementor\Plugin::$instance->documents->get_current();

			// get_main_id() rather than get_id(): inside a revision or autosave
			// the settings the front end reads belong to the parent.
			if ( $document && method_exists( $document, 'get_main_id' ) ) {
				$id = (int) $document->get_main_id();

				if ( $id ) {
					return $id;
				}
			}
		}

		// Rendered outside a document — a shortcode, or a test harness.
		$id = (int) get_the_ID();

		return $id ? $id : (int) get_queried_object_id();
	}

	/**
	 * URL of the page the visitor is actually on.
	 *
	 * Sent to the REST endpoint so paging links in a fetched response point back
	 * here. The endpoint re-validates it against the site's own host before use.
	 *
	 * @return string
	 */
	private function page_url() {
		$url = '';

		if ( is_singular() ) {
			$url = (string) get_permalink( get_queried_object_id() );
		}

		if ( '' === $url && ! is_admin() ) {
			// Archives, the blog index, search: whatever listing this is.
			$url = (string) get_pagenum_link( 1 );
		}

		if ( '' === $url ) {
			return '';
		}

		// This widget's own state is rebuilt from the request, never inherited
		// from the URL it was rendered at.
		return remove_query_arg(
			array(
				self::page_arg( 'zenblog-' . $this->get_id() ),
				$this->search_arg( 'zenblog-' . $this->get_id() ),
				$this->sort_arg( 'zenblog-' . $this->get_id() ),
			),
			$url
		);
	}

	/**
	 * Query-arg name carrying this widget's page number.
	 *
	 * Element-scoped so two Posts widgets on one page paginate independently.
	 *
	 * @param string $uid Widget DOM id.
	 * @return string
	 */
	private static function page_arg( $uid ) {
		return 'zbp_' . preg_replace( '/[^a-z0-9]/i', '', $uid );
	}

	/**
	 * Current page from the URL.
	 *
	 * @param array $settings Widget settings.
	 * @return int
	 */
	private function current_page( array $settings ) {
		unset( $settings );
		$key = self::page_arg( 'zenblog-' . $this->get_id() );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination state, no state change.
		$raw = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';

		return max( 1, (int) $raw );
	}
}
