<?php
/**
 * REST endpoint backing the Posts widget's AJAX pagination and filters.
 *
 * @package Zen_Blogger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Serves paginated / filtered post cards for a specific placed widget.
 */
final class Zen_Blogger_Rest {

	/**
	 * REST namespace.
	 */
	const NS = 'zen-blogger/v1';

	/**
	 * Hook the route registration.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register the posts route.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/posts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_posts' ),
				// Public data only: this returns exactly the published posts the
				// widget already renders on a public page, so no capability is
				// required. It is read-only and takes no user input beyond a page
				// number and a term id, both validated below.
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_id'    => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'element_id' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'paged'      => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'query'      => array(
						'type'              => 'string',
						'default'           => '',
						'description'       => 'Raw filter query string; re-validated server-side against the widget settings.',

						/*
						 * This value is a URL-encoded query string, and the two
						 * obvious sanitisers both corrupt it:
						 *
						 *   wp_kses_post()        turns "&" into "&amp;", so
						 *                         parse_str() sees one key instead
						 *                         of several and every filter after
						 *                         the first is silently dropped.
						 *   sanitize_text_field() strips percent-encoded octets, so
						 *                         a non-Latin search term ("%E1%83…")
						 *                         is destroyed.
						 *
						 * So it is restricted to the characters a query string is
						 * made of, and nothing else. The real validation happens in
						 * parse_filter_query(): term ids are checked against the
						 * widget's own taxonomies, the sort key against a fixed
						 * list, and the search string is sanitised and length-capped
						 * after parse_str() has decoded it.
						 */
						'sanitize_callback' => array( __CLASS__, 'sanitize_query' ),
					),
					'context_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'description'       => 'Post the widget is being displayed for, when that is not the post it is stored in.',
						'sanitize_callback' => 'absint',
					),
					'context'    => array(
						'type'              => 'string',
						'default'           => '',
						'description'       => 'Identity of the archive being displayed, re-validated against real objects before use.',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'dedupe'     => array(
						'type'              => 'string',
						'default'           => '',
						'description'       => 'Comma-separated IDs already shown by other widgets on the page; narrows the result only.',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page_url'   => array(
						'type'              => 'string',
						'default'           => '',
						'description'       => 'URL of the page the widget is on; re-validated against this site before use.',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);
	}

	/**
	 * Restrict the filter query string to query-string characters.
	 *
	 * Deliberately structural only — it keeps the separators and percent-encoding
	 * intact so parse_str() can do its job, and leaves meaning-level validation to
	 * parse_filter_query().
	 *
	 * @param mixed $value Raw parameter.
	 * @return string
	 */
	public static function sanitize_query( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		// Cap the length so a pathological query cannot be used to make the
		// server do a lot of parsing work.
		$value = substr( $value, 0, 2000 );

		$clean = preg_replace( '/[^A-Za-z0-9_\-\[\]=&%.,+~]/', '', $value );

		// preg_replace() returns null if the engine fails — on a backtrack limit,
		// say. A sanitiser that hands back null instead of a string is how a
		// "cleaned" value ends up being neither cleaned nor a string, so failure
		// drops the filter entirely rather than passing anything through.
		return is_string( $clean ) ? $clean : '';
	}

	/**
	 * Return rendered cards for a page of the widget's query.
	 *
	 * Settings are read from the page's own _elementor_data rather than accepted
	 * from the request. That is the whole security model here: a caller can only
	 * ask for "page N of the query this widget was configured with", never define
	 * a query of their own.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_posts( $request ) {
		$post_id    = (int) $request->get_param( 'post_id' );
		$element_id = (string) $request->get_param( 'element_id' );
		$paged      = max( 1, (int) $request->get_param( 'paged' ) );
		$query      = (string) $request->get_param( 'query' );

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== get_post_status( $post ) ) {
			return new WP_Error( 'zenblog_not_found', __( 'Content not found.', 'zen-blogger' ), array( 'status' => 404 ) );
		}

		if ( post_password_required( $post ) ) {
			return new WP_Error( 'zenblog_forbidden', __( 'Content not available.', 'zen-blogger' ), array( 'status' => 403 ) );
		}

		$element = self::find_element( $post_id, $element_id );

		if ( ! $element ) {
			return new WP_Error( 'zenblog_no_widget', __( 'Widget not found on this page.', 'zen-blogger' ), array( 'status' => 404 ) );
		}

		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance->elements_manager ) ) {
			return new WP_Error( 'zenblog_no_elementor', __( 'Elementor is not available.', 'zen-blogger' ), array( 'status' => 500 ) );
		}

		// The instanceof check below silently returns false for an undeclared class,
		// which would look like "unsupported widget" rather than a load-order bug.
		if ( ! class_exists( 'Zen_Blogger_Posts' ) ) {
			require_once ZENBLOG_PATH . 'includes/widgets/class-zen-blogger-posts.php';
		}

		// Render in the context of the page the widget lives on, so "current post"
		// options and Theme Builder context behave the same as a normal page load.
		$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $element );

		if ( ! $widget instanceof Zen_Blogger_Posts ) {
			return new WP_Error( 'zenblog_bad_widget', __( 'Unsupported widget.', 'zen-blogger' ), array( 'status' => 400 ) );
		}

		/*
		 * The filter state is parsed from this string by the widget itself, using
		 * exactly the same validation the front end uses on $_GET — term ids are
		 * checked against the widget's configured taxonomies, the sort key against
		 * a fixed list. A caller cannot define a query of its own.
		 */
		$state = $widget->parse_filter_query( $query );

		/*
		 * Where paging links in the returned markup should point. It arrives from
		 * the client, so it is put through the same check core uses before it will
		 * send anyone anywhere: off-site values are discarded, not followed. The
		 * fallback is the document's own permalink, which is right for an ordinary
		 * page and merely unhelpful for a theme template.
		 */
		$page_url = wp_validate_redirect( (string) $request->get_param( 'page_url' ), '' );

		if ( '' === $page_url ) {
			$page_url = (string) get_permalink( $post_id );
		}

		/*
		 * Restore the post the widget was being displayed for. "Related to the
		 * current post" and "Exclude current post" both ask get_the_ID(), and in a
		 * REST request there is no current post at all — so page two came back
		 * built from a different query than page one, quietly repeating or
		 * dropping posts.
		 */
		$restore = self::enter_context( (int) $request->get_param( 'context_id' ) );

		// Current Query needs the listing itself put back, not just a post.
		$restore_query = self::enter_query_context( (string) $request->get_param( 'context' ) );

		/*
		 * IDs the page has already shown. Client-supplied, but it can only ever
		 * REMOVE posts from a list that is public anyway — the worst a caller can
		 * do with it is show itself fewer posts. Capped so it cannot be used to
		 * build an enormous NOT IN.
		 */
		$dedupe = array_slice(
			array_filter(
				array_map( 'absint', explode( ',', (string) $request->get_param( 'dedupe' ) ) )
			),
			0,
			500
		);

		$response = $widget->render_ajax_page( $paged, $state, $page_url, $dedupe );

		$restore_query();
		$restore();

		return rest_ensure_response( $response );
	}

	/**
	 * Rebuild the archive the widget is displayed on, returning the undo.
	 *
	 * The descriptor names a thing — this term, this author, this search — and
	 * every branch below checks it against a real object before building
	 * anything. A caller cannot hand over query arguments; the worst it can do is
	 * name a different archive it could already have visited.
	 *
	 * @param string $descriptor JSON identity sent by the widget.
	 * @return callable Restores the previous globals.
	 */
	private static function enter_query_context( $descriptor ) {
		global $wp_query, $wp_the_query;

		$previous     = $wp_query;
		$previous_the = $wp_the_query;

		$restore = static function () use ( $previous, $previous_the ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring what this function replaced.
			$GLOBALS['wp_query'] = $previous;
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring what this function replaced.
			$GLOBALS['wp_the_query'] = $previous_the;
		};

		if ( '' === $descriptor ) {
			return $restore;
		}

		$context = json_decode( $descriptor, true );

		if ( ! is_array( $context ) || empty( $context['type'] ) ) {
			return $restore;
		}

		$vars = null;

		switch ( $context['type'] ) {
			case 'term':
				$term = get_term( isset( $context['id'] ) ? (int) $context['id'] : 0 );

				// The taxonomy has to match the one the widget said it was, and be
				// one with public archives at all.
				if ( $term instanceof WP_Term
					&& isset( $context['tax'] )
					&& $term->taxonomy === $context['tax']
					&& is_taxonomy_viewable( $term->taxonomy ) ) {
					$vars = array(
						'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Reproducing the page's own archive.
							array(
								'taxonomy' => $term->taxonomy,
								'field'    => 'term_id',
								'terms'    => array( (int) $term->term_id ),
							),
						),
					);
				}
				break;

			case 'author':
				$author = isset( $context['id'] ) ? (int) $context['id'] : 0;

				if ( $author && get_user_by( 'id', $author ) ) {
					$vars = array( 'author' => $author );
				}
				break;

			case 'search':
				$search = sanitize_text_field( isset( $context['s'] ) ? (string) $context['s'] : '' );

				if ( '' !== $search ) {
					$vars = array( 's' => $search );
				}
				break;

			case 'post_type':
				$type = sanitize_key( isset( $context['pt'] ) ? (string) $context['pt'] : '' );

				if ( $type && is_post_type_viewable( $type ) ) {
					$vars = array( 'post_type' => $type );
				}
				break;

			case 'date':
				$vars = array_filter(
					array(
						'year'     => isset( $context['y'] ) ? (int) $context['y'] : 0,
						'monthnum' => isset( $context['m'] ) ? (int) $context['m'] : 0,
						'day'      => isset( $context['d'] ) ? (int) $context['d'] : 0,
					)
				);

				if ( empty( $vars ) ) {
					$vars = null;
				}
				break;

			case 'home':
				$vars = array();
				break;
		}

		if ( null === $vars ) {
			return $restore;
		}

		/*
		 * Deliberately cheap: only the parsed query_vars are needed, and the
		 * widget throws away posts_per_page and fields when it inherits them.
		 */
		$context_query = new WP_Query(
			array_merge(
				$vars,
				array(
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			)
		);

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Deliberate, undone by the returned callable.
		$GLOBALS['wp_query'] = $context_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Deliberate, undone by the returned callable.
		$GLOBALS['wp_the_query'] = $context_query;

		return $restore;
	}

	/**
	 * Set up the displayed-post context, returning the undo.
	 *
	 * @param int $context_id Post being displayed, 0 for none.
	 * @return callable Restores the previous global state.
	 */
	private static function enter_context( $context_id ) {
		global $post;

		$previous = $post;
		$noop     = static function () use ( $previous ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the value this function replaced.
			$GLOBALS['post'] = $previous;
			wp_reset_postdata();
		};

		if ( ! $context_id ) {
			return $noop;
		}

		$context = get_post( $context_id );

		// Only a post a visitor could already be reading. Anything else — a draft,
		// a password-protected post — is not a context this endpoint will adopt.
		if ( ! $context || 'publish' !== get_post_status( $context ) || post_password_required( $context ) ) {
			return $noop;
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Deliberate, and undone by the returned callable.
		$GLOBALS['post'] = $context;
		setup_postdata( $context );

		return $noop;
	}

	/**
	 * Locate a widget's element data inside a page's _elementor_data.
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $element_id Elementor element ID.
	 * @return array|null
	 */
	private static function find_element( $post_id, $element_id ) {
		$raw = get_post_meta( $post_id, '_elementor_data', true );

		if ( empty( $raw ) ) {
			return null;
		}

		$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;

		if ( ! is_array( $data ) ) {
			return null;
		}

		return self::walk( $data, $element_id );
	}

	/**
	 * Depth-first search for an element by ID.
	 *
	 * @param array  $nodes      Element nodes.
	 * @param string $element_id Target ID.
	 * @return array|null
	 */
	private static function walk( array $nodes, $element_id ) {
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			if ( isset( $node['id'] ) && (string) $node['id'] === $element_id ) {
				if ( isset( $node['widgetType'] ) && 'zen-blogger-posts' === $node['widgetType'] ) {
					return $node;
				}
				return null;
			}

			if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
				$found = self::walk( $node['elements'], $element_id );
				if ( $found ) {
					return $found;
				}
			}
		}

		return null;
	}
}
