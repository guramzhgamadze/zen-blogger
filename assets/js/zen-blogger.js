/**
 * Zen Blogger — Blog Carousel front-end behaviour.
 *
 * Uses the Swiper build that ships with Elementor; no second slider library is
 * loaded. If Swiper is unavailable the markup stays a scroll-snapping row, so
 * the carousel degrades instead of breaking.
 */
( function () {
	'use strict';

	var i18n = window.zenBloggerI18n || {};

	function t( key, fallback ) {
		return typeof i18n[ key ] === 'string' ? i18n[ key ] : fallback;
	}

	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Keys that must never survive into an object handed to Swiper.
	 *
	 * Swiper's option merge (`extend()`) is prototype-pollutable in every release
	 * from 6.5.1 up to 12.1.2 (CVE-2026-27212), and Elementor still bundles 8.4.5.
	 * Our options are generated server-side from editor settings, so there is no
	 * visitor-controlled path into them — but stripping the keys here means the
	 * widget cannot become one even if the attribute is tampered with, on whatever
	 * Swiper build Elementor happens to ship.
	 *
	 * Direct comparison, not indexOf(): the published exploit works by overriding
	 * Array.prototype.indexOf, which is exactly how the upstream check was bypassed.
	 *
	 * @param {string} key Property name.
	 * @return {boolean} True when the key must be dropped.
	 */
	function isUnsafeKey( key ) {
		return key === '__proto__' || key === 'constructor' || key === 'prototype';
	}

	function parseOptions( root ) {
		var raw = root.getAttribute( 'data-zenblog-options' );

		if ( ! raw ) {
			return null;
		}

		try {
			return JSON.parse( raw, function ( key, value ) {
				return isUnsafeKey( key ) ? undefined : value;
			} );
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Rebuild the breakpoint map from scratch rather than forwarding the parsed
	 * object, so only numeric keys and three known numeric properties reach Swiper.
	 *
	 * @param {Object} raw Parsed breakpoint map.
	 * @return {Object} Clean breakpoint map.
	 */
	function safeBreakpoints( raw ) {
		var out = {};

		if ( ! raw || typeof raw !== 'object' ) {
			return out;
		}

		Object.keys( raw ).forEach( function ( key ) {
			if ( ! /^[0-9]+$/.test( key ) || isUnsafeKey( key ) ) {
				return;
			}

			var bp = raw[ key ];

			if ( ! bp || typeof bp !== 'object' ) {
				return;
			}

			out[ key ] = {
				slidesPerView: bp.slidesPerView === 'auto' ? 'auto' : Math.max( 1, parseInt( bp.slidesPerView, 10 ) || 1 ),
				slidesPerGroup: Math.max( 1, parseInt( bp.slidesPerGroup, 10 ) || 1 ),
				spaceBetween: Math.max( 0, parseInt( bp.spaceBetween, 10 ) || 0 )
			};
		} );

		return out;
	}

	/**
	 * Largest slides-per-view across all breakpoints, used to decide whether
	 * there are enough slides to loop.
	 *
	 * @param {Object} opts Widget options.
	 * @return {number} Highest numeric slides-per-view value.
	 */
	function maxPerView( opts ) {
		var max = 1;

		Object.keys( opts.breakpoints || {} ).forEach( function ( key ) {
			var value = opts.breakpoints[ key ].slidesPerView;

			if ( typeof value === 'number' && value > max ) {
				max = value;
			}
		} );

		return max;
	}

	function buildConfig( root, opts ) {
		var reduce = opts.reduceMotion && prefersReducedMotion();
		var rows = Math.max( 1, parseInt( opts.rows, 10 ) || 1 );
		var effect = opts.effect || 'slide';
		var mode = opts.mode || 'standard';
		var stacked = effect === 'cards' || effect === 'creative' || effect === 'fade';

		// Looping needs more slides than are ever visible at once, and Swiper's
		// Grid module does not support it at all.
		var canLoop =
			!! opts.loop &&
			rows === 1 &&
			opts.total > maxPerView( opts );

		var config = {
			// The track element is the .swiper node; slides live in .swiper-wrapper.
			loop: canLoop,
			speed: reduce ? 0 : Math.max( 0, parseInt( opts.speed, 10 ) || 600 ),
			centeredSlides: !! opts.centered && ! stacked,
			grabCursor: !! opts.grabCursor,
			watchSlidesProgress: true,
			roundLengths: true,
			a11y: {
				enabled: true,
				prevSlideMessage: t( 'prevSlide', 'Previous slide' ),
				nextSlideMessage: t( 'nextSlide', 'Next slide' ),
				firstSlideMessage: t( 'firstSlide', 'This is the first slide' ),
				lastSlideMessage: t( 'lastSlide', 'This is the last slide' ),
				paginationBulletMessage: t( 'goToSlide', 'Go to slide {{index}}' ),
				slideLabelMessage: t( 'slideLabel', 'Slide {{index}} of {{slidesLength}}' ),
				containerRoleDescriptionMessage: t( 'carousel', 'carousel' ),
				itemRoleDescriptionMessage: t( 'slide', 'slide' )
			}
		};

		if ( opts.rtl ) {
			// Swiper reads direction from the DOM; this keeps it explicit for
			// pages where the widget is inside an LTR island of an RTL site.
			config.rtl = true;
		}

		if ( stacked ) {
			config.slidesPerView = 1;
			config.slidesPerGroup = 1;
		} else {
			config.breakpoints = opts.breakpoints;
		}

		if ( rows > 1 && ! stacked ) {
			config.grid = { rows: rows, fill: 'row' };
		}

		switch ( effect ) {
			case 'fade':
				config.effect = 'fade';
				config.fadeEffect = { crossFade: true };
				break;
			case 'coverflow':
				config.effect = 'coverflow';
				config.coverflowEffect = {
					rotate: 0,
					stretch: 0,
					depth: 90,
					modifier: 1,
					slideShadows: false
				};
				config.centeredSlides = true;
				break;
			case 'cards':
				config.effect = 'cards';
				config.cardsEffect = { slideShadows: false };
				break;
			case 'creative':
				config.effect = 'creative';
				config.creativeEffect = {
					prev: { shadow: false, translate: [ '-20%', 0, -1 ], opacity: 0.4 },
					next: { translate: [ '100%', 0, 0 ] }
				};
				break;
			default:
				config.effect = 'slide';
		}

		if ( effect === 'slide' && mode === 'free' ) {
			config.freeMode = { enabled: true, sticky: false };
		}

		if ( effect === 'slide' && mode === 'ticker' && ! reduce ) {
			config.loop = opts.total > 1;
			config.freeMode = { enabled: true, momentum: false };
			config.speed = Math.max( 1000, ( parseInt( opts.speed, 10 ) || 600 ) * 8 );
			config.allowTouchMove = true;
			root.classList.add( 'zenblog--ticker' );
		}

		if ( opts.keyboard ) {
			config.keyboard = { enabled: true, onlyInViewport: true };
		}

		if ( opts.mousewheel ) {
			config.mousewheel = { forceToAxis: true };
		}

		if ( opts.arrows ) {
			config.navigation = {
				prevEl: root.querySelector( '.zenblog__arrow--prev' ),
				nextEl: root.querySelector( '.zenblog__arrow--next' ),
				disabledClass: 'swiper-button-disabled'
			};
		}

		var paginationEl = root.querySelector( '.zenblog__pagination' );

		if ( opts.pagination && paginationEl ) {
			if ( opts.pagination === 'scrollbar' ) {
				config.scrollbar = { el: paginationEl, draggable: true };
			} else {
				config.pagination = {
					el: paginationEl,
					clickable: true,
					type: opts.pagination === 'dynamic' ? 'bullets' : opts.pagination,
					dynamicBullets: opts.pagination === 'dynamic'
				};
			}
		}

		if ( opts.autoplay && ! reduce ) {
			config.autoplay = {
				delay: Math.max( 0, parseInt( opts.autoplay.delay, 10 ) || 5000 ),
				pauseOnMouseEnter: !! opts.autoplay.pauseOnMouseEnter,
				disableOnInteraction: !! opts.autoplay.disableOnInteraction
			};

			if ( config.freeMode && config.freeMode.enabled && mode === 'ticker' ) {
				config.autoplay.delay = 0;
				config.autoplay.disableOnInteraction = false;
			}
		}

		return config;
	}

	/**
	 * Keep off-screen slides out of the tab order.
	 *
	 * In loop mode Swiper clones slides, so a six-post carousel hands the keyboard
	 * twelve copies of the same links — and even without looping, tabbing into a
	 * card that is scrolled out of view is disorienting. Marking non-visible slides
	 * inert removes them from both the tab order and the accessibility tree, which
	 * is what the WAI-ARIA carousel pattern asks for.
	 *
	 * @param {Object} swiper Swiper instance.
	 * @return {void}
	 */
	function bindInertSlides( swiper ) {
		var supportsInert = 'inert' in HTMLElement.prototype;

		function apply() {
			var active = document.activeElement;

			swiper.slides.forEach( function ( slide ) {
				var visible = slide.classList.contains( 'swiper-slide-visible' );

				// Never inert the slide the user is currently inside — that would
				// blur them mid-interaction and drop focus to the body.
				if ( ! visible && active && slide.contains( active ) ) {
					visible = true;
				}

				if ( supportsInert ) {
					slide.inert = ! visible;
				} else {
					slide.setAttribute( 'aria-hidden', visible ? 'false' : 'true' );
					slide.querySelectorAll( 'a[href], button, input, select, textarea' ).forEach( function ( el ) {
						if ( visible ) {
							if ( el.dataset.zenblogTab ) {
								el.setAttribute( 'tabindex', el.dataset.zenblogTab );
								delete el.dataset.zenblogTab;
							} else {
								el.removeAttribute( 'tabindex' );
							}
						} else {
							if ( el.hasAttribute( 'tabindex' ) ) {
								el.dataset.zenblogTab = el.getAttribute( 'tabindex' );
							}
							el.setAttribute( 'tabindex', '-1' );
						}
					} );
				}
			} );
		}

		[ 'afterInit', 'slideChangeTransitionEnd', 'transitionEnd', 'resize', 'breakpoint', 'observerUpdate' ].forEach(
			function ( event ) {
				swiper.on( event, apply );
			}
		);

		// Re-check when focus moves, so tabbing into a slide never lands on a node
		// that is about to be inerted.
		swiper.el.addEventListener( 'focusin', apply );

		apply();
	}

	/**
	 * Soften Swiper's slide-change announcements.
	 *
	 * Swiper 8 hard-codes aria-live="assertive" on its notification region, which
	 * interrupts a screen-reader user on every single slide change. Polite is the
	 * behaviour the carousel pattern calls for.
	 *
	 * @param {Object} swiper Swiper instance.
	 * @return {void}
	 */
	function politeAnnouncements( swiper ) {
		var note = swiper.el.querySelector( '.swiper-notification' );

		if ( note ) {
			note.setAttribute( 'aria-live', 'polite' );
		}
	}

	/**
	 * Wire the play/pause control required by WCAG 2.2.2 for moving content.
	 *
	 * @param {Element} root    Widget root.
	 * @param {Object}  swiper  Swiper instance.
	 * @param {boolean} running Whether autoplay actually started.
	 * @return {void}
	 */
	function bindPlayPause( root, swiper, running ) {
		var button = root.querySelector( '.zenblog__playpause' );

		if ( ! button ) {
			return;
		}

		var label = button.querySelector( '.zenblog__playpause-label' );
		var paused = ! running;

		function paint() {
			var text = paused
				? t( 'play', 'Start automatic slide show' )
				: t( 'pause', 'Stop automatic slide show' );

			// The accessible name carries the state (WAI-ARIA carousel pattern);
			// the class only drives the glyph.
			button.classList.toggle( 'zenblog__playpause--paused', paused );

			if ( label ) {
				label.textContent = text;
			}
		}

		button.addEventListener( 'click', function () {
			if ( ! swiper.autoplay ) {
				return;
			}

			paused = ! paused;

			// Remembered so the size-recovery watcher below does not helpfully
			// restart what the visitor just stopped.
			swiper.zenblogUserPaused = paused;

			if ( paused ) {
				swiper.autoplay.stop();
			} else {
				swiper.autoplay.start();
			}

			paint();
		} );

		// Keep the button honest when Swiper stops autoplay on its own — after a
		// swipe, say, or when the tab loses focus.
		if ( swiper.on ) {
			swiper.on( 'autoplayStop', function () {
				paused = true;
				paint();
			} );
			swiper.on( 'autoplayStart', function () {
				paused = false;
				paint();
			} );
		}

		paint();
	}

	/**
	 * Start autoplay once the carousel actually has a width.
	 *
	 * Swiper's autoplay refuses to schedule anything while the carousel measures
	 * zero — run() opens with `if ( ! swiper.size ) { running = false; return; }`
	 * — and nothing ever calls it again. So a carousel that initialises before
	 * its container has been laid out comes up with autoplay permanently dead,
	 * even though every setting says it should be running. That is the whole of
	 * the "works in the editor, frozen on the live site" difference: optimisation
	 * plugins defer or inline-and-async the stylesheet that gives the container
	 * its width, so on the real page the measurement at init is zero.
	 *
	 * @param {Object}  swiper Swiper instance.
	 * @param {Element} root   Widget root, watched for its size arriving.
	 * @return {void}
	 */
	function bindAutoplayRecovery( swiper, root ) {
		if ( ! swiper.autoplay || ! swiper.params.autoplay || ! swiper.params.autoplay.enabled ) {
			return;
		}

		var observer = null;
		var timer = null;
		var attempts = 0;

		function stopTimer() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function stopWatching() {
			if ( observer ) {
				observer.disconnect();
				observer = null;
			}
			stopTimer();
		}

		function rearm() {
			// Never fight the visitor: if they pressed pause, it stays paused.
			if ( ! swiper || swiper.destroyed || swiper.zenblogUserPaused ) {
				stopWatching();
				return true;
			}

			if ( swiper.autoplay.running ) {
				return true;
			}

			if ( ! swiper.size ) {
				return false;
			}

			swiper.update();
			swiper.autoplay.start();

			return swiper.autoplay.running;
		}

		if ( rearm() ) {
			return;
		}

		if ( window.ResizeObserver ) {
			observer = new window.ResizeObserver( function () {
				if ( rearm() ) {
					stopWatching();
				}
			} );
			observer.observe( root );
		}

		// A stylesheet arriving late changes the width without necessarily
		// producing a resize entry for this element, so give up on being told and
		// look — briefly, and only until it works.
		timer = window.setInterval( function () {
			attempts++;

			if ( rearm() ) {
				stopWatching();
				return;
			}

			if ( attempts <= 20 ) {
				return;
			}

			/*
			 * Five seconds of polling is plenty for the case it exists for — a
			 * width that arrives without producing a resize entry. But the
			 * observer is event-driven and costs nothing while idle, so it stays
			 * on: a carousel inside a tab, an accordion or an off-canvas panel
			 * can be revealed minutes after load, and tearing the observer down
			 * here left autoplay dead for the rest of the page's life.
			 *
			 * With no ResizeObserver to fall back on there is nothing left to
			 * wait with, so in that case give up completely rather than poll for
			 * ever.
			 */
			if ( observer ) {
				stopTimer();
			} else {
				stopWatching();
			}
		}, 250 );
	}

	function initWidget( root ) {
		if ( ! root || root.dataset.zenbloggerInit === '1' ) {
			return;
		}

		var track = root.querySelector( '.zenblog__track' );
		var opts = parseOptions( root );

		if ( ! track || ! opts ) {
			return;
		}

		opts.breakpoints = safeBreakpoints( opts.breakpoints );

		if ( typeof window.Swiper !== 'function' ) {
			// No Swiper: the CSS fallback keeps the track scrollable and snapping.
			return;
		}

		if ( track.swiper && typeof track.swiper.destroy === 'function' ) {
			track.swiper.destroy( true, true );
		}

		var config = buildConfig( root, opts );
		var swiper;

		try {
			swiper = new window.Swiper( track, config );
		} catch ( e ) {
			return;
		}

		root.dataset.zenbloggerInit = '1';
		root.classList.add( 'zenblog--ready' );

		politeAnnouncements( swiper );
		bindInertSlides( swiper );
		bindPlayPause( root, swiper, !! config.autoplay );
		bindAutoplayRecovery( swiper, root );
	}

	function initScope( scope ) {
		var node = scope || document;

		if ( node.classList && node.classList.contains( 'zenblog' ) ) {
			initWidget( node );
			return;
		}

		Array.prototype.forEach.call(
			node.querySelectorAll( '.zenblog' ),
			initWidget
		);
	}

	function onElementorInit() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}

		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/zen-blogger-carousel.default',
			function ( $scope ) {
				var el = $scope && $scope[ 0 ] ? $scope[ 0 ] : $scope;

				if ( el ) {
					// Elementor re-renders the widget on every control change, so a
					// previous instance may still be attached to the new markup.
					delete el.dataset.zenbloggerInit;
					initScope( el );
				}
			}
		);
	}

	// Elementor dispatches this through jQuery; binding with jQuery is the
	// version-agnostic route. Native binding is the fallback for the rare page
	// where jQuery is absent.
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', onElementorInit );
	} else {
		window.addEventListener( 'elementor/frontend/init', onElementorInit );
	}

	// Safety net for widgets rendered outside Elementor's ready hook (cached
	// pages, late-injected markup). initWidget() is idempotent.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initScope( document );
		} );
	} else {
		initScope( document );
	}
}() );
