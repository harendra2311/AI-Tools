/* ==========================================================================
   Abasyn Elementor Sections — carousels + scroll reveal  v1.1
   ========================================================================== */
( function () {
	'use strict';

	/* ---- Slider ---------------------------------------------------------- */
	function initSlider( root, externalPrev, externalNext ) {
		if ( root.dataset.absReady ) return;
		root.dataset.absReady = '1';

		var track   = root.querySelector( '.abs-slider__track' );
		var slides  = track ? Array.prototype.slice.call( track.children ) : [];
		var dotsBox = root.querySelector( '.abs-dots' );

		// Internal arrows (hero, testimonials) OR external arrows passed in (categories)
		var prev = externalPrev || root.querySelector( '.abs-arrow--prev' );
		var next = externalNext || root.querySelector( '.abs-arrow--next' );

		if ( ! track || slides.length === 0 ) return;

		var perView  = parseInt( root.dataset.absPer || '1', 10 );
		var loop     = root.dataset.absLoop !== '0';
		var autoplay = root.dataset.absAutoplay === '1';
		var interval = parseInt( root.dataset.absInterval || '6000', 10 );
		var perViewSm = parseInt( root.dataset.absPerSm || root.dataset.absPer || '1', 10 );

		var maxIndex = Math.max( 0, slides.length - perView );
		var index = 0;
		var timer = null;
		var dots  = [];

		function buildDots() {
			if ( ! dotsBox ) return;
			dots = [];
			dotsBox.innerHTML = '';
			var dotCount = Math.max( 0, slides.length - perView ) + 1;
			for ( var i = 0; i < dotCount; i++ ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.className = 'abs-dot';
				b.setAttribute( 'aria-label', 'Go to slide ' + ( i + 1 ) );
				( function ( n ) { b.addEventListener( 'click', function () { go( n, true ); } ); } )( i );
				dotsBox.appendChild( b );
				dots.push( b );
			}
		}

		function render() {
			var step = 100 / perView;
			track.style.transform = 'translateX(-' + ( index * step ) + '%)';
			dots.forEach( function ( d, i ) { d.classList.toggle( 'is-active', i === index ); } );
			// arrow disabled states
			if ( prev ) prev.disabled = ! loop && index === 0;
			if ( next ) next.disabled = ! loop && index >= maxIndex;
		}

		function go( n, user ) {
			if ( n < 0 )        n = loop ? maxIndex : 0;
			if ( n > maxIndex ) n = loop ? 0 : maxIndex;
			index = n;
			render();
			if ( user ) restart();
		}

		if ( next ) next.addEventListener( 'click', function () { go( index + 1, true ); } );
		if ( prev ) prev.addEventListener( 'click', function () { go( index - 1, true ); } );

		function start()   { if ( autoplay && maxIndex > 0 ) timer = setInterval( function () { go( index + 1 ); }, interval ); }
		function stop()    { if ( timer ) { clearInterval( timer ); timer = null; } }
		function restart() { stop(); start(); }

		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );

		function applyResponsive() {
			var target = window.innerWidth <= 768
				? perViewSm
				: parseInt( root.dataset.absPer || '1', 10 );
			if ( target !== perView ) {
				perView  = target;
				maxIndex = Math.max( 0, slides.length - perView );
				if ( index > maxIndex ) index = maxIndex;
				buildDots();
			}
			render();
		}
		window.addEventListener( 'resize', applyResponsive );

		buildDots();
		applyResponsive();
		start();
	}

	/* ---- Categories: connect external nav arrows to the slider ----------- */
	function initCatsNav( scope ) {
		( scope || document ).querySelectorAll( '.abs-cats' ).forEach( function ( cats ) {
			var slider = cats.querySelector( '.abs-slider' );
			if ( ! slider ) return;
			var prev = cats.querySelector( '.abs-cats__nav .abs-arrow--prev' );
			var next = cats.querySelector( '.abs-cats__nav .abs-arrow--next' );
			// pass external arrows into slider init
			if ( slider.dataset.absReady ) {
				// already initialised — re-wire by deleting flag and re-initing
				delete slider.dataset.absReady;
			}
			initSlider( slider, prev, next );
		} );
	}

	/* ---- Scroll reveal --------------------------------------------------- */
	var observer = null;
	function getObserver() {
		if ( observer ) return observer;
		if ( ! ( 'IntersectionObserver' in window ) ) return null;
		observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					var el    = e.target;
					var delay = parseFloat( el.dataset.absDelay || '0' );
					el.style.transitionDelay = delay + 's';
					el.classList.add( 'is-in' );
					observer.unobserve( el );
				}
			} );
		}, { threshold: 0.18 } );
		return observer;
	}
	function initReveal( scope ) {
		var obs   = getObserver();
		var items = ( scope || document ).querySelectorAll( '.abs-reveal:not([data-abs-seen])' );
		items.forEach( function ( el, i ) {
			el.dataset.absSeen = '1';
			if ( ! el.dataset.absDelay ) el.dataset.absDelay = ( i % 4 ) * 0.09;
			if ( obs ) { obs.observe( el ); } else { el.classList.add( 'is-in' ); }
		} );
	}

	/* ---- Mobile nav toggle ---------------------------------------------- */
	function initHeader( scope ) {
		( scope || document ).querySelectorAll( '.abs-hd:not([data-abs-hd])' ).forEach( function ( hd ) {
			hd.dataset.absHd = '1';
			var burger = hd.querySelector( '.abs-hd__burger' );
			if ( burger ) {
				burger.addEventListener( 'click', function () { hd.classList.toggle( 'abs-hd--open' ); } );
			}
			hd.querySelectorAll( '.abs-hd__nav a' ).forEach( function ( a ) {
				a.addEventListener( 'click', function () { hd.classList.remove( 'abs-hd--open' ); } );
			} );
		} );
	}

	/* ---- Boot ------------------------------------------------------------ */
	function initAll( scope ) {
		// Regular sliders (hero, testimonials — arrows are INSIDE .abs-slider)
		( scope || document ).querySelectorAll( '.abs-slider' ).forEach( function ( s ) {
			// skip sliders inside .abs-cats — handled separately below
			if ( s.closest( '.abs-cats' ) ) return;
			initSlider( s );
		} );
		// Categories: external nav arrows
		initCatsNav( scope || document );
		initReveal( scope || document );
		initHeader( scope || document );
	}

	if ( document.readyState !== 'loading' ) { initAll(); }
	else { document.addEventListener( 'DOMContentLoaded', function () { initAll(); } ); }
	window.addEventListener( 'load', function () { initAll(); } );

	// Elementor editor/frontend hooks
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( ! window.elementorFrontend || ! elementorFrontend.hooks ) return;
		var types = [
			'abasyn-header', 'abasyn-hero', 'abasyn-about',
			'abasyn-categories', 'abasyn-why', 'abasyn-how',
			'abasyn-testimonials', 'abasyn-cta'
		];
		types.forEach( function ( t ) {
			elementorFrontend.hooks.addAction( 'frontend/element_ready/' + t + '.default', function ( $scope ) {
				var el = $scope && $scope[ 0 ] ? $scope[ 0 ] : null;
				if ( ! el ) return;
				// Reset all sliders so they re-init cleanly after editor re-render
				el.querySelectorAll( '.abs-slider' ).forEach( function ( s ) {
					delete s.dataset.absReady;
				} );
				initAll( el );
			} );
		} );
	} );
} )();
