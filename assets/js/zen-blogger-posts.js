/**
 * Zen Blogger — Posts widget.
 *
 * Pagination and filters are real links first; this upgrades them to fetch in
 * place. Every AJAX update is announced politely, the grid is marked busy while
 * it loads, focus moves deliberately, and the address bar is kept in step so
 * Back and link sharing keep working.
 */
( function () {
	'use strict';

	var i18n = window.zenBloggerI18n || {};

	function t( key, fallback ) {
		return typeof i18n[ key ] === 'string' ? i18n[ key ] : fallback;
	}

	function isUnsafeKey( key ) {
		return key === '__proto__' || key === 'constructor' || key === 'prototype';
	}

	function parseConfig( root ) {
		var raw = root.getAttribute( 'data-zenblog-posts' );

		if ( ! raw ) {
			return null;
		}

		try {
			return JSON.parse( raw, function ( k, v ) {
				return isUnsafeKey( k ) ? undefined : v;
			} );
		} catch ( e ) {
			return null;
		}
	}

	function closest( el, selector ) {
		return el && el.closest ? el.closest( selector ) : null;
	}

	/**
	 * Make the filter bar work by plain form submission.
	 *
	 * Used when there is no AJAX to fetch with. Every control still carries the
	 * right name and value, so submitting reloads the page with the chosen state
	 * in the query string — the same round trip a visitor without JavaScript
	 * makes, just without having to find a button that is not there.
	 *
	 * @param {Element} root Widget root.
	 * @return {void}
	 */
	function bindPlainForm( root ) {
		var form = root.querySelector( '.zenblog__filters' );

		if ( ! form ) {
			return;
		}

		function submit() {
			if ( form.requestSubmit ) {
				form.requestSubmit();
			} else {
				form.submit();
			}
		}

		form.addEventListener( 'change', function ( e ) {
			// Typing is not a decision; the search box submits on Enter instead.
			if ( closest( e.target, '.zenblog__search-input' ) ) {
				return;
			}

			submit();
		} );

		var search = form.querySelector( '.zenblog__search-input' );

		if ( search ) {
			// With more than one field in the form, browsers do not treat Enter
			// as implicit submission, and there is no submit button to fall back
			// on — so the search box would otherwise swallow the Enter key.
			search.addEventListener( 'keydown', function ( e ) {
				if ( 'Enter' === e.key ) {
					e.preventDefault();
					submit();
				}
			} );
		}

		var reset = root.querySelector( '.zenblog__filter-reset' );

		if ( reset ) {
			reset.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				// The action is this page with every filter argument stripped.
				window.location.href = form.getAttribute( 'action' ) || window.location.pathname;
			} );
		}
	}

	function initPosts( root ) {
		if ( ! root || root.dataset.zenbloggerPostsInit === '1' ) {
			return;
		}

		var cfg = parseConfig( root );

		if ( ! cfg ) {
			return;
		}

		/*
		 * No AJAX: the paging links work by themselves, but the filter bar does
		 * not. Its Apply button only exists inside <noscript>, which is not in
		 * the DOM at all once scripting is on — so without this the controls
		 * change nothing and the bar looks broken. Submitting the form is a real
		 * navigation, which is exactly what the no-JS path already does.
		 */
		if ( ! cfg.ajax || ! window.fetch ) {
			root.dataset.zenbloggerPostsInit = '1';
			bindPlainForm( root );
			return;
		}

		var grid = root.querySelector( '.zenblog__grid' );
		var status = root.querySelector( '.zenblog__status' );

		if ( ! grid ) {
			return;
		}

		root.dataset.zenbloggerPostsInit = '1';

		var form = root.querySelector( '.zenblog__filters' );
		var countEl = root.querySelector( '[data-zenblog-count]' );

		var state = {
			paged: cfg.paged || 1,
			maxPages: cfg.maxPages || 1,
			busy: false,
			autoRuns: 0,
			seq: 0
		};

		/**
		 * The filter state as a query string, read straight off the form.
		 *
		 * Serialising the real inputs means the AJAX request and the no-JS form
		 * submission always send the same thing — there is no second, parallel
		 * notion of "current filters" to drift out of step.
		 *
		 * @return {string} URL-encoded filter state.
		 */
		function filterQuery() {
			if ( ! form ) {
				return '';
			}
			var data = new URLSearchParams( new FormData( form ) );
			// Drop empties so a cleared search does not linger in the URL.
			var clean = new URLSearchParams();
			data.forEach( function ( v, k ) {
				if ( '' !== String( v ).trim() ) {
					clean.append( k, v );
				}
			} );
			return clean.toString();
		}

		var appendMode = cfg.nav === 'load_more' || cfg.nav === 'infinite';
		var autoMax = Math.max( 1, parseInt( cfg.autoMax, 10 ) || 5 );
		var observer = null;
		var idPart = root.id.replace( /[^a-z0-9]/gi, '' );

		function announce( message ) {
			if ( ! status || ! message ) {
				return;
			}
			// Clearing first makes a repeated identical string announce again
			// instead of being swallowed as "no change".
			status.textContent = '';
			window.setTimeout( function () {
				status.textContent = message;
			}, 50 );
		}

		function setBusy( on ) {
			state.busy = on;
			grid.setAttribute( 'aria-busy', on ? 'true' : 'false' );
			root.classList.toggle( 'zenblog--loading', on );

			var btn = root.querySelector( '.zenblog__more-btn' );

			if ( btn ) {
				btn.setAttribute( 'aria-disabled', on ? 'true' : 'false' );
			}
		}

		function endpoint( paged, query ) {
			var url = new URL( cfg.restUrl, window.location.origin );

			// post_id is the document the widget is STORED in; context_id is the
			// post it is being displayed for. On a theme template those are two
			// different posts, and the server needs both.
			url.searchParams.set( 'post_id', cfg.postId );
			url.searchParams.set( 'element_id', cfg.elementId );
			url.searchParams.set( 'paged', paged );
			url.searchParams.set( 'query', query );

			if ( cfg.contextId ) {
				url.searchParams.set( 'context_id', cfg.contextId );
			}

			if ( cfg.pageUrl ) {
				url.searchParams.set( 'page_url', cfg.pageUrl );
			}

			// Current Query only: which archive this is, so the endpoint can
			// rebuild a listing that does not exist in a REST request.
			if ( cfg.context ) {
				url.searchParams.set( 'context', JSON.stringify( cfg.context ) );
			}

			return url.toString();
		}

		function syncAddressBar( paged, query ) {
			if ( ! window.history || ! window.history.pushState ) {
				return;
			}

			var url = new URL( window.location.href );

			// Drop every arg this widget owns, then re-add the live ones. Anything
			// belonging to another widget or plugin on the page is left alone.
			Array.from( url.searchParams.keys() ).forEach( function ( k ) {
				if ( 0 === k.indexOf( 'zbp_' + idPart ) || 0 === k.indexOf( 'zbs_' + idPart ) ||
					0 === k.indexOf( 'zbo_' + idPart ) || 0 === k.indexOf( 'zbt_' + idPart ) ) {
					url.searchParams.delete( k );
				}
			} );

			/*
			 * Load More and infinite scroll accumulate pages, so "page 3" in the
			 * URL would not describe what is on screen — going Back would restore
			 * a single page where the visitor left three. Only real paginators put
			 * a page number in the address bar. Filters always go in: those are
			 * genuinely linkable, restorable state.
			 */
			/*
			 * Current Query pages through the archive's own `paged`, so writing
			 * this widget's private argument would put a number in the URL that
			 * nothing reads — and a reload would land back on page one.
			 */
			if ( ! appendMode && ! cfg.mainPaging && paged > 1 ) {
				url.searchParams.set( 'zbp_' + idPart, paged );
			}

			new URLSearchParams( query ).forEach( function ( v, k ) {
				url.searchParams.append( k, v );
			} );

			window.history.pushState( { zenblog: root.id, paged: paged, query: query }, '', url.toString() );
		}

		/**
		 * Replace the paginator with the one the server just rendered.
		 *
		 * Patching the existing links by hand cannot work for numbered pages: the
		 * window slides with the current page, so the set of links itself changes.
		 * Editing only aria-current left the window frozen on its first range,
		 * which made every page beyond it unreachable.
		 *
		 * @param {string} html Rendered <nav>, empty when there is nothing left to page to.
		 * @return {void}
		 */
		function updateNav( html ) {
			var nav = root.querySelector( '.zenblog__nav' );

			if ( 'string' !== typeof html ) {
				return;
			}

			if ( '' === html.trim() ) {
				if ( nav ) {
					nav.remove();
				}
				return;
			}

			var holder = document.createElement( 'div' );
			holder.innerHTML = html;
			var fresh = holder.querySelector( '.zenblog__nav' );

			if ( ! fresh ) {
				return;
			}

			if ( nav ) {
				nav.replaceWith( fresh );
			} else {
				root.appendChild( fresh );
			}
		}

		/**
		 * @param {number}  paged     Page to load.
		 * @param {number}  term      Term filter id, 0 for all.
		 * @param {boolean} append    Append rather than replace.
		 * @param {boolean} moveFocus Move focus to the first new card.
		 * @return {void}
		 */
		function load( paged, query, append, moveFocus ) {
			// A newer request supersedes an in-flight one rather than queueing
			// behind it: typing in the search box fires several in a row, and the
			// slowest must not be the one that wins.
			var ticket = ++state.seq;

			setBusy( true );

			fetch( endpoint( paged, query ), { headers: { Accept: 'application/json' } } )
				.then( function ( r ) {
					if ( ! r.ok ) {
						throw new Error( 'HTTP ' + r.status );
					}
					return r.json();
				} )
				.then( function ( data ) {
					if ( ticket !== state.seq ) {
						return; // superseded
					}
					var firstNew = null;

					if ( append ) {
						var holder = document.createElement( 'div' );
						holder.innerHTML = data.html;
						var added = Array.prototype.slice.call( holder.children );
						firstNew = added[ 0 ] || null;
						added.forEach( function ( node ) {
							grid.appendChild( node );
						} );
					} else {
						grid.innerHTML = data.html;
						firstNew = grid.querySelector( '.zenblog__item' );
					}

					state.paged = data.paged;
					state.maxPages = data.maxPages;

					if ( countEl && data.countLabel ) {
						countEl.textContent = data.countLabel;
					}

					updateNav( data.nav );
					syncResetVisibility();

					/*
					 * On a tall viewport the sentinel can still be on screen after a
					 * page is appended. IntersectionObserver only fires on a change of
					 * intersection, so without this nudge the feed stalls until the
					 * visitor scrolls away and back.
					 */
					if ( observer && append && state.paged < state.maxPages && state.autoRuns < autoMax ) {
						var sentinelNow = root.querySelector( '.zenblog__sentinel' );
						if ( sentinelNow ) {
							observer.unobserve( sentinelNow );
							window.requestAnimationFrame( function () {
								observer.observe( sentinelNow );
							} );
						}
					}
					syncAddressBar( data.paged, query );
					announce( data.message );

					// Focus only ever moves for something the visitor did. On an
					// auto-triggered infinite load, taking focus would yank them
					// out of whatever they were reading.
					if ( moveFocus && firstNew && firstNew.focus ) {
						firstNew.focus( { preventScroll: append } );
					}
				} )
				.catch( function () {
					announce( t( 'loadError', 'Could not load posts. Please try again.' ) );
				} )
				.then( function () {
					if ( ticket === state.seq ) {
						setBusy( false );
					}
				} );
		}

		function syncResetVisibility() {
			var reset = root.querySelector( '.zenblog__filter-reset' );

			if ( reset ) {
				reset.hidden = '' === filterQuery();
			}
		}

		function applyFilters( moveFocus ) {
			state.autoRuns = 0;
			syncResetVisibility();
			load( 1, filterQuery(), false, moveFocus );
		}

		syncResetVisibility();

		if ( form ) {
			// Submitting still works with the keyboard and is the no-JS path; here
			// it just becomes an in-place fetch.
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				applyFilters( true );
			} );

			// Radios, checkboxes and selects apply immediately — but focus is NOT
			// moved, because the visitor is still working through the controls.
			form.addEventListener( 'change', function ( e ) {
				if ( closest( e.target, '.zenblog__search-input' ) ) {
					return;
				}
				applyFilters( false );
			} );

			var searchInput = form.querySelector( '.zenblog__search-input' );

			if ( searchInput ) {
				var timer = null;
				searchInput.addEventListener( 'input', function () {
					window.clearTimeout( timer );
					timer = window.setTimeout( function () {
						applyFilters( false );
					}, 350 );
				} );
			}

			var reset = root.querySelector( '.zenblog__filter-reset' );

			if ( reset ) {
				reset.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					form.reset();
					Array.prototype.forEach.call( form.querySelectorAll( 'input[type=checkbox]' ), function ( c ) {
						c.checked = false;
					} );
					Array.prototype.forEach.call( form.querySelectorAll( 'input[type=radio][value=""]' ), function ( r ) {
						r.checked = true;
					} );
					Array.prototype.forEach.call( form.querySelectorAll( 'input[type=search]' ), function ( i ) {
						i.value = '';
					} );
					applyFilters( true );
				} );
			}
		}

		root.addEventListener( 'click', function ( e ) {
			// Let modified clicks (new tab, middle-click) behave normally.
			if ( e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || 0 !== e.button ) {
				return;
			}

			var page = closest( e.target, '.zenblog__page' );

			if ( page ) {
				e.preventDefault();
				load( parseInt( page.getAttribute( 'data-zenblog-page' ), 10 ) || 1, filterQuery(), false, true );
				return;
			}

			var more = closest( e.target, '.zenblog__more-btn' );

			if ( more ) {
				e.preventDefault();

				// The server stops rendering this button on the last page, so
				// reaching here past the end means stale markup — asking for a
				// page that does not exist would append an empty result.
				if ( state.paged >= state.maxPages ) {
					updateNav( '' );
					return;
				}

				state.autoRuns = 0; // pressing the button re-arms auto-loading

				if ( observer ) {
					root.classList.add( 'zenblog--auto' );
				}

				load( state.paged + 1, filterQuery(), true, true );
			}
		} );

		window.addEventListener( 'popstate', function () {
			var url = new URL( window.location.href );
			var paged = parseInt( url.searchParams.get( 'zbp_' + idPart ), 10 ) || 1;

			// Restore the form controls first — the inputs ARE the filter state, so
			// leaving them stale would both mislead the visitor and make the next
			// interaction send the wrong query.
			var restored = new URLSearchParams();
			url.searchParams.forEach( function ( v, k ) {
				if ( 0 === k.indexOf( 'zbs_' + idPart ) || 0 === k.indexOf( 'zbo_' + idPart ) ||
					0 === k.indexOf( 'zbt_' + idPart ) ) {
					restored.append( k, v );
				}
			} );

			if ( form ) {
				Array.prototype.forEach.call( form.elements, function ( el ) {
					if ( ! el.name ) {
						return;
					}
					var values = restored.getAll( el.name ) .concat( restored.getAll( el.name.replace( /\[\]$/, '' ) ) );
					if ( 'checkbox' === el.type || 'radio' === el.type ) {
						el.checked = values.indexOf( el.value ) !== -1 || ( 'radio' === el.type && '' === el.value && ! values.length );
					} else {
						el.value = values.length ? values[ 0 ] : '';
					}
				} );
			}

			load( paged, restored.toString(), false, false );
		} );

		if ( 'infinite' === cfg.nav && window.IntersectionObserver ) {
			/*
			 * The sentinel is a dedicated element after the paginator, not the
			 * paginator itself: the paginator is replaced on every page, and an
			 * observer left watching the detached node never fires again.
			 */
			var sentinel = root.querySelector( '.zenblog__sentinel' );

			if ( sentinel ) {
				observer = new IntersectionObserver(
					function ( entries ) {
						entries.forEach( function ( entry ) {
							if ( ! entry.isIntersecting || state.busy ) {
								return;
							}

							if ( state.paged >= state.maxPages ) {
								// Nothing left; stop watching rather than spinning.
								observer.disconnect();
								return;
							}

							if ( state.autoRuns >= autoMax ) {
								// Hand control back: show the real button again.
								root.classList.remove( 'zenblog--auto' );
								return;
							}

							state.autoRuns++;
							load( state.paged + 1, filterQuery(), true, false );
						} );
					},
					{ rootMargin: '300px' }
				);
				/*
				 * Auto-loading is live, so the button has nothing to say — the
				 * stylesheet swaps it for the spinner while this class is set,
				 * keeping it reachable by Tab. It comes off again the moment
				 * auto-loading pauses, which is when there IS something to press.
				 */
				root.classList.add( 'zenblog--auto' );
				observer.observe( sentinel );
			}
		}
	}

	function initAll( scope ) {
		var node = scope || document;

		if ( node.classList && node.classList.contains( 'zenblog-posts' ) ) {
			initPosts( node );
			return;
		}

		Array.prototype.forEach.call( node.querySelectorAll( '.zenblog-posts' ), initPosts );
	}

	function onInit() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}

		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/zen-blogger-posts.default',
			function ( $scope ) {
				var el = $scope && $scope[ 0 ] ? $scope[ 0 ] : $scope;

				if ( el ) {
					delete el.dataset.zenbloggerPostsInit;
					initAll( el );
				}
			}
		);
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', onInit );
	} else {
		window.addEventListener( 'elementor/frontend/init', onInit );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}
}() );
