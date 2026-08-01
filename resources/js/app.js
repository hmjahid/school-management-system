import './bootstrap';

// ---------- Toast notifications ----------
window.showToast = function (message, type = 'info', duration = 5000) {
    const root = document.getElementById('toast-root');
    if (!root || !message) return;

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <span class="flex-1">${escapeHtml(message)}</span>
        <button type="button" class="shrink-0 rounded-md p-1 opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
    `;

    const dismiss = () => {
        toast.classList.add('toast--leaving');
        setTimeout(() => toast.remove(), 250);
    };

    toast.querySelector('button').addEventListener('click', dismiss);
    root.appendChild(toast);
    if (duration > 0) setTimeout(dismiss, duration);
};

document.querySelectorAll('[data-flash-toast]').forEach((el) => {
    showToast(el.dataset.message, el.dataset.type || 'info');
});

// ---------- Confirm modal ----------
window.confirmAction = function (options = {}) {
    return new Promise((resolve) => {
        const root = document.getElementById('confirm-modal-root');
        if (!root) { resolve(window.confirm(options.message || 'Are you sure?')); return; }

        const title = root.querySelector('[data-confirm-title]');
        const message = root.querySelector('[data-confirm-message]');
        const okBtn = root.querySelector('[data-confirm-ok]');
        const cancelBtn = root.querySelector('[data-confirm-cancel]');
        const backdrop = root.querySelector('[data-confirm-backdrop]');

        title.textContent = options.title || 'Are you sure?';
        message.textContent = options.message || 'This action cannot be undone.';
        okBtn.textContent = options.confirmLabel || 'Confirm';

        const close = (result) => {
            root.classList.add('hidden');
            root.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            resolve(result);
        };

        const onOk = () => close(true);
        const onCancel = () => close(false);
        const onBackdrop = (e) => { if (e.target === backdrop) close(false); };
        const onKey = (e) => { if (e.key === 'Escape') close(false); };

        okBtn.addEventListener('click', onOk, { once: true });
        cancelBtn.addEventListener('click', onCancel, { once: true });
        backdrop.addEventListener('click', onBackdrop, { once: true });
        document.addEventListener('keydown', onKey, { once: true });

        root.classList.remove('hidden');
        root.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        okBtn.focus();
    });
};

document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (!form) return;
    e.preventDefault();
    const ok = await confirmAction({
        title: form.dataset.confirmTitle || 'Confirm action',
        message: form.dataset.confirm || 'Are you sure?',
        confirmLabel: form.dataset.confirmLabel || 'Confirm',
    });
    if (ok) form.submit();
});

document.addEventListener('click', async (e) => {
    const link = e.target.closest('a[data-confirm]');
    if (!link) return;
    e.preventDefault();
    const ok = await confirmAction({
        title: link.dataset.confirmTitle || 'Confirm action',
        message: link.dataset.confirm || 'Are you sure?',
        confirmLabel: link.dataset.confirmLabel || 'Confirm',
    });
    if (ok) window.location.href = link.href;
});

// ---------- User menu ----------
(function () {
    const root = document.querySelector('[data-user-menu-root]');
    if (!root) return;
    const toggle = root.querySelector('[data-user-menu-toggle]');
    const panel = root.querySelector('[data-user-menu-panel]');

    const close = () => {
        panel.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
    };
    const open = () => {
        panel.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.contains('hidden') ? open() : close();
    });
    document.addEventListener('click', (e) => { if (!root.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();

// ---------- Notifications dropdown ----------
(function () {
    const root = document.querySelector('[data-notifications-root]');
    if (!root) return;

    const toggle = root.querySelector('[data-notifications-toggle]');
    const panel = root.querySelector('[data-notifications-panel]');
    const list = root.querySelector('[data-notifications-list]');
    const badge = root.querySelector('[data-notifications-badge]');
    const url = list.dataset.url;
    let pollTimer = null;

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function render(items) {
        if (!items || items.length === 0) {
            list.innerHTML = '<div class="px-4 py-8 text-center text-sm text-slate-500">No notifications</div>';
            return;
        }
        list.innerHTML = items.map(n => `
            <a href="/dashboard/notifications/${n.id}/read"
               class="block border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 ${n.unread ? 'bg-brand-50/50' : ''}">
                <p class="text-sm font-medium text-slate-900">${escapeHtml(n.title || n.type)}</p>
                ${n.message ? `<p class="mt-0.5 text-xs text-slate-600">${escapeHtml(n.message)}</p>` : ''}
                <p class="mt-1 text-[0.65rem] text-slate-400">${formatTime(n.created_at)}</p>
            </a>
        `).join('');
    }

    function updateBadge(count) {
        if (count > 0) {
            if (badge) {
                badge.textContent = count > 99 ? '99+' : String(count);
            } else {
                const b = document.createElement('span');
                b.setAttribute('data-notifications-badge', '');
                b.className = 'absolute right-1 top-1 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[0.6rem] font-bold leading-none text-white';
                b.textContent = count > 99 ? '99+' : String(count);
                toggle.appendChild(b);
            }
        } else if (badge) {
            badge.remove();
        }
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function formatTime(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        const diff = (Date.now() - d.getTime()) / 1000;
        if (diff < 60) return Math.floor(diff) + 's ago';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }

    async function fetchAndRender() {
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            render(data.items);
            updateBadge(data.unread_count);
        } catch (e) {}
    }

    function open() {
        panel.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
        fetchAndRender();
        pollTimer = setInterval(fetchAndRender, 30000);
    }

    function close() {
        panel.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        panel.classList.contains('hidden') ? open() : close();
    });

    document.addEventListener('click', (e) => { if (!root.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

    root.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-notifications-mark-all]');
        if (!btn) return;
        e.preventDefault();
        try {
            const res = await fetch('/dashboard/notifications/mark-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (res.ok) { updateBadge(0); fetchAndRender(); }
        } catch (e) {}
    });

    setInterval(() => {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(d => d && updateBadge(d.unread_count))
            .catch(() => {});
    }, 60000);
})();

// ---------- Scroll reveal (Intersection Observer) ----------
(function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    });
})();

// ---------- Count-up animation ----------
window.countUp = function(el, target, duration = 2000) {
    const start = performance.now();
    const from = 0;

    function update(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(from + (target - from) * eased);
        el.textContent = current.toLocaleString();
        if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-countup]').forEach(el => {
        const target = parseInt(el.dataset.countup, 10);
        if (!isNaN(target)) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        countUp(el, target);
                        observer.unobserve(entry);
                    }
                });
            }, { threshold: 0.5 });
            observer.observe(el);
        }
    });
});

// ---------- Scroll-to-top button ----------
(function() {
    const btn = document.createElement('button');
    btn.id = 'scroll-to-top';
    btn.className = 'no-print fixed bottom-6 right-6 z-50 flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition-all duration-300 hover:bg-brand-700 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-brand-500/50';
    btn.setAttribute('aria-label', 'Scroll to top');
    btn.innerHTML = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>';
    btn.style.opacity = '0';
    btn.style.transform = 'translateY(1rem)';
    btn.style.pointerEvents = 'none';
    document.body.appendChild(btn);

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            btn.style.opacity = '1';
            btn.style.transform = 'translateY(0)';
            btn.style.pointerEvents = 'auto';
        } else {
            btn.style.opacity = '0';
            btn.style.transform = 'translateY(1rem)';
            btn.style.pointerEvents = 'none';
        }
    });

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();

// ---------- Photo slider carousel (homepage) ----------
(function() {
    document.querySelectorAll('[data-slider-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-slider-track]');
        const prev = carousel.querySelector('[data-slider-prev]');
        const next = carousel.querySelector('[data-slider-next]');
        if (!track) return;

        const slideAmount = () => {
            const slide = track.querySelector(':scope > *');
            if (!slide) return track.clientWidth;
            const style = window.getComputedStyle(slide);
            const gap = parseFloat(style.marginRight || '0');
            return slide.offsetWidth + gap;
        };

        if (prev) prev.addEventListener('click', () => track.scrollBy({ left: -slideAmount(), behavior: 'smooth' }));
        if (next) next.addEventListener('click', () => track.scrollBy({ left: slideAmount(), behavior: 'smooth' }));
    });
})();

// ---------- Gallery tab filtering ----------
(function() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-filter-tabs] button[data-filter]');
        if (!btn) return;

        const tabs = btn.closest('[data-filter-tabs]');
        const grid = document.querySelector(tabs.dataset.target || '[data-gallery-grid]');
        if (!grid) return;

        const filter = btn.dataset.filter;

        tabs.querySelectorAll('button[data-filter]').forEach(b => {
            b.dataset.active = b.dataset.filter === filter ? 'true' : 'false';
            b.classList.toggle('bg-blue-600', b.dataset.filter === filter);
            b.classList.toggle('text-white', b.dataset.filter === filter);
            b.classList.toggle('bg-slate-100', b.dataset.filter !== filter);
            b.classList.toggle('text-slate-700', b.dataset.filter !== filter);
        });

        grid.querySelectorAll('[data-category]').forEach(el => {
            if (filter === 'all' || el.dataset.category === filter) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
    });
})();

// ---------- Gallery lightbox ----------
(function() {
    const openLightbox = (images, index = 0) => {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[200] flex items-center justify-center bg-black/90 backdrop-blur-sm';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Image gallery lightbox');

        const img = document.createElement('img');
        img.className = 'max-h-[90vh] max-w-[90vw] rounded-lg object-contain shadow-2xl transition-opacity duration-300';
        img.alt = 'Gallery image';
        img.src = images[index];

        const close = () => { overlay.remove(); document.body.style.overflow = ''; };

        const prev = () => { index = (index - 1 + images.length) % images.length; img.src = images[index]; };
        const next = () => { index = (index + 1) % images.length; img.src = images[index]; };

        overlay.innerHTML = `
            <button class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/20 p-3 text-white backdrop-blur-sm hover:bg-white/30 focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Previous image">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/20 p-3 text-white backdrop-blur-sm hover:bg-white/30 focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Next image">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <button class="absolute right-4 top-4 rounded-full bg-white/20 p-2 text-white backdrop-blur-sm hover:bg-white/30 focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close lightbox">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;

        overlay.prepend(img);
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        overlay.querySelector('button[aria-label="Previous image"]').addEventListener('click', prev);
        overlay.querySelector('button[aria-label="Next image"]').addEventListener('click', next);
        overlay.querySelector('button[aria-label="Close lightbox"]').addEventListener('click', close);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
        }, { once: false });
    };

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-lightbox]');
        if (!trigger) return;
        e.preventDefault();
        const images = JSON.parse(trigger.dataset.lightbox || '[]');
        const index = parseInt(trigger.dataset.index || '0', 10);
        if (images.length) openLightbox(images, index);
    });
})();

// ---------- Multi-step form ----------
(function() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-step]');
        if (!btn) return;
        const form = btn.closest('[data-multistep]');
        if (!form) return;
        const steps = form.querySelectorAll('[data-step-panel]');
        const progress = form.querySelector('[data-step-progress]');
        let current = parseInt(form.dataset.currentStep || '0', 10);
        const target = parseInt(btn.dataset.step, 10);

        if (target < 0 || target >= steps.length) return;

        // Validate current step if moving forward
        if (target > current) {
            const currentPanel = steps[current];
            const inputs = currentPanel.querySelectorAll('input, select, textarea');
            let valid = true;
            inputs.forEach(input => {
                if (input.required && !input.value.trim()) {
                    valid = false;
                    input.classList.add('border-red-500');
                    input.addEventListener('input', function fix() {
                        this.classList.remove('border-red-500');
                        this.removeEventListener('input', fix);
                    }, { once: true });
                }
            });
            if (!valid) return;
        }

        steps.forEach((s, i) => {
            s.classList.toggle('hidden', i !== target);
        });
        form.dataset.currentStep = target;

        if (progress) {
            const pct = ((target + 1) / steps.length) * 100;
            progress.style.width = pct + '%';
            progress.textContent = Math.round(pct) + '%';
        }
    });
})();

// ---------- Countdown timer ----------
window.createCountdown = function(element, targetDate) {
    const target = new Date(targetDate).getTime();
    function tick() {
        const now = Date.now();
        const diff = target - now;
        if (diff <= 0) {
            element.innerHTML = '<span class="text-brand-600 font-bold">Event started!</span>';
            return;
        }
        const days = Math.floor(diff / 86400000);
        const hours = Math.floor((diff % 86400000) / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        element.innerHTML = `
            <span class="countdown-item"><span class="countdown-num">${days}</span><span class="countdown-label">d</span></span>
            <span class="countdown-sep">:</span>
            <span class="countdown-item"><span class="countdown-num">${String(hours).padStart(2, '0')}</span><span class="countdown-label">h</span></span>
            <span class="countdown-sep">:</span>
            <span class="countdown-item"><span class="countdown-num">${String(minutes).padStart(2, '0')}</span><span class="countdown-label">m</span></span>
            <span class="countdown-sep">:</span>
            <span class="countdown-item"><span class="countdown-num">${String(seconds).padStart(2, '0')}</span><span class="countdown-label">s</span></span>
        `;
    }
    tick();
    return setInterval(tick, 1000);
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-countdown]').forEach(el => {
        createCountdown(el, el.dataset.countdown);
    });
});

// ---------- Command palette (Cmd+K) ----------
(function() {
    let palette = null;

    function createPalette(links) {
        palette = document.createElement('div');
        palette.className = 'no-print fixed inset-0 z-[300] flex items-start justify-center pt-[15vh]';
        palette.style.opacity = '0';
        palette.style.pointerEvents = 'none';
        palette.style.transition = 'opacity 0.2s ease';

        palette.innerHTML = `
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" class="w-full border-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-slate-100" placeholder="Search pages, modules, settings..." autofocus>
                    <kbd class="hidden rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-xs text-slate-400 sm:inline-block dark:border-slate-600 dark:bg-slate-700">ESC</kbd>
                </div>
                <div class="max-h-72 overflow-y-auto p-2" data-palette-results></div>
            </div>
        `;

        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 -z-10 bg-slate-900/50 backdrop-blur-sm';

        palette.prepend(overlay);
        document.body.appendChild(palette);

        const input = palette.querySelector('input');
        const results = palette.querySelector('[data-palette-results]');

        function filterItems(query) {
            const q = query.toLowerCase();
            const filtered = links.filter(item =>
                item.label.toLowerCase().includes(q) || (item.keywords && item.keywords.toLowerCase().includes(q))
            );
            if (filtered.length === 0) {
                results.innerHTML = '<div class="px-3 py-8 text-center text-sm text-slate-400">No results found</div>';
                return;
            }
            results.innerHTML = filtered.map(item => `
                <a href="${item.url}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-700 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">
                    <span class="flex-1">${item.label}</span>
                    <span class="text-xs text-slate-400">${item.section || ''}</span>
                </a>
            `).join('');
        }

        input.addEventListener('input', () => filterItems(input.value));

        overlay.addEventListener('click', close);
        palette.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

        function open() {
            palette.style.opacity = '1';
            palette.style.pointerEvents = 'auto';
            input.value = '';
            filterItems('');
            setTimeout(() => input.focus(), 100);
        }

        function close() {
            palette.style.opacity = '0';
            palette.style.pointerEvents = 'none';
        }

        return { open, close };
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('[data-search-backdrop]') || document.getElementById('dashboard-search-modal')) return;
        const cmdLinks = [
            { label: 'Dashboard', url: '/dashboard', section: 'Main', keywords: 'home index' },
            { label: 'Students', url: '/dashboard/students', section: 'Academic', keywords: 'pupil learner' },
            { label: 'Teachers', url: '/dashboard/teachers', section: 'Academic', keywords: 'staff faculty' },
            { label: 'Parents', url: '/dashboard/parents', section: 'Academic', keywords: 'guardian' },
            { label: 'Classes', url: '/dashboard/classes', section: 'Academic', keywords: 'grades course' },
            { label: 'Attendance', url: '/dashboard/attendance', section: 'Academic', keywords: 'present absent' },
            { label: 'Exams', url: '/dashboard/exams', section: 'Academic', keywords: 'test assessment' },
            { label: 'Fees', url: '/dashboard/fees', section: 'Finance', keywords: 'payment collection' },
            { label: 'Events', url: '/dashboard/events', section: 'Academic', keywords: 'calendar' },
            { label: 'News', url: '/dashboard/news', section: 'Website', keywords: 'article blog' },
            { label: 'Gallery', url: '/dashboard/gallery', section: 'Website', keywords: 'photos images' },
            { label: 'Settings', url: '/dashboard/settings', section: 'System', keywords: 'config' },
            { label: 'Reports', url: '/dashboard/reports', section: 'System', keywords: 'analytics' },
            { label: 'Website CMS', url: '/dashboard/cms/pages', section: 'Website', keywords: 'content editor' },
            { label: 'Admissions', url: '/dashboard/admissions', section: 'Academic', keywords: 'enrollment apply' },
            { label: 'Transport', url: '/dashboard/transport/vehicles', section: 'Academic', keywords: 'bus route' },
            { label: 'Payroll', url: '/dashboard/payroll/payslips', section: 'Finance', keywords: 'salary payslip' },
            { label: 'Activity Log', url: '/dashboard/activity', section: 'System', keywords: 'audit log' },
        ];

        const paletteCtrl = createPalette(cmdLinks);

        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                paletteCtrl.open();
            }
        });
    });
})();

// ---------- Search debounce utility ----------
window.debounce = function(fn, delay = 300) {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
};

// ---------- Unsaved changes warning ----------
(function() {
    let dirty = false;
    document.addEventListener('input', (e) => {
        if (e.target.closest('[data-track-dirty]')) {
            dirty = true;
        }
    });
    document.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (e) => {
        if (dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();

// ---------- PWA Install Prompt ----------
(function() {
    window.__deferredInstallPrompt = null;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.__deferredInstallPrompt = e;
        document.querySelectorAll('[data-pwa-install]').forEach(btn => {
            btn.classList.remove('hidden');
            if (btn.dataset.pwaInline === 'true') btn.classList.add('inline-flex');
            else btn.classList.add('flex');
        });
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-pwa-install]');
        if (!btn || !window.__deferredInstallPrompt) return;
        e.preventDefault();
        window.__deferredInstallPrompt.prompt();
        const result = await window.__deferredInstallPrompt.userChoice;
        if (result.outcome === 'accepted') {
            btn.classList.add('hidden');
            btn.classList.remove('inline-flex', 'flex');
        }
        window.__deferredInstallPrompt = null;
    });

    window.addEventListener('appinstalled', () => {
        window.__deferredInstallPrompt = null;
        document.querySelectorAll('[data-pwa-install]').forEach(btn => {
            btn.classList.add('hidden');
            btn.classList.remove('inline-flex', 'flex');
        });
    });
})();
