<?php
/**
 * Exercises the Zen Blogger carousel widget against the typed stubs.
 */

// phpcs:disable

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

$failures = array();
$notices  = array();

set_error_handler(
	static function ( $no, $str, $file, $line ) use ( &$notices ) {
		$notices[] = sprintf( '[%s] %s (%s:%d)', $no, $str, basename( $file ), $line );
		return true;
	}
);

require __DIR__ . '/stubs.php';

define( 'ZENBLOG_VERSION', '1.0.0' );
define( 'ZENBLOG_PATH', 'C:/Users/Guram/Documents/zen-blogger/' );
define( 'ZENBLOG_URL', 'https://example.test/wp-content/plugins/zen-blogger/' );

require ZENBLOG_PATH . 'includes/class-zen-blogger-assets.php';
require ZENBLOG_PATH . 'includes/class-zen-blogger-query.php';
require ZENBLOG_PATH . 'includes/trait-zen-blogger-card.php';
require ZENBLOG_PATH . 'includes/trait-zen-blogger-filter.php';
require ZENBLOG_PATH . 'includes/widgets/class-zen-blogger-carousel.php';

function ok( $label ) { echo "  PASS  $label\n"; }
function fail( $label, $detail = '' ) {
	global $failures;
	$failures[] = $label . ( $detail ? " — $detail" : '' );
	echo "  FAIL  $label" . ( $detail ? " — $detail" : '' ) . "\n";
}

/* ------------------------------------------------------------------ */
echo "\n== 1. Class declaration & typed signatures ==\n";
/* ------------------------------------------------------------------ */

$widget = new Zen_Blogger_Carousel();
ok( 'widget instantiates (no typed-signature fatal)' );

/*
 * Regression: Elementor calls get_style_depends() / get_script_depends() on widget
 * TYPES as well as instances — Widgets_Manager::enqueue_widgets_styles() does it in
 * the editor preview. A type has no settings, so anything that reads settings here
 * fatals inside Elementor and white-screens every page. Reported from production as
 * "sanitize_settings(): Argument #1 ($settings) must be of type array, null given".
 */
$fresh = new Zen_Blogger_Carousel(); // no settings — exactly a registered type
foreach ( array( 'get_style_depends', 'get_script_depends' ) as $method ) {
	try {
		$handles = $fresh->$method();
		if ( is_array( $handles ) && ! empty( $handles ) ) {
			ok( "$method() is safe on a widget type with no settings" );
		} else {
			fail( "$method() returned nothing", wp_json_encode( $handles ) );
		}
	} catch ( \Throwable $e ) {
		fail( "$method() fatals in type context", $e->getMessage() );
	}
}

// And prove the harness can actually catch it: reading settings in that context must throw.
try {
	$fresh->get_settings_for_display();
	fail( 'harness cannot reproduce the production fatal — test is meaningless' );
} catch ( \Throwable $e ) {
	ok( 'harness reproduces the type-context TypeError, so the check above is real' );
}

$ref = new ReflectionClass( $widget );
foreach ( array( 'has_widget_inner_wrapper' => 'bool', 'is_dynamic_content' => 'bool' ) as $method => $type ) {
	$m = $ref->getMethod( $method );
	$rt = $m->getReturnType();
	if ( $rt && (string) $rt === $type ) {
		ok( "$method() returns $type" );
	} else {
		fail( "$method() return type", $rt ? (string) $rt : 'none' );
	}
}

$m = $ref->getMethod( 'has_widget_inner_wrapper' );
$m->setAccessible( true );
if ( false === $m->invoke( $widget ) ) { ok( 'has_widget_inner_wrapper() === false' ); }
else { fail( 'has_widget_inner_wrapper() should be false' ); }

$m = $ref->getMethod( 'is_dynamic_content' );
$m->setAccessible( true );
if ( true === $m->invoke( $widget ) ) { ok( 'is_dynamic_content() === true (query output is not cacheable)' ); }
else { fail( 'is_dynamic_content() should be true' ); }

/* ------------------------------------------------------------------ */
echo "\n== 2. register_controls() ==\n";
/* ------------------------------------------------------------------ */

$GLOBALS['zenblog_is_admin'] = true;
$widget->run_register_controls();
$controls = $widget->registered_controls;
printf( "  %d controls registered\n", count( $controls ) );

if ( count( $controls ) > 80 ) { ok( 'control count is substantial' ); }
else { fail( 'too few controls', (string) count( $controls ) ); }

// Golden rule: no COLOR control may carry a default the user cannot switch off.
$colour_defaults = array();
foreach ( $controls as $id => $args ) {
	if ( ( $args['type'] ?? '' ) === Elementor\Controls_Manager::COLOR && isset( $args['default'] ) && '' !== $args['default'] ) {
		$colour_defaults[] = $id;
	}
}
if ( empty( $colour_defaults ) ) { ok( 'no COLOR control carries a default' ); }
else { fail( 'COLOR controls with defaults', implode( ', ', $colour_defaults ) ); }

// Every control must be prefixed.
$unprefixed = array();
foreach ( array_keys( $controls ) as $id ) {
	$name = str_replace( 'group:', '', $id );
	if ( 0 !== strpos( $name, 'zenblog_' ) ) {
		$unprefixed[] = $id;
	}
}
if ( empty( $unprefixed ) ) { ok( 'every control is prefixed zenblog_' ); }
else { fail( 'unprefixed controls', implode( ', ', $unprefixed ) ); }

// Selectors must never target the legacy inner wrapper.
$legacy = array();
foreach ( $controls as $id => $args ) {
	$blob = json_encode( array( $args['selectors'] ?? array(), $args['selector'] ?? '' ) );
	if ( false !== strpos( (string) $blob, 'elementor-widget-container' ) ) {
		$legacy[] = $id;
	}
}
if ( empty( $legacy ) ) { ok( 'no selector targets .elementor-widget-container' ); }
else { fail( 'legacy wrapper selectors', implode( ', ', $legacy ) ); }

// Clamp controls must offer a genuine "off".
foreach ( array( 'zenblog_title_lines', 'zenblog_excerpt_lines' ) as $id ) {
	if ( isset( $controls[ $id ]['options'][''] ) ) { ok( "$id offers an empty (no-limit) option" ); }
	else { fail( "$id has no empty option" ); }
}

/* ------------------------------------------------------------------ */
echo "\n== 3. Query building ==\n";
/* ------------------------------------------------------------------ */

$build = ( new ReflectionClass( 'Zen_Blogger_Query' ) )->getMethod( 'build_args' );
$build->setAccessible( true );

$cases = array(
	'latest defaults' => array(
		array( 'zenblog_source' => 'latest', 'zenblog_post_type' => 'post', 'zenblog_per_page' => 9 ),
		static function ( $a ) { return 'post' === $a['post_type'] && 9 === $a['posts_per_page']; },
	),
	'empty manual selection returns nothing' => array(
		array( 'zenblog_source' => 'manual', 'zenblog_manual_ids' => array() ),
		static function ( $a ) { return array( 0 ) === $a['post__in']; },
	),
	'manual keeps chosen order' => array(
		array( 'zenblog_source' => 'manual', 'zenblog_manual_ids' => array( '7', '3', '9' ) ),
		static function ( $a ) { return array( 7, 3, 9 ) === $a['post__in'] && 'post__in' === $a['orderby'] && 3 === $a['posts_per_page']; },
	),
	'taxonomy include + exclude' => array(
		array(
			'zenblog_source'              => 'latest',
			'zenblog_post_type'           => 'post',
			'zenblog_tax_category'         => array( 5 ),
			'zenblog_tax_exclude_post_tag' => array( 6 ),
			'zenblog_tax_relation'         => 'OR',
		),
		static function ( $a ) {
			return isset( $a['tax_query'] ) && 'OR' === $a['tax_query']['relation'] && 3 === count( $a['tax_query'] );
		},
	),
	'meta ordering without a key falls back to date' => array(
		array( 'zenblog_source' => 'latest', 'zenblog_orderby' => 'meta_value', 'zenblog_meta_key' => '' ),
		static function ( $a ) { return 'date' === $a['orderby'] && ! isset( $a['meta_key'] ); },
	),
	'random order emits no order key' => array(
		array( 'zenblog_source' => 'latest', 'zenblog_orderby' => 'rand' ),
		static function ( $a ) { return 'rand' === $a['orderby'] && ! isset( $a['order'] ); },
	),
	'sticky: only' => array(
		array( 'zenblog_source' => 'latest', 'zenblog_sticky' => 'only' ),
		static function ( $a ) { return array( 101 ) === $a['post__in']; },
	),
	'sticky: exclude' => array(
		array( 'zenblog_source' => 'latest', 'zenblog_sticky' => 'exclude' ),
		static function ( $a ) { return in_array( 101, $a['post__not_in'], true ); },
	),
	'orderby injection is rejected' => array(
		array( 'zenblog_source' => 'latest', 'zenblog_orderby' => 'date; DROP TABLE wp_posts' ),
		static function ( $a ) { return 'date' === $a['orderby']; },
	),
	'per_page is clamped' => array(
		array( 'zenblog_source' => 'latest', 'zenblog_per_page' => 99999 ),
		static function ( $a ) { return 100 === $a['posts_per_page']; },
	),
	'featured-image requirement' => array(
		array( 'zenblog_source' => 'latest', 'zenblog_require_thumbnail' => 'yes' ),
		static function ( $a ) { return isset( $a['meta_query'][0]['key'] ) && '_thumbnail_id' === $a['meta_query'][0]['key']; },
	),
);

foreach ( $cases as $label => $case ) {
	list( $settings, $assert ) = $case;
	$args = $build->invoke( null, $settings );
	if ( $assert( $args ) ) { ok( $label ); }
	else { fail( $label, json_encode( $args ) ); }
}

/* ------------------------------------------------------------------ */
echo "\n== 4. render() across states ==\n";
/* ------------------------------------------------------------------ */


/**
 * Reproduce the settings array Elementor hands to get_settings_for_display():
 * a responsive control contributes _tablet and _mobile keys, seeded from
 * tablet_default / mobile_default when the theme author supplied them.
 */
function zenblog_default_settings( array $controls ) {
	$out = array();
	foreach ( $controls as $id => $args ) {
		if ( 0 === strpos( $id, 'group:' ) ) {
			continue;
		}
		$out[ $id ] = $args['default'] ?? '';
		if ( ! empty( $args['__responsive'] ) ) {
			$out[ $id . '_tablet' ] = $args['tablet_default'] ?? '';
			$out[ $id . '_mobile' ] = $args['mobile_default'] ?? '';
		}
	}
	return $out;
}

$base = zenblog_default_settings( $controls );

function render_with( Zen_Blogger_Carousel $w, array $settings, array $posts ) {
	$GLOBALS['zenblog_test_posts'] = $posts;
	$GLOBALS['zenblog_test_index'] = -1;
	$w->set_test_settings( $settings );
	ob_start();
	$w->run_render();
	return ob_get_clean();
}

$posts = array(
	array( 'ID' => 101, 'title' => "O'Reilly & <script>alert(1)</script>", 'thumb' => 55, 'sticky' => true,  'terms' => array( 'News & Views' ), 'excerpt' => '', 'content' => str_repeat( 'word ', 500 ), 'comments' => 3 ),
	array( 'ID' => 102, 'title' => 'Second post', 'thumb' => 0, 'sticky' => false, 'terms' => array(), 'excerpt' => 'A short excerpt.', 'content' => 'Body', 'comments' => 0 ),
	array( 'ID' => 103, 'title' => 'Third post', 'thumb' => 56, 'sticky' => false, 'terms' => array( 'Tech' ), 'excerpt' => '', 'content' => 'ქართული ტექსტი აქ', 'comments' => 1 ),
);

$html = render_with( $widget, $base, $posts );

$checks = array(
	'renders three slides'                 => 3 === substr_count( $html, 'class="zenblog__slide swiper-slide"' ),
	'root carries region + label'          => false !== strpos( $html, 'role="region"' ) && false !== strpos( $html, 'aria-label="Latest posts"' ),
	'slides carry carousel semantics'      => 3 === substr_count( $html, 'aria-roledescription="slide"' ),
	'slide labels are numbered'            => false !== strpos( $html, 'aria-label="1 of 3"' ) && false !== strpos( $html, 'aria-label="3 of 3"' ),
	'title is escaped, no raw script tag'  => false === strpos( $html, '<script>' ) && false !== strpos( $html, '&lt;script&gt;' ),
	'apostrophe is entity-encoded'         => false !== strpos( $html, '&#039;Reilly' ) || false !== strpos( $html, '&#39;Reilly' ),
	'first image is high priority'         => false !== strpos( $html, 'fetchpriority="high"' ),
	'3 visible slides => all 3 eager'      => false === strpos( $html, 'loading="lazy"' ) && 2 === substr_count( $html, 'loading="eager"' ),
	'post without a thumbnail has no img'  => 2 === substr_count( $html, '<img src="https://example.test/i.jpg"' ),
	'sticky badge only on the sticky post' => 1 === substr_count( $html, 'zenblog__badge' ),
	'options blob is valid JSON'           => (bool) json_decode( html_entity_decode( preg_match( '/data-zenblog-options="([^"]*)"/', $html, $m ) ? $m[1] : '{}', ENT_QUOTES ) ),
	'arrows are real buttons'              => 2 === substr_count( $html, '<button type="button" class="zenblog__arrow' ),
	'no play/pause while autoplay is off'  => false === strpos( $html, 'zenblog__playpause' ),
);

foreach ( $checks as $label => $result ) {
	$result ? ok( $label ) : fail( $label );
}

/* --- lazy-loading actually kicks in once slides fall outside the first view --- */
$one_up = render_with( $widget, array_merge( $base, array( 'zenblog_slides_per_view' => '1' ) ), $posts );
if ( 1 === substr_count( $one_up, 'fetchpriority="high"' ) && 1 === substr_count( $one_up, 'loading="lazy"' ) && 1 === substr_count( $one_up, 'loading="eager"' ) ) {
	ok( 'one slide visible => first image eager+priority, the rest lazy' );
} else {
	fail( 'lazy-loading boundary' );
}

$all_thumbs = array(
	array( 'ID' => 301, 'title' => 'A', 'thumb' => 1, 'terms' => array(), 'excerpt' => 'x', 'content' => 'x', 'comments' => 0 ),
	array( 'ID' => 302, 'title' => 'B', 'thumb' => 2, 'terms' => array(), 'excerpt' => 'x', 'content' => 'x', 'comments' => 0 ),
	array( 'ID' => 303, 'title' => 'C', 'thumb' => 3, 'terms' => array(), 'excerpt' => 'x', 'content' => 'x', 'comments' => 0 ),
);

$one_row  = render_with( $widget, array_merge( $base, array( 'zenblog_slides_per_view' => '1', 'zenblog_rows' => 1 ) ), $all_thumbs );
$two_rows = render_with( $widget, array_merge( $base, array( 'zenblog_slides_per_view' => '1', 'zenblog_rows' => 2 ) ), $all_thumbs );

if ( 2 === substr_count( $one_row, 'loading="lazy"' ) && 1 === substr_count( $two_rows, 'loading="lazy"' ) ) {
	ok( 'the eager window widens with the row count (1 row: 2 lazy, 2 rows: 1 lazy)' );
} else {
	fail( 'multi-row eager window', substr_count( $one_row, 'loading="lazy"' ) . ' / ' . substr_count( $two_rows, 'loading="lazy"' ) );
}

/* --- reading time must survive scripts str_word_count() cannot count --- */
$rt = render_with(
	$widget,
	array_merge( $base, array( 'zenblog_meta_items' => array( 'reading_time', 'comments', 'modified' ) ) ),
	array(
		array( 'ID' => 201, 'title' => 'Georgian', 'thumb' => 0, 'terms' => array(), 'excerpt' => 'x', 'content' => str_repeat( 'ქართული ტექსტი აქ ', 300 ), 'comments' => 2 ),
	)
);
if ( preg_match( '/(\d+) min read/', $rt, $m ) && (int) $m[1] > 1 ) {
	ok( 'Georgian content yields a sensible reading time (' . $m[1] . ' min), not 1' );
} else {
	fail( 'non-Latin reading time', $rt );
}
if ( false !== strpos( $rt, '2 comments' ) && false !== strpos( $rt, 'zenblog__meta-sep' ) ) {
	ok( 'comment count pluralises and separators render between items' );
} else {
	fail( 'meta items / separators' );
}

/* --- autoplay on: the play/pause control must appear --- */
$auto = array_merge( $base, array( 'zenblog_autoplay' => 'yes' ) );
$html_auto = render_with( $widget, $auto, $posts );
if ( false !== strpos( $html_auto, 'zenblog__playpause' )
	&& false !== strpos( $html_auto, 'Stop automatic slide show' )
	&& false === strpos( $html_auto, 'aria-pressed' ) ) {
	ok( 'autoplay renders a named play/pause button, no contradictory aria-pressed (WCAG 2.2.2 / APG)' );
} else {
	fail( 'play/pause button markup' );
}

/* --- every skin renders --- */
foreach ( array( 'classic', 'overlay', 'overlap', 'side', 'minimal', 'editorial' ) as $skin ) {
	$s = array_merge( $base, array( 'zenblog_skin' => $skin ) );
	$out = render_with( $widget, $s, $posts );
	$has_media = false !== strpos( $out, 'zenblog__media' );
	$expected  = 'minimal' !== $skin;

	if ( false !== strpos( $out, 'zenblog--skin-' . $skin ) && $has_media === $expected ) {
		ok( "skin: $skin" );
	} else {
		fail( "skin: $skin", $has_media ? 'has media' : 'no media' );
	}
}

/* --- every effect and pagination type produces valid options JSON --- */
foreach ( array( 'slide', 'fade', 'coverflow', 'cards', 'creative' ) as $effect ) {
	foreach ( array( '', 'bullets', 'dynamic', 'fraction', 'progressbar', 'scrollbar' ) as $pager ) {
		$s = array_merge( $base, array( 'zenblog_effect' => $effect, 'zenblog_pagination' => $pager ) );
		$out = render_with( $widget, $s, $posts );
		preg_match( '/data-zenblog-options="([^"]*)"/', $out, $m );
		$json = json_decode( html_entity_decode( $m[1] ?? '', ENT_QUOTES ), true );
		if ( ! is_array( $json ) || $json['effect'] !== $effect ) {
			fail( "options JSON for $effect/$pager" );
			continue 2;
		}
	}
}
ok( 'all 30 effect × pagination combinations emit valid options' );

/* --- empty result --- */
$empty = render_with( $widget, $base, array() );
if ( false !== strpos( $empty, 'zenblog__empty' ) && false === strpos( $empty, 'swiper' ) ) {
	ok( 'empty query renders the message and no carousel' );
} else {
	fail( 'empty query state' );
}

$silent = render_with( $widget, array_merge( $base, array( 'zenblog_empty_message' => '' ) ), array() );
if ( '' === trim( $silent ) ) { ok( 'empty message cleared renders absolutely nothing' ); }
else { fail( 'cleared empty message still rendered output', $silent ); }

/* --- stretched link must not double up on the button --- */
$stretch = render_with( $widget, array_merge( $base, array( 'zenblog_link_whole_card' => 'yes' ) ), $posts );
if ( false !== strpos( $stretch, 'zenblog__stretch' ) && false !== strpos( $stretch, 'aria-hidden="true" tabindex="-1"' ) ) {
	ok( 'stretched card hides the duplicate read-more link from assistive tech' );
} else {
	fail( 'stretched card link handling' );
}

$unstretched = render_with( $widget, array_merge( $base, array( 'zenblog_link_whole_card' => '' ) ), $posts );
if ( false !== strpos( $unstretched, 'aria-label="Read more:' ) && false === strpos( $unstretched, 'zenblog__stretch' ) ) {
	ok( 'unstretched read-more link is individually labelled' );
} else {
	fail( 'unstretched read-more labelling' );
}

/* --- responsive breakpoints --- */
$resp = array_merge(
	$base,
	array(
		'zenblog_slides_per_view'        => '4',
		'zenblog_slides_per_view_tablet' => '2',
		'zenblog_slides_per_view_mobile' => '1',
		'zenblog_gap'                    => array( 'unit' => 'px', 'size' => 30 ),
	)
);
$out = render_with( $widget, $resp, $posts );
preg_match( '/data-zenblog-options="([^"]*)"/', $out, $m );
$json = json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true );
if ( 4 === $json['breakpoints']['1025']['slidesPerView'] && 2 === $json['breakpoints']['768']['slidesPerView'] && 1 === $json['breakpoints']['0']['slidesPerView'] && 30 === $json['breakpoints']['0']['spaceBetween'] ) {
	ok( 'responsive slides-per-view maps onto Elementor breakpoints, inheriting the gap' );
} else {
	fail( 'responsive breakpoint mapping', json_encode( $json['breakpoints'] ) );
}

$auto_view = render_with( $widget, array_merge( $base, array( 'zenblog_slides_per_view' => 'auto' ) ), $posts );
preg_match( '/data-zenblog-options="([^"]*)"/', $auto_view, $m );
$json = json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true );
if ( 'auto' === $json['breakpoints']['1025']['slidesPerView'] ) { ok( '"auto" slides-per-view survives as a string' ); }
else { fail( 'auto slides-per-view' ); }

/* --- duplicate avoidance across two widgets --- */
Zen_Blogger_Query::mark_rendered( array( 101, 102 ) );
$args = $build->invoke( null, array( 'zenblog_source' => 'latest', 'zenblog_avoid_duplicates' => 'yes' ) );
if ( in_array( 101, $args['post__not_in'], true ) && in_array( 102, $args['post__not_in'], true ) ) {
	ok( 'avoid-duplicates excludes posts rendered earlier on the page' );
} else {
	fail( 'avoid-duplicates', json_encode( $args ) );
}

/* ------------------------------------------------------------------ */
echo "\n== 5. Structured data (SEO / GEO) ==\n";
/* ------------------------------------------------------------------ */

function zenblog_extract_jsonld( $html ) {
	if ( ! preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $m ) ) {
		return null;
	}
	return json_decode( $m[1], true );
}

// Default: summary ItemList, no Zen GEO present.
$out  = render_with( $widget, $base, $posts );
$json = zenblog_extract_jsonld( $out );

if ( is_array( $json ) && 'ItemList' === $json['@type'] && 3 === $json['numberOfItems'] ) {
	ok( 'default output includes a valid ItemList with the right item count' );
} else {
	fail( 'default ItemList', wp_json_encode( $json ) );
}

$positions = array_column( $json['itemListElement'], 'position' );
if ( array( 1, 2, 3 ) === $positions && ! isset( $json['itemListElement'][0]['item'] ) ) {
	ok( 'summary mode emits position + url only, in order' );
} else {
	fail( 'summary mode shape', wp_json_encode( $json['itemListElement'] ) );
}

if ( ! isset( $json['isPartOf'] ) ) {
	ok( 'no Zen GEO present => no isPartOf link invented' );
} else {
	fail( 'isPartOf emitted without Zen GEO' );
}

// Full mode.
$full = zenblog_extract_jsonld( render_with( $widget, array_merge( $base, array( 'zenblog_schema' => 'full', 'zenblog_schema_type' => 'NewsArticle' ) ), $posts ) );
$item = $full['itemListElement'][0]['item'] ?? array();

if ( 'NewsArticle' === ( $item['@type'] ?? '' )
	&& isset( $item['headline'], $item['datePublished'], $item['dateModified'], $item['author'] )
	&& false === strpos( $item['headline'], '<' ) ) {
	ok( 'full mode emits typed items with stripped headline, dates and author' );
} else {
	fail( 'full mode item shape', wp_json_encode( $item ) );
}

if ( isset( $item['@id'] ) && substr( $item['@id'], -8 ) === '#article' ) {
	ok( 'item @id follows Zen GEO\'s permalink#article convention (one node per post)' );
} else {
	fail( 'item @id convention', $item['@id'] ?? 'missing' );
}

// Now simulate Zen GEO being active.
define( 'ZENGEO_VERSION', '1.5.0' );
$withgeo = zenblog_extract_jsonld( render_with( $widget, $base, $posts ) );

if ( isset( $withgeo['isPartOf']['@id'] ) && '#article' === substr( $withgeo['isPartOf']['@id'], -8 ) ) {
	ok( 'Zen GEO active => list attaches to its page node via isPartOf' );
} else {
	fail( 'Zen GEO isPartOf link', wp_json_encode( $withgeo['isPartOf'] ?? null ) );
}

if ( isset( $withgeo['@id'] ) && false !== strpos( $withgeo['@id'], '#zenblog-' ) ) {
	ok( 'list node carries its own element-scoped @id (no collision between carousels)' );
} else {
	fail( 'list @id', $withgeo['@id'] ?? 'missing' );
}

// Zen GEO must never end up describing the same page twice.
if ( $withgeo['@id'] !== ( $withgeo['isPartOf']['@id'] ?? '' ) ) {
	ok( 'list node and page node are distinct ids — no competing description' );
} else {
	fail( 'list node collides with Zen GEO page node' );
}

// A title that tries to close the script tag must not be able to.
$evil = render_with(
	$widget,
	array_merge( $base, array( 'zenblog_schema' => 'full' ) ),
	array( array( 'ID' => 401, 'title' => 'Breakout </script><img src=x onerror=alert(1)>', 'thumb' => 0, 'terms' => array(), 'excerpt' => 'x', 'content' => 'x', 'comments' => 0 ) )
);
$script_body = preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $evil, $mm ) ? $mm[1] : '';

if ( '' !== $script_body && false === stripos( $script_body, '</script' ) && null !== json_decode( $script_body, true ) ) {
	ok( 'a "</script>" post title cannot terminate the JSON-LD block' );
} else {
	fail( 'JSON-LD script breakout', substr( $evil, 0, 400 ) );
}

if ( false === stripos( $evil, '<img src=x' ) ) {
	ok( 'injected markup never reaches the page unescaped' );
} else {
	fail( 'injected markup rendered' );
}

// The author display name is NOT run through the title's sanitiser, so it is the
// field that proves the encoder itself is safe rather than one lucky call site.
$evil_author = render_with(
	$widget,
	array_merge( $base, array( 'zenblog_schema' => 'full' ) ),
	array( array( 'ID' => 402, 'title' => 'Clean', 'thumb' => 0, 'terms' => array(), 'excerpt' => 'x', 'content' => 'x', 'comments' => 0, 'author' => 'Ada </script><img src=x onerror=alert(1)>' ) )
);
$body_a = preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $evil_author, $ma ) ? $ma[1] : '';

if ( '' !== $body_a && false === stripos( $body_a, '</script' ) && false === strpos( $evil_author, 'Ada </script>' ) && null !== json_decode( $body_a, true ) ) {
	ok( 'a "</script>" AUTHOR NAME cannot terminate the JSON-LD block either' );
} else {
	fail( 'JSON-LD breakout via author name', substr( $body_a, 0, 200 ) );
}

if ( false !== strpos( $body_a, '\\/' ) ) {
	ok( 'slashes are escaped by the encoder, so the fix cannot regress per-field' );
} else {
	fail( 'slashes not escaped in JSON-LD' );
}

// Opt-out paths.
$off = render_with( $widget, array_merge( $base, array( 'zenblog_schema' => '' ) ), $posts );
if ( false === strpos( $off, 'application/ld+json' ) ) {
	ok( 'schema set to None emits no JSON-LD at all' );
} else {
	fail( 'schema None still emitted output' );
}

$empty_schema = render_with( $widget, $base, array() );
if ( false === strpos( $empty_schema, 'application/ld+json' ) ) {
	ok( 'empty query emits no empty ItemList' );
} else {
	fail( 'empty ItemList emitted' );
}

/* ------------------------------------------------------------------ */
echo "\n== 6. PHP notices ==\n";
/* ------------------------------------------------------------------ */

if ( empty( $notices ) ) {
	ok( 'no PHP notices, warnings or deprecations during any render' );
} else {
	foreach ( array_unique( $notices ) as $n ) {
		fail( 'PHP notice', $n );
	}
}

/* ------------------------------------------------------------------ */
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED\n";
	exit( 0 );
}
echo count( $failures ) . " FAILURE(S)\n";
exit( 1 );
