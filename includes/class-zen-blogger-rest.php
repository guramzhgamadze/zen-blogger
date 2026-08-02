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

		return preg_replace( '/[^A-Za-z0-9_\-\[\]=&%.,+~]/', '', $value );
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

		return rest_ensure_response( $widget->render_ajax_page( $paged, $state ) );
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
