<?php
/**
 * Minimal typed stubs for WordPress + Elementor, used to exercise the widget
 * outside a real site. Return types on Widget_Base mirror Elementor 3.26+ so a
 * signature mismatch fails at class-declaration time, exactly as it would live.
 */

// phpcs:disable

namespace {

define( 'ABSPATH', __DIR__ . '/' );
define( 'DATE_W3C_FMT', 'Y-m-d\TH:i:sP' );

$GLOBALS['zenblog_test_posts'] = array();
$GLOBALS['zenblog_test_index'] = -1;

/* ------------------------- WordPress surface ------------------------- */

function __( $t, $d = null )            { return $t; }
function _x( $t, $c, $d = null )        { return $t; }
function _n( $s, $p, $n, $d = null )    { return 1 === (int) $n ? $s : $p; }
function esc_html( $t )                 { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t )                 { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t )                  { return (string) $t; }
function esc_html__( $t, $d = null )    { return esc_html( $t ); }
function esc_attr__( $t, $d = null )    { return esc_attr( $t ); }
function esc_html_e( $t, $d = null )    { echo esc_html( $t ); }
function esc_attr_e( $t, $d = null )    { echo esc_attr( $t ); }
function esc_html_x( $t, $c, $d = null ){ return esc_html( $t ); }
function number_format_i18n( $n )       { return (string) $n; }
function sanitize_key( $k )             { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_html_class( $c )      { return preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $c ); }
function sanitize_text_field( $s )      { return is_scalar( $s ) ? trim( strip_tags( (string) $s ) ) : ''; }
function tag_escape( $t )               { return preg_replace( '/[^a-zA-Z0-9_:]/', '', (string) $t ); }
function wp_json_encode( $d, $options = 0, $depth = 512 ) { return json_encode( $d, $options, $depth ); }
function wp_strip_all_tags( $s )        { return strip_tags( (string) $s ); }
function wpautop( $s, $br = true )      { return '<p>' . implode( '</p><p>', preg_split( "/
\s*
/", trim( (string) $s ) ) ) . '</p>'; }
function strip_shortcodes( $s )         { return (string) $s; }
function wp_trim_words( $s, $n, $more ) { $w = preg_split( '/\s+/', trim( (string) $s ), -1, PREG_SPLIT_NO_EMPTY ); return count( $w ) > $n ? implode( ' ', array_slice( $w, 0, $n ) ) . $more : implode( ' ', $w ); }
function is_rtl()                       { return false; }
function is_admin()                     { return $GLOBALS['zenblog_is_admin'] ?? false; }
function wp_doing_ajax()                { return false; }
function apply_filters( $h, $v )        { return $v; }
function do_action() {}
function add_action() {}
function add_filter() {}
function wp_reset_postdata() {}
function get_option( $n, $d = false )   { return 'sticky_posts' === $n ? array( 101 ) : $d; }
function taxonomy_exists( $t )          { return in_array( $t, array( 'category', 'post_tag' ), true ); }
function human_time_diff( $a, $b )      { return '2 days'; }
function wp_kses( $html, $allowed )     { return $html; }
function wp_style_is( $h, $l = '' )     { return true; }
function wp_script_is( $h, $l = '' )    { return true; }
function get_avatar( $id, $size = 96, $d = '', $alt = '', $args = array() ) {
	return '<img class="' . esc_attr( $args['class'] ?? '' ) . '" src="https://example.test/a.png" alt="" width="' . (int) $size . '" height="' . (int) $size . '" />';
}

function get_post_types( $args = array(), $output = 'names' ) {
	$types = array(
		'post' => (object) array( 'name' => 'post', 'label' => 'Posts', 'public' => true, 'show_ui' => true ),
		'page' => (object) array( 'name' => 'page', 'label' => 'Pages', 'public' => true, 'show_ui' => true ),
	);
	return $types;
}

function get_object_taxonomies( $pt, $output = 'names' ) {
	if ( 'post' !== $pt ) {
		return array();
	}
	return array(
		'category' => (object) array( 'name' => 'category', 'label' => 'Categories', 'public' => true, 'show_ui' => true ),
		'post_tag' => (object) array( 'name' => 'post_tag', 'label' => 'Tags', 'public' => true, 'show_ui' => true ),
	);
}

function get_taxonomy( $t ) {
	$all = get_object_taxonomies( 'post', 'objects' );
	return $all[ $t ] ?? false;
}

function get_terms( $args ) {
	return array(
		(object) array( 'term_id' => 5, 'name' => 'News' ),
		(object) array( 'term_id' => 6, 'name' => "O'Reilly & Co" ),
	);
}

function get_posts( $args ) {
	return array(
		(object) array( 'ID' => 101, 'post_title' => 'Hello' ),
		(object) array( 'ID' => 102, 'post_title' => '' ),
	);
}

function get_users( $args ) {
	return array( (object) array( 'ID' => 1, 'display_name' => 'Ada' ) );
}

function wp_get_object_terms( $id, $tax, $args = array() ) { return array( 5 ); }

function is_wp_error( $t ) { return $t instanceof WP_Error; }

class WP_Error {
	public function get_error_message() { return 'error'; }
}

/* ------------------------- Loop surface ------------------------- */

function zenblog_current_post() {
	$i = $GLOBALS['zenblog_test_index'];
	return $GLOBALS['zenblog_test_posts'][ $i ] ?? null;
}

function get_the_ID()                   { $p = zenblog_current_post(); return $p ? $p['ID'] : 0; }
function get_permalink( $id = 0 )       { return 'https://example.test/?p=' . get_the_ID(); }
function get_the_title( $id = 0 )       { $p = zenblog_current_post(); return $p['title'] ?? ''; }
function get_post_thumbnail_id( $id = 0 ) { $p = zenblog_current_post(); return $p['thumb'] ?? 0; }
function is_sticky( $id = 0 )           { $p = zenblog_current_post(); return ! empty( $p['sticky'] ); }
function has_excerpt( $id = 0 )         { $p = zenblog_current_post(); return ! empty( $p['excerpt'] ); }
function get_the_excerpt( $id = 0 )     { $p = zenblog_current_post(); return $p['excerpt'] ?? ''; }
function get_the_content()              { $p = zenblog_current_post(); return $p['content'] ?? ''; }
function get_post_field( $f, $id )      { $p = zenblog_current_post(); return $p['content'] ?? ''; }
function get_the_author()               { $p = zenblog_current_post(); return $p['author'] ?? 'Ada'; }
function get_the_author_meta( $f )      { return 1; }
function get_comments_number( $id = 0 ) { $p = zenblog_current_post(); return $p['comments'] ?? 0; }
function get_post_time( $f, $gmt = false )          { return 1700000000; }
function get_post_modified_time( $f, $gmt = false ) { return 1700100000; }
function get_the_date( $f = '' )                    { return '2024-01-01'; }
function get_the_modified_date( $f = '' )           { return '2024-01-02'; }

function get_the_terms( $id, $tax ) {
	$p = zenblog_current_post();
	if ( empty( $p['terms'] ) ) {
		return false;
	}
	return array_map(
		static function ( $n ) {
			return (object) array( 'term_id' => 5, 'name' => $n, 'slug' => 'x', 'taxonomy' => 'category' );
		},
		$p['terms']
	);
}

function get_term_link( $term ) { return 'https://example.test/cat/'; }

function wp_get_attachment_image( $id, $size, $icon = false, $attr = array() ) {
	$out = '<img src="https://example.test/i.jpg" width="800" height="500"';
	foreach ( $attr as $k => $v ) {
		$out .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	return $out . ' alt="Photo" />';
}

function get_post_meta( $id, $key, $single = false ) { return 'Alt text'; }

class WP_Query {
	public $post_count = 0;
	private $i = -1;

	public function __construct( $args = array() ) {
		$this->post_count = count( $GLOBALS['zenblog_test_posts'] );
	}

	public function have_posts() {
		return $this->i + 1 < $this->post_count;
	}

	public function the_post() {
		$this->i++;
		$GLOBALS['zenblog_test_index'] = $this->i;
	}
}

} // end global namespace

/* ------------------------- Elementor surface ------------------------- */

namespace Elementor {

class Controls_Manager {
	const TAB_CONTENT = 'content';
	const TAB_STYLE   = 'style';
	const TEXT        = 'text';
	const TEXTAREA    = 'textarea';
	const NUMBER      = 'number';
	const SELECT      = 'select';
	const SELECT2     = 'select2';
	const SWITCHER    = 'switcher';
	const SLIDER      = 'slider';
	const COLOR       = 'color';
	const CHOOSE      = 'choose';
	const DIMENSIONS  = 'dimensions';
	const MEDIA       = 'media';
	const ICONS       = 'icons';
	const HEADING     = 'heading';
	const DATE_TIME   = 'date_time';
	const RAW_HTML    = 'raw_html';
}

abstract class Group_Control_Base {
	public static function get_type() { return static::class; }
}
class Group_Control_Typography extends Group_Control_Base {}
class Group_Control_Text_Shadow extends Group_Control_Base {}
class Group_Control_Box_Shadow extends Group_Control_Base {}
class Group_Control_Border extends Group_Control_Base {}
class Group_Control_Background extends Group_Control_Base {}
class Group_Control_Css_Filter extends Group_Control_Base {}

class Group_Control_Image_Size extends Group_Control_Base {
	public static function get_attachment_image_src( $id, $name, $settings ) {
		return 'https://example.test/custom.jpg';
	}
}

class Icons_Manager {
	public static function render_icon( $icon, $attrs = array(), $tag = 'i' ) {
		if ( empty( $icon['value'] ) ) {
			return;
		}
		echo '<i class="' . esc_attr( $icon['value'] ) . '" aria-hidden="true"></i>';
	}
}

class Utils {
	public static function validate_html_tag( $tag ) {
		$allowed = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' );
		return in_array( strtolower( (string) $tag ), $allowed, true ) ? $tag : 'div';
	}
}

class Breakpoint {
	private $v;
	public function __construct( $v ) { $this->v = $v; }
	public function get_value() { return $this->v; }
}

class Breakpoints_Manager {
	public function get_active_breakpoints() {
		return array( 'mobile' => new Breakpoint( 767 ), 'tablet' => new Breakpoint( 1024 ) );
	}
}

class Preview_Manager {
	public function is_preview_mode() { return false; }
}

class Plugin {
	public static $instance;
	public $breakpoints;
	public $preview;
	public function __construct() {
		$this->breakpoints = new Breakpoints_Manager();
		$this->preview     = new Preview_Manager();
	}
}
Plugin::$instance = new Plugin();

/**
 * Typed Widget_Base stub. The return types here are the ones Elementor declares —
 * a child that omits or mismatches one fatals at declaration time.
 */
abstract class Widget_Base {

	public $registered_controls = array();
	private $attrs              = array();
	private $settings           = null;

	abstract public function get_name();

	public $test_id = 'abc123';

	public function get_id() { return $this->test_id; }

	public function has_widget_inner_wrapper(): bool { return true; }

	protected function is_dynamic_content(): bool { return false; }

	public function set_test_settings( array $settings ) { $this->settings = $settings; }

	/**
	 * Faithful to Elementor: on a widget TYPE (no instance data) the stored
	 * settings are null, and Controls_Stack::sanitize_settings() is typed
	 * `array`, so calling this in a type context is a TypeError — the real
	 * white-screen reported from production.
	 */
	public function get_settings_for_display( $key = null ) {
		return $this->sanitize_settings( $this->settings );
	}

	private function sanitize_settings( array $settings ) { return $settings; }

	public function start_controls_section( $id, $args = array() ) {}
	public function end_controls_section() {}
	public function start_controls_tabs( $id, $args = array() ) {}
	public function end_controls_tabs() {}
	public function start_controls_tab( $id, $args = array() ) {}
	public function end_controls_tab() {}

	public function add_control( $id, $args = array(), $options = array() ) {
		$this->registered_controls[ $id ] = $args;
	}

	public function add_responsive_control( $id, $args = array(), $options = array() ) {
		$args['__responsive'] = true;
		$this->registered_controls[ $id ] = $args;
	}

	public function add_group_control( $type, $args = array() ) {
		$this->registered_controls[ 'group:' . ( $args['name'] ?? $type ) ] = $args;
	}

	public function add_render_attribute( $key, $k = null, $v = null, $overwrite = false ) {
		if ( is_array( $k ) ) {
			foreach ( $k as $name => $value ) {
				$this->attrs[ $key ][ $name ] = is_array( $value ) ? implode( ' ', $value ) : $value;
			}
			return;
		}
		$this->attrs[ $key ][ $k ] = is_array( $v ) ? implode( ' ', $v ) : $v;
	}

	public function get_render_attribute_string( $key ) {
		$out = '';
		foreach ( $this->attrs[ $key ] ?? array() as $name => $value ) {
			$out .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		return trim( $out );
	}

	public function print_render_attribute_string( $key ) {
		echo $this->get_render_attribute_string( $key );
	}

	public function add_inline_editing_attributes( $key, $type = 'none' ) {}

	abstract protected function register_controls();
	abstract protected function render();

	public function run_register_controls() { $this->register_controls(); }
	public function run_render() { $this->render(); }
}

}

namespace {
	function get_the_post_thumbnail_url( $id = 0, $size = 'post-thumbnail' ) {
		$p = zenblog_current_post();
		return ! empty( $p['thumb'] ) ? 'https://example.test/thumb.jpg' : false;
	}
	function wp_print_inline_script_tag( $js, $attrs = array() ) {
		$a = '';
		foreach ( $attrs as $k => $v ) { $a .= ' ' . $k . '="' . esc_attr( $v ) . '"'; }
		echo '<script' . $a . '>' . $js . '</script>';
	}
}

namespace {
	function get_queried_object_id() { return 999; }
	function is_singular( $t = '' )  { return $GLOBALS['zenblog_is_singular'] ?? true; }
	function home_url( $p = '/' )    { return 'https://example.test' . $p; }
}
