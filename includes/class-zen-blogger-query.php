<?php
/**
 * Turns the widget's query controls into a WP_Query.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Query builder for the Zen Blogger carousel.
 */
final class Zen_Blogger_Query {

	/**
	 * Post IDs already rendered by a Zen Blogger widget on this request.
	 *
	 * Used by the "Avoid duplicates" control so two carousels on the same page do not
	 * show the same posts.
	 *
	 * @var int[]
	 */
	private static $rendered = array();

	/**
	 * Record post IDs as rendered.
	 *
	 * @param int[] $ids Post IDs.
	 * @return void
	 */
	public static function mark_rendered( array $ids ) {
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 && ! in_array( $id, self::$rendered, true ) ) {
				self::$rendered[] = $id;
			}
		}
	}

	/**
	 * Post IDs already rendered on this request.
	 *
	 * @return int[]
	 */
	public static function rendered() {
		return self::$rendered;
	}

	/**
	 * Build and run the query for a widget's settings.
	 *
	 * @param array  $settings  Widget settings from get_settings_for_display().
	 * @param string $widget_id Elementor element ID, exposed to the filter for targeting.
	 * @return WP_Query
	 */
	public static function run( array $settings, $widget_id = '' ) {
		$args = self::build_args( $settings );

		/**
		 * Filter the arguments used for a Zen Blogger carousel query.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $args      WP_Query arguments.
		 * @param array  $settings  Widget settings.
		 * @param string $widget_id Elementor element ID.
		 */
		$args = apply_filters( 'zenblog_query_args', $args, $settings, $widget_id );

		return new WP_Query( $args );
	}

	/**
	 * Translate widget settings into WP_Query arguments.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	private static function build_args( array $settings ) {
		$source    = isset( $settings['zenblog_source'] ) ? $settings['zenblog_source'] : 'latest';
		$post_type = ! empty( $settings['zenblog_post_type'] ) ? sanitize_key( $settings['zenblog_post_type'] ) : 'post';
		$per_page  = isset( $settings['zenblog_per_page'] ) ? (int) $settings['zenblog_per_page'] : 9;
		$per_page  = max( 1, min( 100, $per_page ) );

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'suppress_filters'    => false,
		);

		if ( 'current' === $source ) {
			return self::current_query_args( $args );
		}

		if ( 'manual' === $source ) {
			$ids = self::int_list( isset( $settings['zenblog_manual_ids'] ) ? $settings['zenblog_manual_ids'] : array() );

			if ( empty( $ids ) ) {
				// An empty manual selection must return nothing, not "everything".
				$args['post__in'] = array( 0 );
				return $args;
			}

			$args['post__in']       = $ids;
			$args['orderby']        = 'post__in';
			$args['posts_per_page'] = count( $ids );

			// No unset( $args['order'] ) here: this branch returns before
			// apply_order() runs, so nothing has ever set it — and 'order' is
			// ignored for a post__in ordering regardless.
			return $args;
		}

		$offset = isset( $settings['zenblog_offset'] ) ? max( 0, (int) $settings['zenblog_offset'] ) : 0;
		if ( $offset > 0 ) {
			$args['offset'] = $offset;
		}

		$tax_query = self::tax_query( $settings, $post_type );

		if ( 'related' === $source ) {
			$related = self::related_tax_query( $settings, $post_type );
			if ( empty( $related ) ) {
				$args['post__in'] = array( 0 );
				return $args;
			}
			$tax_query = array_merge( $tax_query, $related );
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$relation  = ( isset( $settings['zenblog_tax_relation'] ) && 'OR' === $settings['zenblog_tax_relation'] ) ? 'OR' : 'AND';
				$tax_query = array_merge( array( 'relation' => $relation ), $tax_query );
			}
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- User-configured taxonomy filter is the feature.
		}

		self::apply_authors( $args, $settings );
		self::apply_dates( $args, $settings );
		self::apply_order( $args, $settings );
		self::apply_sticky( $args, $settings );
		self::apply_exclusions( $args, $settings );

		return $args;
	}

	/**
	 * Reuse the main query's vars, overriding only what the widget controls.
	 *
	 * @param array $args Base arguments.
	 * @return array
	 */
	private static function current_query_args( array $args ) {
		global $wp_query;

		if ( ! $wp_query instanceof WP_Query ) {
			return $args;
		}

		$vars = $wp_query->query_vars;

		unset( $vars['posts_per_page'], $vars['paged'], $vars['offset'], $vars['fields'] );

		return array_merge(
			$vars,
			array(
				'posts_per_page' => $args['posts_per_page'],
				'no_found_rows'  => true,
			)
		);
	}

	/**
	 * Build the taxonomy clauses from the per-taxonomy include/exclude controls.
	 *
	 * @param array  $settings  Widget settings.
	 * @param string $post_type Selected post type.
	 * @return array
	 */
	private static function tax_query( array $settings, $post_type ) {
		$clauses = array();

		foreach ( self::taxonomies_for( $post_type ) as $taxonomy ) {
			$include = self::int_list( isset( $settings[ 'zenblog_tax_' . $taxonomy ] ) ? $settings[ 'zenblog_tax_' . $taxonomy ] : array() );
			$exclude = self::int_list( isset( $settings[ 'zenblog_tax_exclude_' . $taxonomy ] ) ? $settings[ 'zenblog_tax_exclude_' . $taxonomy ] : array() );

			if ( ! empty( $include ) ) {
				$clauses[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $include,
				);
			}

			if ( ! empty( $exclude ) ) {
				$clauses[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $exclude,
					'operator' => 'NOT IN',
				);
			}
		}

		return $clauses;
	}

	/**
	 * Build the taxonomy clause that matches the current post's terms.
	 *
	 * @param array  $settings  Widget settings.
	 * @param string $post_type Selected post type.
	 * @return array
	 */
	private static function related_tax_query( array $settings, $post_type ) {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return array();
		}

		$taxonomy   = ! empty( $settings['zenblog_related_taxonomy'] ) ? sanitize_key( $settings['zenblog_related_taxonomy'] ) : '';
		$taxonomies = self::taxonomies_for( $post_type );

		if ( ! $taxonomy || ! in_array( $taxonomy, $taxonomies, true ) ) {
			$taxonomy = reset( $taxonomies );
		}

		if ( ! $taxonomy ) {
			return array();
		}

		$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => array_map( 'intval', $terms ),
			),
		);
	}

	/**
	 * Apply author include/exclude.
	 *
	 * @param array $args     Query args, by reference.
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private static function apply_authors( array &$args, array $settings ) {
		$include = self::int_list( isset( $settings['zenblog_authors'] ) ? $settings['zenblog_authors'] : array() );
		$exclude = self::int_list( isset( $settings['zenblog_authors_exclude'] ) ? $settings['zenblog_authors_exclude'] : array() );

		if ( ! empty( $include ) ) {
			$args['author__in'] = $include;
		}

		if ( ! empty( $exclude ) ) {
			$args['author__not_in'] = $exclude;
		}
	}

	/**
	 * Apply the date range control.
	 *
	 * @param array $args     Query args, by reference.
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private static function apply_dates( array &$args, array $settings ) {
		$range = isset( $settings['zenblog_date_range'] ) ? $settings['zenblog_date_range'] : 'any';

		if ( 'any' === $range || '' === $range ) {
			return;
		}

		if ( 'custom' === $range ) {
			$clause = array( 'inclusive' => true );

			if ( ! empty( $settings['zenblog_date_after'] ) ) {
				$clause['after'] = sanitize_text_field( $settings['zenblog_date_after'] );
			}
			if ( ! empty( $settings['zenblog_date_before'] ) ) {
				$clause['before'] = sanitize_text_field( $settings['zenblog_date_before'] );
			}

			if ( count( $clause ) > 1 ) {
				$args['date_query'] = array( $clause );
			}

			return;
		}

		$map = array(
			'day'     => '-1 day',
			'week'    => '-1 week',
			'month'   => '-1 month',
			'quarter' => '-3 months',
			'year'    => '-1 year',
		);

		if ( isset( $map[ $range ] ) ) {
			$args['date_query'] = array(
				array(
					'after'     => $map[ $range ],
					'inclusive' => true,
				),
			);
		}
	}

	/**
	 * Apply ordering.
	 *
	 * @param array $args     Query args, by reference.
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private static function apply_order( array &$args, array $settings ) {
		$allowed = array( 'date', 'modified', 'title', 'menu_order', 'comment_count', 'rand', 'meta_value', 'meta_value_num' );
		$orderby = isset( $settings['zenblog_orderby'] ) ? $settings['zenblog_orderby'] : 'date';

		if ( ! in_array( $orderby, $allowed, true ) ) {
			$orderby = 'date';
		}

		if ( 'meta_value' === $orderby || 'meta_value_num' === $orderby ) {
			$meta_key = isset( $settings['zenblog_meta_key'] ) ? sanitize_text_field( $settings['zenblog_meta_key'] ) : '';

			if ( '' === $meta_key ) {
				$orderby = 'date';
			} else {
				$args['meta_key'] = $meta_key; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Ordering by a custom field is the documented purpose of this control.
			}
		}

		$args['orderby'] = $orderby;

		if ( 'rand' !== $orderby ) {
			$args['order'] = ( isset( $settings['zenblog_order'] ) && 'ASC' === $settings['zenblog_order'] ) ? 'ASC' : 'DESC';
		}
	}

	/**
	 * Apply the sticky-post handling control.
	 *
	 * @param array $args     Query args, by reference.
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private static function apply_sticky( array &$args, array $settings ) {
		$mode = isset( $settings['zenblog_sticky'] ) ? $settings['zenblog_sticky'] : 'ignore';
		$ids  = array_map( 'intval', (array) get_option( 'sticky_posts', array() ) );

		if ( 'only' === $mode ) {
			$args['post__in']            = ! empty( $ids ) ? $ids : array( 0 );
			$args['ignore_sticky_posts'] = true;
			return;
		}

		if ( 'exclude' === $mode && ! empty( $ids ) ) {
			$args['post__not_in'] = isset( $args['post__not_in'] )
				? array_merge( $args['post__not_in'], $ids )
				: $ids;
			return;
		}

		if ( 'first' === $mode ) {
			// Let WP_Query float sticky posts to the top of the result set.
			$args['ignore_sticky_posts'] = false;
		}
	}

	/**
	 * Apply "exclude current post", "require featured image" and "avoid duplicates".
	 *
	 * @param array $args     Query args, by reference.
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private static function apply_exclusions( array &$args, array $settings ) {
		$not_in = isset( $args['post__not_in'] ) ? $args['post__not_in'] : array();

		if ( ! empty( $settings['zenblog_exclude_current'] ) && 'yes' === $settings['zenblog_exclude_current'] ) {
			$current = get_the_ID();
			if ( $current ) {
				$not_in[] = (int) $current;
			}
		}

		if ( ! empty( $settings['zenblog_avoid_duplicates'] ) && 'yes' === $settings['zenblog_avoid_duplicates'] ) {
			$not_in = array_merge( $not_in, self::rendered() );
		}

		$manual_exclude = self::int_list( isset( $settings['zenblog_exclude_ids'] ) ? $settings['zenblog_exclude_ids'] : array() );
		if ( ! empty( $manual_exclude ) ) {
			$not_in = array_merge( $not_in, $manual_exclude );
		}

		if ( ! empty( $not_in ) ) {
			$args['post__not_in'] = array_values( array_unique( array_map( 'intval', $not_in ) ) );
		}

		if ( ! empty( $settings['zenblog_require_thumbnail'] ) && 'yes' === $settings['zenblog_require_thumbnail'] ) {
			// meta_query is the only reliable way to require a featured image in core.
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Opt-in control; documented as a heavier query.
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			);
		}
	}

	/**
	 * Public taxonomies registered for a post type, excluding internal ones.
	 *
	 * @param string $post_type Post type slug.
	 * @return string[]
	 */
	public static function taxonomies_for( $post_type ) {
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		$names      = array();

		foreach ( $taxonomies as $taxonomy ) {
			if ( empty( $taxonomy->public ) || empty( $taxonomy->show_ui ) ) {
				continue;
			}
			$names[] = $taxonomy->name;
		}

		return $names;
	}

	/**
	 * Public post types that can be shown in the carousel.
	 *
	 * @return array<string,string> Slug => label.
	 */
	public static function post_types() {
		$types  = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);
		$output = array();

		foreach ( $types as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			$output[ $type->name ] = $type->label;
		}

		return $output;
	}

	/**
	 * Normalise a control value into a list of positive integers.
	 *
	 * @param mixed $value Raw control value.
	 * @return int[]
	 */
	private static function int_list( $value ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();

		foreach ( $value as $item ) {
			$id = (int) $item;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
