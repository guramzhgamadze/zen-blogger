<?php
/**
 * Shared card, query and style behaviour for Zen Blogger post widgets.
 *
 * The Blog Carousel and the Posts widget draw the same card, offer the same query
 * builder and the same style controls; only the container around the cards differs.
 * Keeping that in one place means a fix to escaping, schema or markup lands in both
 * widgets at once instead of drifting between two copies.
 *
 * A trait rather than a base class: Elementor widgets already extend Widget_Base,
 * and a trait composes into that without a second inheritance step.
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

trait Zen_Blogger_Card_Trait {

	/**
	 * Enqueue Elementor's icon-font stylesheet, once, at render time.
	 *
	 * Called only from the paths that actually output a library icon, so the
	 * default inline-SVG chevrons never pull the font in. Styles enqueued during
	 * the body are printed by WordPress via wp_print_late_styles() in the footer.
	 *
	 * @return void
	 */
	private function enqueue_icon_styles() {
		if ( wp_style_is( 'elementor-icons', 'registered' ) && ! wp_style_is( 'elementor-icons', 'enqueued' ) ) {
			wp_enqueue_style( 'elementor-icons' );
		}
	}

	/**
	 * Content → Layout.
	 *
	 * @return void
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'zenblog_section_layout',
			array(
				'label' => esc_html__( 'Layout', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'zenblog_skin',
			array(
				'label'   => esc_html__( 'Skin', 'zen-blogger' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'classic',
				'options' => array(
					'classic'   => esc_html__( 'Classic — image above text', 'zen-blogger' ),
					'overlay'   => esc_html__( 'Overlay — text on the image', 'zen-blogger' ),
					'overlap'   => esc_html__( 'Overlap — card lifted over the image', 'zen-blogger' ),
					'side'      => esc_html__( 'Side by side', 'zen-blogger' ),
					'minimal'   => esc_html__( 'Minimal — no image', 'zen-blogger' ),
					'editorial' => esc_html__( 'Editorial — oversized numbers', 'zen-blogger' ),
					'template'  => esc_html__( 'Elementor template — design your own card', 'zen-blogger' ),
				),
			)
		);

		$this->add_control(
			'zenblog_template_id',
			array(
				'label'       => esc_html__( 'Card Template', 'zen-blogger' ),
				'description' => esc_html__( 'Any saved Elementor template. It is rendered once per post, with the post in context, so Dynamic Tags inside it resolve to that post. No Elementor Pro required.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => '',
				'options'     => $this->template_options(),
				'condition'   => array( 'zenblog_skin' => 'template' ),
			)
		);

		$this->add_control(
			'zenblog_template_hint',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Build the card under Templates → Saved Templates, using Dynamic Tags for the title, image, excerpt and link. The Style controls below do not apply to a template card — the template carries its own design.', 'zen-blogger' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array( 'zenblog_skin' => 'template' ),
			)
		);

		$this->add_control(
			'zenblog_side_media_position',
			array(
				'label'     => esc_html__( 'Image Position', 'zen-blogger' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'start',
				'options'   => array(
					'start' => array(
						'title' => esc_html__( 'Start', 'zen-blogger' ),
						'icon'  => 'eicon-h-align-left',
					),
					'end'   => array(
						'title' => esc_html__( 'End', 'zen-blogger' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'condition' => array( 'zenblog_skin' => 'side' ),
			)
		);

		$this->add_control(
			'zenblog_show_image',
			array(
				'label'        => esc_html__( 'Featured Image', 'zen-blogger' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'condition'    => array( 'zenblog_skin!' => 'minimal' ),
				'return_value' => 'yes',
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'zenblog_image',
				'default'   => 'large',
				'separator' => 'none',
				'condition' => array(
					'zenblog_show_image' => 'yes',
					'zenblog_skin!'      => 'minimal',
				),
			)
		);

		$this->add_control(
			'zenblog_image_fallback',
			array(
				'label'       => esc_html__( 'Fallback Image', 'zen-blogger' ),
				'description' => esc_html__( 'Shown only for posts with no featured image. Leave empty to render no image at all.', 'zen-blogger' ),
				'type'        => Controls_Manager::MEDIA,
				'condition'   => array(
					'zenblog_show_image' => 'yes',
					'zenblog_skin!'      => 'minimal',
				),
			)
		);

		$this->add_control(
			'zenblog_scrim',
			array(
				'label'       => esc_html__( 'Readability Scrim', 'zen-blogger' ),
				'description' => esc_html__( 'Darkens the bottom of the image so text stays legible over a light photo. Switch it off to take full control with the Content Background style control.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'condition'   => array( 'zenblog_skin' => 'overlay' ),
			)
		);

		$this->add_control(
			'zenblog_show_terms',
			array(
				'label'     => esc_html__( 'Categories / Terms', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_terms_taxonomy',
			array(
				'label'     => esc_html__( 'Taxonomy', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'category',
				'options'   => $this->taxonomy_options(),
				'condition' => array( 'zenblog_show_terms' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_terms_max',
			array(
				'label'     => esc_html__( 'Max Terms', 'zen-blogger' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 10,
				'default'   => 1,
				'condition' => array( 'zenblog_show_terms' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_terms_position',
			array(
				'label'     => esc_html__( 'Terms Position', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'body',
				'options'   => array(
					'body'  => esc_html__( 'Above the title', 'zen-blogger' ),
					'media' => esc_html__( 'On the image', 'zen-blogger' ),
				),
				'condition' => array(
					'zenblog_show_terms' => 'yes',
					'zenblog_show_image' => 'yes',
					'zenblog_skin!'      => array( 'minimal', 'overlay' ),
				),
			)
		);

		$this->add_control(
			'zenblog_show_title',
			array(
				'label'     => esc_html__( 'Title', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_title_tag',
			array(
				'label'     => esc_html__( 'Title HTML Tag', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'p'    => 'p',
					'span' => 'span',
				),
				'condition' => array( 'zenblog_show_title' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_title_lines',
			array(
				'label'       => esc_html__( 'Limit Title To', 'zen-blogger' ),
				'description' => esc_html__( 'Clamp the title to a number of lines so cards stay the same height. "No limit" emits no CSS at all.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '2',
				'options'     => $this->line_clamp_options(),
				'condition'   => array( 'zenblog_show_title' => 'yes' ),
				'selectors'   => array(
					'{{WRAPPER}} .zenblog__title' => 'display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: {{VALUE}}; line-clamp: {{VALUE}}; overflow: hidden;',
				),
			)
		);

		$this->add_control(
			'zenblog_show_meta',
			array(
				'label'     => esc_html__( 'Meta', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_meta_position',
			array(
				'label'     => esc_html__( 'Meta Position', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'title',
				'options'   => array(
					'title'  => esc_html__( 'Under the title', 'zen-blogger' ),
					'bottom' => esc_html__( 'At the bottom of the card', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_show_meta' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_meta_divider',
			array(
				'label'     => esc_html__( 'Divider Above Meta', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'zenblog_show_meta'     => 'yes',
					'zenblog_meta_position' => 'bottom',
				),
			)
		);

		$this->add_control(
			'zenblog_meta_items',
			array(
				'label'       => esc_html__( 'Meta Items', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => array( 'author', 'date' ),
				'options'     => array(
					'author'       => esc_html__( 'Author', 'zen-blogger' ),
					'date'         => esc_html__( 'Date', 'zen-blogger' ),
					'modified'     => esc_html__( 'Last updated', 'zen-blogger' ),
					'comments'     => esc_html__( 'Comment count', 'zen-blogger' ),
					'reading_time' => esc_html__( 'Reading time', 'zen-blogger' ),
				),
				'condition'   => array( 'zenblog_show_meta' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_show_avatar',
			array(
				'label'     => esc_html__( 'Author Avatar', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array(
					'zenblog_show_meta'  => 'yes',
					'zenblog_meta_items' => 'author',
				),
			)
		);

		$this->add_control(
			'zenblog_date_style',
			array(
				'label'     => esc_html__( 'Date Format', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => array(
					'default' => esc_html__( 'Site default', 'zen-blogger' ),
					'human'   => esc_html__( 'Relative (2 days ago)', 'zen-blogger' ),
					'custom'  => esc_html__( 'Custom', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_show_meta' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_date_format',
			array(
				'label'       => esc_html__( 'Custom Format', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'M j, Y',
				'placeholder' => 'M j, Y',
				'condition'   => array(
					'zenblog_show_meta'  => 'yes',
					'zenblog_date_style' => 'custom',
				),
			)
		);

		$this->add_control(
			'zenblog_reading_time_text',
			array(
				'label'       => esc_html__( 'Reading Time Text', 'zen-blogger' ),
				/* translators: %s is a literal placeholder the user types, not a variable. */
				'description' => esc_html__( 'Use %s for the number of minutes.', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				/* translators: %s: number of minutes. */
				'placeholder' => esc_html__( '%s min read', 'zen-blogger' ),
				'condition'   => array(
					'zenblog_show_meta'  => 'yes',
					'zenblog_meta_items' => 'reading_time',
				),
			)
		);

		$this->add_control(
			'zenblog_comments_text',
			array(
				'label'       => esc_html__( 'Comment Count Text', 'zen-blogger' ),
				/* translators: %s is a literal placeholder the user types, not a variable. */
				'description' => esc_html__( 'Use %s for the number of comments.', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				/* translators: %s: number of comments. */
				'placeholder' => esc_html__( '%s comments', 'zen-blogger' ),
				'condition'   => array(
					'zenblog_show_meta'  => 'yes',
					'zenblog_meta_items' => 'comments',
				),
			)
		);

		$this->add_control(
			'zenblog_meta_separator',
			array(
				'label'     => esc_html__( 'Separator', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '·',
				'condition' => array( 'zenblog_show_meta' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_show_excerpt',
			array(
				'label'     => esc_html__( 'Excerpt', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_excerpt_source',
			array(
				'label'       => esc_html__( 'Excerpt From', 'zen-blogger' ),
				'description' => esc_html__( 'Reading only paragraphs keeps headings, list items, captions and table cells out of the card text.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'       => esc_html__( 'Manual excerpt, else paragraphs', 'zen-blogger' ),
					'paragraphs' => esc_html__( 'Paragraphs only — ignore the manual excerpt', 'zen-blogger' ),
					'content'    => esc_html__( 'Manual excerpt, else all content', 'zen-blogger' ),
				),
				'condition'   => array( 'zenblog_show_excerpt' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_excerpt_length',
			array(
				'label'     => esc_html__( 'Excerpt Length (words)', 'zen-blogger' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 120,
				'default'   => 20,
				'condition' => array( 'zenblog_show_excerpt' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_excerpt_more',
			array(
				'label'     => esc_html__( 'Excerpt Ellipsis', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '…',
				'condition' => array( 'zenblog_show_excerpt' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_excerpt_lines',
			array(
				'label'     => esc_html__( 'Limit Excerpt To', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '3',
				'options'   => $this->line_clamp_options(),
				'condition' => array( 'zenblog_show_excerpt' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__excerpt' => 'display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: {{VALUE}}; line-clamp: {{VALUE}}; overflow: hidden;',
				),
			)
		);

		$this->add_control(
			'zenblog_show_button',
			array(
				'label'     => esc_html__( 'Read More Button', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_button_text',
			array(
				'label'       => esc_html__( 'Button Text', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Read more', 'zen-blogger' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'zenblog_show_button' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_button_icon',
			array(
				'label'            => esc_html__( 'Button Icon', 'zen-blogger' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'button_icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'condition'        => array( 'zenblog_show_button' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_show_badge',
			array(
				'label'     => esc_html__( 'Sticky Badge', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_badge_text',
			array(
				'label'     => esc_html__( 'Badge Text', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Featured', 'zen-blogger' ),
				'condition' => array( 'zenblog_show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_link_whole_card',
			array(
				'label'       => esc_html__( 'Make the Whole Card Clickable', 'zen-blogger' ),
				'description' => esc_html__( 'Uses a stretched title link, so screen readers still announce a single, properly named link.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'zenblog_link_target',
			array(
				'label'   => esc_html__( 'Open Links In', 'zen-blogger' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '_self',
				'options' => array(
					'_self'  => esc_html__( 'Same tab', 'zen-blogger' ),
					'_blank' => esc_html__( 'New tab', 'zen-blogger' ),
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_content_align',
			array(
				'label'     => esc_html__( 'Content Alignment', 'zen-blogger' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'start'  => array(
						'title' => esc_html__( 'Left', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-center',
					),
					'end'    => array(
						'title' => esc_html__( 'Right', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .zenblog__body' => 'text-align: {{VALUE}}; align-items: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content → Query.
	 *
	 * @return void
	 */
	private function register_query_controls() {
		$this->start_controls_section(
			'zenblog_section_query',
			array(
				'label' => esc_html__( 'Query', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'zenblog_source',
			array(
				'label'       => esc_html__( 'Source', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'latest',
				'options'     => array(
					'latest'  => esc_html__( 'Latest posts', 'zen-blogger' ),
					'related' => esc_html__( 'Related to the current post', 'zen-blogger' ),
					'manual'  => esc_html__( 'Hand-picked', 'zen-blogger' ),
					'current' => esc_html__( 'Current Query', 'zen-blogger' ),
				),
				'description' => esc_html__( 'Current Query inherits whatever the page itself is already showing — an archive, a search result, the blog index — so the widget follows the template it is placed in rather than defining a query of its own.', 'zen-blogger' ),
			)
		);

		$this->add_control(
			'zenblog_post_type',
			array(
				'label'     => esc_html__( 'Post Type', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'post',
				'options'   => Zen_Blogger_Query::post_types(),
				'condition' => array( 'zenblog_source!' => 'current' ),
			)
		);

		$this->add_control(
			'zenblog_manual_ids',
			array(
				'label'       => esc_html__( 'Choose Posts', 'zen-blogger' ),
				'description' => esc_html__( 'Order here is the order shown.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->post_options(),
				'condition'   => array( 'zenblog_source' => 'manual' ),
			)
		);

		$this->add_control(
			'zenblog_related_taxonomy',
			array(
				'label'     => esc_html__( 'Relate By', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'category',
				'options'   => $this->taxonomy_options(),
				'condition' => array( 'zenblog_source' => 'related' ),
			)
		);

		// Per-taxonomy include / exclude, conditioned on the selected post type.
		foreach ( $this->all_taxonomies() as $taxonomy => $data ) {
			$this->add_control(
				'zenblog_tax_' . $taxonomy,
				array(

					/* translators: %s: taxonomy label, e.g. "Categories". */
					'label'       => sprintf( esc_html__( '%s — include', 'zen-blogger' ), $data['label'] ),
					'type'        => Controls_Manager::SELECT2,
					'multiple'    => true,
					'label_block' => true,
					'options'     => $this->term_options( $taxonomy ),
					'condition'   => array(
						'zenblog_source'    => array( 'latest', 'related' ),
						'zenblog_post_type' => $data['post_types'],
					),
				)
			);

			$this->add_control(
				'zenblog_tax_exclude_' . $taxonomy,
				array(

					/* translators: %s: taxonomy label, e.g. "Categories". */
					'label'       => sprintf( esc_html__( '%s — exclude', 'zen-blogger' ), $data['label'] ),
					'type'        => Controls_Manager::SELECT2,
					'multiple'    => true,
					'label_block' => true,
					'options'     => $this->term_options( $taxonomy ),
					'condition'   => array(
						'zenblog_source'    => array( 'latest', 'related' ),
						'zenblog_post_type' => $data['post_types'],
					),
				)
			);
		}

		$this->add_control(
			'zenblog_tax_relation',
			array(
				'label'     => esc_html__( 'Combine Taxonomy Filters With', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'AND',
				'options'   => array(
					'AND' => esc_html__( 'AND — must match all', 'zen-blogger' ),
					'OR'  => esc_html__( 'OR — may match any', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_source' => array( 'latest', 'related' ) ),
			)
		);

		$this->add_control(
			'zenblog_authors',
			array(
				'label'       => esc_html__( 'Authors — include', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->author_options(),
				'condition'   => array( 'zenblog_source' => array( 'latest', 'related' ) ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'zenblog_authors_exclude',
			array(
				'label'       => esc_html__( 'Authors — exclude', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->author_options(),
				'condition'   => array( 'zenblog_source' => array( 'latest', 'related' ) ),
			)
		);

		$this->add_control(
			'zenblog_date_range',
			array(
				'label'     => esc_html__( 'Published', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'any',
				'options'   => array(
					'any'     => esc_html__( 'Any time', 'zen-blogger' ),
					'day'     => esc_html__( 'Past 24 hours', 'zen-blogger' ),
					'week'    => esc_html__( 'Past week', 'zen-blogger' ),
					'month'   => esc_html__( 'Past month', 'zen-blogger' ),
					'quarter' => esc_html__( 'Past 3 months', 'zen-blogger' ),
					'year'    => esc_html__( 'Past year', 'zen-blogger' ),
					'custom'  => esc_html__( 'Custom range', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_source' => array( 'latest', 'related' ) ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_date_after',
			array(
				'label'          => esc_html__( 'After', 'zen-blogger' ),
				'type'           => Controls_Manager::DATE_TIME,
				'picker_options' => array( 'enableTime' => false ),
				'condition'      => array(
					'zenblog_source'     => array( 'latest', 'related' ),
					'zenblog_date_range' => 'custom',
				),
			)
		);

		$this->add_control(
			'zenblog_date_before',
			array(
				'label'          => esc_html__( 'Before', 'zen-blogger' ),
				'type'           => Controls_Manager::DATE_TIME,
				'picker_options' => array( 'enableTime' => false ),
				'condition'      => array(
					'zenblog_source'     => array( 'latest', 'related' ),
					'zenblog_date_range' => 'custom',
				),
			)
		);

		$this->add_control(
			'zenblog_orderby',
			array(
				'label'     => esc_html__( 'Order By', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'date',
				'options'   => array(
					'date'           => esc_html__( 'Published date', 'zen-blogger' ),
					'modified'       => esc_html__( 'Last modified', 'zen-blogger' ),
					'title'          => esc_html__( 'Title', 'zen-blogger' ),
					'menu_order'     => esc_html__( 'Menu order', 'zen-blogger' ),
					'comment_count'  => esc_html__( 'Comment count', 'zen-blogger' ),
					'rand'           => esc_html__( 'Random', 'zen-blogger' ),
					'meta_value'     => esc_html__( 'Custom field (text)', 'zen-blogger' ),
					'meta_value_num' => esc_html__( 'Custom field (number)', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_source!' => array( 'manual', 'current' ) ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_meta_key',
			array(
				'label'       => esc_html__( 'Custom Field Key', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'condition'   => array(
					'zenblog_source!' => array( 'manual', 'current' ),
					'zenblog_orderby' => array( 'meta_value', 'meta_value_num' ),
				),
			)
		);

		$this->add_control(
			'zenblog_order',
			array(
				'label'     => esc_html__( 'Order', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'DESC',
				'options'   => array(
					'DESC' => esc_html__( 'Descending', 'zen-blogger' ),
					'ASC'  => esc_html__( 'Ascending', 'zen-blogger' ),
				),
				'condition' => array(
					'zenblog_source!'  => array( 'manual', 'current' ),
					'zenblog_orderby!' => 'rand',
				),
			)
		);

		$this->add_control(
			'zenblog_per_page',
			array(
				'label'     => esc_html__( 'Number of Posts', 'zen-blogger' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 100,
				'default'   => 9,
				'condition' => array( 'zenblog_source!' => 'manual' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_offset',
			array(
				'label'       => esc_html__( 'Offset', 'zen-blogger' ),
				'description' => esc_html__( 'Skip this many posts before the first slide.', 'zen-blogger' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 500,
				'default'     => 0,
				'condition'   => array( 'zenblog_source' => array( 'latest', 'related' ) ),
			)
		);

		$this->add_control(
			'zenblog_sticky',
			array(
				'label'     => esc_html__( 'Sticky Posts', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ignore',
				'options'   => array(
					'ignore'  => esc_html__( 'Treat like any other post', 'zen-blogger' ),
					'first'   => esc_html__( 'Show first', 'zen-blogger' ),
					'only'    => esc_html__( 'Only sticky posts', 'zen-blogger' ),
					'exclude' => esc_html__( 'Exclude sticky posts', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_source' => array( 'latest', 'related' ) ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_exclude_ids',
			array(
				'label'       => esc_html__( 'Exclude Posts', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->post_options(),
				'condition'   => array( 'zenblog_source' => array( 'latest', 'related' ) ),
			)
		);

		$this->add_control(
			'zenblog_exclude_current',
			array(
				'label'     => esc_html__( 'Exclude the Current Post', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'zenblog_source' => array( 'latest', 'related' ) ),
			)
		);

		$this->add_control(
			'zenblog_avoid_duplicates',
			array(
				'label'       => esc_html__( 'Avoid Duplicates on This Page', 'zen-blogger' ),
				'description' => esc_html__( 'Skips posts already shown by another Zen Blogger carousel further up the page.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'condition'   => array( 'zenblog_source' => array( 'latest', 'related' ) ),
			)
		);

		$this->add_control(
			'zenblog_require_thumbnail',
			array(
				'label'       => esc_html__( 'Only Posts With a Featured Image', 'zen-blogger' ),
				'description' => esc_html__( 'Adds a meta query — slightly heavier on very large sites.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'condition'   => array( 'zenblog_source' => array( 'latest', 'related' ) ),
			)
		);

		$this->add_control(
			'zenblog_empty_message',
			array(
				'label'       => esc_html__( 'Nothing-Found Message', 'zen-blogger' ),
				'description' => esc_html__( 'Leave empty to render nothing at all when the query returns no posts.', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'No posts found.', 'zen-blogger' ),
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content → SEO & AI.
	 *
	 * @return void
	 */
	private function register_seo_controls() {
		$this->start_controls_section(
			'zenblog_section_seo',
			array(
				'label' => esc_html__( 'SEO & AI', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'zenblog_schema_notice',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Every slide is rendered server-side as a real link inside an <article>, so search engines and AI crawlers read the whole list from the page source without running any JavaScript.', 'zen-blogger' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'zenblog_schema',
			array(
				'label'       => esc_html__( 'Structured Data (JSON-LD)', 'zen-blogger' ),
				'description' => esc_html__( 'Describes the carousel as a schema.org ItemList. Set to None if your SEO plugin already outputs an ItemList for this section.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'summary',
				'options'     => array(
					''        => esc_html__( 'None', 'zen-blogger' ),
					'summary' => esc_html__( 'ItemList — positions and URLs', 'zen-blogger' ),
					'full'    => esc_html__( 'ItemList — full post details', 'zen-blogger' ),
				),
			)
		);

		$this->add_control(
			'zenblog_schema_type',
			array(
				'label'     => esc_html__( 'Item Type', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'BlogPosting',
				'options'   => array(
					'BlogPosting'  => 'BlogPosting',
					'Article'      => 'Article',
					'NewsArticle'  => 'NewsArticle',
					'CreativeWork' => 'CreativeWork',
					'Product'      => 'Product',
				),
				'condition' => array( 'zenblog_schema' => 'full' ),
			)
		);

		$this->add_control(
			'zenblog_schema_name',
			array(
				'label'       => esc_html__( 'List Name', 'zen-blogger' ),
				'description' => esc_html__( 'Names the list for search engines and AI answers, e.g. "Latest articles".', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'condition'   => array( 'zenblog_schema!' => '' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Card.
	 *
	 * @return void
	 */
	private function register_card_style() {
		$this->start_controls_section(
			'zenblog_style_card',
			array(
				'label'     => esc_html__( 'Card', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'zenblog_skin!' => 'template' ),
			)
		);

		$this->add_responsive_control(
			'zenblog_card_padding',
			array(
				'label'      => esc_html__( 'Content Padding', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_card_gap',
			array(
				'label'      => esc_html__( 'Gap Between Elements', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__body' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_body_bg',
				'label'    => esc_html__( 'Content Background', 'zen-blogger' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__body',
			)
		);

		$this->add_responsive_control(
			'zenblog_card_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'zenblog_card_tabs' );

		$this->start_controls_tab(
			'zenblog_card_tab_normal',
			array( 'label' => esc_html__( 'Normal', 'zen-blogger' ) )
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_card_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__card',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'zenblog_card_border',
				'selector' => '{{WRAPPER}} .zenblog__card',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'zenblog_card_shadow',
				'selector' => '{{WRAPPER}} .zenblog__card',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'zenblog_card_tab_hover',
			array( 'label' => esc_html__( 'Hover', 'zen-blogger' ) )
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_card_bg_hover',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__card:hover, {{WRAPPER}} .zenblog__card:focus-within',
			)
		);

		$this->add_control(
			'zenblog_card_border_color_hover',
			array(
				'label'     => esc_html__( 'Border Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__card:hover, {{WRAPPER}} .zenblog__card:focus-within' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'zenblog_card_shadow_hover',
				'selector' => '{{WRAPPER}} .zenblog__card:hover, {{WRAPPER}} .zenblog__card:focus-within',
			)
		);

		$this->add_responsive_control(
			'zenblog_card_lift',
			array(
				'label'      => esc_html__( 'Lift', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -30,
						'max' => 30,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__card:hover, {{WRAPPER}} .zenblog__card:focus-within' => 'transform: translateY({{SIZE}}{{UNIT}});',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control(
			'zenblog_card_transition',
			array(
				'label'      => esc_html__( 'Transition Duration', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 's' ),
				'range'      => array(
					's' => array(
						'min'  => 0,
						'max'  => 2,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'unit' => 's',
					'size' => 0.3,
				),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__card' => 'transition-duration: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Image.
	 *
	 * @return void
	 */
	private function register_image_style() {
		$this->start_controls_section(
			'zenblog_style_image',
			array(
				'label'     => esc_html__( 'Image', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					// Both skins hide this section; PHP keeps only the last duplicate
					// key, so they must be one array rather than two entries.
					'zenblog_skin!' => array( 'template', 'minimal' ),
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_image_ratio',
			array(
				'label'       => esc_html__( 'Aspect Ratio', 'zen-blogger' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array(
					'px' => array(
						'min'  => 0.4,
						'max'  => 2.5,
						'step' => 0.05,
					),
				),
				'default'     => array( 'size' => 1.6 ),
				'description' => esc_html__( 'Width divided by height. Reserving the box up front is what keeps the carousel from shifting the layout while images load.', 'zen-blogger' ),
				'selectors'   => array(
					'{{WRAPPER}} .zenblog__media' => 'aspect-ratio: {{SIZE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_image_width',
			array(
				'label'      => esc_html__( 'Image Width', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min' => 20,
						'max' => 70,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 40,
				),
				'condition'  => array( 'zenblog_skin' => 'side' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__media' => 'flex-basis: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'zenblog_image_fit',
			array(
				'label'     => esc_html__( 'Fit', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => esc_html__( 'Cover', 'zen-blogger' ),
					'contain' => esc_html__( 'Contain', 'zen-blogger' ),
					'fill'    => esc_html__( 'Fill', 'zen-blogger' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__media img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_image_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__media' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'zenblog_image_tabs' );

		$this->start_controls_tab(
			'zenblog_image_tab_normal',
			array( 'label' => esc_html__( 'Normal', 'zen-blogger' ) )
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'zenblog_image_filters',
				'selector' => '{{WRAPPER}} .zenblog__media img',
			)
		);

		$this->add_control(
			'zenblog_overlay_color',
			array(
				'label'     => esc_html__( 'Overlay', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__media::after' => 'background: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'zenblog_image_tab_hover',
			array( 'label' => esc_html__( 'Hover', 'zen-blogger' ) )
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'zenblog_image_filters_hover',
				'selector' => '{{WRAPPER}} .zenblog__card:hover .zenblog__media img, {{WRAPPER}} .zenblog__card:focus-within .zenblog__media img',
			)
		);

		$this->add_control(
			'zenblog_overlay_color_hover',
			array(
				'label'     => esc_html__( 'Overlay', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__card:hover .zenblog__media::after, {{WRAPPER}} .zenblog__card:focus-within .zenblog__media::after' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_image_zoom',
			array(
				'label'     => esc_html__( 'Zoom', 'zen-blogger' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 1,
						'max'  => 1.5,
						'step' => 0.01,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__card:hover .zenblog__media img, {{WRAPPER}} .zenblog__card:focus-within .zenblog__media img' => 'transform: scale({{SIZE}});',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Style → Badge.
	 *
	 * @return void
	 */
	private function register_badge_style() {
		$this->start_controls_section(
			'zenblog_style_badge',
			array(
				'label'     => esc_html__( 'Badge', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'zenblog_skin!'      => 'template',
					'zenblog_show_badge' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'zenblog_badge_typography',
				'selector' => '{{WRAPPER}} .zenblog__badge',
			)
		);

		$this->add_control(
			'zenblog_badge_color',
			array(
				'label'     => esc_html__( 'Text Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__badge' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_badge_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__badge',
			)
		);

		$this->add_responsive_control(
			'zenblog_badge_padding',
			array(
				'label'      => esc_html__( 'Padding', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_badge_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_badge_offset',
			array(
				'label'      => esc_html__( 'Offset', 'zen-blogger' ),
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
					'size' => 12,
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__badge' => 'inset-block-start: {{SIZE}}{{UNIT}}; inset-inline-start: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Terms.
	 *
	 * @return void
	 */
	private function register_terms_style() {
		$this->start_controls_section(
			'zenblog_style_terms',
			array(
				'label'     => esc_html__( 'Terms', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'zenblog_skin!'      => 'template',
					'zenblog_show_terms' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'zenblog_terms_typography',
				'selector' => '{{WRAPPER}} .zenblog__term',
			)
		);

		$this->add_control(
			'zenblog_terms_color',
			array(
				'label'     => esc_html__( 'Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__term' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_terms_color_hover',
			array(
				'label'     => esc_html__( 'Color — hover', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} a.zenblog__term:hover, {{WRAPPER}} a.zenblog__term:focus-visible' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_terms_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__term',
			)
		);

		$this->add_control(
			'zenblog_terms_bg_hover',
			array(
				'label'     => esc_html__( 'Background — hover', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} a.zenblog__term:hover, {{WRAPPER}} a.zenblog__term:focus-visible' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_terms_padding',
			array(
				'label'      => esc_html__( 'Padding', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__term' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_terms_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__term' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_terms_gap',
			array(
				'label'      => esc_html__( 'Gap', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__terms' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Title.
	 *
	 * @return void
	 */
	private function register_title_style() {
		$this->start_controls_section(
			'zenblog_style_title',
			array(
				'label'     => esc_html__( 'Title', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'zenblog_skin!'      => 'template',
					'zenblog_show_title' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'zenblog_title_typography',
				'selector' => '{{WRAPPER}} .zenblog__title',
			)
		);

		$this->add_control(
			'zenblog_title_color',
			array(
				'label'     => esc_html__( 'Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__title, {{WRAPPER}} .zenblog__title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_title_color_hover',
			array(
				'label'     => esc_html__( 'Color — hover', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__card:hover .zenblog__title a, {{WRAPPER}} .zenblog__title a:focus-visible' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'zenblog_title_shadow',
				'selector' => '{{WRAPPER}} .zenblog__title',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Meta.
	 *
	 * @return void
	 */
	private function register_meta_style() {
		$this->start_controls_section(
			'zenblog_style_meta',
			array(
				'label'     => esc_html__( 'Meta', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'zenblog_skin!'     => 'template',
					'zenblog_show_meta' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'zenblog_meta_typography',
				'selector' => '{{WRAPPER}} .zenblog__meta',
			)
		);

		$this->add_control(
			'zenblog_meta_color',
			array(
				'label'     => esc_html__( 'Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__meta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_meta_link_color',
			array(
				'label'     => esc_html__( 'Link Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__meta a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_meta_sep_color',
			array(
				'label'     => esc_html__( 'Separator Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__meta-sep' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_meta_divider_color',
			array(
				'label'     => esc_html__( 'Divider Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'zenblog_meta_position' => 'bottom' ),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__meta--divided' => 'border-block-start-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_meta_divider_width',
			array(
				'label'      => esc_html__( 'Divider Thickness', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 1,
						'max' => 10,
					),
				),
				'condition'  => array( 'zenblog_meta_position' => 'bottom' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__meta--divided' => 'border-block-start-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_meta_divider_space',
			array(
				'label'      => esc_html__( 'Space Above Meta', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'condition'  => array( 'zenblog_meta_position' => 'bottom' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__meta--bottom' => 'padding-block-start: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_meta_align',
			array(
				'label'     => esc_html__( 'Alignment', 'zen-blogger' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start'    => array(
						'title' => esc_html__( 'Left', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'        => array(
						'title' => esc_html__( 'Center', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'      => array(
						'title' => esc_html__( 'Right', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-right',
					),
					'space-between' => array(
						'title' => esc_html__( 'Spread', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .zenblog__meta' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_meta_gap',
			array(
				'label'      => esc_html__( 'Gap', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__meta' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_avatar_size',
			array(
				'label'      => esc_html__( 'Avatar Size', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 64,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'condition'  => array( 'zenblog_show_avatar' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__avatar' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Excerpt.
	 *
	 * @return void
	 */
	private function register_excerpt_style() {
		$this->start_controls_section(
			'zenblog_style_excerpt',
			array(
				'label'     => esc_html__( 'Excerpt', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'zenblog_skin!'        => 'template',
					'zenblog_show_excerpt' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'zenblog_excerpt_typography',
				'selector' => '{{WRAPPER}} .zenblog__excerpt',
			)
		);

		$this->add_control(
			'zenblog_excerpt_color',
			array(
				'label'     => esc_html__( 'Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__excerpt' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_excerpt_align',
			array(
				'label'     => esc_html__( 'Alignment', 'zen-blogger' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'    => array(
						'title' => esc_html__( 'Left', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => esc_html__( 'Center', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => esc_html__( 'Right', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => esc_html__( 'Justified', 'zen-blogger' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),

				/*
				 * Separate from Content Alignment because justified is not one of
				 * the same four things: that control also drives align-items on the
				 * card, and "justify" is not a value align-items has. The body
				 * alignment stays the default here so a justified paragraph is not
				 * fighting a shrink-wrapped column.
				 */
				'selectors' => array(
					'{{WRAPPER}} .zenblog__excerpt' => 'text-align: {{VALUE}}; align-self: stretch; width: 100%;',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style → Read More.
	 *
	 * @return void
	 */
	private function register_button_style() {
		$this->start_controls_section(
			'zenblog_style_button',
			array(
				'label'     => esc_html__( 'Read More', 'zen-blogger' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'zenblog_skin!'       => 'template',
					'zenblog_show_button' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'zenblog_button_typography',
				'selector' => '{{WRAPPER}} .zenblog__more',
			)
		);

		$this->start_controls_tabs( 'zenblog_button_tabs' );

		$this->start_controls_tab(
			'zenblog_button_tab_normal',
			array( 'label' => esc_html__( 'Normal', 'zen-blogger' ) )
		);

		$this->add_control(
			'zenblog_button_color',
			array(
				'label'     => esc_html__( 'Text Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__more' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'zenblog_button_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zenblog__more',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'zenblog_button_border',
				'selector' => '{{WRAPPER}} .zenblog__more',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'zenblog_button_tab_hover',
			array( 'label' => esc_html__( 'Hover', 'zen-blogger' ) )
		);

		$this->add_control(
			'zenblog_button_color_hover',
			array(
				'label'     => esc_html__( 'Text Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__more:hover, {{WRAPPER}} .zenblog__more:focus-visible' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_button_bg_hover',
			array(
				'label'     => esc_html__( 'Background', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__more:hover, {{WRAPPER}} .zenblog__more:focus-visible' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'zenblog_button_border_color_hover',
			array(
				'label'     => esc_html__( 'Border Color', 'zen-blogger' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zenblog__more:hover, {{WRAPPER}} .zenblog__more:focus-visible' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'zenblog_button_padding',
			array(
				'label'      => esc_html__( 'Padding', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__more' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_button_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'zen-blogger' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__more' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'zenblog_button_icon_gap',
			array(
				'label'      => esc_html__( 'Icon Spacing', 'zen-blogger' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .zenblog__more' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Whether the current request is one where control option lists are actually shown.
	 *
	 * @return bool
	 */
	private function is_editor_request() {
		if ( is_admin() || wp_doing_ajax() ) {
			return true;
		}

		return isset( \Elementor\Plugin::$instance->preview )
			&& \Elementor\Plugin::$instance->preview->is_preview_mode();
	}

	/**
	 * Public taxonomies keyed by name, with their label and owning post types.
	 *
	 * @return array<string,array>
	 */
	private function all_taxonomies() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$cache = array();

		foreach ( array_keys( Zen_Blogger_Query::post_types() ) as $post_type ) {
			foreach ( Zen_Blogger_Query::taxonomies_for( $post_type ) as $taxonomy ) {
				if ( ! isset( $cache[ $taxonomy ] ) ) {
					$object = get_taxonomy( $taxonomy );

					$cache[ $taxonomy ] = array(
						'label'      => $object ? $object->label : $taxonomy,
						'post_types' => array(),
					);
				}

				$cache[ $taxonomy ]['post_types'][] = $post_type;
			}
		}

		return $cache;
	}

	/**
	 * Options for the line-clamp SELECT controls.
	 *
	 * An empty first option matters: Elementor emits nothing for an empty value, so
	 * "No limit" genuinely produces no CSS rather than a rule the user cannot undo.
	 *
	 * @return array<string,string>
	 */
	private function line_clamp_options() {
		$options = array( '' => esc_html__( 'No limit', 'zen-blogger' ) );

		for ( $i = 1; $i <= 10; $i++ ) {
			$options[ (string) $i ] = sprintf(
				/* translators: %s: number of lines. */
				_n( '%s line', '%s lines', $i, 'zen-blogger' ),
				number_format_i18n( $i )
			);
		}

		return $options;
	}

	/**
	 * Saved Elementor templates, for the loop-item card.
	 *
	 * Queries the elementor_library post type directly rather than going through
	 * the Template Library API, which gates some sources behind Pro.
	 *
	 * @return array<string,string>
	 */
	private function template_options() {
		$options = array( '' => esc_html__( '— Select a template —', 'zen-blogger' ) );

		if ( ! $this->is_editor_request() ) {
			return $options;
		}

		$templates = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		foreach ( $templates as $template ) {
			// Cast to string: SELECT2 compares option keys as strings.
			$options[ (string) $template->ID ] = $template->post_title
				? $template->post_title
				: sprintf(
					/* translators: %d: template ID. */
					esc_html__( '(untitled) #%d', 'zen-blogger' ),
					$template->ID
				);
		}

		return $options;
	}

	/**
	 * Render the current loop post through a saved Elementor template.
	 *
	 * The post is already the global $post here (we are inside the_post()), which
	 * is exactly what Dynamic Tags inside the template read — so a template built
	 * with Post Title / Featured Image / Post Excerpt resolves per post with no
	 * extra plumbing, and without Elementor Pro.
	 *
	 * @param array $settings Widget settings.
	 * @param int   $index    1-based position; CSS is inlined for the first card only.
	 * @return bool True when a template was rendered and the built-in card should be skipped.
	 */
	private function render_loop_template( array $settings, $index ) {
		if ( 'template' !== ( isset( $settings['zenblog_skin'] ) ? $settings['zenblog_skin'] : '' ) ) {
			return false;
		}

		$template_id = isset( $settings['zenblog_template_id'] ) ? (int) $settings['zenblog_template_id'] : 0;
		$editing     = isset( \Elementor\Plugin::$instance->editor )
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( $template_id < 1 ) {
			if ( $editing ) {
				echo '<div class="zenblog__template-notice">' . esc_html__( 'Choose a card template.', 'zen-blogger' ) . '</div>';
			}
			return true;
		}

		// Mirrors Elementor's own recursion guard: a template that embeds the page
		// it is placed on renders forever.
		if ( (int) get_queried_object_id() === $template_id ) {
			if ( $editing ) {
				echo '<div class="zenblog__template-notice">' . esc_html__( 'The card template cannot be the page it is placed on.', 'zen-blogger' ) . '</div>';
			}
			return true;
		}

		$template = get_post( $template_id );

		if ( ! $template || 'publish' !== $template->post_status ) {
			if ( $editing ) {
				echo '<div class="zenblog__template-notice">' . esc_html__( 'That card template is missing or not published.', 'zen-blogger' ) . '</div>';
			}
			return true;
		}

		/*
		 * CSS inline on the first card only. Passing true for every post would
		 * repeat the same <style> block once per slide — on a nine-post carousel
		 * that is eight redundant copies of the template's stylesheet.
		 */
		$with_css = ( 1 === (int) $index );

		// Anything the template styles from a dynamic tag has to be scoped to this
		// card, because the shared stylesheet can only describe one post.
		$scope = 'zenblog-tpl-' . (int) get_the_ID();

		echo '<div class="zenblog__template ' . esc_attr( $scope ) . '">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor-generated markup from a user-selected template.
		echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, $with_css );
		$this->print_template_dynamic_css( $template_id, $scope );
		echo '</div>';

		return true;
	}

	/**
	 * Emit this card's share of the template's dynamic CSS.
	 *
	 * Elementor keeps dynamic-tag values that render as CSS out of a document's
	 * cached stylesheet and supplies them separately, once per request, against
	 * the current post. In a loop that is both too few times and under a selector
	 * shared by every card, so a container background bound to the featured image
	 * simply never appears. Generating it here, scoped per card, is what makes it
	 * vary by post — see Zen_Blogger_Template_CSS.
	 *
	 * @param int    $template_id Template post ID.
	 * @param string $scope       Class the card wrapper carries.
	 * @return void
	 */
	private function print_template_dynamic_css( $template_id, $scope ) {
		if ( ! class_exists( '\Elementor\Core\DynamicTags\Dynamic_CSS' ) || ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			return;
		}

		$post_css = \Elementor\Core\Files\CSS\Post::create( $template_id );
		$meta     = $post_css->get_meta();

		// dynamic_elements_ids is only recorded when the stylesheet is generated.
		if ( empty( $meta['status'] ) ) {
			$post_css->update();
			$meta = $post_css->get_meta();
		}

		// The overwhelming majority of templates style nothing dynamically, and
		// this is the check that keeps them from paying for a second CSS parse
		// on every single card.
		if ( empty( $meta['dynamic_elements_ids'] ) ) {
			return;
		}

		if ( ! class_exists( 'Zen_Blogger_Template_CSS' ) ) {
			require_once ZENBLOG_PATH . 'includes/class-zen-blogger-template-css.php';
		}

		/*
		 * Constructed directly rather than through create(): the files manager
		 * caches instances by class and arguments, which would hand every card
		 * the first card's CSS.
		 */
		$css = new Zen_Blogger_Template_CSS( $template_id, $post_css );
		$css->zenblog_set_scope( '.' . $scope );

		$content = trim( (string) $css->get_content() );

		if ( '' === $content ) {
			return;
		}

		// Printed inline rather than enqueued: by the time a card renders, the
		// stylesheet this would attach to has long since gone out in the head.
		printf( '<style>%s</style>', $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor-generated CSS.
	}

	/**
	 * Flat taxonomy list for SELECT controls.
	 *
	 * @return array<string,string>
	 */
	private function taxonomy_options() {
		$options = array();

		foreach ( $this->all_taxonomies() as $taxonomy => $data ) {
			$options[ $taxonomy ] = $data['label'];
		}

		return $options;
	}

	/**
	 * Terms of a taxonomy for SELECT2 controls.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array<int,string>
	 */
	private function term_options( $taxonomy ) {
		if ( ! $this->is_editor_request() ) {
			return array();
		}

		/**
		 * Filter how many terms per taxonomy are offered in the widget panel.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $limit    Maximum number of terms.
		 * @param string $taxonomy Taxonomy name.
		 */
		$limit = (int) apply_filters( 'zenblog_term_choices_limit', 300, $taxonomy );

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => $limit,
				'orderby'    => 'name',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$options = array();

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Posts for the hand-picked / exclude SELECT2 controls.
	 *
	 * @return array<int,string>
	 */
	private function post_options() {
		if ( ! $this->is_editor_request() ) {
			return array();
		}

		/**
		 * Filter how many posts are offered in the hand-picked control.
		 *
		 * @since 1.0.0
		 *
		 * @param int $limit Maximum number of posts.
		 */
		$limit = (int) apply_filters( 'zenblog_post_choices_limit', 200 );

		$posts = get_posts(
			array(
				'post_type'        => array_keys( Zen_Blogger_Query::post_types() ),
				'post_status'      => 'publish',
				'posts_per_page'   => $limit,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
			)
		);

		$options = array();

		foreach ( $posts as $post ) {
			$options[ $post->ID ] = $post->post_title ? $post->post_title : sprintf(
				/* translators: %d: post ID. */
				esc_html__( '(no title) #%d', 'zen-blogger' ),
				$post->ID
			);
		}

		return $options;
	}

	/**
	 * Authors for the author SELECT2 controls.
	 *
	 * @return array<int,string>
	 */
	private function author_options() {
		if ( ! $this->is_editor_request() ) {
			return array();
		}

		$users = get_users(
			array(
				'who'                 => 'authors',
				'has_published_posts' => true,
				'number'              => 200,
				'orderby'             => 'display_name',
				'fields'              => array( 'ID', 'display_name' ),
			)
		);

		$options = array();

		foreach ( $users as $user ) {
			$options[ $user->ID ] = $user->display_name;
		}

		return $options;
	}

	/**
	 * Collect the structured-data record for the current post in the loop.
	 *
	 * @param array $settings Widget settings.
	 * @param int   $position 1-based position in the list.
	 * @return array
	 */
	private function schema_item( array $settings, $position ) {
		$mode = isset( $settings['zenblog_schema'] ) ? $settings['zenblog_schema'] : '';

		if ( '' === $mode ) {
			return array();
		}

		$url = get_permalink();

		// Summary form: Google's documented shape for a list that links out to
		// each item's own page — position and URL, nothing else.
		if ( 'full' !== $mode ) {
			return array(
				'@type'    => 'ListItem',
				'position' => (int) $position,
				'url'      => $url,
			);
		}

		$type = ! empty( $settings['zenblog_schema_type'] ) ? $settings['zenblog_schema_type'] : 'BlogPosting';

		/*
		 * Node identity deliberately follows Zen GEO's convention (permalink +
		 * "#article"). When both plugins are installed, a post described here and
		 * the same post described by Zen GEO on its own page resolve to ONE node
		 * instead of two competing descriptions of the same URL.
		 */
		$item = array(
			'@type'    => $type,
			'@id'      => $url . '#article',
			'url'      => $url,
			'headline' => wp_strip_all_tags( get_the_title() ),
		);

		$image = get_the_post_thumbnail_url( get_the_ID(), 'large' );

		if ( $image ) {
			$item['image'] = $image;
		}

		$excerpt = has_excerpt() ? get_the_excerpt() : '';

		if ( '' !== $excerpt ) {
			$item['description'] = wp_strip_all_tags( $excerpt );
		}

		$item['datePublished'] = get_the_date( DATE_W3C );
		$item['dateModified']  = get_the_modified_date( DATE_W3C );

		$author = get_the_author();

		if ( '' !== $author ) {
			$item['author'] = array(
				'@type' => 'Person',
				'name'  => wp_strip_all_tags( $author ),
			);
		}

		return array(
			'@type'    => 'ListItem',
			'position' => (int) $position,
			'item'     => $item,
		);
	}

	/**
	 * Whether the Zen GEO plugin is active on this request.
	 *
	 * Checked at runtime, never at file-load: plugins are loaded in alphabetical
	 * order, so "zen-blogger" is parsed before "zen-geo" has defined anything.
	 *
	 * @return bool
	 */
	private function zengeo_active() {
		/**
		 * Filter whether Zen Blogger links its structured data into Zen GEO's graph.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $active Whether the Zen GEO integration should apply.
		 */
		return (bool) apply_filters( 'zenblog_zengeo_integration', defined( 'ZENGEO_VERSION' ) );
	}

	/**
	 * The @id of the page node this carousel belongs to.
	 *
	 * Mirrors Zen GEO's own identifier for the current page so the two graphs join
	 * up instead of describing the same page twice.
	 *
	 * @return string
	 */
	private function schema_page_id() {
		$post_id = get_queried_object_id();

		if ( ! $post_id || ! is_singular() ) {
			return home_url( '/#website' );
		}

		return get_permalink( $post_id ) . '#article';
	}

	/**
	 * A stable, unique @id for this carousel's list node.
	 *
	 * Element-scoped so two carousels on one page never collide.
	 *
	 * @return string
	 */
	private function schema_list_id() {
		$base = is_singular() ? get_permalink( get_queried_object_id() ) : home_url( '/' );

		return $base . '#zenblog-' . $this->get_id();
	}

	/**
	 * Emit the carousel's schema.org ItemList as JSON-LD.
	 *
	 * Describing the carousel as an ordered list is what lets search engines treat
	 * it as a list of articles rather than a wall of links, and gives generative
	 * engines an unambiguous reading of what the section contains and in what order.
	 *
	 * @param array $settings Widget settings.
	 * @param array $items    Collected ListItem records.
	 * @return void
	 */
	private function render_schema( array $settings, array $items ) {
		$items = array_values( array_filter( $items ) );

		if ( empty( $items ) || empty( $settings['zenblog_schema'] ) ) {
			return;
		}

		$graph = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'@id'             => $this->schema_list_id(),
			'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		);

		/*
		 * Zen GEO prints its own @graph on wp_head, which has already run by the
		 * time a widget renders — so the two cannot be merged into one script.
		 * Instead this list attaches itself to Zen GEO's page node, so the output
		 * reads as one connected graph rather than two unrelated blobs.
		 *
		 * Detection is deliberately at runtime: plugins load alphabetically, and
		 * "zen-blogger" sorts before "zen-geo", so a file-scope check would always
		 * come back false.
		 */
		if ( $this->zengeo_active() ) {
			$page_id = $this->schema_page_id();

			if ( '' !== $page_id ) {
				$graph['isPartOf'] = array( '@id' => $page_id );
			}
		}

		$name = isset( $settings['zenblog_schema_name'] ) ? trim( $settings['zenblog_schema_name'] ) : '';

		if ( '' === $name && ! empty( $settings['zenblog_region_label'] ) ) {
			$name = trim( $settings['zenblog_region_label'] );
		}

		if ( '' !== $name ) {
			$graph['name'] = $name;
		}

		/**
		 * Filter the JSON-LD graph emitted for a Zen Blogger carousel.
		 *
		 * Return an empty array to suppress the output entirely.
		 *
		 * @since 1.0.0
		 *
		 * @param array $graph    The schema.org ItemList.
		 * @param array $settings Widget settings.
		 */
		$graph = apply_filters( 'zenblog_schema_graph', $graph, $settings );

		if ( empty( $graph ) ) {
			return;
		}

		/*
		 * Slashes stay escaped on purpose. With JSON_UNESCAPED_SLASHES a value
		 * containing "</script>" — a display name, a term name, anything not run
		 * through wp_strip_all_tags() — closes this block and injects markup into
		 * the page. Escaping "/" as "\/" makes that impossible at the encoder
		 * level, so it cannot regress when a new field is added later.
		 * JSON_UNESCAPED_UNICODE is kept: it is safe and keeps non-Latin titles
		 * readable rather than \uXXXX noise.
		 */
		$json = wp_json_encode( $graph, JSON_UNESCAPED_UNICODE );

		if ( ! $json ) {
			return;
		}

		wp_print_inline_script_tag( $json, array( 'type' => 'application/ld+json' ) );
	}

	/**
	 * Render the "nothing found" state.
	 *
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_empty_state( array $settings ) {
		$message = isset( $settings['zenblog_empty_message'] ) ? trim( $settings['zenblog_empty_message'] ) : '';

		if ( '' === $message ) {
			return;
		}

		echo '<p class="zenblog__empty">' . esc_html( $message ) . '</p>';
	}

	/**
	 * Render the featured image block.
	 *
	 * @param array $settings  Widget settings.
	 * @param bool  $eager     Load eagerly with high priority.
	 * @param bool  $with_terms Render the term chips over the image.
	 * @param int   $index     1-based slide number.
	 * @return void
	 */
	private function render_media( array $settings, $eager, $with_terms, $index ) {
		$post_id  = get_the_ID();
		$thumb_id = get_post_thumbnail_id( $post_id );
		$fallback = isset( $settings['zenblog_image_fallback']['id'] ) ? (int) $settings['zenblog_image_fallback']['id'] : 0;

		if ( ! $thumb_id && $fallback ) {
			$thumb_id = $fallback;
		}

		$lazy = 'yes' === ( isset( $settings['zenblog_lazy_images'] ) ? $settings['zenblog_lazy_images'] : 'yes' );

		$attr = array(
			'class'    => 'zenblog__img',
			'decoding' => 'async',
			'alt'      => '',
		);

		if ( $lazy && ! $eager ) {
			$attr['loading'] = 'lazy';
		} else {
			$attr['loading'] = 'eager';
			if ( 1 === $index ) {
				$attr['fetchpriority'] = 'high';
			}
		}

		$image_html = '';

		if ( $thumb_id ) {
			$size = isset( $settings['zenblog_image_size_size'] ) ? $settings['zenblog_image_size_size'] : 'large';

			if ( 'custom' === $size ) {
				$src = Group_Control_Image_Size::get_attachment_image_src( $thumb_id, 'zenblog_image', $settings );

				if ( $src ) {
					$image_html = sprintf(
						'<img src="%1$s" alt="%2$s" class="%3$s" decoding="async" loading="%4$s"%5$s />',
						esc_url( $src ),
						esc_attr( (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) ),
						esc_attr( $attr['class'] ),
						esc_attr( $attr['loading'] ),
						isset( $attr['fetchpriority'] ) ? ' fetchpriority="high"' : ''
					);
				}
			} else {
				unset( $attr['alt'] );
				$image_html = wp_get_attachment_image( $thumb_id, $size, false, $attr );
			}
		}

		if ( '' === $image_html ) {
			// No image and no fallback chosen — render nothing rather than a placeholder.
			if ( $with_terms ) {
				$this->render_terms( $settings );
			}
			return;
		}
		?>
		<div class="zenblog__media">
			<?php echo wp_kses( $image_html, self::allowed_image_html() ); ?>
			<?php
			if ( 'yes' === ( isset( $settings['zenblog_show_badge'] ) ? $settings['zenblog_show_badge'] : '' ) && is_sticky( $post_id ) ) {
				$badge = isset( $settings['zenblog_badge_text'] ) ? trim( $settings['zenblog_badge_text'] ) : '';
				if ( '' !== $badge ) {
					echo '<span class="zenblog__badge">' . esc_html( $badge ) . '</span>';
				}
			}

			if ( $with_terms ) {
				$this->render_terms( $settings );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render the inside of a card, in the order the settings ask for.
	 *
	 * Both widgets call this, so a change to the order or to any part lands in
	 * the carousel and the grid at once.
	 *
	 * @param array $settings  Widget settings.
	 * @param bool  $stretched Whether the title link covers the whole card.
	 * @param int   $position  1-based position, used by the editorial index.
	 * @return void
	 */
	private function render_card_body( array $settings, $stretched, $position ) {
		$skin      = isset( $settings['zenblog_skin'] ) ? $settings['zenblog_skin'] : 'classic';
		$terms_on  = 'yes' === ( isset( $settings['zenblog_show_terms'] ) ? $settings['zenblog_show_terms'] : '' );
		$terms_pos = isset( $settings['zenblog_terms_position'] ) ? $settings['zenblog_terms_position'] : 'body';
		$meta_pos  = isset( $settings['zenblog_meta_position'] ) ? $settings['zenblog_meta_position'] : 'title';

		if ( 'overlay' === $skin ) {
			$terms_pos = 'body';
		}

		if ( 'editorial' === $skin ) {
			echo '<span class="zenblog__index" aria-hidden="true">' . esc_html( str_pad( (string) $position, 2, '0', STR_PAD_LEFT ) ) . '</span>';
		}

		if ( $terms_on && 'body' === $terms_pos ) {
			$this->render_terms( $settings );
		}

		$this->render_title( $settings, $stretched );

		if ( 'bottom' !== $meta_pos ) {
			$this->render_meta( $settings );
		}

		$this->render_excerpt( $settings );
		$this->render_button( $settings, $stretched );

		// Rendered last so it sits under the excerpt and the button, pushed to the
		// foot of the card by CSS.
		if ( 'bottom' === $meta_pos ) {
			$this->render_meta( $settings );
		}
	}

	/**
	 * Render the term chips.
	 *
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_terms( array $settings ) {
		$taxonomy = ! empty( $settings['zenblog_terms_taxonomy'] ) ? sanitize_key( $settings['zenblog_terms_taxonomy'] ) : 'category';

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$terms = get_the_terms( get_the_ID(), $taxonomy );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$max    = isset( $settings['zenblog_terms_max'] ) ? max( 1, (int) $settings['zenblog_terms_max'] ) : 1;
		$terms  = array_slice( $terms, 0, $max );
		$target = $this->link_target( $settings );

		echo '<div class="zenblog__terms">';

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				echo '<span class="zenblog__term">' . esc_html( $term->name ) . '</span>';
				continue;
			}

			printf(
				'<a class="zenblog__term" href="%1$s"%2$s>%3$s</a>',
				esc_url( $link ),
				'_blank' === $target ? ' target="_blank" rel="noopener noreferrer"' : '',
				esc_html( $term->name )
			);
		}

		echo '</div>';
	}

	/**
	 * Render the post title.
	 *
	 * @param array $settings  Widget settings.
	 * @param bool  $stretched Whether the title link covers the whole card.
	 * @return void
	 */
	private function render_title( array $settings, $stretched ) {
		if ( 'yes' !== ( isset( $settings['zenblog_show_title'] ) ? $settings['zenblog_show_title'] : '' ) ) {
			return;
		}

		$tag    = Utils::validate_html_tag( isset( $settings['zenblog_title_tag'] ) ? $settings['zenblog_title_tag'] : 'h3' );
		$target = $this->link_target( $settings );
		$link   = get_permalink();

		printf(
			'<%1$s class="zenblog__title"><a class="zenblog__title-link%2$s" href="%3$s"%4$s>%5$s</a></%1$s>',
			tag_escape( $tag ),
			$stretched ? ' zenblog__stretch' : '',
			esc_url( $link ),
			'_blank' === $target ? ' target="_blank" rel="noopener noreferrer"' : '',
			esc_html( get_the_title() )
		);
	}

	/**
	 * Render the meta row.
	 *
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_meta( array $settings ) {
		if ( 'yes' !== ( isset( $settings['zenblog_show_meta'] ) ? $settings['zenblog_show_meta'] : '' ) ) {
			return;
		}

		$items = isset( $settings['zenblog_meta_items'] ) ? (array) $settings['zenblog_meta_items'] : array();

		if ( empty( $items ) ) {
			return;
		}

		$separator = isset( $settings['zenblog_meta_separator'] ) ? $settings['zenblog_meta_separator'] : '';
		$parts     = array();

		foreach ( $items as $item ) {
			$html = $this->meta_item_html( $item, $settings );

			if ( '' !== $html ) {
				$parts[] = $html;
			}
		}

		if ( empty( $parts ) ) {
			return;
		}

		$bottom  = 'bottom' === ( isset( $settings['zenblog_meta_position'] ) ? $settings['zenblog_meta_position'] : 'title' );
		$divided = $bottom && 'yes' === ( isset( $settings['zenblog_meta_divider'] ) ? $settings['zenblog_meta_divider'] : 'yes' );

		$classes = 'zenblog__meta';
		if ( $bottom ) {
			$classes .= ' zenblog__meta--bottom';
		}
		if ( $divided ) {
			$classes .= ' zenblog__meta--divided';
		}

		echo '<div class="' . esc_attr( $classes ) . '">';

		$last = count( $parts ) - 1;

		foreach ( $parts as $i => $part ) {
			echo '<span class="zenblog__meta-item">' . wp_kses( $part, self::allowed_meta_html() ) . '</span>';

			if ( $i < $last && '' !== $separator ) {
				echo '<span class="zenblog__meta-sep" aria-hidden="true">' . esc_html( $separator ) . '</span>';
			}
		}

		echo '</div>';
	}

	/**
	 * Build the markup for one meta item.
	 *
	 * @param string $item     Meta item key.
	 * @param array  $settings Widget settings.
	 * @return string
	 */
	private function meta_item_html( $item, array $settings ) {
		switch ( $item ) {
			case 'author':
				$author_id = (int) get_the_author_meta( 'ID' );
				$name      = get_the_author();

				if ( '' === $name ) {
					return '';
				}

				$avatar = '';

				if ( 'yes' === ( isset( $settings['zenblog_show_avatar'] ) ? $settings['zenblog_show_avatar'] : '' ) ) {
					$avatar = get_avatar( $author_id, 48, '', '', array( 'class' => 'zenblog__avatar' ) );
					$avatar = is_string( $avatar ) ? $avatar : '';
				}

				return $avatar . '<span class="zenblog__author">' . esc_html( $name ) . '</span>';

			case 'date':
				return $this->date_html( 'date', $settings );

			case 'modified':
				return $this->date_html( 'modified', $settings );

			case 'comments':
				$count = (int) get_comments_number();

				return '<span class="zenblog__comments">' . esc_html(
					$this->countable_text(
						$settings,
						'zenblog_comments_text',
						$count,
						/* translators: %s: number of comments. */
						_n( '%s comment', '%s comments', $count, 'zen-blogger' )
					)
				) . '</span>';

			case 'reading_time':
				$minutes = $this->reading_time( get_the_ID() );

				return '<span class="zenblog__reading-time">' . esc_html(
					$this->countable_text(
						$settings,
						'zenblog_reading_time_text',
						$minutes,
						/* translators: %s: estimated reading time in minutes. */
						_n( '%s min read', '%s min read', $minutes, 'zen-blogger' )
					)
				) . '</span>';
		}

		return '';
	}

	/**
	 * A count string, using the site owner's wording when they supplied one.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $key      Setting holding the template.
	 * @param int    $count    The number to substitute.
	 * @param string $fallback Translated plural used when the setting is empty.
	 * @return string
	 */
	private function countable_text( array $settings, $key, $count, $fallback ) {
		$template = isset( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';

		if ( '' === $template ) {
			return sprintf( $fallback, number_format_i18n( $count ) );
		}

		// str_replace, not sprintf: a stray % in user-typed wording would be fatal.
		if ( false === strpos( $template, '%s' ) ) {
			return $template;
		}

		return str_replace( '%s', number_format_i18n( $count ), $template );
	}

	/**
	 * Build a <time> element for the published or modified date.
	 *
	 * @param string $which    'date' or 'modified'.
	 * @param array  $settings Widget settings.
	 * @return string
	 */
	private function date_html( $which, array $settings ) {
		$style     = isset( $settings['zenblog_date_style'] ) ? $settings['zenblog_date_style'] : 'default';
		$timestamp = 'modified' === $which ? get_post_modified_time( 'U', true ) : get_post_time( 'U', true );
		$machine   = 'modified' === $which ? get_the_modified_date( DATE_W3C ) : get_the_date( DATE_W3C );

		if ( 'human' === $style ) {
			$label = sprintf(
				/* translators: %s: human-readable time difference, e.g. "2 days". */
				esc_html__( '%s ago', 'zen-blogger' ),
				human_time_diff( (int) $timestamp, time() )
			);
		} elseif ( 'custom' === $style ) {
			$format = ! empty( $settings['zenblog_date_format'] ) ? $settings['zenblog_date_format'] : 'M j, Y';
			$label  = 'modified' === $which ? get_the_modified_date( $format ) : get_the_date( $format );
		} else {
			$label = 'modified' === $which ? get_the_modified_date() : get_the_date();
		}

		return sprintf(
			'<time class="zenblog__date" datetime="%1$s">%2$s</time>',
			esc_attr( $machine ),
			esc_html( $label )
		);
	}

	/**
	 * Estimated reading time in whole minutes.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private function reading_time( $post_id ) {
		$content = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) );

		// str_word_count() only counts A–Z, so it returns 0 for Georgian, Cyrillic,
		// Greek, Arabic and Hebrew. Counting Unicode letter/number runs instead is
		// correct for every alphabetic script and needs no mbstring extension —
		// which is not guaranteed to be installed.
		$words = preg_match_all( '/[\p{L}\p{N}\p{M}]+/u', $content );

		if ( false === $words ) {
			// Invalid UTF-8 — fall back to the ASCII counter rather than returning 0.
			$words = (int) str_word_count( $content );
		}

		// Scripts written without spaces produce one enormous "word". Counting the
		// characters and assuming roughly two per word is far closer than 1.
		$cjk = preg_match_all( '/[\x{3040}-\x{30FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{AC00}-\x{D7AF}]/u', $content );

		if ( $cjk ) {
			$words += (int) ceil( $cjk / 2 );
		}

		$words = max( 1, (int) $words );

		/**
		 * Filter the words-per-minute used for the reading time estimate.
		 *
		 * @since 1.0.0
		 *
		 * @param int $wpm     Words per minute.
		 * @param int $post_id Post ID.
		 */
		$wpm = max( 1, (int) apply_filters( 'zenblog_reading_speed', 200, $post_id ) );

		return max( 1, (int) ceil( $words / $wpm ) );
	}

	/**
	 * Render the excerpt.
	 *
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_excerpt( array $settings ) {
		if ( 'yes' !== ( isset( $settings['zenblog_show_excerpt'] ) ? $settings['zenblog_show_excerpt'] : '' ) ) {
			return;
		}

		$length = isset( $settings['zenblog_excerpt_length'] ) ? max( 1, (int) $settings['zenblog_excerpt_length'] ) : 20;
		$more   = isset( $settings['zenblog_excerpt_more'] ) ? $settings['zenblog_excerpt_more'] : '…';
		$source = isset( $settings['zenblog_excerpt_source'] ) ? $settings['zenblog_excerpt_source'] : 'auto';

		if ( 'paragraphs' === $source ) {
			$raw = $this->paragraph_text();
		} elseif ( 'content' === $source ) {
			$raw = has_excerpt() ? get_the_excerpt() : wp_strip_all_tags( strip_shortcodes( get_the_content() ) );
		} else {
			$raw = has_excerpt() ? get_the_excerpt() : $this->paragraph_text();
		}

		$text = wp_trim_words( $raw, $length, $more );

		if ( '' === trim( $text ) ) {
			return;
		}

		echo '<div class="zenblog__excerpt">' . esc_html( $text ) . '</div>';
	}

	/**
	 * The post's prose, taken from its paragraphs only.
	 *
	 * Reading only <p> elements gives the actual prose a card excerpt is for.
	 * wp_strip_all_tags() over the whole post flattens everything into the
	 * excerpt — headings, list items, captions, table cells, button labels — and
	 * because it simply removes tags without inserting spaces, adjacent list
	 * items run together ("List item oneList item two").
	 *
	 * @return string
	 */
	private function paragraph_text() {
		$content = strip_shortcodes( (string) get_the_content() );

		// Drop anything whose text is not prose at all before parsing.
		$content = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', $content );

		// The classic editor stores plain text with blank lines and no <p> at all;
		// wpautop() gives it the paragraphs the parser below is looking for.
		if ( false === stripos( $content, '<p' ) ) {
			$content = wpautop( $content );
		}

		preg_match_all( '#<p\b[^>]*>(.*?)</p>#is', $content, $matches );

		$parts = array();

		foreach ( (array) $matches[1] as $paragraph ) {
			$text = wp_strip_all_tags( $paragraph );
			// Non-breaking spaces are common in pasted content and would otherwise
			// survive as visible artefacts in the excerpt.
			$text = trim( str_replace( "\xc2\xa0", ' ', $text ) );

			if ( '' !== $text ) {
				$parts[] = $text;
			}
		}

		if ( empty( $parts ) ) {
			// No paragraphs at all — a page built entirely of blocks or shortcodes.
			// Falling back to the flattened content is better than an empty card.
			return wp_strip_all_tags( $content );
		}

		return implode( ' ', $parts );
	}

	/**
	 * Render the read-more button.
	 *
	 * @param array $settings  Widget settings.
	 * @param bool  $stretched Whether the card already has a stretched title link.
	 * @return void
	 */
	private function render_button( array $settings, $stretched ) {
		if ( 'yes' !== ( isset( $settings['zenblog_show_button'] ) ? $settings['zenblog_show_button'] : '' ) ) {
			return;
		}

		$text = isset( $settings['zenblog_button_text'] ) ? trim( $settings['zenblog_button_text'] ) : '';

		if ( '' === $text ) {
			return;
		}

		$target = $this->link_target( $settings );
		$title  = get_the_title();

		// A stretched title link already covers the card, so the button becomes decorative:
		// hiding it from assistive tech avoids announcing the same destination twice.
		$aria = $stretched
			? ' aria-hidden="true" tabindex="-1"'
			: sprintf(
				' aria-label="%s"',
				esc_attr(
					sprintf(
						/* translators: %s: post title. */
						__( 'Read more: %s', 'zen-blogger' ),
						$title
					)
				)
			);

		printf(
			'<a class="zenblog__more" href="%1$s"%2$s%3$s><span class="zenblog__more-text">%4$s</span>',
			esc_url( get_permalink() ),
			'_blank' === $target ? ' target="_blank" rel="noopener noreferrer"' : '',
			$aria, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from esc_attr() output and literals.
			esc_html( $text )
		);

		if ( ! empty( $settings['zenblog_button_icon']['value'] ) ) {
			$this->enqueue_icon_styles();
			echo '<span class="zenblog__more-icon">';
			Icons_Manager::render_icon( $settings['zenblog_button_icon'], array( 'aria-hidden' => 'true' ) );
			echo '</span>';
		}

		echo '</a>';
	}

	/**
	 * Resolve the link target setting.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function link_target( array $settings ) {
		return ( isset( $settings['zenblog_link_target'] ) && '_blank' === $settings['zenblog_link_target'] ) ? '_blank' : '_self';
	}

	/**
	 * How many leading slides should load their image eagerly.
	 *
	 * @param array $settings Widget settings.
	 * @return int
	 */
	private function eager_count( array $settings ) {
		$per_view = isset( $settings['zenblog_slides_per_view'] ) ? $settings['zenblog_slides_per_view'] : '3';
		$rows     = isset( $settings['zenblog_rows'] ) ? max( 1, (int) $settings['zenblog_rows'] ) : 1;
		$count    = ( 'auto' === $per_view ) ? 3 : max( 1, (int) $per_view );

		return $count * $rows;
	}

	/**
	 * Elementor's active tablet / desktop breakpoint boundaries.
	 *
	 * @return array{tablet:int,desktop:int}
	 */
	private function breakpoint_values() {
		$mobile = 767;
		$tablet = 1024;

		if ( isset( \Elementor\Plugin::$instance->breakpoints ) ) {
			$active = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();

			if ( isset( $active['mobile'] ) ) {
				$mobile = (int) $active['mobile']->get_value();
			}
			if ( isset( $active['tablet'] ) ) {
				$tablet = (int) $active['tablet']->get_value();
			}
		}

		return array(
			'tablet'  => $mobile + 1,
			'desktop' => $tablet + 1,
		);
	}

	/**
	 * Read a responsive control into desktop / tablet / mobile values, inheriting upwards.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $key      Control name.
	 * @param mixed  $fallback Value used when nothing is set.
	 * @return array{desktop:mixed,tablet:mixed,mobile:mixed}
	 */
	private function responsive_value( array $settings, $key, $fallback ) {
		$desktop = ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) ? $settings[ $key ] : $fallback;
		$tablet  = ( isset( $settings[ $key . '_tablet' ] ) && '' !== $settings[ $key . '_tablet' ] ) ? $settings[ $key . '_tablet' ] : $desktop;
		$mobile  = ( isset( $settings[ $key . '_mobile' ] ) && '' !== $settings[ $key . '_mobile' ] ) ? $settings[ $key . '_mobile' ] : $tablet;

		return array(
			'desktop' => $this->numeric_or_auto( $desktop ),
			'tablet'  => $this->numeric_or_auto( $tablet ),
			'mobile'  => $this->numeric_or_auto( $mobile ),
		);
	}

	/**
	 * Read a responsive SLIDER control into desktop / tablet / mobile integers.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $key      Control name.
	 * @param int    $fallback Value used when nothing is set.
	 * @return array{desktop:int,tablet:int,mobile:int}
	 */
	private function responsive_slider( array $settings, $key, $fallback ) {
		$read = static function ( $value, $when_empty ) {
			return ( is_array( $value ) && isset( $value['size'] ) && '' !== $value['size'] ) ? (int) $value['size'] : $when_empty;
		};

		$desktop = $read( isset( $settings[ $key ] ) ? $settings[ $key ] : null, $fallback );
		$tablet  = $read( isset( $settings[ $key . '_tablet' ] ) ? $settings[ $key . '_tablet' ] : null, $desktop );
		$mobile  = $read( isset( $settings[ $key . '_mobile' ] ) ? $settings[ $key . '_mobile' ] : null, $tablet );

		return array(
			'desktop' => $desktop,
			'tablet'  => $tablet,
			'mobile'  => $mobile,
		);
	}

	/**
	 * Normalise a slides-per-view value to an int or the literal string "auto".
	 *
	 * @param mixed $value Raw value.
	 * @return int|string
	 */
	private function numeric_or_auto( $value ) {
		return ( 'auto' === $value ) ? 'auto' : max( 1, (int) $value );
	}

	/**
	 * Attributes permitted on the rendered featured image.
	 *
	 * @return array
	 */
	private static function allowed_image_html() {
		return array(
			'img' => array(
				'src'           => true,
				'srcset'        => true,
				'sizes'         => true,
				'alt'           => true,
				'class'         => true,
				'width'         => true,
				'height'        => true,
				'loading'       => true,
				'decoding'      => true,
				'fetchpriority' => true,
				'style'         => true,
			),
		);
	}

	/**
	 * Tags permitted inside a meta item.
	 *
	 * @return array
	 */
	private static function allowed_meta_html() {
		return array(
			'span' => array(
				'class' => true,
			),
			'time' => array(
				'class'    => true,
				'datetime' => true,
			),
			'img'  => array(
				'src'      => true,
				'srcset'   => true,
				'alt'      => true,
				'class'    => true,
				'width'    => true,
				'height'   => true,
				'loading'  => true,
				'decoding' => true,
			),
			'a'    => array(
				'href'   => true,
				'class'  => true,
				'target' => true,
				'rel'    => true,
			),
		);
	}
}
