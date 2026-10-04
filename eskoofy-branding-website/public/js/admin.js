/*
 * Eskoofy admin shell — theme, sidebar, filters, command palette, charts.
 *
 * No build step and no framework: plain ES5-compatible syntax so this runs on the
 * same browsers the rest of the site already supports.
 *
 * Loaded with `defer` from views/layouts/admin.php.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §5, §9
 */
(function () {
    'use strict';

    var root = document.documentElement;
    var THEME_KEY = 'esk_admin_theme';
    var RANGE_KEY = 'esk_admin_range';

    /* ------------------------------------------------------------ theme --- */

    function readStoredTheme() {
        try {
            return window.localStorage.getItem(THEME_KEY);
        } catch (e) {
            return null;
        }
    }

    function storeTheme(value) {
        try {
            window.localStorage.setItem(THEME_KEY, value);
        } catch (e) {
            /* private mode — the theme just will not persist */
        }
    }

    function systemTheme() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }

    function effectiveTheme(pref) {
        return pref === 'system' || !pref ? systemTheme() : pref;
    }

    function applyTheme(pref) {
        var resolved = effectiveTheme(pref);
        root.setAttribute('data-theme', resolved);

        if (window.ESKCharts) {
            window.ESKCharts.refreshTheme();
        }
    }

    // Listen for the OS flipping while the user has chosen "system".
    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var onChange = function () {
            var pref = readStoredTheme();
            if (!pref || pref === 'system') {
                applyTheme(pref);
            }
        };
        if (mq.addEventListener) {
            mq.addEventListener('change', onChange);
        } else if (mq.addListener) {
            mq.addListener(onChange);
        }
    }

    function setUpThemeToggle() {
        var buttons = document.querySelectorAll('[data-theme-set]');
        if (!buttons.length) {
            return;
        }

        function sync() {
            var pref = readStoredTheme() || 'system';
            Array.prototype.forEach.call(buttons, function (b) {
                b.setAttribute('aria-pressed', String(b.getAttribute('data-theme-set') === pref));
            });
        }

        Array.prototype.forEach.call(buttons, function (btn) {
            btn.addEventListener('click', function () {
                var pref = btn.getAttribute('data-theme-set');
                storeTheme(pref);
                applyTheme(pref);
                sync();
            });
        });

        sync();
    }

    /* ---------------------------------------------------------- sidebar --- */

    function setUpSidebar() {
        var sidebar = document.querySelector('[data-admin-sidebar]');
        if (!sidebar) {
            return;
        }
        var backdrop = document.querySelector('[data-admin-backdrop]');
        var openBtn = document.querySelector('[data-admin-open]');

        function open() {
            sidebar.setAttribute('data-open', 'true');
            if (backdrop) {
                backdrop.hidden = false;
            }
            document.body.style.overflow = 'hidden';
        }

        function close() {
            sidebar.setAttribute('data-open', 'false');
            if (backdrop) {
                backdrop.hidden = true;
            }
            document.body.style.overflow = '';
        }

        if (openBtn) {
            openBtn.addEventListener('click', open);
        }
        if (backdrop) {
            backdrop.addEventListener('click', close);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                close();
            }
        });

        // Collapse to an icon rail on wide screens.
        var railBtn = document.querySelector('[data-admin-rail]');
        var RAIL_KEY = 'esk_admin_rail';
        if (railBtn) {
            var stored = null;
            try {
                stored = window.localStorage.getItem(RAIL_KEY);
            } catch (e) {
                /* ignore */
            }
            if (stored === '1') {
                root.setAttribute('data-rail', 'true');
            }

            function syncRail() {
                var on = root.getAttribute('data-rail') === 'true';
                railBtn.setAttribute('aria-pressed', String(on));
            }

            railBtn.addEventListener('click', function () {
                var on = root.getAttribute('data-rail') === 'true';
                root.setAttribute('data-rail', on ? 'false' : 'true');
                try {
                    window.localStorage.setItem(RAIL_KEY, on ? '0' : '1');
                } catch (e) {
                    /* ignore */
                }
                syncRail();
                if (window.ESKCharts) {
                    window.ESKCharts.resizeAll();
                }
            });

            syncRail();
        }
    }

    /* ------------------------------------------------------- range picker --- */

    function setUpRangePicker() {
        var input = document.querySelector('[data-range-input]');
        if (!input) {
            return;
        }

        input.addEventListener('change', function () {
            var form = input.closest('form');
            if (form) {
                form.submit();
            }
        });

        Array.prototype.forEach.call(document.querySelectorAll('[data-range-preset]'), function (btn) {
            btn.addEventListener('click', function () {
                var form = btn.closest('form');
                if (!form) {
                    return;
                }
                var field = form.querySelector('[name="range"]');
                if (field) {
                    field.value = btn.getAttribute('data-range-preset');
                }
                form.submit();
            });
        });
    }

    /* -------------------------------------------------- command palette --- */

    function setUpPalette() {
        var root_ = document.querySelector('[data-palette]');
        if (!root_) {
            return;
        }

        var input = root_.querySelector('[data-palette-input]');
        var list = root_.querySelector('[data-palette-list]');
        var backdrop = root_.querySelector('[data-palette-backdrop]');
        var closeBtn = root_.querySelector('[data-palette-close]');
        var liveRegion = root_.querySelector('[data-palette-live]');
        var opener = document.querySelector('[data-palette-open]');

        var items = []; // flattened options currently rendered
        var cursor = 0;
        var timer = null;
        var lastQuery = '';

        function close() {
            root_.hidden = true;
            document.body.style.overflow = '';
            if (opener) {
                opener.focus();
            }
        }

        function open() {
            root_.hidden = false;
            document.body.style.overflow = 'hidden';
            if (input) {
                input.value = '';
                input.focus();
            }
            load('');
        }

        function load(query) {
            var url = (root_.getAttribute('data-palette-endpoint') || '/admin/palette') +
                '?q=' + encodeURIComponent(query);

            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(function () {
                window
                    .fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (payload) {
                        render(payload && payload.data ? payload.data : []);
                    })
                    .catch(function () {
                        render([]);
                    });
            }, query.length < 2 ? 0 : 200);
        }

        function render(groups) {
            if (!list) {
                return;
            }
            list.innerHTML = '';
            items = [];

            if (!groups.length) {
                var empty = document.createElement('div');
                empty.className = 'esk-empty';
                empty.textContent = 'No matches.';
                list.appendChild(empty);
                if (liveRegion) {
                    liveRegion.textContent = 'No matches.';
                }
                return;
            }

            groups.forEach(function (group) {
                var heading = document.createElement('div');
                heading.className = 'esk-palette-group';
                heading.textContent = group.group || '';
                list.appendChild(heading);

                (group.items || []).forEach(function (item) {
                    var btn = document.createElement('a');
                    btn.className = 'esk-palette-item';
                    btn.href = item.href;
                    btn.setAttribute('role', 'option');
                    btn.id = 'esk-palette-opt-' + items.length;

                    var label = document.createElement('span');
                    label.textContent = item.label;
                    btn.appendChild(label);

                    if (item.hint) {
                        var hint = document.createElement('span');
                        hint.className = 'esk-palette-hint';
                        hint.textContent = item.hint;
                        btn.appendChild(hint);
                    }

                    list.appendChild(btn);
                    items.push(btn);
                });
            });

            cursor = 0;
            highlight();
            if (liveRegion) {
                liveRegion.textContent = items.length + ' result(s) available.';
            }
        }

        function highlight() {
            items.forEach(function (el, i) {
                el.setAttribute('aria-selected', String(i === cursor));
            });
            if (items[cursor]) {
                items[cursor].scrollIntoView({ block: 'nearest' });
                if (input) {
                    input.setAttribute('aria-activedescendant', items[cursor].id);
                }
            }
        }

        function move(delta) {
            if (!items.length) {
                return;
            }
            cursor = (cursor + delta + items.length) % items.length;
            highlight();
        }

        if (opener) {
            opener.addEventListener('click', open);
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', close);
        }
        if (backdrop) {
            backdrop.addEventListener('click', function (e) {
                if (e.target === backdrop) {
                    close();
                }
            });
        }

        if (input) {
            input.addEventListener('input', function () {
                var q = input.value.trim();
                if (q === lastQuery) {
                    return;
                }
                lastQuery = q;
                load(q);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    move(1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    move(-1);
                } else if (e.key === 'Enter' && items[cursor]) {
                    e.preventDefault();
                    window.location.href = items[cursor].getAttribute('href');
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    close();
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            var typing =
                document.activeElement &&
                /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);

            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if (root_.hidden) {
                    open();
                } else {
                    close();
                }
            } else if (e.key === '/' && !typing && root_.hidden) {
                e.preventDefault();
                open();
            }
        });

        root_.hidden = true;
    }

    /* ----------------------------------------------------- lazy charts --- */

    function setUpLazyCharts() {
        var nodes = document.querySelectorAll('[data-esk-chart]');
        if (!nodes.length || !('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(nodes, function (n) {
                if (window.ESKCharts) {
                    window.ESKCharts.mount(n);
                }
            });
            return;
        }

        var io = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    if (window.ESKCharts) {
                        window.ESKCharts.mount(entry.target);
                    }
                    io.unobserve(entry.target);
                });
            },
            { rootMargin: '200px' }
        );

        Array.prototype.forEach.call(nodes, function (n) {
            io.observe(n);
        });
    }

    /* ------------------------------------------------------- boot ---- */

    function boot() {
        applyTheme(readStoredTheme());
        setUpThemeToggle();
        setUpSidebar();
        setUpRangePicker();
        setUpPalette();
        setUpLazyCharts();

        // The shell can change width (sidebar rail, window resize); charts sized
        // to their container need to be told.
        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (resizeTimer) {
                window.clearTimeout(resizeTimer);
            }
            resizeTimer = window.setTimeout(function () {
                if (window.ESKCharts) {
                    window.ESKCharts.resizeAll();
                }
            }, 150);
        });

        document.dispatchEvent(new CustomEvent('esk:admin-ready'));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Applied before first paint by an inline snippet in layouts/admin.php to
    // avoid a flash of the wrong theme; re-applied here once DOM is ready.
    applyTheme(readStoredTheme());
})();