# Zen Blogger

Accessible, fast blog widgets for Elementor — a **Blog Carousel** and a **Posts** grid that share one card, one query builder and one stylesheet.

Requires Elementor (free). No Elementor Pro.

---

## Why it exists

Surveying the free Elementor post widgets turned up the same three gaps everywhere:

- **They ship a second slider library.** Happy Addons, Premium Addons, HT Mega and the dedicated post-carousel plugins each bundle their own Slick or Swiper on top of the copy Elementor already loads. Zen Blogger uses Elementor's.
- **Nothing announces an AJAX update.** Across Better Post & Filter, Essential Addons Lite, PowerPack Lite, Ultimate Post Kit and HT Mega, the only `aria-live` occurrences are inside vendored libraries. Three have no focus management at all, so Load More silently strands keyboard users.
- **No autoplay carousel offers a pause control**, and `prefers-reduced-motion` appears in none of their carousel CSS or JS.

## What that buys you

**Accessibility is not optional here.** Autoplay renders a play/pause button that cannot be deleted — moving content with no way to stop it fails WCAG 2.2.2. Off-screen slides are `inert`, so a looping carousel does not hand the keyboard twelve copies of the same links. After an AJAX page or filter change the result count is announced politely, the grid reports `aria-busy`, and focus moves to the first new post — but never on an auto-triggered infinite load, because nobody asked for it.

**Performance is structural.** Nothing is enqueued on pages without the widget, and the carousel and grid ship separate scripts so neither downloads the other's. The first visible row loads eagerly with `fetchpriority="high"`, everything after it lazily, and the image box reserves its aspect ratio so nothing shifts.

**Everything degrades.** Filters and pagination are real links and real form controls before any JavaScript runs; the script only upgrades them to fetch in place. If Swiper never loads, the carousel stays a scroll-snapping row.

## Widgets

| | |
|---|---|
| **Blog Carousel** | Six skins, slide/fade/coverflow/cards/creative effects, free-scroll and ticker modes, multiple rows, dots / fraction / progress bar / scrollbar |
| **Posts** | Grid, masonry, list and feature layouts; page numbers, previous-next, Load More or infinite scroll; a real search-and-filter form |

Both share: a query builder (post type, taxonomy include *and* exclude with AND/OR, author, date range, custom-field ordering, related-to-current, hand-picked, current-query), six card skins plus an Elementor-template skin, and a full set of style controls.

## Card templates and dynamic tags

The template skin renders any saved Elementor template once per post with that post in context — a loop item without Pro. Since free Elementor ships no dynamic tags at all, the plugin adds nine of its own (title, excerpt, date, terms, author, reading time, comment count, URL, featured image) so a template card can actually read the post.

## SEO and GEO

Every card is server-rendered as a real `<a href>` inside an `<article>`. The widget also describes itself as a schema.org `ItemList` in JSON-LD.

If [Zen GEO](https://wordpress.org/plugins/zen-geo/) is installed the two cooperate: Zen Blogger reuses Zen GEO's node identity (`permalink#article`), so a post described in a carousel and the same post described by Zen GEO resolve to **one** node, and the list attaches to Zen GEO's page node with `isPartOf`. Detection is at runtime, so load order never matters. Neither plugin requires the other.

## Filters

| Filter | Purpose |
|---|---|
| `zenblog_query_args` | Modify the `WP_Query` arguments before the query runs |
| `zenblog_schema_graph` | Alter the JSON-LD graph, or return `[]` to suppress it |
| `zenblog_zengeo_integration` | Disable the Zen GEO linkage |
| `zenblog_reading_speed` | Words per minute for the reading-time estimate (default 200) |
| `zenblog_term_choices_limit` / `zenblog_post_choices_limit` | How many terms and posts the panel offers |

## Development

`tests/` holds a stub harness that runs the widgets outside WordPress against a **typed** `\Elementor\Widget_Base`. It exists because `php -l` cannot catch the failures that actually ship:

```bash
php tests/test-widget.php        # ~70 assertions: signatures, controls, query, render, schema
php tests/build-preview.php      # renders real widget output into a standalone page
```

The typed stub is what caught an untyped `has_widget_inner_wrapper()` override — a white screen on every page — and a reproduction of the editor-preview fatal from 1.0.0 is a permanent test.

Before packaging: PHPCS (`--standard=WordPress`) and Plugin Check must both be at zero errors.

## Licence

GPL-2.0-or-later.
