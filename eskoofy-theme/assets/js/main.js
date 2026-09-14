/*!
 * Eskoofy theme — front-end behaviours.
 * Reveal-on-scroll, stat count-up, horizontal sliders, sticky header,
 * mobile menu, search overlay, notice ticker speed and dark-mode toggle.
 */
(function () {
	'use strict';

	document.documentElement.classList.add('js');
	document.body.classList.add('js');

	var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ── Sticky header shadow ─────────────────────────────────────────── */
	var header = document.querySelector('.esk-header');
	function onScroll() {
		if (!header) return;
		document.body.classList.toggle('esk-scrolled', window.scrollY > 8);
	}
	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();

	/* ── Reveal on scroll ─────────────────────────────────────────────── */
	var reveals = document.querySelectorAll('.reveal');
	if (prefersReduced || !('IntersectionObserver' in window)) {
		reveals.forEach(function (el) { el.classList.add('is-revealed'); });
	} else if (reveals.length) {
		var revealObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-revealed');
					revealObserver.unobserve(entry.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
		reveals.forEach(function (el) { revealObserver.observe(el); });
	}

	/* ── Stat count-up ────────────────────────────────────────────────── */
	var countups = document.querySelectorAll('[data-countup]');
	function runCount(el) {
		var target = parseInt(el.getAttribute('data-target') || '0', 10);
		var suffix = el.getAttribute('data-suffix') || '';
		var start = performance.now();
		var duration = 1400;
		function frame(now) {
			var p = Math.min((now - start) / duration, 1);
			var eased = 1 - Math.pow(1 - p, 3);
			el.textContent = Math.round(target * eased).toLocaleString() + suffix;
			if (p < 1) window.requestAnimationFrame(frame);
		}
		window.requestAnimationFrame(frame);
	}
	if (!prefersReduced && 'IntersectionObserver' in window && countups.length) {
		var countObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					runCount(entry.target);
					countObserver.unobserve(entry.target);
				}
			});
		}, { threshold: 0.5 });
		countups.forEach(function (el) { countObserver.observe(el); });
	} else {
		countups.forEach(runCount);
	}

	/* ── Horizontal sliders (common scroll-by) ────────────────────────── */
	var sliderPairs = [
		['[data-teachers-prev]', '[data-teachers-next]', '[data-teachers-track]'],
		['[data-committee-prev]', '[data-committee-next]', '[data-committee-track]'],
		['[data-slider-prev]', '[data-slider-next]', '[data-slider-track]']
	];
	sliderPairs.forEach(function (pair) {
		var prev = document.querySelector(pair[0]);
		var next = document.querySelector(pair[1]);
		var track = document.querySelector(pair[2]);
		if (!prev || !next || !track) return;
		function scrollBy(delta) { track.scrollBy({ left: delta, behavior: prefersReduced ? 'auto' : 'smooth' }); }
		prev.addEventListener('click', function () { scrollBy(-(track.clientWidth * 0.8)); });
		next.addEventListener('click', function () { scrollBy(track.clientWidth * 0.8); });
	});

	/* ── Notices scroll speed ─────────────────────────────────────────── */
	document.querySelectorAll('.notice-scroll-container').forEach(function (wrap) {
		var speed = parseFloat(wrap.getAttribute('data-scroll-speed') || '12');
		wrap.style.setProperty('--esk-ticker', speed + 's');
	});

	/* ── Mobile menu ──────────────────────────────────────────────────── */
	var hamburger = document.querySelector('.esk-hamburger');
	var panel = document.querySelector('.esk-mobile-panel');
	var backdrop = document.querySelector('.esk-overlay-backdrop');
	var panelClose = document.querySelector('.esk-panel-close');

	function togglePanel(force) {
		if (!menuItemsExist()) return;
		var open = typeof force === 'boolean' ? force : !(panel && panel.classList.contains('is-open'));
		if (hamburger) hamburger.classList.toggle('is-open', open);
		if (panel) panel.classList.toggle('is-open', open);
		if (backdrop) backdrop.classList.toggle('is-open', open);
		document.body.style.overflow = open ? 'hidden' : '';
	}
	function menuItemsExist() {
		return Boolean((panel && panel.querySelector('.esk-panel-menu') && panel.querySelector('.esk-panel-menu').children.length) || (hamburger && hamburger.querySelectorAll('button').length));
	}
	if (hamburger) hamburger.addEventListener('click', function () { togglePanel(); });
	if (backdrop) backdrop.addEventListener('click', function () { togglePanel(false); });
	if (panelClose) panelClose.addEventListener('click', function () { togglePanel(false); });
	if (panel) {
		panel.querySelectorAll('a').forEach(function (link) {
			link.addEventListener('click', function () { togglePanel(false); });
		});
	}

	/* ── Search overlay ───────────────────────────────────────────────── */
	var searchToggle = document.querySelector('.esk-search-toggle');
	var searchOverlay = document.querySelector('.esk-search-overlay');
	var searchInput = document.querySelector('.esk-search-input');
	var searchClose = document.querySelector('.esk-close-search');

	function toggleSearch(open) {
		if (!searchOverlay) return;
		var willOpen = typeof open === 'boolean' ? open : !searchOverlay.classList.contains('is-open');
		searchOverlay.classList.toggle('is-open', willOpen);
		document.body.style.overflow = willOpen ? 'hidden' : '';
		if (willOpen && searchInput) {
			window.setTimeout(function () { searchInput.focus(); }, 120);
		}
	}
	if (searchToggle) searchToggle.addEventListener('click', function () { toggleSearch(true); });
	if (searchClose) searchClose.addEventListener('click', function () { toggleSearch(false); });
	if (searchOverlay) {
		searchOverlay.addEventListener('click', function (event) {
			if (event.target === searchOverlay) toggleSearch(false);
		});
	}
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			toggleSearch(false);
			togglePanel(false);
		}
		if (event.key === '/' && searchOverlay && !searchOverlay.classList.contains('is-open')) {
			var tag = (event.target.tagName || '').toLowerCase();
			if (!/^(input|textarea|select)$/.test(tag)) toggleSearch(true);
		}
	});

	/* ── Dark mode toggle ─────────────────────────────────────────────── */
	var darkToggle = document.querySelector('.esk-dark-toggle');
	function applyDark(dark) {
		document.body.classList.toggle('esk-dark', dark);
		try { window.localStorage.setItem('school-dark-mode', dark ? '1' : ''); } catch (e) {}
	}
	if (darkToggle) {
		darkToggle.addEventListener('click', function () {
			applyDark(!document.body.classList.contains('esk-dark'));
		});
	}
})();