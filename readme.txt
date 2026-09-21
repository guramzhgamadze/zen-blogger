=== Zen Blogger ===
Contributors: guramzhgamadze
Tags: elementor, carousel, blog, slider, posts
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accessible blog carousel and post grid widgets for Elementor, with six skins, a deep query builder and a search-and-filter bar.

== Description ==

Zen Blogger adds two widgets to Elementor: **Blog Carousel** and **Posts**. Both draw the same card and share the same query builder, so a layout you design once works either way.

= Accessibility =

Turning autoplay on adds a play/pause button — you can move it or make it appear on hover, but you cannot delete it, because moving content with no way to stop it fails WCAG 2.2.2. Arrows and the play/pause control are real buttons at a 44px target. Slides carry proper carousel semantics, keyboard control is on by default, and visitors whose system asks for reduced motion get no autoplay and instant transitions.

= Performance =

The carousel runs on the Swiper library Elementor already loads, so no additional slider script is added to the page. Nothing is enqueued on pages that do not use the widget. Images in the first visible row load eagerly with high fetch priority and everything after them loads lazily, and the image box reserves its aspect ratio up front so the carousel does not shift your layout while it loads. If the script never runs, the markup stays a scroll-snapping row instead of collapsing.

= Query builder =

Filter by post type, taxonomy (include *and* exclude, combined with AND or OR), author, date range, and custom field ordering. Show posts related to the post being viewed, hand-pick them, or reuse the archive's own query. Exclude the current post, skip posts already shown by another carousel on the page, or require a featured image.

= The Posts widget =

Grid, masonry, list, or a feature layout where the first post spans wider. Pagination is page numbers, previous/next, a Load More button, or infinite scroll — and every one of them starts life as a real link, so the widget works with JavaScript disabled and every page stays crawlable.

When a page of results is fetched in the background:

* The new result count is announced politely — "Showing 6 of 8 posts".
* The grid is marked `aria-busy` while it loads.
* Focus moves to the first newly loaded post, so keyboard users continue from the new content. On an automatic infinite-scroll load, focus is deliberately left alone.
* Infinite scroll loads a set number of pages automatically before the button must be pressed again, so the footer stays reachable.
* The address bar keeps up with filters, so Back works and a filtered view can be shared.

The optional search-and-filter bar is a real form. It offers only the terms present in the posts the widget is showing, and reflects the current selection with `aria-current`.

= Setting up a widget =

1. Edit a page, or a Theme Builder template, with Elementor.
2. Search the widget panel for "Zen" and drag **Blog Carousel** or **Posts** onto the canvas.
3. **Content -> Query** picks what to show: the Source (latest posts, related to the post being viewed, hand-picked, or the current page's own query), then post type, taxonomy include and exclude, author, date range and ordering.
4. **Content -> Layout** picks how a card looks: the Skin, and for the Posts widget the layout (grid, masonry, list or feature) and the number of columns per device.
5. **Content -> Pagination**, on the Posts widget, offers page numbers, previous/next, a Load More button, infinite scroll, or none.
6. **Content -> Search & Filter**, on the Posts widget, switches on the filter bar. Choose which taxonomies it offers, and whether to include a search box and a sort control.
7. **Content -> Accessibility**, on the carousel, controls autoplay, where the play/pause button sits and when it is visible, and whether reduced-motion settings are respected.
8. The **Style** tab has its own section for the card, image, terms, title, meta, excerpt, read more, filter bar and pagination.

Every text label the widget renders is an editable field, and every colour, font, size, spacing, border, radius and shadow is an Elementor control.

= Skins =

* Classic — image above the text
* Overlay — text over the image
* Overlap — the text block lifted over the image
* Side by side — image left or right
* Minimal — no image
* Editorial — oversized running numbers

= Motion =

Slide, fade, coverflow, cards and creative effects; free scroll and a continuous ticker mode; multiple rows; centred slides; dots, dynamic dots, a fraction counter, a progress bar or a draggable scrollbar.

= Styling =

Every colour, font, size, spacing, border, radius and shadow is an Elementor control — including hover and focus states. Nothing is hard-coded, and no colour is applied as a default you cannot switch off.

= SEO and AI (GEO) =

Every slide is rendered server-side as a real `<a href>` inside an `<article>`, with a selectable heading tag and a machine-readable `<time datetime>`. Search engines and AI crawlers read the entire list from the page source — nothing is fetched by JavaScript, and nothing is hidden behind an interaction.

The carousel also describes itself as a schema.org **ItemList** in JSON-LD, either as positions and URLs or with full post details (headline, image, dates, author) under BlogPosting, Article, NewsArticle, CreativeWork or Product. That is what lets a search engine treat the section as an ordered list of articles rather than a cluster of links, and gives generative engines an unambiguous reading of what the section contains and in what order. Set it to None if your SEO plugin already outputs an ItemList for the same section.

= Works with Zen GEO =

If [Zen GEO](https://wordpress.org/plugins/zen-geo/) is installed, the two plugins share their structured data:

* Zen GEO prints its page graph in `<head>`; Zen Blogger prints its list where the widget renders. They never overwrite each other.
* Zen Blogger uses **Zen GEO's own node identity** (`permalink#article`), so a post described in a carousel and the same post described by Zen GEO on its own page resolve to one node instead of two competing descriptions of the same URL.
* The carousel's list attaches to Zen GEO's page node with `isPartOf`, so the output reads as one connected graph.
* Detection is at runtime, so load order never matters. Turn the integration off with the `zenblog_zengeo_integration` filter.

Neither plugin requires the other.

= Developer notes =

* `zenblog_query_args` — modify the WP_Query arguments before the query runs.
* `zenblog_schema_graph` — alter the JSON-LD graph, or return an empty array to suppress it.
* `zenblog_zengeo_integration` — disable the Zen GEO linkage.
* `zenblog_reading_speed` — words per minute for the reading-time estimate (default 200).
* `zenblog_filter_scope_limit` — how many posts the filter bar inspects to decide which terms to offer (default 1000).
* `zenblog_term_choices_limit` / `zenblog_post_choices_limit` — how many terms and posts the panel offers.

== Installation ==

1. Install and activate Elementor.
2. Upload the `zen-blogger` folder to `/wp-content/plugins/`, or install the zip through Plugins → Add New.
3. Activate Zen Blogger.
4. Edit a page with Elementor, search the widget panel for "Zen", and drag **Blog Carousel** or **Posts** from the *Zen Blogger* category onto the canvas.
5. See "Setting up a widget" above for what each panel does.

== Frequently Asked Questions ==

= Does it need Elementor Pro? =

No. Zen Blogger works with free Elementor.

= Why can't I remove the play/pause button? =

Because autoplay is on. WCAG 2.2.2 requires a way to stop content that moves automatically, so the button is rendered whenever autoplay is enabled. You can position it in any corner and set it to appear on hover and keyboard focus. Turning autoplay off removes it.

= Does it work with custom post types? =

Yes. Any public post type with a UI can be used as the source, and its public taxonomies appear as filters.

= Will it slow my site down? =

The CSS and JS are only enqueued on pages that actually contain the widget, and the carousel reuses the Swiper library Elementor already loads rather than adding another one.

= Does it support RTL? =

Yes. The layout uses logical properties throughout and the carousel direction follows the site's text direction.

== Screenshots ==

1. The Posts widget in a grid, with the search-and-filter bar, the result count and a Load More button.
2. The Blog Carousel using the Classic skin, with dots and the arrows outside the track.
3. The Blog Carousel using the Overlay skin, where the text sits over the image.
4. The Posts widget in the Feature layout, where the first post spans wider than the rest.
5. The Posts widget in the List layout with the Side-by-side skin and numbered pagination.
6. The Content panel: query, layout, search and filter, pagination and accessibility controls.
7. The Style panel: card, image, badge and term controls, each with Normal and Hover states.

== Changelog ==

= 1.9.0 =
* Added a Focal Point control. Cropped photographs were always cropped from the middle, which is what cuts the heads off portraits in a wide card. Nine presets, or Custom for a precise point.
* Added a Shape control: circle, oval, diamond, hexagon, pentagon, octagon, triangle, trapezoid, parallelogram, or your own mask image. The shape is cut on the image box, so a hover zoom moves the photograph inside a shape that stays still.
* Added Scale Down and None to Fit, and an Empty Space colour for the bars the letterboxing fits leave behind.
* Fixed: the Image Resolution select never did anything. It was read under the wrong name, so every card was served the `large` size whatever was picked, and the custom-size option could not be reached at all.
* Added a Quality slider for the custom size, since that is the only size this widget generates itself and therefore the only one it can honestly compress.
* Fixed: the Aspect Ratio control shipped a default that was written into every page whether or not anyone chose it, silently overriding the ratio each skin sets for itself. The overlay skin's taller crop, the side skin's full-height image and the feature card's wide crop were all unreachable.
* Custom-size images now carry width and height, so the page stops shifting as each photograph arrives.

= 1.8.6 =
* Tested against WordPress 7.1.
* Fixed: on a hierarchical taxonomy, the count beside a parent term ignored everything filed under its children. Selecting the term returns those posts, so a parent could read "1" next to a button that produced ten results.
* Fixed: a term matching every post the widget is showing is no longer offered. Selecting it returned exactly what "All" already showed, which on a category archive meant a button that could not change anything.
* Fixed: two strings carried a different translator comment in each place they were used. Gettext merges those into a single entry, so translators were shown two contradictory notes for the same string.

= 1.8.5 =
* Corrected the Contributors username and the author profile link, which were both missing a letter.
* Rewrote the description to describe this plugin on its own terms, without comparisons to other post widgets, and added a step-by-step guide to configuring a widget.

= 1.8.4 =
* Fixed: a widget nested inside a container that does not keep its children in a plain list could not be found by the background request, so pagination and filtering failed on exactly those layouts. Elementor's own recursive search is now used, which descends through the filter such containers use to expose their children.

= 1.8.3 =
* Fixed: "Avoid duplicates" stopped working past the first page. It reads a list of what the page has already shown, and that list lives for one request — a Load More or infinite-scroll fetch is a new request, where it starts empty. So page two was drawn from a different result set than page one, and posts could repeat or be skipped between them. The set is now carried across.

= 1.8.2 =
* Fixed: loading the dynamic tags warned on every page if glob() failed — an unreadable directory or an open_basedir restriction — because the result was iterated without checking it was a list.
* Fixed: the REST filter sanitiser could return null rather than a string if the regex engine gave up, so a value that was neither cleaned nor a string could travel on. A failure now drops the filter.
* Removed dead code: an unset() of an array key that was never set, and an is_array() guard on a value that is always an array.

= 1.8.1 =
* Changed: the excerpt is a paragraph rather than a div. Text-processing plugins — hyphenation, typography filters, translation — look for `<p>`, and a div quietly opted the excerpt out of every one of them. The theme's paragraph margin is reset with it, so card spacing stays owned by the card.

= 1.8.0 =
* Changed: with infinite scroll the feed loads itself, so it no longer shows a button telling you to do what it is already doing. A small spinner appears while a page is fetched, and the button returns — visible — once auto-loading reaches its limit and there is genuinely something to press. It stays reachable by Tab throughout, and remains a real link for visitors without JavaScript.
* Fixed: the loading spinner was styled but never rendered, so no pagination mode has ever shown one.
* Removed: the outline the paginator drew around Load More and the page numbers. The Border, Background and Radius controls are still there for anyone who wants one.

= 1.7.4 =
* Changed: Clear and Apply now take the Selected Text Color and Selected Background — the same pair that marks a chosen term — so an action reads as a button using a colour the bar already defines. The borders 1.7.3 put on every pill are gone with them.

= 1.7.3 =
* Fixed: the filter pills, Clear and Apply had no border or background of their own, so the whole bar read as a row of stray words rather than controls. They now carry the same neutral outline the paginator got, which any Border, Background or Radius control still overrides.

= 1.7.2 =
* Fixed: the Clear button took the theme's button colour on hover and kept it on focus after being clicked, while the filter controls beside it did not. Themes style bare `button:hover` and `button:focus` at a specificity a single class cannot beat — Astra does exactly this. Those states are now held to the filter bar's own design, and every filter style control reaches them too, so a colour or background set for the bar carries into hover and focus instead of being dropped.

= 1.7.1 =
* Fixed: the filter bar's two spacing sliders shipped with no default, so the panel showed them at zero and saved zero the moment either was touched — leaving the search box and the controls beneath it in contact. Both now carry a real default. An existing widget already saved at zero keeps it; set Space Between Rows to restore the gap.

= 1.7.0 =
* Fixed: the filter bar did nothing at all whenever AJAX was off — with pagination set to None, with the Update Without Reloading switch off, or on the Current Query source. Its Apply button only exists for visitors without JavaScript, so with scripting on there was no way to submit it. Changing a control now reloads the page with the chosen filters, the same round trip the no-JavaScript path makes.
* New: Current Query works with Load More and infinite scroll on archives. The widget now tells the endpoint which listing it is on — this term, this author, this search — and the endpoint checks that identity against real objects before rebuilding it, so a caller still cannot define a query of its own.
* Changed: a taxonomy left offering a single term is dropped from the filter bar. On a category archive every post already has that category, so the control was a button that could not change anything; search and sort remain.

= 1.6.0 =
* Fixed: the filter bar listed every term on the site with its site-wide count, whatever the widget was actually showing. On an author or category archive that meant offering terms with nothing in the archive at all, and counts that did not match the results underneath. It now lists only the terms present in the widget's own posts, counted against them.
* New: `zenblog_filter_scope_limit` filter, for how many posts the bar may inspect when working out which terms are present (default 1000).

= 1.5.2 =
* Fixed: with the Current Query source, the widget stayed on page one while the rest of the archive moved. It read its own page number instead of the archive's, so /page/2/ showed page one's posts. It now follows the page the visitor is on, and its paging links drive the archive rather than a private query argument.
* Fixed: the result count read "0 posts" whenever pagination was switched off, because the query was told not to count rows.

= 1.5.1 =
* New: drop shadow controls for the filter bar — one for the bar itself, one for the controls inside it.
* New: drop shadow control for the paginator, which had every other style control but this one.

= 1.5.0 =
* Fixed: pagination did nothing at all when the widget was placed in a Theme Builder template. The widget reported the post being viewed, but its settings live in the template, so every background request looked for the widget in the wrong post and was refused. It now reports the document it was placed in, and passes the displayed post separately.
* Fixed: "Related to the current post" and "Exclude current post" were ignored from page two onwards. A background request has no current post, so the second page was built from a different query than the first.
* Fixed: the Load More button stayed on screen after the last page. Pressing it asked for a page that does not exist and appended the "no posts found" notice below the results — the stray line of unstyled text under the grid.
* Fixed: a card template's dynamic background image never appeared on the front end, though it showed in the editor. Elementor supplies that kind of value once per page under one shared selector, which cannot describe nine different posts; each card now carries its own scoped rule.
* Fixed: carousel autoplay could come up dead on an optimised site. Swiper refuses to schedule anything while the carousel measures zero, and never tries again — so a stylesheet arriving late left autoplay permanently off. It now starts as soon as the carousel has a width.
* Fixed: Load More and the page numbers rendered as bare text, with no border or background of their own.
* New: alignment and padding controls for the paginator, plus a full-width option for the Load More button.
* New: the Load More button's text can be edited when Infinite Scroll is selected, not only Load More.
* New: alignment control for the excerpt, including justified.
* New: typography, colour, alignment and spacing controls for the result count.
* Changed: Clear Filters now takes the same style controls as the rest of the filter bar.
* Changed: with the "Current page query" source, pagination stays as real page links. That query only exists on the page itself and cannot be rebuilt for a background request.

= 1.4.1 =
* Fixed: infinite scroll stopped after the first automatic load. It watched the paginator, which is replaced on every page, so the observer was left watching a node that was no longer in the document. It now watches a dedicated element that is never re-rendered.
* Fixed: paging links inside an AJAX response pointed at the REST endpoint instead of the page, because they were built from the current request. Opening one in a new tab produced raw JSON.
* Fixed: infinite scroll could stall on a tall screen, where the trigger stayed on-screen after a page was appended and so never crossed the threshold again.
* New: the infinite-scroll auto-load limit is a control rather than a fixed value.

= 1.4.0 =
* Fixed: with many pages, the numbered paginator was a trap — the window stayed on its first range, so pages beyond it could never be reached. It now shows first and last with an ellipsis, and the server re-renders the window on every AJAX page change.
* Fixed: the search box shared the top row with the filters on the front end even though it looked right in the editor. It now owns its own full-width row at any container width.
* Fixed: the meta divider stopped where the text stopped, because Content Alignment shrink-wraps every child of the card body. It now spans the card.
* Fixed: Read More sits at the foot of the card, directly above the meta divider, instead of wherever the excerpt happened to end.
* Removed: the Apply button, for anyone with JavaScript. It now exists only inside <noscript>, so it is genuinely absent rather than hidden.
* New: the search, filters and sort share one container with its own background, border, radius, padding and row spacing.
* New: custom text for the result count, reading time and comment count, each with a %s placeholder.
* New: meta alignment — left, centre, right or spread.
* Changed: Clear filters is a button, matching the filter controls rather than reading as a stray link.
* Changed: the carousel's play/pause button is out of sight by default and appears when a keyboard user reaches it. It still cannot be removed while autoplay is on.

= 1.3.0 =
* New: sort options are now a repeater. Choose which orders to offer, rename each one, and drag to reorder — the first row is the default.
* Changed: the Apply button is hidden once the script takes over, because results already update as a filter changes. It is still rendered for visitors without JavaScript, and a new "Apply Automatically" switch turns auto-updating off if you prefer an explicit Apply.
* Fixed: the sort repeater's row label used a triple brace, which injects user-typed text raw into the editor panel.

= 1.2.2 =
* New: excerpts are built from paragraphs only, so headings, list items, captions and table cells no longer end up in the card text. Choose "all content" if you preferred the old behaviour.
* New: meta can sit under the title or at the foot of the card, with an optional divider you can colour, thicken and space.
* Changed: headings no longer gain an underline on hover. The Title hover colour control is there if you want an affordance.
* Fixed: the focus ring used currentColor, so on a focused pill or button — where the text is white — it was invisible against a light card. It now draws a two-tone ring that contrasts on any background, and uses the system Highlight colour in forced-colors mode.
* Fixed: changing Columns in the editor did not re-render the widget, leaving the Feature layout's column span stale until another control forced a redraw.

= 1.2.1 =
* Fixed: the Posts widget never applied the skin class, so Overlay, Overlap, Side, Editorial and Minimal all rendered as Classic.
* Fixed: combining two taxonomy filters silently applied only the first. The AJAX endpoint sanitised the filter query with a function that turns "&" into "&amp;".
* Fixed: a non-Latin search term was destroyed in transit for the same reason, by a sanitiser that strips percent-encoding.
* Fixed: theme link styling underlined card titles, term pills and the Read More button. Card link rules now out-specify the theme without !important.
* Added a Respect "Reduce Motion" control to the Posts widget, matching the carousel.

= 1.2.0 =
* New: card templates. Pick any saved Elementor template and it is rendered once per post, with that post in context — a loop item without Elementor Pro.
* New: nine dynamic tags (title, excerpt, date, terms, author, reading time, comment count, URL, featured image) so template cards can actually read the post. Free Elementor ships none of its own.
* Rebuilt the Posts filter as a real search-and-filter form: keyword search, several taxonomies at once, buttons / checkboxes / dropdowns, a sort control, live result count and a Clear button. It is a GET form, so it works with JavaScript off and every view has a shareable URL.
* Fixed: the masonry Row Gap control had no effect, because row-gap does nothing on a CSS-columns layout.
* Fixed: a feature card could span every column, leaving nothing beside it. The span is now capped against the column count, per breakpoint.
* Fixed: cards did not stretch to fill their grid cell, so rows of mixed-length posts did not line up.
* Fixed: the address bar could accumulate duplicate filter parameters.
* Fixed: the Clear button never appeared after an AJAX update.

= 1.1.0 =
* New **Posts** widget: grid, masonry, list and feature layouts with page numbers, previous/next, Load More or infinite scroll.
* AJAX pagination and an optional taxonomy filter bar, both built on real links so they work without JavaScript.
* Result counts are announced, the grid reports its busy state, and focus moves to the first new post after a visitor-initiated load.
* Card, query and style code now lives in one shared implementation used by both widgets.
* The Posts script is a separate file, so a page with only a carousel never downloads it.

= 1.0.1 =
* Fixed a fatal error in the Elementor editor. get_style_depends() read widget settings, but Elementor also calls it on widget types, which have none — producing "sanitize_settings(): Argument #1 must be of type array, null given" and a white screen. The icon stylesheet is now enqueued at render time instead.

= 1.0.0 =
* Initial release: the Blog Carousel widget with six skins, a full query builder, Swiper-based motion, and WCAG 2.2 AA controls.
* schema.org ItemList output in JSON-LD, with a Zen GEO integration that shares node identity instead of duplicating it.

== Upgrade Notice ==

= 1.4.1 =
Fixes infinite scroll, which stopped after one automatic load.

= 1.4.0 =
Fixes a paginator that could strand visitors on long archives, plus a set of filter-bar and card layout corrections.

= 1.3.0 =
Editable sort options, and no more redundant Apply button.

= 1.2.2 =
Cleaner excerpts, meta positioning, and a focus ring that is actually visible.

= 1.2.1 =
Fixes card skins being ignored in the Posts widget and multi-taxonomy filtering. Recommended for everyone.

= 1.2.0 =
Adds card templates, dynamic tags and a real search-and-filter bar, and fixes several grid layout bugs.

= 1.1.0 =
Adds the Posts widget. No changes are needed to existing carousels.

= 1.0.1 =
Fixes a fatal error that could white-screen the Elementor editor. Update immediately.

= 1.0.0 =
First release.
