<?php
/**
 * Renders real widget output into a standalone page so the CSS + JS can be
 * verified in a browser against the same Swiper build Elementor ships.
 */

// phpcs:disable

require __DIR__ . '/stubs.php';

define( 'ZENBLOG_VERSION', '1.0.0' );
define( 'ZENBLOG_PATH', 'C:/Users/Guram/Documents/zen-blogger/' );
define( 'ZENBLOG_URL', './' );

require ZENBLOG_PATH . 'includes/class-zen-blogger-assets.php';
require ZENBLOG_PATH . 'includes/class-zen-blogger-query.php';
require ZENBLOG_PATH . 'includes/trait-zen-blogger-card.php';
require ZENBLOG_PATH . 'includes/widgets/class-zen-blogger-carousel.php';

$GLOBALS['zenblog_is_admin'] = true;

$widget = new Zen_Blogger_Carousel();
$widget->run_register_controls();


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

$base = zenblog_default_settings( $widget->registered_controls );

$titles = array(
	'Designing carousels that do not fight the keyboard',
	'What Core Web Vitals really measure on a blog index',
	"O'Reilly & the long tail of technical publishing",
	'ქართული ტიპოგრაფია ვებზე',
	'A field guide to Elementor query performance',
	'Why your slider ships two copies of the same library',
);

$posts = array();
foreach ( $titles as $i => $title ) {
	$posts[] = array(
		'ID'       => 100 + $i,
		'title'    => $title,
		'thumb'    => 50 + $i,
		'sticky'   => 0 === $i,
		'terms'    => array( array( 'Engineering', 'Design', 'Performance' )[ $i % 3 ] ),
		'excerpt'  => 'A short standfirst that explains what the article covers, long enough to show the clamp behaviour working across two or three lines of body copy.',
		'content'  => str_repeat( 'word ', 600 ),
		'comments' => $i,
	);
}

function zenblog_preview( Zen_Blogger_Carousel $w, array $settings, array $posts ) {
	$GLOBALS['zenblog_test_posts'] = $posts;
	$GLOBALS['zenblog_test_index'] = -1;
	$w->set_test_settings( $settings );
	ob_start();
	$w->run_render();
	return ob_get_clean();
}

$variants = array(
	'Classic — 3 up, outside arrows, dots' => array(
		'zenblog_skin'            => 'classic',
		'zenblog_arrows_position' => 'outside',
		'zenblog_pagination'      => 'bullets',
	),
	'Overlay — autoplay on, play/pause required' => array(
		'zenblog_skin'                => 'overlay',
		'zenblog_autoplay'            => 'yes',
		'zenblog_slides_per_view'     => '2',
		'zenblog_pagination'          => 'progressbar',
		'zenblog_playpause_position'  => 'top-right',
	),
	'Side by side — 2 up, fraction' => array(
		'zenblog_skin'            => 'side',
		'zenblog_slides_per_view' => '2',
		'zenblog_pagination'      => 'fraction',
		'zenblog_arrows_position' => 'bottom',
	),
	'Overlap — 3 up' => array(
		'zenblog_skin'       => 'overlap',
		'zenblog_pagination' => 'bullets',
	),
	'Editorial — minimal images off' => array(
		'zenblog_skin'            => 'editorial',
		'zenblog_slides_per_view' => '3',
		'zenblog_pagination'      => 'scrollbar',
	),
	'Coverflow — centred, 1 up' => array(
		'zenblog_skin'            => 'classic',
		'zenblog_effect'          => 'coverflow',
		'zenblog_pagination'      => 'dynamic',
		'zenblog_arrows_position' => 'inside',
	),
);

$css  = file_get_contents( ZENBLOG_PATH . 'assets/css/zen-blogger.css' );
$js   = file_get_contents( ZENBLOG_PATH . 'assets/js/zen-blogger.js' );
$swcss = file_get_contents( __DIR__ . '/comp/ht-mega-for-elementor/ht-mega-for-elementor/assets/css/swiper.min.css' );
$swjs  = file_get_contents( __DIR__ . '/comp/ht-mega-for-elementor/ht-mega-for-elementor/assets/js/swiper.min.js' );

$sections = '';
$variant_n = 0;
foreach ( $variants as $label => $overrides ) {
	// Each Elementor widget on a page has its own element id; mirror that here so
	// per-element ids (schema @id, aria-controls) are exercised honestly.
	++$variant_n;
	$widget->test_id = 'zbw' . $variant_n;
	$html      = zenblog_preview( $widget, array_merge( $base, $overrides ), $posts );
	$sections .= '<section class="demo"><h2>' . esc_html( $label ) . '</h2>' . $html . '</section>';
}

// Stand-in for Elementor's generated control CSS, so the demo is not colourless.
$theme_css = <<<CSS
body { margin:0; padding:32px; font-family: system-ui, sans-serif; background:#f6f7f9; color:#1d2327; }
h1 { font-size:20px; } h2 { font-size:14px; text-transform:uppercase; letter-spacing:.08em; color:#646970; margin:40px 0 12px; }
.demo { max-width: 1100px; margin: 0 auto 24px; }
img { max-width:100%; }
/* Stands in for the CSS Elementor generates from the style controls. */
.zenblog__card { background:#fff; border:1px solid #e2e4e7; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.zenblog__card:hover { box-shadow:0 12px 28px rgba(0,0,0,.10); transform:translateY(-4px); }
.zenblog__title { font-size:1.05rem; line-height:1.35; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; overflow:hidden; }
.zenblog__excerpt { font-size:.875rem; color:#50575e; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:3; overflow:hidden; }
.zenblog__term { background:#eef2ff; color:#3730a3; padding:2px 10px; border-radius:999px; font-size:.75rem; }
.zenblog__badge { background:#111; color:#fff; }
.zenblog__meta { color:#646970; }
.zenblog__more { color:#2271b1; font-weight:600; font-size:.875rem; }
.zenblog__arrow { background:#fff; border:1px solid #dcdcde; color:#1d2327; }
.zenblog__arrow:hover { background:#1d2327; color:#fff; }
.zenblog__playpause { background:rgba(0,0,0,.6); color:#fff; }
.zenblog__pagination .swiper-pagination-bullet { background:#1d2327; }
.zenblog--pagination-progressbar .zenblog__pagination,
.zenblog--pagination-scrollbar .zenblog__pagination { background:#dcdcde; }
.swiper-pagination-progressbar-fill, .swiper-scrollbar-drag { background:#2271b1; }
.zenblog--skin-overlay .zenblog__card { background:#111; }
.zenblog--skin-overlap .zenblog__body { background:#fff; box-shadow:0 8px 24px rgba(0,0,0,.12); }
.zenblog__media { background:linear-gradient(135deg,#c7d2fe,#fbcfe8); }
CSS;

$page = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
	. '<meta name="viewport" content="width=device-width, initial-scale=1">'
	. '<title>Zen Blogger — render harness</title>'
	. '<style>' . $swcss . '</style>'
	. '<style>' . $css . '</style>'
	. '<style>' . $theme_css . '</style>'
	. '</head><body><h1>Zen Blogger — Blog Carousel render harness</h1>'
	. $sections
	. '<script>' . $swjs . '</script>'
	. '<script>window.zenBloggerI18n={};'
	// Minimal stand-in for Elementor's frontend hook bus, so the widget is driven
	// through the same path it uses on a real site (and re-driven, the way the
	// editor re-renders a widget on every control change).
	. 'window.elementorFrontend={hooks:{_a:{},'
	. 'addAction:function(n,cb){(this._a[n]=this._a[n]||[]).push(cb);},'
	. 'doAction:function(n,a){(this._a[n]||[]).forEach(function(cb){cb(a);});}}};'
	. '</script>'
	. '<script>' . $js . '</script>'
	. '<script>'
	. 'window.dispatchEvent(new Event("elementor/frontend/init"));'
	. 'window.zenbloggerElementReady=function(el){'
	. 'window.elementorFrontend.hooks.doAction("frontend/element_ready/zen-blogger-carousel.default",[el]);};'
	. 'document.querySelectorAll(".zenblog").forEach(function(el){window.zenbloggerElementReady(el);});'
	. '</script>'
	. '</body></html>';

// The real widget uses attachment IDs; swap in real images for the harness.
$page = preg_replace_callback(
	'/src="https:\/\/example\.test\/i\.jpg"/',
	static function () {
		static $n = 0;
		$n++;
		return 'src="https://picsum.photos/seed/zenblog' . $n . '/800/500"';
	},
	$page
);

file_put_contents( __DIR__ . '/preview.html', $page );
echo "wrote preview.html (" . strlen( $page ) . " bytes)\n";
