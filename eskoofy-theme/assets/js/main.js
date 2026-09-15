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

	/* ── Top loading bar (mirrors app global loading-bar) ─────────────── */
	var loadingBar = document.querySelector('[data-esk-loading-bar]');
	if (loadingBar) {
		var progress = 0;
		var timer = null;
		document.addEventListener('click', function (event) {
			var link = event.target.closest('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not([data-no-loading])');
			if (link && !link.closest('[data-no-loading]') && link.href && link.href.indexOf(window.location.origin) === 0 && link.href !== window.location.href) {
				loadingBar.classList.add('is-active');
				progress = 0;
				loadingBar.style.width = '10%';
				timer = setInterval(function () {
					progress += 5;
					if (progress > 90) { clearInterval(timer); return; }
					loadingBar.style.width = progress + '%';
				}, 100);
			}
		});
		window.addEventListener('load', function () {
			if (timer) clearInterval(timer);
			if (loadingBar) {
				loadingBar.style.width = '100%';
				setTimeout(function () {
					loadingBar.classList.remove('is-active');
					loadingBar.style.width = '0';
				}, 300);
			}
		});
	}

	/* ── Toast notifications (mirrors app showToast) ──────────────────── */
	function escHtml(value) {
		var div = document.createElement('div');
		div.textContent = String(value == null ? '' : value);
		return div.innerHTML;
	}
	window.eskToast = function (message, type, duration) {
		var root = document.getElementById('esk-toast-root');
		if (!root || !message) return;
		type = type || 'info';
		duration = typeof duration === 'number' ? duration : 5000;
		var toast = document.createElement('div');
		toast.className = 'esk-toast esk-toast--' + type;
		toast.setAttribute('role', 'alert');
		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'esk-toast__close';
		close.setAttribute('aria-label', 'Dismiss');
		close.innerHTML = '&times;';
		var text = document.createElement('span');
		text.textContent = message;
		toast.appendChild(text);
		toast.appendChild(close);
		var dismiss = function () {
			toast.classList.add('esk-toast--leaving');
			setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 250);
		};
		close.addEventListener('click', dismiss);
		root.appendChild(toast);
		if (duration > 0) setTimeout(dismiss, duration);
	};
	document.querySelectorAll('[data-esk-flash-toast]').forEach(function (el) {
		window.eskToast(el.getAttribute('data-message'), el.getAttribute('data-type') || 'info');
	});

	/* ── Confirm modal (mirrors app confirmAction) ────────────────────── */
	window.eskConfirm = function (options) {
		options = options || {};
		return new Promise(function (resolve) {
			var root = document.getElementById('esk-confirm-modal');
			if (!root) { resolve(window.confirm(options.message || 'Are you sure?')); return; }
			var title = root.querySelector('[data-confirm-title]');
			var message = root.querySelector('[data-confirm-message]');
			var okBtn = root.querySelector('[data-confirm-ok]');
			var cancelBtn = root.querySelector('[data-confirm-cancel]');
			var backdrop = root.querySelector('[data-confirm-backdrop]');
			var previouslyFocused = document.activeElement;
			title.textContent = options.title || 'Are you sure?';
			message.textContent = options.message || 'This action cannot be undone.';
			okBtn.textContent = options.confirmLabel || 'Confirm';
			var close = function (result) {
				root.classList.remove('is-open');
				root.setAttribute('aria-hidden', 'true');
				document.body.style.overflow = '';
				document.removeEventListener('keydown', onKey);
				if (previouslyFocused && typeof previouslyFocused.focus === 'function') previouslyFocused.focus();
				resolve(result);
			};
			var onKey = function (e) {
				if (e.key === 'Escape') { e.preventDefault(); close(false); return; }
			};
			okBtn.addEventListener('click', function () { close(true); }, { once: true });
			cancelBtn.addEventListener('click', function () { close(false); }, { once: true });
			backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(false); }, { once: true });
			document.addEventListener('keydown', onKey);
			root.classList.add('is-open');
			root.setAttribute('aria-hidden', 'false');
			document.body.style.overflow = 'hidden';
			cancelBtn.focus();
		});
	};
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('form[data-confirm]');
		if (!form) return;
		e.preventDefault();
		window.eskConfirm({
			title: form.getAttribute('data-confirm-title') || 'Confirm action',
			message: form.getAttribute('data-confirm') || 'Are you sure?',
			confirmLabel: form.getAttribute('data-confirm-label') || 'Confirm'
		}).then(function (ok) { if (ok) form.submit(); });
	});
	document.addEventListener('click', function (e) {
		var link = e.target.closest('a[data-confirm]');
		if (!link) return;
		e.preventDefault();
		window.eskConfirm({
			title: link.getAttribute('data-confirm-title') || 'Confirm action',
			message: link.getAttribute('data-confirm') || 'Are you sure?',
			confirmLabel: link.getAttribute('data-confirm-label') || 'Confirm'
		}).then(function (ok) { if (ok) window.location.href = link.href; });
	});

	/* ── Slider auto-scroll (pauses on hover/focus, reduced-motion aware) */
	if (!prefersReduced) {
		var autoSliders = [
			['[data-teachers-track]'],
			['[data-committee-track]'],
			['[data-photo-track]', '[data-slider-track]']
		];
		autoSliders.forEach(function (selectors) {
			var track = null;
			selectors.some(function (sel) {
				track = document.querySelector(sel);
				return Boolean(track);
			});
			if (!track || track.scrollWidth <= track.clientWidth + 8) return;
			var timer = null;
			var step = function () {
				clearInterval(timer);
				timer = setInterval(function () {
					if (track.getAttribute('data-autoplay-paused') === '1') return;
					var max = track.scrollWidth - track.clientWidth;
					if (track.scrollLeft >= max - 4) {
						track.scrollTo({ left: 0, behavior: 'smooth' });
					} else {
						track.scrollBy({ left: track.clientWidth * 0.8, behavior: 'smooth' });
					}
				}, 5000);
			};
			['mouseenter', 'focusin', 'touchstart'].forEach(function (ev) {
				track.addEventListener(ev, function () {
					track.setAttribute('data-autoplay-paused', '1');
				}, { passive: true });
			});
			['mouseleave', 'focusout', 'touchend'].forEach(function (ev) {
				track.addEventListener(ev, function () {
					track.setAttribute('data-autoplay-paused', '0');
				}, { passive: true });
			});
			step();
		});
	}
})();