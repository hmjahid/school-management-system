/*!
 * Eskoofy theme — front-end behaviours.
 *
 * Feature-equivalent port of the Laravel app's resources/js/app.js, adapted
 * to the WordPress theme's vanilla-JS / esk-* conventions. Includes:
 * reveal-on-scroll, stat count-up, horizontal sliders, sticky header, mobile
 * menu, search overlay, notice ticker speed, dark-mode toggle, loading bar,
 * toasts, confirm modal, scroll-top, gallery filter + lightbox, multi-step
 * forms, countdowns, command palette (Cmd/Ctrl+K), debounce, unsaved-changes
 * warning, PWA install prompt, inline image previews, notifications dropdown
 * and dashboard favorites.
 */
(function () {
	'use strict';

	document.documentElement.classList.add('js');
	document.body.classList.add('js');

	var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function eskMetaContent(names) {
		for (var i = 0; i < names.length; i++) {
			var m = document.querySelector('meta[name="' + names[i] + '"]');
			if (m && m.getAttribute('content')) return m.getAttribute('content');
		}
		return '';
	}

	/* AJAX (admin-ajax.php) nonce — verified against action 'esk_ajax_nonce'. */
	window.eskAjaxNonce = function () {
		return (window.eskAdmin && window.eskAdmin.nonce) || eskMetaContent(['esk-nonce', 'csrf-token']);
	};

	/* REST (wp-json) nonce — WP requires the 'wp_rest' nonce for cookie auth. */
	window.eskRestNonce = function () {
		return (window.eskAdmin && window.eskAdmin.restNonce) || eskMetaContent(['wp-nonce', 'esk-rest-nonce']);
	};

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
			var danger = !!options.danger;

			title.textContent = options.title || 'Are you sure?';
			message.textContent = options.message || 'This action cannot be undone.';
			okBtn.textContent = options.confirmLabel || 'Confirm';

			if (danger) {
				root.classList.add('is-danger');
				okBtn.classList.add('esk-btn-danger');
				okBtn.classList.remove('esk-btn-accent');
			} else {
				root.classList.remove('is-danger');
				okBtn.classList.add('esk-btn-accent');
				okBtn.classList.remove('esk-btn-danger');
			}

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
				if (e.key !== 'Tab') return;
				var focusables = root.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
				var list = Array.prototype.filter.call(focusables, function (el) { return el.offsetParent !== null; });
				if (list.length === 0) return;
				var first = list[0];
				var last = list[list.length - 1];
				if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
				else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
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
			confirmLabel: form.getAttribute('data-confirm-label') || 'Confirm',
			danger: form.hasAttribute('data-confirm-danger')
		}).then(function (ok) { if (ok) form.submit(); });
	});
	document.addEventListener('click', function (e) {
		var link = e.target.closest('a[data-confirm]');
		if (!link) return;
		e.preventDefault();
		window.eskConfirm({
			title: link.getAttribute('data-confirm-title') || 'Confirm action',
			message: link.getAttribute('data-confirm') || 'Are you sure?',
			confirmLabel: link.getAttribute('data-confirm-label') || 'Confirm',
			danger: link.hasAttribute('data-confirm-danger')
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

	/* ── Scroll-to-top button (mirrors app) ───────────────────────────── */
	(function () {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.id = 'esk-scroll-top';
		btn.className = 'esk-scroll-top';
		btn.setAttribute('aria-label', 'Scroll to top');
		btn.innerHTML = '<svg class="esk-scroll-top-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>';
		btn.style.opacity = '0';
		btn.style.transform = 'translateY(1rem)';
		btn.style.pointerEvents = 'none';
		document.body.appendChild(btn);

		window.addEventListener('scroll', function () {
			if (window.scrollY > 300) {
				btn.style.opacity = '1';
				btn.style.transform = 'translateY(0)';
				btn.style.pointerEvents = 'auto';
			} else {
				btn.style.opacity = '0';
				btn.style.transform = 'translateY(1rem)';
				btn.style.pointerEvents = 'none';
			}
		}, { passive: true });

		btn.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: prefersReduced ? 'auto' : 'smooth' });
		});
	})();

	/* ── Gallery tab filtering (mirrors app) ──────────────────────────── */
	(function () {
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-filter-tabs] button[data-filter]');
			if (!btn) return;

			var tabs = btn.closest('[data-filter-tabs]');
			var grid = document.querySelector(tabs.getAttribute('data-target') || '[data-gallery-grid]');
			if (!grid) return;

			var filter = btn.getAttribute('data-filter');

			tabs.querySelectorAll('button[data-filter]').forEach(function (b) {
				var active = b.getAttribute('data-filter') === filter;
				b.setAttribute('data-active', active ? 'true' : 'false');
				b.classList.toggle('esk-filter-active', active);
				b.setAttribute('aria-pressed', active ? 'true' : 'false');
			});

			grid.querySelectorAll('[data-category]').forEach(function (el) {
				if (filter === 'all' || el.getAttribute('data-category') === filter) {
					el.classList.remove('is-hidden');
				} else {
					el.classList.add('is-hidden');
				}
			});
		});
	})();

	/* ── Gallery lightbox (mirrors app) ───────────────────────────────── */
	(function () {
		var openLightbox = function (images, index) {
			index = index || 0;
			var overlay = document.createElement('div');
			overlay.className = 'esk-lightbox';
			overlay.setAttribute('role', 'dialog');
			overlay.setAttribute('aria-modal', 'true');
			overlay.setAttribute('aria-label', 'Image gallery lightbox');

			var img = document.createElement('img');
			img.className = 'esk-lightbox-img';
			img.alt = 'Gallery image';
			img.src = images[index];

			var previouslyFocused = document.activeElement;

			var close = function () {
				overlay.remove();
				document.body.style.overflow = '';
				document.removeEventListener('keydown', onKey);
				if (previouslyFocused && typeof previouslyFocused.focus === 'function') previouslyFocused.focus();
			};
			var prev = function () { index = (index - 1 + images.length) % images.length; img.src = images[index]; };
			var next = function () { index = (index + 1) % images.length; img.src = images[index]; };

			overlay.innerHTML =
				'<button type="button" class="esk-lightbox-btn esk-lightbox-prev" aria-label="Previous image">' +
					'<svg class="esk-lightbox-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>' +
				'</button>' +
				'<button type="button" class="esk-lightbox-btn esk-lightbox-next" aria-label="Next image">' +
					'<svg class="esk-lightbox-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>' +
				'</button>' +
				'<button type="button" class="esk-lightbox-btn esk-lightbox-close" aria-label="Close lightbox">' +
					'<svg class="esk-lightbox-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
				'</button>';

			overlay.insertBefore(img, overlay.firstChild);
			document.body.appendChild(overlay);
			document.body.style.overflow = 'hidden';

			overlay.querySelector('.esk-lightbox-prev').addEventListener('click', prev);
			overlay.querySelector('.esk-lightbox-next').addEventListener('click', next);
			overlay.querySelector('.esk-lightbox-close').addEventListener('click', close);
			overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

			var onKey = function (e) {
				if (e.key === 'Escape') { e.preventDefault(); close(); return; }
				if (e.key === 'ArrowLeft') { e.preventDefault(); prev(); return; }
				if (e.key === 'ArrowRight') { e.preventDefault(); next(); return; }
				if (e.key !== 'Tab') return;
				var buttons = Array.prototype.filter.call(overlay.querySelectorAll('button'), function (b) { return b.offsetParent !== null; });
				if (buttons.length === 0) return;
				var first = buttons[0];
				var last = buttons[buttons.length - 1];
				if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
				else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
			};
			document.addEventListener('keydown', onKey);

			overlay.querySelector('.esk-lightbox-close').focus();
		};

		document.addEventListener('click', function (e) {
			var trigger = e.target.closest('[data-lightbox]');
			if (!trigger) return;
			e.preventDefault();
			var images = [];
			try { images = JSON.parse(trigger.getAttribute('data-lightbox') || '[]'); } catch (err) { images = []; }
			var index = parseInt(trigger.getAttribute('data-index') || '0', 10);
			if (images.length) openLightbox(images, index);
		});
	})();

	/* ── Multi-step form (mirrors app) ────────────────────────────────── */
	(function () {
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-step]');
			if (!btn) return;
			var form = btn.closest('[data-multistep]');
			if (!form) return;
			var steps = form.querySelectorAll('[data-step-panel]');
			var progress = form.querySelector('[data-step-progress]');
			var current = parseInt(form.getAttribute('data-current-step') || '0', 10);
			var target = parseInt(btn.getAttribute('data-step'), 10);

			if (isNaN(target) || target < 0 || target >= steps.length) return;

			if (target > current) {
				var currentPanel = steps[current];
				var inputs = currentPanel.querySelectorAll('input, select, textarea');
				var valid = true;
				inputs.forEach(function (input) {
					if (input.required && !String(input.value).trim()) {
						valid = false;
						input.classList.add('esk-input--error');
						input.addEventListener('input', function fix() {
							this.classList.remove('esk-input--error');
							this.removeEventListener('input', fix);
						}, { once: true });
					}
				});
				if (!valid) return;
			}

			steps.forEach(function (s, i) {
				s.classList.toggle('is-hidden', i !== target);
			});
			form.setAttribute('data-current-step', target);

			if (progress) {
				var pct = ((target + 1) / steps.length) * 100;
				progress.style.width = pct + '%';
				progress.textContent = Math.round(pct) + '%';
			}
		});
	})();

	/* ── Admission wizard (6-step, mirrors app) ───────────────────────── */
	(function () {
		var root = document.querySelector('[data-esk-wizard]');
		if (!root) return;
		var form = root.querySelector('[data-esk-wizard-form]');
		var steps = root.querySelectorAll('[data-esk-wizard-panel]');
		var dots = root.querySelectorAll('[data-esk-wizard-step-dot]');
		var current = 0;

		function go(target) {
			if (target < 0 || target >= steps.length) return;
			if (target > current) {
				var panel = steps[current];
				var inputs = panel.querySelectorAll('input, select, textarea');
				var valid = true;
				inputs.forEach(function (input) {
					if (input.required && !String(input.value).trim()) {
						valid = false;
						input.classList.add('esk-input--error');
						input.addEventListener('input', function fix() {
							this.classList.remove('esk-input--error');
							this.removeEventListener('input', fix);
						}, { once: true });
					}
				});
				if (!valid) return;
			}
			current = target;
			steps.forEach(function (s, i) {
				s.hidden = i !== current;
			});
			dots.forEach(function (d, i) {
				d.classList.toggle('is-active', i === current);
				d.classList.toggle('is-complete', i < current);
			});
			if (current === steps.length - 1) buildReview();
			if (root.scrollIntoView) root.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		function labelFor(name) {
			var input = form.querySelector('[name="' + name + '"]');
			if (!input) return '';
			var label = input.closest('.esk-form-group') ? input.closest('.esk-form-group').querySelector('label') : null;
			return label ? label.textContent.trim().replace(/\s*\*$/, '') : name;
		}

		function valueFor(name) {
			var input = form.querySelector('[name="' + name + '"]');
			if (!input) return '';
			if (input.type === 'file') {
				return input.files && input.files.length ? input.files[0].name : '—';
			}
			return String(input.value || '').trim() || '—';
		}

		function buildReview() {
			var review = root.querySelector('[data-esk-wizard-review]');
			if (!review) return;
			var groups = [
				['first_name', 'last_name', 'gender', 'date_of_birth'],
				['email', 'phone', 'address', 'city', 'postal_code'],
				['academic_session_id', 'batch_id', 'previous_school', 'previous_class'],
				['father_name', 'father_phone', 'mother_name', 'mother_phone', 'guardian_name', 'guardian_relation', 'guardian_phone'],
				['photo', 'transfer_certificate', 'birth_certificate'],
			];
			var html = '';
			groups.forEach(function (names) {
				html += '<div class="esk-wizard-review-group">';
				names.forEach(function (name) {
					html += '<p><strong>' + labelFor(name) + ':</strong> ' + valueFor(name) + '</p>';
				});
				html += '</div>';
			});
			review.innerHTML = html;
		}

		root.addEventListener('click', function (e) {
			if (e.target.closest('[data-esk-wizard-next]')) { go(current + 1); }
			else if (e.target.closest('[data-esk-wizard-back]')) { go(current - 1); }
		});
	})();

	/* ── Portal tabs (mirrors app portal) ─────────────────────────────── */
	(function () {
		var tabs = document.querySelector('[data-esk-portal-tabs]');
		if (!tabs) return;
		var panels = document.querySelectorAll('[data-esk-portal-panel]');
		function activate(name) {
			tabs.querySelectorAll('[data-esk-portal-tab]').forEach(function (b) {
				b.classList.toggle('is-active', b.getAttribute('data-esk-portal-tab') === name);
			});
			panels.forEach(function (p) {
				p.classList.toggle('is-active', p.getAttribute('data-esk-portal-panel') === name);
				p.hidden = p.getAttribute('data-esk-portal-panel') !== name;
			});
		}
		tabs.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-esk-portal-tab]');
			if (btn) activate(btn.getAttribute('data-esk-portal-tab'));
		});
	})();

	/* ── Countdown timer (mirrors app createCountdown) ────────────────── */
	window.createCountdown = function (element, targetDate) {
		var target = new Date(targetDate).getTime();
		function pad2(n) { return String(n).length < 2 ? '0' + n : String(n); }
		function tick() {
			var now = Date.now();
			var diff = target - now;
			if (diff <= 0) {
				element.innerHTML = '<span class="esk-countdown-done">Event started!</span>';
				return null;
			}
			var days = Math.floor(diff / 86400000);
			var hours = Math.floor((diff % 86400000) / 3600000);
			var minutes = Math.floor((diff % 3600000) / 60000);
			var seconds = Math.floor((diff % 60000) / 1000);
			element.innerHTML =
				'<span class="esk-countdown-item"><span class="esk-countdown-num">' + days + '</span><span class="esk-countdown-label">d</span></span>' +
				'<span class="esk-countdown-sep">:</span>' +
				'<span class="esk-countdown-item"><span class="esk-countdown-num">' + pad2(hours) + '</span><span class="esk-countdown-label">h</span></span>' +
				'<span class="esk-countdown-sep">:</span>' +
				'<span class="esk-countdown-item"><span class="esk-countdown-num">' + pad2(minutes) + '</span><span class="esk-countdown-label">m</span></span>' +
				'<span class="esk-countdown-sep">:</span>' +
				'<span class="esk-countdown-item"><span class="esk-countdown-num">' + pad2(seconds) + '</span><span class="esk-countdown-label">s</span></span>';
			return true;
		}
		tick();
		return setInterval(tick, 1000);
	};
	document.querySelectorAll('[data-countdown]').forEach(function (el) {
		window.createCountdown(el, el.getAttribute('data-countdown'));
	});

	/* ── Command palette (Cmd/Ctrl+K, mirrors app) ───────────────────── */
	(function () {
		var palette = null;
		var paletteCtrl = null;

		function createPalette(links) {
			palette = document.createElement('div');
			palette.className = 'esk-command-palette';
			palette.style.opacity = '0';
			palette.style.pointerEvents = 'none';
			palette.style.transition = 'opacity 0.2s ease';

			palette.innerHTML =
				'<div class="esk-command-backdrop" data-command-backdrop></div>' +
				'<div class="esk-command-panel" role="dialog" aria-modal="true" aria-label="Search pages, modules, settings">' +
					'<div class="esk-command-search">' +
						'<svg class="esk-command-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>' +
						'<input type="text" class="esk-command-input" placeholder="Search pages, modules, settings..." role="combobox" aria-expanded="true" aria-controls="esk-command-results" autocomplete="off">' +
						'<kbd class="esk-command-kbd">ESC</kbd>' +
					'</div>' +
					'<div class="esk-command-results" id="esk-command-results" data-esk-command-results></div>' +
				'</div>';

			document.body.appendChild(palette);

			var input = palette.querySelector('.esk-command-input');
			var results = palette.querySelector('[data-esk-command-results]');
			var backdrop = palette.querySelector('[data-command-backdrop]');

			function filterItems(query) {
				var q = String(query).toLowerCase();
				var filtered = links.filter(function (item) {
					return item.label.toLowerCase().indexOf(q) !== -1 || (item.keywords && item.keywords.toLowerCase().indexOf(q) !== -1);
				});
				if (filtered.length === 0) {
					results.innerHTML = '<div class="esk-command-empty">No results found</div>';
					return;
				}
				results.innerHTML = filtered.map(function (item) {
					return '<a href="' + escHtml(item.url) + '" class="esk-command-item">' +
						'<span class="esk-command-item-label">' + escHtml(item.label) + '</span>' +
						'<span class="esk-command-item-section">' + escHtml(item.section || '') + '</span>' +
						'</a>';
				}).join('');
			}

			input.addEventListener('input', function () { filterItems(input.value); });

			backdrop.addEventListener('click', close);
			palette.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

			function open() {
				palette.style.opacity = '1';
				palette.style.pointerEvents = 'auto';
				input.value = '';
				filterItems('');
				setTimeout(function () { input.focus(); }, 100);
			}

			function close() {
				palette.style.opacity = '0';
				palette.style.pointerEvents = 'none';
			}

			return { open: open, close: close };
		}

		var cmdLinks = [
			{ label: 'Dashboard', url: '/wp-admin/admin.php?page=esk-dashboard', section: 'Main', keywords: 'home index' },
			{ label: 'Students', url: '/wp-admin/admin.php?page=esk-students', section: 'Academic', keywords: 'pupil learner' },
			{ label: 'Teachers', url: '/wp-admin/admin.php?page=esk-teachers', section: 'Academic', keywords: 'staff faculty' },
			{ label: 'Parents', url: '/wp-admin/admin.php?page=esk-guardians', section: 'Academic', keywords: 'guardian' },
			{ label: 'Classes', url: '/wp-admin/admin.php?page=esk-classes', section: 'Academic', keywords: 'grades course' },
			{ label: 'Attendance', url: '/wp-admin/admin.php?page=esk-attendance', section: 'Academic', keywords: 'present absent' },
			{ label: 'Exams', url: '/wp-admin/admin.php?page=esk-exams', section: 'Academic', keywords: 'test assessment' },
			{ label: 'Fees', url: '/wp-admin/admin.php?page=esk-fees', section: 'Finance', keywords: 'payment collection' },
			{ label: 'Events', url: '/wp-admin/admin.php?page=esk-events', section: 'Academic', keywords: 'calendar' },
			{ label: 'News', url: '/wp-admin/admin.php?page=esk-news', section: 'Website', keywords: 'article blog' },
			{ label: 'Gallery', url: '/wp-admin/admin.php?page=esk-gallery', section: 'Website', keywords: 'photos images' },
			{ label: 'Settings', url: '/wp-admin/admin.php?page=esk-settings', section: 'System', keywords: 'config' },
			{ label: 'Reports', url: '/wp-admin/admin.php?page=esk-reports', section: 'System', keywords: 'analytics' },
			{ label: 'Website CMS', url: '/wp-admin/admin.php?page=esk-cms', section: 'Website', keywords: 'content editor' },
			{ label: 'Admissions', url: '/wp-admin/admin.php?page=esk-admissions', section: 'Academic', keywords: 'enrollment apply' },
			{ label: 'Transport', url: '/wp-admin/admin.php?page=esk-transport', section: 'Academic', keywords: 'bus route' },
			{ label: 'Payroll', url: '/wp-admin/admin.php?page=esk-payroll', section: 'Finance', keywords: 'salary payslip' },
			{ label: 'Activity Log', url: '/wp-admin/admin.php?page=esk-activity', section: 'System', keywords: 'audit log' }
		];

		paletteCtrl = createPalette(cmdLinks);

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && palette && palette.style.pointerEvents === 'auto') {
				paletteCtrl.close();
				return;
			}
			if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
				e.preventDefault();
				paletteCtrl.open();
			}
		});
	})();

	/* ── Debounce utility (mirrors app) ───────────────────────────────── */
	window.debounce = function (fn, delay) {
		var timer = null;
		delay = typeof delay === 'number' ? delay : 300;
		return function () {
			var args = Array.prototype.slice.call(arguments);
			var ctx = this;
			clearTimeout(timer);
			timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
		};
	};

	/* ── Unsaved changes warning (mirrors app) ────────────────────────── */
	(function () {
		var dirty = false;
		document.addEventListener('input', function (e) {
			if (e.target.closest && e.target.closest('[data-track-dirty]')) dirty = true;
		});
		document.addEventListener('change', function (e) {
			if (e.target.closest && e.target.closest('[data-track-dirty]')) dirty = true;
		});
		document.addEventListener('submit', function () { dirty = false; });
		window.addEventListener('beforeunload', function (e) {
			if (dirty) {
				e.preventDefault();
				e.returnValue = '';
			}
		});
	})();

	/* ── PWA install prompt (mirrors app) ─────────────────────────────── */
	(function () {
		window.__deferredInstallPrompt = null;

		window.addEventListener('beforeinstallprompt', function (e) {
			e.preventDefault();
			window.__deferredInstallPrompt = e;
			document.querySelectorAll('[data-pwa-install]').forEach(function (btn) {
				btn.classList.remove('is-hidden');
				if (btn.getAttribute('data-pwa-inline') === 'true') btn.classList.add('is-inline-flex');
				else btn.classList.add('is-flex');
			});
		});

		document.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-pwa-install]');
			if (!btn || !window.__deferredInstallPrompt) return;
			e.preventDefault();
			window.__deferredInstallPrompt.prompt();
			window.__deferredInstallPrompt.userChoice.then(function (result) {
				if (result.outcome === 'accepted') {
					btn.classList.add('is-hidden');
					btn.classList.remove('is-inline-flex', 'is-flex');
				}
				window.__deferredInstallPrompt = null;
			});
		});

		window.addEventListener('appinstalled', function () {
			window.__deferredInstallPrompt = null;
			document.querySelectorAll('[data-pwa-install]').forEach(function (btn) {
				btn.classList.add('is-hidden');
				btn.classList.remove('is-inline-flex', 'is-flex');
			});
		});
	})();

	/* ── Inline image previews (mirrors app) ──────────────────────────── */
	document.addEventListener('change', function (e) {
		var input = e.target.closest('input[type="file"][data-image-preview]');
		if (!input) return;
		var target = document.getElementById(input.getAttribute('data-image-preview'));
		if (!target) return;
		var file = input.files && input.files[0];
		if (file && file.type && file.type.indexOf('image/') === 0) {
			var reader = new FileReader();
			reader.onload = function () {
				target.src = reader.result;
				target.classList.remove('is-hidden');
			};
			reader.readAsDataURL(file);
		}
	});

	document.addEventListener('input', function (e) {
		var input = e.target.closest('input[data-image-url-preview]');
		if (!input) return;
		var target = document.getElementById(input.getAttribute('data-image-url-preview'));
		if (!target) return;
		var url = input.value.trim();
		if (url) {
			target.src = url;
			target.classList.remove('is-hidden');
		} else {
			target.classList.add('is-hidden');
			target.removeAttribute('src');
		}
	});

	/* ── Notifications dropdown (mirrors app; WP REST at esk/v1) ──────── */
	(function () {
		var root = document.querySelector('[data-notifications-root]');
		if (!root) return;

		var toggle = root.querySelector('[data-notifications-toggle]');
		var panel = root.querySelector('[data-notifications-panel]');
		var list = root.querySelector('[data-notifications-list]');
		var badge = root.querySelector('[data-notifications-badge]');
		var url = (list && list.getAttribute('data-url')) || '/wp-json/esk/v1/notifications';
		var pollTimer = null;

		function nonce() {
			return window.eskRestNonce();
		}

		function formatTime(iso) {
			if (!iso) return '';
			var d = new Date(iso);
			if (isNaN(d.getTime())) return '';
			var diff = (Date.now() - d.getTime()) / 1000;
			if (diff < 60) return Math.floor(diff) + 's ago';
			if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
			if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
			return Math.floor(diff / 86400) + 'd ago';
		}

		function render(items) {
			if (!items || items.length === 0) {
				list.innerHTML = '<div class="esk-notifications-empty">No notifications</div>';
				return;
			}
			list.innerHTML = items.map(function (n) {
				var title = escHtml(n.title || n.type || '');
				var msg = n.message ? '<p class="esk-notification-message">' + escHtml(n.message) + '</p>' : '';
				return '<a href="/wp-admin/admin.php?page=esk-notifications" class="esk-notification' + (n.unread ? ' esk-notification--unread' : '') + '">' +
					'<p class="esk-notification-title">' + title + '</p>' +
					msg +
					'<p class="esk-notification-time">' + escHtml(formatTime(n.created_at)) + '</p>' +
					'</a>';
			}).join('');
		}

		function updateBadge(count) {
			if (count > 0) {
				if (badge) {
					badge.textContent = count > 99 ? '99+' : String(count);
				} else {
					var b = document.createElement('span');
					b.setAttribute('data-notifications-badge', '');
					b.className = 'esk-notifications-badge';
					b.textContent = count > 99 ? '99+' : String(count);
					if (toggle) toggle.appendChild(b);
					badge = b;
				}
			} else if (badge) {
				badge.remove();
				badge = null;
			}
		}

		function fetchAndRender() {
			fetch(url, {
				headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-WP-Nonce': nonce() },
				credentials: 'same-origin'
			}).then(function (res) {
				if (!res.ok) return null;
				return res.json();
			}).then(function (data) {
				if (!data) return;
				var items = data.items || data.data || [];
				var unread = data.unread_count != null ? data.unread_count : (data.unread || 0);
				if (Array.isArray(items)) render(items);
				updateBadge(parseInt(unread, 10) || 0);
			}).catch(function () {});
		}

		function open() {
			panel.classList.add('is-open');
			if (toggle) toggle.setAttribute('aria-expanded', 'true');
			fetchAndRender();
			pollTimer = setInterval(fetchAndRender, 30000);
		}

		function close() {
			panel.classList.remove('is-open');
			if (toggle) toggle.setAttribute('aria-expanded', 'false');
			if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
		}

		if (toggle) {
			toggle.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				panel.classList.contains('is-open') ? close() : open();
			});
		}

		document.addEventListener('click', function (e) { if (!root.contains(e.target)) close(); });
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

		root.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-notifications-mark-all]');
			if (!btn) return;
			e.preventDefault();
			fetch('/wp-json/esk/v1/notifications/mark-all', {
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce(), 'X-Requested-With': 'XMLHttpRequest' },
				credentials: 'same-origin'
			}).then(function (res) {
				if (!res.ok) return null;
				return res.json();
			}).then(function (data) {
				if (!data) return;
				updateBadge(0);
				fetchAndRender();
			}).catch(function () {});
		});

		// Poll badge count while the page sits idle.
		setInterval(function () {
			fetch(url, {
				headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-WP-Nonce': nonce() },
				credentials: 'same-origin'
			}).then(function (res) { return res.ok ? res.json() : null; })
				.then(function (d) {
					if (d) updateBadge(parseInt(d.unread_count != null ? d.unread_count : (d.unread || 0), 10) || 0);
				})
				.catch(function () {});
		}, 60000);
	})();

	/* ── Dashboard favorites (mirrors app; WP AJAX action) ────────────── */
	(function () {
		function nonce() {
			return window.eskAjaxNonce();
		}

		function ajaxUrl() {
			return (window.eskAdmin && window.eskAdmin.ajaxUrl) || '/wp-admin/admin-ajax.php';
		}

		function syncStar(btn, isFav) {
			if (!btn) return;
			btn.setAttribute('data-favorited', isFav ? '1' : '0');
			var empty = btn.querySelector('[data-favorite-icon-empty]');
			var filled = btn.querySelector('[data-favorite-icon-filled]');
			if (empty) empty.style.display = isFav ? 'none' : '';
			if (filled) filled.style.display = isFav ? '' : 'none';
		}

		function findFavRow(list, targetUrl) {
			var rows = list.querySelectorAll('[data-fav-url]');
			for (var i = 0; i < rows.length; i++) {
				if (rows[i].getAttribute('data-fav-url') === targetUrl) return rows[i];
			}
			return null;
		}

		function favoritesList() {
			return document.querySelector('[data-favorites-list]') || document.getElementById('esk-fav-list');
		}

		function favoritesGroup() {
			return document.querySelector('[data-favorites-group]') || document.getElementById('esk-fav-group');
		}

		document.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-favorite-toggle]');
			if (!btn) return;
			e.preventDefault();
			e.stopPropagation();

			var token = nonce();
			if (!token) return;

			var payload = {
				url: window.location.pathname,
				label: (document.title || '').replace(/\s*[—–|-]\s*.*$/, '').trim().slice(0, 120)
			};
			if (btn.hasAttribute('data-pin-url')) {
				payload.url = btn.getAttribute('data-pin-url');
				payload.label = btn.getAttribute('data-pin-label') || payload.label;
			}

			var toggling = btn.getAttribute('data-favorited') !== '1';
			btn.disabled = true;

			var body = new URLSearchParams();
			body.set('action', 'esk_toggle_favorite');
			body.set('nonce', token);
			body.set('url', payload.url);
			body.set('label', payload.label);

			fetch(ajaxUrl(), {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			}).then(function (res) {
				return res.json().catch(function () { return {}; }).then(function (data) {
					return { ok: res.ok, data: data };
				});
			}).then(function (result) {
				var data = result.data || {};
				if (data.success === false) return;
				var isFav = !toggling || (data.favorite != null ? !!data.favorite : toggling);
				syncStar(btn, isFav);

				var group = favoritesGroup();
				var list = favoritesList();
				if (group && list) {
					if (isFav && !findFavRow(list, payload.url)) {
						var row = document.createElement('div');
						row.setAttribute('data-fav-url', payload.url);
						row.className = 'esk-favorite-row';
						row.innerHTML =
							'<a href="' + escHtml(payload.url) + '" class="esk-favorite-link">' + escHtml(payload.label) + '</a>' +
							'<button type="button" data-unpin-fav data-pin-url="' + escHtml(payload.url) + '" class="esk-favorite-unpin" aria-label="Unpin" title="Unpin">&times;</button>';
						list.insertBefore(row, list.firstChild);
					} else if (!isFav) {
						var existing = findFavRow(list, payload.url);
						if (existing) existing.remove();
					}
					group.hidden = !list.querySelector('a');
				}
			}).catch(function () {})
				.then(function () { btn.disabled = false; });
		});

		document.addEventListener('click', function (e) {
			var unpin = e.target.closest('[data-unpin-fav]');
			if (!unpin) return;
			e.preventDefault();
			e.stopPropagation();

			var token = nonce();
			var star = document.querySelector('[data-favorite-toggle]');
			if (!token || !star) return;

			var pinUrl = unpin.getAttribute('data-pin-url') || unpin.getAttribute('data-url');
			var row = unpin.closest('[data-fav-url]') || unpin.parentNode;

			var body = new URLSearchParams();
			body.set('action', 'esk_toggle_favorite');
			body.set('nonce', token);
			body.set('url', pinUrl);
			body.set('label', (unpin.getAttribute('data-label') || '').slice(0, 120));

			fetch(ajaxUrl(), {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			}).then(function (res) {
				return res.ok ? res.json().catch(function () { return {}; }) : {};
			}).then(function (data) {
				if (data.success === false) return;
				if (row && row.parentNode) row.parentNode.removeChild(row);
				var group = favoritesGroup();
				var list = favoritesList();
				if (group && list && !list.querySelector('a')) group.hidden = true;
				if (star && pinUrl && window.location.pathname === pinUrl.split('?')[0]) syncStar(star, false);
			}).catch(function () {});
		});
	})();
})();