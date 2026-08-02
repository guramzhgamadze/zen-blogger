<?php
/**
 * Search-and-filter bar for the Posts widget.
 *
 * Built as a real <form method="get"> containing real inputs, so it works with
 * JavaScript disabled and every state is a shareable URL. The script upgrades it
 * to fetch in place; it never becomes the only way to use it.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

trait Zen_Blogger_Filter_Trait {

	/**
	 * Content → Search & Filter.
	 *
	 * @return void
	 */
	private function register_filter_controls() {
		$this->start_controls_section(
			'zenblog_section_filter',
			array(
				'label' => esc_html__( 'Search & Filter', 'zen-blogger' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'zenblog_filter',
			array(
				'label'       => esc_html__( 'Show Search & Filter Bar', 'zen-blogger' ),
				'description' => esc_html__( 'A real form with real inputs. It works with JavaScript disabled, and every filtered view has its own shareable URL.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
			)
		);

		$this->add_control(
			'zenblog_search',
			array(
				'label'     => esc_html__( 'Search Box', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_search_placeholder',
			array(
				'label'     => esc_html__( 'Search Placeholder', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Search posts…', 'zen-blogger' ),
				'condition' => array(
					'zenblog_filter' => 'yes',
					'zenblog_search' => 'yes',
				),
			)
		);

		$this->add_control(
			'zenblog_search_label',
			array(
				'label'       => esc_html__( 'Search Label', 'zen-blogger' ),
				'description' => esc_html__( 'Always rendered for screen readers. Placeholder text alone is not a label.', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Search posts', 'zen-blogger' ),
				'condition'   => array(
					'zenblog_filter' => 'yes',
					'zenblog_search' => 'yes',
				),
			)
		);

		$this->add_control(
			'zenblog_filter_taxonomies',
			array(
				'label'       => esc_html__( 'Filter By', 'zen-blogger' ),
				'description' => esc_html__( 'Choose one or more taxonomies. Each becomes its own labelled group.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => array( 'category' ),
				'options'     => $this->taxonomy_options(),
				'condition'   => array( 'zenblog_filter' => 'yes' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'zenblog_filter_style',
			array(
				'label'     => esc_html__( 'Control Type', 'zen-blogger' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'links',
				'options'   => array(
					'links'    => esc_html__( 'Buttons — pick one', 'zen-blogger' ),
					'checkbox' => esc_html__( 'Checkboxes — pick several', 'zen-blogger' ),
					'dropdown' => esc_html__( 'Dropdown', 'zen-blogger' ),
				),
				'condition' => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_filter_relation',
			array(
				'label'       => esc_html__( 'Match', 'zen-blogger' ),
				'description' => esc_html__( 'When several terms are ticked.', 'zen-blogger' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'IN',
				'options'     => array(
					'IN'  => esc_html__( 'Any of them', 'zen-blogger' ),
					'AND' => esc_html__( 'All of them', 'zen-blogger' ),
				),
				'condition'   => array(
					'zenblog_filter'       => 'yes',
					'zenblog_filter_style' => 'checkbox',
				),
			)
		);

		$this->add_control(
			'zenblog_filter_all_label',
			array(
				'label'     => esc_html__( '"All" Label', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All', 'zen-blogger' ),
				'condition' => array(
					'zenblog_filter'       => 'yes',
					'zenblog_filter_style' => array( 'links', 'dropdown' ),
				),
			)
		);

		$this->add_control(
			'zenblog_filter_counts',
			array(
				'label'     => esc_html__( 'Show Post Counts', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_filter_limit',
			array(
				'label'     => esc_html__( 'Max Terms Per Taxonomy', 'zen-blogger' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 100,
				'default'   => 12,
				'condition' => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_filter_empty_terms',
			array(
				'label'       => esc_html__( 'Include Empty Terms', 'zen-blogger' ),
				'description' => esc_html__( 'Off by default: a filter that always returns nothing is just a dead end.', 'zen-blogger' ),
				'type'        => Controls_Manager::SWITCHER,
				'condition'   => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_sort',
			array(
				'label'     => esc_html__( 'Sort Control', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => array( 'zenblog_filter' => 'yes' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'zenblog_sort_label',
			array(
				'label'     => esc_html__( 'Sort Label', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Sort by', 'zen-blogger' ),
				'condition' => array(
					'zenblog_filter' => 'yes',
					'zenblog_sort'   => 'yes',
				),
			)
		);

		$this->add_control(
			'zenblog_show_count',
			array(
				'label'     => esc_html__( 'Show Result Count', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_filter_clear',
			array(
				'label'     => esc_html__( '"Clear" Button', 'zen-blogger' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_control(
			'zenblog_filter_clear_text',
			array(
				'label'     => esc_html__( 'Clear Text', 'zen-blogger' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Clear filters', 'zen-blogger' ),
				'condition' => array(
					'zenblog_filter'       => 'yes',
					'zenblog_filter_clear' => 'yes',
				),
			)
		);

		$this->add_control(
			'zenblog_filter_submit_text',
			array(
				'label'       => esc_html__( 'Submit Text', 'zen-blogger' ),
				'description' => esc_html__( 'Shown to everyone when JavaScript is off, and always available to keyboard users.', 'zen-blogger' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Apply', 'zen-blogger' ),
				'condition'   => array( 'zenblog_filter' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'zenblog_filter_align',
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
				'condition' => array( 'zenblog_filter' => 'yes' ),
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .zenblog__filter-row' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/*
	 * -------------------------------------------------------------------
	 * State
	 * -------------------------------------------------------------------
	 */

	/**
	 * Taxonomies this widget filters on.
	 *
	 * @param array $settings Widget settings.
	 * @return string[]
	 */
	private function filter_taxonomies( array $settings ) {
		$raw = isset( $settings['zenblog_filter_taxonomies'] ) ? (array) $settings['zenblog_filter_taxonomies'] : array();

		$out = array();

		foreach ( $raw as $taxonomy ) {
			$taxonomy = sanitize_key( $taxonomy );
			if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
				$out[] = $taxonomy;
			}
		}

		return $out;
	}

	/**
	 * Query-arg name for a taxonomy's selected terms.
	 *
	 * @param string $uid      Widget DOM id.
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	private function tax_arg( $uid, $taxonomy ) {
		return 'zbt_' . preg_replace( '/[^a-z0-9]/i', '', $uid ) . '_' . preg_replace( '/[^a-z0-9_]/i', '', $taxonomy );
	}

	/**
	 * Query-arg name for the search term.
	 *
	 * @param string $uid Widget DOM id.
	 * @return string
	 */
	private function search_arg( $uid ) {
		return 'zbs_' . preg_replace( '/[^a-z0-9]/i', '', $uid );
	}

	/**
	 * Query-arg name for the sort order.
	 *
	 * @param string $uid Widget DOM id.
	 * @return string
	 */
	private function sort_arg( $uid ) {
		return 'zbo_' . preg_replace( '/[^a-z0-9]/i', '', $uid );
	}

	/**
	 * The sort options offered, keyed by the value that goes in the URL.
	 *
	 * @return array<string,string>
	 */
	private function sort_options() {
		return array(
			''           => esc_html__( 'Newest first', 'zen-blogger' ),
			'oldest'     => esc_html__( 'Oldest first', 'zen-blogger' ),
			'title'      => esc_html__( 'Title A–Z', 'zen-blogger' ),
			'title_desc' => esc_html__( 'Title Z–A', 'zen-blogger' ),
			'comments'   => esc_html__( 'Most discussed', 'zen-blogger' ),
			'modified'   => esc_html__( 'Recently updated', 'zen-blogger' ),
			'rand'       => esc_html__( 'Random', 'zen-blogger' ),
		);
	}

	/**
	 * Read the whole filter state out of the request.
	 *
	 * Everything is validated here: term ids are integers checked against the
	 * configured taxonomies, the sort key against a fixed list, and the search
	 * string is sanitised and length-capped. Nothing from the URL reaches the
	 * query unchecked.
	 *
	 * @param array      $settings Widget settings.
	 * @param string     $uid      Widget DOM id.
	 * @param array|null $source   Values to read; defaults to the request query.
	 * @return array
	 */
	private function filter_state( array $settings, $uid, array $source = null ) {
		if ( null === $source ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view state; nothing is written.
			$source = wp_unslash( $_GET );
		}

		$state = array(
			'search' => '',
			'terms'  => array(),
			'sort'   => '',
		);

		if ( 'yes' !== ( isset( $settings['zenblog_filter'] ) ? $settings['zenblog_filter'] : '' ) ) {
			return $state;
		}

		if ( 'yes' === ( isset( $settings['zenblog_search'] ) ? $settings['zenblog_search'] : '' ) ) {
			$key = $this->search_arg( $uid );
			if ( isset( $source[ $key ] ) ) {
				$state['search'] = trim( sanitize_text_field( $source[ $key ] ) );
				if ( function_exists( 'mb_substr' ) ) {
					$state['search'] = mb_substr( $state['search'], 0, 120 );
				} else {
					$state['search'] = substr( $state['search'], 0, 120 );
				}
			}
		}

		foreach ( $this->filter_taxonomies( $settings ) as $taxonomy ) {
			$key = $this->tax_arg( $uid, $taxonomy );

			if ( ! isset( $source[ $key ] ) ) {
				continue;
			}

			$raw = $source[ $key ];
			$raw = is_array( $raw ) ? $raw : explode( ',', (string) $raw );

			$ids = array();

			foreach ( $raw as $one ) {
				$id = (int) $one;
				// A term id that is not in this taxonomy is simply dropped.
				if ( $id > 0 && get_term( $id, $taxonomy ) instanceof WP_Term ) {
					$ids[] = $id;
				}
			}

			if ( $ids ) {
				$state['terms'][ $taxonomy ] = array_values( array_unique( $ids ) );
			}
		}

		if ( 'yes' === ( isset( $settings['zenblog_sort'] ) ? $settings['zenblog_sort'] : '' ) ) {
			$key = $this->sort_arg( $uid );
			$raw = isset( $source[ $key ] ) ? sanitize_key( $source[ $key ] ) : '';
			if ( array_key_exists( $raw, $this->sort_options() ) ) {
				$state['sort'] = $raw;
			}
		}

		return $state;
	}

	/**
	 * Parse a filter query string into validated state.
	 *
	 * Used by the REST endpoint so an AJAX request goes through exactly the same
	 * validation as a page load: same taxonomy allowlist, same term-existence
	 * check, same sort allowlist, same search length cap. There is deliberately
	 * no second, looser path into the query.
	 *
	 * @param string $query Raw query string, e.g. "zbs_abc=yoga&zbt_abc_category=3,4".
	 * @return array
	 */
	public function parse_filter_query( $query ) {
		$parsed = array();
		parse_str( (string) $query, $parsed );

		return $this->filter_state(
			$this->get_settings_for_display(),
			'zenblog-' . $this->get_id(),
			is_array( $parsed ) ? $parsed : array()
		);
	}

	/**
	 * Whether any filter is currently active.
	 *
	 * @param array $state Filter state.
	 * @return bool
	 */
	private function filter_active( array $state ) {
		return '' !== $state['search'] || ! empty( $state['terms'] ) || '' !== $state['sort'];
	}

	/*
	 * -------------------------------------------------------------------
	 * Render
	 * -------------------------------------------------------------------
	 */

	/**
	 * Render the search-and-filter form.
	 *
	 * @param array  $settings Widget settings.
	 * @param array  $state    Current filter state.
	 * @param string $uid      Widget DOM id.
	 * @param int    $total    Result count for the current state.
	 * @return void
	 */
	private function render_filter_bar( array $settings, array $state, $uid, $total ) {
		if ( 'yes' !== ( isset( $settings['zenblog_filter'] ) ? $settings['zenblog_filter'] : '' ) ) {
			return;
		}

		$taxonomies = $this->filter_taxonomies( $settings );
		$style      = isset( $settings['zenblog_filter_style'] ) ? $settings['zenblog_filter_style'] : 'links';
		$has_search = 'yes' === ( isset( $settings['zenblog_search'] ) ? $settings['zenblog_search'] : '' );
		$has_sort   = 'yes' === ( isset( $settings['zenblog_sort'] ) ? $settings['zenblog_sort'] : '' );

		if ( ! $has_search && ! $has_sort && empty( $taxonomies ) ) {
			return;
		}

		// The form posts back to the page it is on, so no-JS submission simply
		// reloads with the chosen state in the query string.
		$action = remove_query_arg(
			array_merge(
				array( $this->search_arg( $uid ), $this->sort_arg( $uid ), self::page_arg( $uid ) ),
				array_map(
					function ( $t ) use ( $uid ) {
						return $this->tax_arg( $uid, $t );
					},
					$taxonomies
				)
			)
		);
		?>
		<form class="zenblog__filters" method="get" action="<?php echo esc_url( $action ); ?>"
			aria-label="<?php echo esc_attr__( 'Search and filter posts', 'zen-blogger' ); ?>">

			<div class="zenblog__filter-row">
				<?php
				if ( $has_search ) {
					$this->render_search_field( $settings, $state, $uid );
				}

				foreach ( $taxonomies as $taxonomy ) {
					$this->render_taxonomy_group( $settings, $state, $uid, $taxonomy, $style );
				}

				if ( $has_sort ) {
					$this->render_sort_field( $settings, $state, $uid );
				}
				?>

				<button type="submit" class="zenblog__filter-submit">
					<?php echo esc_html( $this->text_or( $settings, 'zenblog_filter_submit_text', __( 'Apply', 'zen-blogger' ) ) ); ?>
				</button>

				<?php if ( 'yes' === ( isset( $settings['zenblog_filter_clear'] ) ? $settings['zenblog_filter_clear'] : '' ) ) : ?>
					<?php
					/*
					 * Always rendered, hidden while nothing is filtered. Rendering it
					 * only when a filter is active would mean it never appears after an
					 * AJAX update, because the form is not re-rendered.
					 */
					?>
					<a class="zenblog__filter-reset" href="<?php echo esc_url( $action ); ?>" <?php echo $this->filter_active( $state ) ? '' : 'hidden'; ?>>
						<?php echo esc_html( $this->text_or( $settings, 'zenblog_filter_clear_text', __( 'Clear filters', 'zen-blogger' ) ) ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( 'yes' === ( isset( $settings['zenblog_show_count'] ) ? $settings['zenblog_show_count'] : '' ) ) : ?>
				<p class="zenblog__result-count" data-zenblog-count>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of matching posts. */
							_n( '%s post', '%s posts', $total, 'zen-blogger' ),
							number_format_i18n( $total )
						)
					);
					?>
				</p>
			<?php endif; ?>
		</form>
		<?php
	}

	/**
	 * Read a text setting, falling back when the user cleared it.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $key      Setting key.
	 * @param string $fallback Fallback string.
	 * @return string
	 */
	private function text_or( array $settings, $key, $fallback ) {
		$value = isset( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';

		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Search input.
	 *
	 * @param array  $settings Widget settings.
	 * @param array  $state    Filter state.
	 * @param string $uid      Widget DOM id.
	 * @return void
	 */
	private function render_search_field( array $settings, array $state, $uid ) {
		$id    = $uid . '-search';
		$label = $this->text_or( $settings, 'zenblog_search_label', __( 'Search posts', 'zen-blogger' ) );
		?>
		<div class="zenblog__filter-field zenblog__filter-field--search">
			<?php // A real label, always present. A placeholder is not a label. ?>
			<label class="zenblog__sr" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input
				type="search"
				id="<?php echo esc_attr( $id ); ?>"
				class="zenblog__search-input"
				name="<?php echo esc_attr( $this->search_arg( $uid ) ); ?>"
				value="<?php echo esc_attr( $state['search'] ); ?>"
				placeholder="<?php echo esc_attr( $this->text_or( $settings, 'zenblog_search_placeholder', __( 'Search posts…', 'zen-blogger' ) ) ); ?>"
				autocomplete="off"
			/>
		</div>
		<?php
	}

	/**
	 * Sort dropdown.
	 *
	 * @param array  $settings Widget settings.
	 * @param array  $state    Filter state.
	 * @param string $uid      Widget DOM id.
	 * @return void
	 */
	private function render_sort_field( array $settings, array $state, $uid ) {
		$id = $uid . '-sort';
		?>
		<div class="zenblog__filter-field zenblog__filter-field--sort">
			<label class="zenblog__filter-label" for="<?php echo esc_attr( $id ); ?>">
				<?php echo esc_html( $this->text_or( $settings, 'zenblog_sort_label', __( 'Sort by', 'zen-blogger' ) ) ); ?>
			</label>
			<select id="<?php echo esc_attr( $id ); ?>" class="zenblog__select" name="<?php echo esc_attr( $this->sort_arg( $uid ) ); ?>">
				<?php foreach ( $this->sort_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $state['sort'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * One taxonomy's controls.
	 *
	 * @param array  $settings Widget settings.
	 * @param array  $state    Filter state.
	 * @param string $uid      Widget DOM id.
	 * @param string $taxonomy Taxonomy name.
	 * @param string $style    links | checkbox | dropdown.
	 * @return void
	 */
	private function render_taxonomy_group( array $settings, array $state, $uid, $taxonomy, $style ) {
		$object = get_taxonomy( $taxonomy );
		$label  = $object ? $object->label : $taxonomy;

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => 'yes' !== ( isset( $settings['zenblog_filter_empty_terms'] ) ? $settings['zenblog_filter_empty_terms'] : '' ),
				'number'     => isset( $settings['zenblog_filter_limit'] ) ? max( 1, (int) $settings['zenblog_filter_limit'] ) : 12,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$selected = isset( $state['terms'][ $taxonomy ] ) ? $state['terms'][ $taxonomy ] : array();
		$counts   = 'yes' === ( isset( $settings['zenblog_filter_counts'] ) ? $settings['zenblog_filter_counts'] : '' );
		$arg      = $this->tax_arg( $uid, $taxonomy );
		$group_id = $uid . '-' . preg_replace( '/[^a-z0-9_]/i', '', $taxonomy );

		if ( 'dropdown' === $style ) {
			?>
			<div class="zenblog__filter-field zenblog__filter-field--select">
				<label class="zenblog__filter-label" for="<?php echo esc_attr( $group_id ); ?>"><?php echo esc_html( $label ); ?></label>
				<select id="<?php echo esc_attr( $group_id ); ?>" class="zenblog__select" name="<?php echo esc_attr( $arg ); ?>">
					<option value=""><?php echo esc_html( $this->text_or( $settings, 'zenblog_filter_all_label', __( 'All', 'zen-blogger' ) ) ); ?></option>
					<?php foreach ( $terms as $term ) : ?>
						<option value="<?php echo esc_attr( (string) (int) $term->term_id ); ?>" <?php selected( in_array( (int) $term->term_id, $selected, true ) ); ?>>
							<?php
							echo esc_html( $counts ? sprintf( '%s (%s)', $term->name, number_format_i18n( $term->count ) ) : $term->name );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
			return;
		}

		if ( 'checkbox' === $style ) {
			?>
			<fieldset class="zenblog__filter-field zenblog__filter-field--checks">
				<legend class="zenblog__filter-label"><?php echo esc_html( $label ); ?></legend>
				<?php foreach ( $terms as $term ) : ?>
					<?php $cid = $group_id . '-' . (int) $term->term_id; ?>
					<span class="zenblog__check">
						<input type="checkbox" id="<?php echo esc_attr( $cid ); ?>"
							name="<?php echo esc_attr( $arg ); ?>[]"
							value="<?php echo esc_attr( (string) (int) $term->term_id ); ?>"
							<?php checked( in_array( (int) $term->term_id, $selected, true ) ); ?> />
						<label for="<?php echo esc_attr( $cid ); ?>">
							<?php echo esc_html( $term->name ); ?>
							<?php if ( $counts ) : ?>
								<span class="zenblog__filter-count"><?php echo esc_html( number_format_i18n( $term->count ) ); ?></span>
							<?php endif; ?>
						</label>
					</span>
				<?php endforeach; ?>
			</fieldset>
			<?php
			return;
		}

		/*
		 * Button style. These are radio inputs with a visible label, not <a>
		 * elements dressed as buttons: a single-choice filter IS a radio group,
		 * so arrow keys move between options and the state is announced as
		 * "selected" rather than as a link the user has to guess at.
		 */
		$current = $selected ? (int) $selected[0] : 0;
		?>
		<fieldset class="zenblog__filter-field zenblog__filter-field--links">
			<legend class="zenblog__sr"><?php echo esc_html( $label ); ?></legend>
			<span class="zenblog__check zenblog__check--pill">
				<input type="radio" id="<?php echo esc_attr( $group_id ); ?>-all" name="<?php echo esc_attr( $arg ); ?>" value="" <?php checked( 0, $current ); ?> />
				<label for="<?php echo esc_attr( $group_id ); ?>-all"><?php echo esc_html( $this->text_or( $settings, 'zenblog_filter_all_label', __( 'All', 'zen-blogger' ) ) ); ?></label>
			</span>
			<?php foreach ( $terms as $term ) : ?>
				<?php $rid = $group_id . '-' . (int) $term->term_id; ?>
				<span class="zenblog__check zenblog__check--pill">
					<input type="radio" id="<?php echo esc_attr( $rid ); ?>" name="<?php echo esc_attr( $arg ); ?>"
						value="<?php echo esc_attr( (string) (int) $term->term_id ); ?>"
						<?php checked( (int) $term->term_id, $current ); ?> />
					<label for="<?php echo esc_attr( $rid ); ?>">
						<?php echo esc_html( $term->name ); ?>
						<?php if ( $counts ) : ?>
							<span class="zenblog__filter-count"><?php echo esc_html( number_format_i18n( $term->count ) ); ?></span>
						<?php endif; ?>
					</label>
				</span>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}
}
