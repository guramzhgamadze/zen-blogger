<?php
/**
 * Zen Blogger — Blog Carousel widget.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Utils;
use Elementor\Widget_Base;

/**
 * An accessible, query-driven post carousel.
 */
class Zen_Blogger_Carousel extends Widget_Base {

	use Zen_Blogger_Card_Trait;

	/**
	 * Widget name stored in _elementor_data. Never rename this.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'zen-blogger-carousel';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Blog Carousel', 'zen-blogger' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-posts-carousel';
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
		return array( 'blog', 'post', 'posts', 'carousel', 'slider', 'zen', 'news', 'article', 'loop' );
	}

	/**
	 * Stylesheet dependencies.
	 *
	 * ⚠️ Never read settings here. Elementor calls this on widget *types* as well
	 * as instances — `Widgets_Manager::enqueue_widgets_styles()` in the editor
	 * preview does exactly that — and on a type there is no data, so
	 * `get_settings_for_display()` fatals inside Elementor
	 * (`sanitize_settings(): Argument #1 must be of type array, null given`).
	 * The failure is a white screen on every page, and it happens *inside* the
	 * call, so guarding the return value does not help.
	 *
	 * The icon-font stylesheet is therefore enqueued at render time instead, only
	 * when a slide actually draws a library icon. See enqueue_icon_styles().
	 *
	 * @return string[]
	 */
	public function get_style_depends(): array {
		return Zen_Blogger_Assets::style_depends();
	}


	/**
	 * Script dependencies.
	 *
	 * @return string[]
	 */
	public function get_script_depends(): array {
		return Zen_Blogger_Assets::script_depends();
	}

	/**
	 * Skip the legacy .elementor-widget-container wrapper.
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * Output depends on live query results, so it must not be cached.
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
		$this->register_layout_controls();
		$this->register_query_controls();
		$this->register_carousel_controls();
		$this->register_accessibility_controls();
		$this->register_seo_controls();

		$this->register_card_style();
		$this->register_image_style();
		$this->register_badge_style();
		$this->register_terms_style();
		$this->register_title_style();
		$this->register_meta_style();
		$this->register_excerpt_style();
		$this->register_button_style();
		$this->register_arrows_style();
		$this->register_pagination_style();
		$this->register_playpause_style();
	}



	/**
	 * Content → Carousel.
	 *
	 * @return void
	 */
	private function register_carousel_controls() {
		$this->start_controls_section(
			'zenblog_section_carousel',
			array(
				'label' => esc_html__( 'Carousel', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'zenblog_slides_per_view',
			array(
				'label'          => esc_html__( 'Slides Per View', 'zen-blogger' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'auto' => esc_html__( 'Auto (card width)', 'zen-blogger' ),
					'1'    => '1',
					'2'    => '2',
					'3'    => '3',
					'4'    => '4',
					'5'    => '5',
					'6'    => '6',
					'7'    => '7',
					'8'    => '8',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_slides_per_group',
			array(
				'label'   => esc_html__( 'Slides to Scroll', 'zen-blogger' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 8,
				'default' => 1,
			)
		);

		$this->add_responsive_control(
			'zenblog_gap',
			array(
				'label'      => esc_html__( 'Gap Between Slides', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
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
			)
		);

		$this->add_control(
			'zenblog_rows',
			array(
				'label'       => esc_html__( 'Rows', 'zen-blogger' ),
				'description' => esc_html__( 'More than one row stacks slides into a grid before scrolling.', 'zen-blogger' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 4,
				'default'     => 1,
			)
		);

		$this->add_control(
			'zenblog_effect',
			array(
				'label'     => esc_html__( 'Effect', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'slide',
				'options'   => array(
					'slide'     => esc_html__( 'Slide', 'zen-blogger' ),
					'fade'      => esc_html__( 'Fade', 'zen-blogger' ),
					'coverflow' => esc_html__( 'Coverflow', 'zen-blogger' ),
					'cards'     => esc_html__( 'Cards', 'zen-blogger' ),
					'creative'  => esc_html__( 'Creative — stacked depth', 'zen-blogger' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_mode',
			array(
				'label'     => esc_html__( 'Motion Mode', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'standard',
				'options'   => array(
					'standard' => esc_html__( 'Standard slide steps', 'zen-blogger' ),
					'free'     => esc_html__( 'Free scroll', 'zen-blogger' ),
					'ticker'   => esc_html__( 'Ticker — continuous marquee', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_effect' => 'slide' ),
			)
		);

		$this->add_control(
			'zenblog_centered',
			array(
				'label'     => esc_html__( 'Center Active Slide', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => array( 'zenblog_effect' => array( 'slide', 'coverflow' ) ),
			)
		);

		$this->add_control(
			'zenblog_loop',
			array(
				'label'   => esc_html__( 'Infinite Loop', 'zen-blogger' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'zenblog_speed',
			array(
				'label'      => esc_html__( 'Transition Speed', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 100,
						'max'  => 5000,
						'step' => 50,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 600,
				),
			)
		);

		$this->add_control(
			'zenblog_autoplay',
			array(
				'label'       => esc_html__( 'Autoplay', 'zen-blogger' ),
				'description' => esc_html__( 'A play/pause button is added automatically — WCAG 2.2.2 requires one for content that moves on its own.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'zenblog_autoplay_delay',
			array(
				'label'      => esc_html__( 'Autoplay Delay', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 1000,
						'max'  => 15000,
						'step' => 250,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 5000,
				),
				'condition'  => array( 'zenblog_autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_pause_on_hover',
			array(
				'label'     => esc_html__( 'Pause on Hover', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'zenblog_autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_stop_on_interaction',
			array(
				'label'     => esc_html__( 'Stop After User Interaction', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'zenblog_autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_arrows',
			array(
				'label'     => esc_html__( 'Arrows', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_arrow_prev_icon',
			array(
				'label'       => esc_html__( 'Previous Icon', 'zen-blogger' ),
				'description' => esc_html__( 'Leave empty to use the built-in chevron, which needs no icon font.', 'zen-blogger' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'condition'   => array( 'zenblog_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_arrow_next_icon',
			array(
				'label'       => esc_html__( 'Next Icon', 'zen-blogger' ),
				'description' => esc_html__( 'Leave empty to use the built-in chevron, which needs no icon font.', 'zen-blogger' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'condition'   => array( 'zenblog_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_arrows_position',
			array(
				'label'     => esc_html__( 'Arrows Position', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'inside',
				'options'   => array(
					'inside'  => esc_html__( 'Over the slides', 'zen-blogger' ),
					'outside' => esc_html__( 'Outside the slides', 'zen-blogger' ),
					'top'     => esc_html__( 'Above, aligned right', 'zen-blogger' ),
					'bottom'  => esc_html__( 'Below, next to the dots', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_pagination',
			array(
				'label'     => esc_html__( 'Pagination', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'bullets',
				'options'   => array(
					''            => esc_html__( 'None', 'zen-blogger' ),
					'bullets'     => esc_html__( 'Dots', 'zen-blogger' ),
					'dynamic'     => esc_html__( 'Dots — dynamic', 'zen-blogger' ),
					'fraction'    => esc_html__( 'Fraction (2 / 9)', 'zen-blogger' ),
					'progressbar' => esc_html__( 'Progress bar', 'zen-blogger' ),
					'scrollbar'   => esc_html__( 'Draggable scrollbar', 'zen-blogger' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_keyboard',
			array(
				'label'     => esc_html__( 'Keyboard Control', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_mousewheel',
			array(
				'label' => esc_html__( 'Mousewheel Control', 'zen-blogger' ),
				'type'  => Controls_Manager::SWITCHER,
			)
		);

		$this->add_control(
			'zenblog_grab_cursor',
			array(
				'label'   => esc_html__( 'Grab Cursor', 'zen-blogger' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'zenblog_equal_height',
			array(
				'label'   => esc_html__( 'Equal Card Height', 'zen-blogger' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content → Accessibility.
	 *
	 * @return void
	 */

	/**
	 * Content → Accessibility.
	 *
	 * @return void
	 */
	private function register_accessibility_controls() {
		$this->start_controls_section(
			'zenblog_section_a11y',
			array(
				'label' => esc_html__( 'Accessibility', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'zenblog_region_label',
			array(
				'label'       => esc_html__( 'Carousel Label', 'zen-blogger' ),
				'description' => esc_html__( 'Announced by screen readers, e.g. "Latest articles". Give each carousel on a page a different label.', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Latest posts', 'zen-blogger' ),
			)
		);

		$this->add_control(
			'zenblog_playpause_visibility',
			array(
				'label'       => esc_html__( 'Play / Pause Button', 'zen-blogger' ),
				'description' => esc_html__( 'Only rendered when autoplay is on. It cannot be removed — a moving carousel without a stop control fails WCAG 2.2.2.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'always',
				'options'     => array(
					'always' => esc_html__( 'Always visible', 'zen-blogger' ),
					'hover'  => esc_html__( 'Visible on hover and keyboard focus', 'zen-blogger' ),
				),
				'condition'   => array( 'zenblog_autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_playpause_position',
			array(
				'label'     => esc_html__( 'Button Position', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'bottom-right',
				'options'   => array(
					'top-left'     => esc_html__( 'Top left', 'zen-blogger' ),
					'top-right'    => esc_html__( 'Top right', 'zen-blogger' ),
					'bottom-left'  => esc_html__( 'Bottom left', 'zen-blogger' ),
					'bottom-right' => esc_html__( 'Bottom right', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_reduced_motion',
			array(
				'label'       => esc_html__( 'Respect "Reduce Motion"', 'zen-blogger' ),
				'description' => esc_html__( 'Visitors whose system asks for reduced motion get no autoplay and instant transitions.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'zenblog_lazy_images',
			array(
				'label'       => esc_html__( 'Lazy-Load Off-Screen Slides', 'zen-blogger' ),
				'description' => esc_html__( 'Images in the first visible row load eagerly with high priority; the rest load lazily.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	/*
	 * -------------------------------------------------------------------
	 * Style sections
	 * -------------------------------------------------------------------
	 */










	/**
	 * Style → Arrows.
	 *
	 * @return void
	 */
	private function register_arrows_style() {
		$this->start_controls_section(
			'zenblog_style_arrows',
			array(
				'label'     => esc_html__( 'Arrows', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'zenblog_arrows' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'zenblog_arrow_size',
			array(
				'label'      => esc_html__( 'Icon Size', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 8,
						'max' => 60,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 18,
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__arrow' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_arrow_box',
			array(
				'label'       => esc_html__( 'Button Size', 'zen-blogger' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min' => 32,
						'max' => 96,
					),
				),
				'default'     => array(
					'unit' => 'px',
					'size' => 44,
				),
				'description' => esc_html__( 'Kept at 44px or more by default so the target meets WCAG 2.5.8.', 'zen-blogger' ),
				'selectors'   => array(
					'{{WRAPPER}} .zenblog__arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'zenblog_arrow_tabs' );

		$this->start_controls_tab(
			'zenblog_arrow_tab_normal',
			array( 'label' => esc_html__( 'Normal', 'zen-blogger' ) )
		);

		$this->add_control(
			'zenblog_arrow_color',
			array(
				'label'     => esc_html__( 'Icon Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__arrow' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_arrow_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__arrow',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'zenblog_arrow_border',
				'selector' => '{{WRAPPER}} .zenblog__arrow',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'zenblog_arrow_tab_hover',
			array( 'label' => esc_html__( 'Hover', 'zen-blogger' ) )
		);

		$this->add_control(
			'zenblog_arrow_color_hover',
			array(
				'label'     => esc_html__( 'Icon Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__arrow:hover, {{WRAPPER}} .zenblog__arrow:focus-visible' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_arrow_bg_hover',
			array(
				'label'     => esc_html__( 'Background', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__arrow:hover, {{WRAPPER}} .zenblog__arrow:focus-visible' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_arrow_border_color_hover',
			array(
				'label'     => esc_html__( 'Border Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__arrow:hover, {{WRAPPER}} .zenblog__arrow:focus-visible' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'zenblog_arrow_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__arrow' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_arrow_offset',
			array(
				'label'      => esc_html__( 'Horizontal Offset', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -80,
						'max' => 80,
					),
				),
				'condition'  => array( 'zenblog_arrows_position' => array( 'inside', 'outside' ) ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog' => '--zenblog-arrow-offset: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Pagination.
	 *
	 * @return void
	 */
	private function register_pagination_style() {
		$this->start_controls_section(
			'zenblog_style_pagination',
			array(
				'label'     => esc_html__( 'Pagination', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'zenblog_pagination!' => '' ),
			)
		);

		$this->add_responsive_control(
			'zenblog_dot_size',
			array(
				'label'      => esc_html__( 'Dot Size', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 4,
						'max' => 32,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 8,
				),
				'condition'  => array( 'zenblog_pagination' => array( 'bullets', 'dynamic' ) ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__pagination .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'zenblog_dot_color',
			array(
				'label'     => esc_html__( 'Dot Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'zenblog_pagination' => array( 'bullets', 'dynamic' ) ),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__pagination .swiper-pagination-bullet' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_dot_color_active',
			array(
				'label'     => esc_html__( 'Active Dot Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'zenblog_pagination' => array( 'bullets', 'dynamic' ) ),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__pagination .swiper-pagination-bullet-active' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_dot_gap',
			array(
				'label'      => esc_html__( 'Spacing', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'condition'  => array( 'zenblog_pagination' => array( 'bullets', 'dynamic' ) ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__pagination' => '--zenblog-dot-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'zenblog_fraction_typography',
				'selector'  => '{{WRAPPER}} .zenblog__pagination',
				'condition' => array( 'zenblog_pagination' => 'fraction' ),
			)
		);

		$this->add_control(
			'zenblog_fraction_color',
			array(
				'label'     => esc_html__( 'Text Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'zenblog_pagination' => 'fraction' ),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__pagination' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_bar_color',
			array(
				'label'     => esc_html__( 'Track Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'zenblog_pagination' => array( 'progressbar', 'scrollbar' ) ),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__pagination' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_bar_color_fill',
			array(
				'label'     => esc_html__( 'Fill Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'zenblog_pagination' => array( 'progressbar', 'scrollbar' ) ),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__pagination .swiper-pagination-progressbar-fill' => 'background: {{VALUE}};',
					'{{WRAPPER}} .zenblog__pagination .swiper-scrollbar-drag'              => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_pagination_offset',
			array(
				'label'      => esc_html__( 'Distance From Slides', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 20,
				),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__footer' => 'margin-block-start: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Play / Pause.
	 *
	 * @return void
	 */
	private function register_playpause_style() {
		$this->start_controls_section(
			'zenblog_style_playpause',
			array(
				'label'     => esc_html__( 'Play / Pause', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'zenblog_autoplay' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'zenblog_playpause_size',
			array(
				'label'      => esc_html__( 'Button Size', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 32,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 44,
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__playpause' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'zenblog_playpause_color',
			array(
				'label'     => esc_html__( 'Icon Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__playpause' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_playpause_bg',
			array(
				'label'     => esc_html__( 'Background', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__playpause' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_playpause_color_hover',
			array(
				'label'     => esc_html__( 'Icon Color — hover', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__playpause:hover, {{WRAPPER}} .zenblog__playpause:focus-visible' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_playpause_bg_hover',
			array(
				'label'     => esc_html__( 'Background — hover', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__playpause:hover, {{WRAPPER}} .zenblog__playpause:focus-visible' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_playpause_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__playpause' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/*
	 * -------------------------------------------------------------------
	 * Control option sources
	 * These hit the database, and register_controls() also runs on the front
	 * end. Option lists are therefore only built for editor-side requests —
	 * saved values resolve from _elementor_data regardless of the list.
	 * -------------------------------------------------------------------
	 */









	/*
	 * -------------------------------------------------------------------
	 * Render
	 * -------------------------------------------------------------------
	 */


	/**
	 * Render the carousel.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$query    = Zen_Blogger_Query::run( $settings, $this->get_id() );

		if ( ! $query->have_posts() ) {
			$this->render_empty_state( $settings );
			wp_reset_postdata();
			return;
		}

		$total = (int) $query->post_count;
		$skin  = ! empty( $settings['zenblog_skin'] ) ? sanitize_html_class( $settings['zenblog_skin'] ) : 'classic';

		$autoplay   = 'yes' === ( isset( $settings['zenblog_autoplay'] ) ? $settings['zenblog_autoplay'] : '' );
		$pagination = isset( $settings['zenblog_pagination'] ) ? $settings['zenblog_pagination'] : 'bullets';
		$arrows     = 'yes' === ( isset( $settings['zenblog_arrows'] ) ? $settings['zenblog_arrows'] : '' );
		$arrows_pos = ! empty( $settings['zenblog_arrows_position'] ) ? sanitize_html_class( $settings['zenblog_arrows_position'] ) : 'inside';
		$uid        = 'zenblog-' . $this->get_id();

		$classes = array(
			'zenblog',
			'zenblog--skin-' . $skin,
			'zenblog--arrows-' . ( $arrows ? $arrows_pos : 'none' ),
			'zenblog--pagination-' . ( $pagination ? sanitize_html_class( $pagination ) : 'none' ),
		);

		if ( 'side' === $skin && 'end' === ( isset( $settings['zenblog_side_media_position'] ) ? $settings['zenblog_side_media_position'] : 'start' ) ) {
			$classes[] = 'zenblog--media-end';
		}

		if ( 'yes' === ( isset( $settings['zenblog_equal_height'] ) ? $settings['zenblog_equal_height'] : 'yes' ) ) {
			$classes[] = 'zenblog--equal-height';
		}

		if ( 'yes' === ( isset( $settings['zenblog_reduced_motion'] ) ? $settings['zenblog_reduced_motion'] : 'yes' ) ) {
			$classes[] = 'zenblog--respect-motion';
		}

		if ( 'overlay' === $skin && 'yes' === ( isset( $settings['zenblog_scrim'] ) ? $settings['zenblog_scrim'] : 'yes' ) ) {
			$classes[] = 'zenblog--scrim';
		}

		if ( $autoplay && 'hover' === ( isset( $settings['zenblog_playpause_visibility'] ) ? $settings['zenblog_playpause_visibility'] : 'always' ) ) {
			$classes[] = 'zenblog--playpause-hover';
		}

		if ( $autoplay ) {
			$classes[] = 'zenblog--playpause-' . sanitize_html_class(
				! empty( $settings['zenblog_playpause_position'] ) ? $settings['zenblog_playpause_position'] : 'bottom-right'
			);
		}

		$this->add_render_attribute(
			'zenblog',
			array(
				'class'                => $classes,
				'id'                   => $uid,
				'data-zenblog-options' => wp_json_encode( $this->swiper_options( $settings, $total ) ),
			)
		);

		$label = isset( $settings['zenblog_region_label'] ) ? trim( $settings['zenblog_region_label'] ) : '';

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
			<div class="zenblog__viewport">
				<div class="zenblog__track swiper">
					<div class="zenblog__slides swiper-wrapper">
						<?php
						$index  = 0;
						$eager  = $this->eager_count( $settings );
						$shown  = array();
						$schema = array();

						while ( $query->have_posts() ) {
							$query->the_post();
							++$index;
							$schema[] = $this->schema_item( $settings, $index );
							$shown[]  = get_the_ID();
							$this->render_slide( $settings, $index, $total, $index <= $eager );
						}
						?>
					</div>
				</div>
			</div>

			<?php if ( $arrows ) : ?>
				<div class="zenblog__arrows">
					<button type="button" class="zenblog__arrow zenblog__arrow--prev" aria-controls="<?php echo esc_attr( $uid ); ?>">
						<?php $this->render_arrow_icon( $settings, 'prev' ); ?>
						<span class="zenblog__sr"><?php esc_html_e( 'Previous slide', 'zen-blogger' ); ?></span>
					</button>
					<button type="button" class="zenblog__arrow zenblog__arrow--next" aria-controls="<?php echo esc_attr( $uid ); ?>">
						<?php $this->render_arrow_icon( $settings, 'next' ); ?>
						<span class="zenblog__sr"><?php esc_html_e( 'Next slide', 'zen-blogger' ); ?></span>
					</button>
				</div>
			<?php endif; ?>

			<?php if ( $pagination ) : ?>
				<div class="zenblog__footer">
					<div class="zenblog__pagination"></div>
				</div>
			<?php endif; ?>

			<?php if ( $autoplay ) : ?>
				<?php
				/*
				 * Rotation control per the WAI-ARIA carousel pattern: one button whose
				 * accessible NAME changes between play and pause. Deliberately no
				 * aria-pressed — a changing name plus a pressed state is announced as
				 * "Start automatic slide show, toggle button, pressed", which contradicts
				 * itself. The visual state is carried by a class instead.
				 */
				?>
				<button type="button" class="zenblog__playpause" aria-controls="<?php echo esc_attr( $uid ); ?>">
					<span class="zenblog__playpause-icon" aria-hidden="true"></span>
					<span class="zenblog__playpause-label zenblog__sr"><?php esc_html_e( 'Stop automatic slide show', 'zen-blogger' ); ?></span>
				</button>
			<?php endif; ?>
		</div>
		<?php
		$this->render_schema( $settings, $schema );

		Zen_Blogger_Query::mark_rendered( $shown );
		wp_reset_postdata();
	}







	/**
	 * Render one slide. Must be called inside the loop.
	 *
	 * @param array $settings Widget settings.
	 * @param int   $index    1-based slide number.
	 * @param int   $total    Total slide count.
	 * @param bool  $eager    Whether this slide's image should load eagerly.
	 * @return void
	 */
	private function render_slide( array $settings, $index, $total, $eager ) {
		$skin       = isset( $settings['zenblog_skin'] ) ? $settings['zenblog_skin'] : 'classic';
		$show_image = 'minimal' !== $skin && 'yes' === ( isset( $settings['zenblog_show_image'] ) ? $settings['zenblog_show_image'] : '' );
		$terms_on   = 'yes' === ( isset( $settings['zenblog_show_terms'] ) ? $settings['zenblog_show_terms'] : '' );
		$terms_pos  = isset( $settings['zenblog_terms_position'] ) ? $settings['zenblog_terms_position'] : 'body';
		$stretched  = 'yes' === ( isset( $settings['zenblog_link_whole_card'] ) ? $settings['zenblog_link_whole_card'] : '' );

		if ( 'overlay' === $skin ) {
			$terms_pos = 'body';
		}

		$slide_label = sprintf(
			/* translators: 1: current slide number, 2: total slide count. */
			esc_attr__( '%1$d of %2$d', 'zen-blogger' ),
			$index,
			$total
		);
		?>
		<article class="zenblog__slide swiper-slide" role="group" aria-roledescription="<?php esc_attr_e( 'slide', 'zen-blogger' ); ?>" aria-label="<?php echo esc_attr( $slide_label ); ?>">
			<?php
			// A user-designed template replaces the built-in card entirely; the
			// slide wrapper and its carousel semantics stay, so keyboard and screen
			// reader behaviour is identical either way.
			if ( $this->render_loop_template( $settings, $index ) ) {
				echo '</article>';
				return;
			}
			?>
			<div class="zenblog__card<?php echo $stretched ? ' zenblog__card--stretched' : ''; ?>">
				<?php
				if ( $show_image ) {
					$this->render_media( $settings, $eager, $terms_on && 'media' === $terms_pos, $index );
				}
				?>
				<div class="zenblog__body">
					<?php $this->render_card_body( $settings, $stretched, $index ); ?>
				</div>
			</div>
		</article>
		<?php
	}










	/*
	 * -------------------------------------------------------------------
	 * Helpers
	 * -------------------------------------------------------------------
	 */


	/**
	 * Draw an arrow glyph.
	 *
	 * Defaults to an inline SVG chevron rather than an icon-font glyph: it needs no
	 * webfont, cannot render as an empty circle if that font fails to load, and
	 * inherits currentColor so the existing colour controls still drive it. A user
	 * who picks an icon from the library gets that instead.
	 *
	 * The SVG is echoed as a string literal, never passed through wp_kses(), which
	 * lowercases attribute names and would turn viewBox into an ignored viewbox.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $which    'prev' or 'next'.
	 * @return void
	 */
	private function render_arrow_icon( array $settings, $which ) {
		$key = 'zenblog_arrow_' . $which . '_icon';

		if ( ! empty( $settings[ $key ]['value'] ) ) {
			$this->enqueue_icon_styles();
			Icons_Manager::render_icon( $settings[ $key ], array( 'aria-hidden' => 'true' ) );
			return;
		}

		if ( 'prev' === $which ) {
			echo '<svg class="zenblog__chevron" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M15 4 7 12l8 8"></path></svg>';
			return;
		}

		echo '<svg class="zenblog__chevron" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 4l8 8-8 8"></path></svg>';
	}



	/**
	 * Build the Swiper configuration handed to the front-end script.
	 *
	 * @param array $settings Widget settings.
	 * @param int   $total    Number of slides.
	 * @return array
	 */
	private function swiper_options( array $settings, $total ) {
		$breakpoints = $this->breakpoint_values();
		$effect      = isset( $settings['zenblog_effect'] ) ? $settings['zenblog_effect'] : 'slide';
		$mode        = ( 'slide' === $effect && isset( $settings['zenblog_mode'] ) ) ? $settings['zenblog_mode'] : 'standard';
		$rows        = isset( $settings['zenblog_rows'] ) ? max( 1, (int) $settings['zenblog_rows'] ) : 1;
		$per_view    = $this->responsive_value( $settings, 'zenblog_slides_per_view', '3' );
		$per_group   = $this->responsive_value( $settings, 'zenblog_slides_per_group', 1 );
		$gap         = $this->responsive_slider( $settings, 'zenblog_gap', 24 );

		$options = array(
			'effect'       => $effect,
			'mode'         => $mode,
			'rows'         => $rows,
			'loop'         => 'yes' === ( isset( $settings['zenblog_loop'] ) ? $settings['zenblog_loop'] : '' ),
			'speed'        => isset( $settings['zenblog_speed']['size'] ) ? (int) $settings['zenblog_speed']['size'] : 600,
			'centered'     => 'yes' === ( isset( $settings['zenblog_centered'] ) ? $settings['zenblog_centered'] : '' ),
			'keyboard'     => 'yes' === ( isset( $settings['zenblog_keyboard'] ) ? $settings['zenblog_keyboard'] : '' ),
			'mousewheel'   => 'yes' === ( isset( $settings['zenblog_mousewheel'] ) ? $settings['zenblog_mousewheel'] : '' ),
			'grabCursor'   => 'yes' === ( isset( $settings['zenblog_grab_cursor'] ) ? $settings['zenblog_grab_cursor'] : '' ),
			'pagination'   => isset( $settings['zenblog_pagination'] ) ? $settings['zenblog_pagination'] : '',
			'arrows'       => 'yes' === ( isset( $settings['zenblog_arrows'] ) ? $settings['zenblog_arrows'] : '' ),
			'rtl'          => is_rtl(),
			'total'        => (int) $total,
			'reduceMotion' => 'yes' === ( isset( $settings['zenblog_reduced_motion'] ) ? $settings['zenblog_reduced_motion'] : 'yes' ),
			'autoplay'     => false,
			'breakpoints'  => array(
				0                       => array(
					'slidesPerView'  => $per_view['mobile'],
					'slidesPerGroup' => $per_group['mobile'],
					'spaceBetween'   => $gap['mobile'],
				),
				$breakpoints['tablet']  => array(
					'slidesPerView'  => $per_view['tablet'],
					'slidesPerGroup' => $per_group['tablet'],
					'spaceBetween'   => $gap['tablet'],
				),
				$breakpoints['desktop'] => array(
					'slidesPerView'  => $per_view['desktop'],
					'slidesPerGroup' => $per_group['desktop'],
					'spaceBetween'   => $gap['desktop'],
				),
			),
		);

		if ( 'yes' === ( isset( $settings['zenblog_autoplay'] ) ? $settings['zenblog_autoplay'] : '' ) ) {
			$options['autoplay'] = array(
				'delay'                => isset( $settings['zenblog_autoplay_delay']['size'] ) ? (int) $settings['zenblog_autoplay_delay']['size'] : 5000,
				'pauseOnMouseEnter'    => 'yes' === ( isset( $settings['zenblog_pause_on_hover'] ) ? $settings['zenblog_pause_on_hover'] : '' ),
				'disableOnInteraction' => 'yes' === ( isset( $settings['zenblog_stop_on_interaction'] ) ? $settings['zenblog_stop_on_interaction'] : '' ),
			);
		}

		return $options;
	}
}
