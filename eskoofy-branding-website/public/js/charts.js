/*
 * Eskoofy admin — chart mounting + theming.
 *
 * Wraps the two vendored bundles behind one tiny surface so swapping either
 * library touches only this file (see public/vendor/charts/VERSIONS.md).
 *
 * Every colour is read from the CSS custom properties in public/css/admin.css
 * rather than hardcoded, which is why charts re-theme correctly when
 * `data-theme` flips: read the tokens again and hand the new values to the
 * library. Canvas is transparent, so the panel behind it supplies the surface.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §5.3
 */
(function () {
    'use strict';

    var instances = []; // [{ el, chart }]

    function token(name, fallback) {
        var value = getComputedStyle(document.documentElement).getPropertyValue(name);
        value = (value || '').trim();

        return value || fallback;
    }

    function tokens() {
        return {
            brand: token('--brand', '#2563eb'),
            text: token('--text', '#0f172a'),
            muted: token('--text-muted', '#64748b'),
            subtle: token('--text-subtle', '#94a3b8'),
            border: token('--border', '#e2e8f0'),
            surface: token('--surface', '#ffffff'),
            surface2: token('--surface-2', '#f8fafc'),
            success: token('--success', '#16a34a'),
            warning: token('--warning', '#f59e0b'),
            danger: token('--danger', '#ef4444'),
            info: token('--info', '#0ea5e9')
        };
    }

    /** The four product brand colours, in Catalog order. */
    function productColors() {
        return [
            token('--p-app', '#4f46e5'),
            token('--p-php', '#0284c7'),
            token('--p-theme', '#7c3aed'),
            token('--p-node', '#059669')
        ];
    }

    /** The two variant accents, BD first. */
    function variantColors() {
        return [token('--v-bd', '#16a34a'), token('--v-int', '#2563eb')];
    }

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function parse(el, key, fallback) {
        var raw = el.getAttribute(key);
        if (!raw) {
            return fallback;
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    }

    /* ------------------------------------------------------ Chart.js --- */

    function chartJsDefaults(t, animate) {
        var hasChart = typeof window.Chart !== 'undefined';

        if (hasChart) {
            window.Chart.defaults.font.family =
                "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif";
            window.Chart.defaults.font.size = 11;
            window.Chart.defaults.color = t.muted;
            window.Chart.defaults.borderColor = t.border;
            window.Chart.defaults.animation = animate;
            window.Chart.defaults.plugins.legend.display = false;
            window.Chart.defaults.plugins.tooltip.backgroundColor = t.text;
            window.Chart.defaults.plugins.tooltip.padding = 10;
            window.Chart.defaults.plugins.tooltip.cornerRadius = 8;
            window.Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };
            window.Chart.defaults.plugins.tooltip.displayColors = true;
            window.Chart.defaults.maintainAspectRatio = false;
        }

        return hasChart;
    }

    /** Vertical gradient under a line/area series. */
    function areaFill(t, color) {
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        var grad = ctx.createLinearGradient(0, 0, 0, 260);
        grad.addColorStop(0, color + '40');
        grad.addColorStop(1, color + '00');

        return grad;
    }

    function mountChartJs(el, spec, t, animate) {
        var canvas = el.querySelector('canvas');
        if (!canvas) {
            return null;
        }

        var type = spec.type === 'bar' ? 'bar' : 'line';
        var scales = spec.scales || {};
        applyCompactTicks(scales, spec.valueFormat || 'number');
        var config = {
            type: type,
            data: {
                labels: spec.labels || [],
                datasets: (spec.datasets || []).map(function (ds, i) {
                    var palette = ds.colors && ds.colors.length
                        ? ds.colors
                        : ds.variant === true
                        ? variantColors()
                        : productColors();
                    var color = ds.color || palette[i % palette.length] || t.brand;

                    var out = {
                        label: ds.label || '',
                        data: ds.data || [],
                        borderColor: color,
                        borderWidth: ds.borderWidth == null ? 2 : ds.borderWidth,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHitRadius: 12,
                        tension: ds.tension == null ? 0.35 : ds.tension,
                        fill: ds.fill === false ? false : ds.fill === true ? 'origin' : false
                    };

                    if (type === 'bar') {
                        out.backgroundColor = ds.backgroundColor || color;
                        out.borderRadius = 4;
                        out.borderSkipped = false;
                        out.maxBarThickness = 36;
                        // Floating bars: [start, end] — used by the MRR waterfall.
                        if (ds.floating) {
                            out.data = (ds.data || []).map(function (pair) {
                                return { y: [pair[0], pair[1]] };
                            });
                        }
                    } else if (ds.fill === true) {
                        out.backgroundColor = areaFill(t, color);
                        out.fill = 'origin';
                    }

                    if (ds.order !== undefined) {
                        out.order = ds.order;
                    }
                    if (ds.yAxisID) {
                        out.yAxisID = ds.yAxisID;
                    }

                    return out;
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: animate,
                interaction: { mode: 'index', intersect: false },
                scales: scales,
                plugins: {
                    tooltip: {
                        callbacks: Object.assign(
                            {
                                label: function (ctx2) {
                                    var fmt = spec.valueFormat || 'number';
                                    return ctx2.dataset.label
                                        ? ctx2.dataset.label + ': ' + formatValue(ctx2.parsed.y, fmt)
                                        : formatValue(ctx2.parsed.y, fmt);
                                }
                            },
                            spec.tooltip || {}
                        )
                    }
                },
                onClick: spec.onClick ? undefined : undefined
            }
        };

        return new window.Chart(canvas.getContext('2d'), config);
    }

    /* --------------------------------------------------- ApexCharts --- */

    function apexDefaults(t, animate) {
        var hasApex = typeof window.ApexCharts !== 'undefined';
        if (!hasApex) {
            return false;
        }

        window.ApexCharts.defaults = Object.assign({}, window.ApexCharts.defaults || {}, {
            chart: {
                fontFamily: "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
                foreColor: t.muted,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: { enabled: animate },
                parentHeightOffset: 0
            },
            grid: {
                borderColor: t.border,
                strokeDashArray: 3,
                padding: { top: 0, right: 8, bottom: 0, left: 8 }
            },
            dataLabels: { enabled: false },
            legend: { fontSize: '12px', labels: { colors: t.muted } },
            tooltip: { theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light' }
        });

        return true;
    }

    function mountApex(el, spec, t, animate) {
        var host = el.querySelector('[data-apex-target]') || el;
        var colors = spec.colors === 'variant'
            ? variantColors()
            : spec.colors === 'product'
            ? productColors()
            : spec.colors || [t.brand];

        var options = Object.assign({ colors: colors }, spec.options || {});

        var chart = new window.ApexCharts(host, options);
        chart.render();

        return {
            updateOptions: function (next) {
                chart.updateOptions(next, false, true);
            },
            destroy: function () {
                chart.destroy();
            },
            raw: chart
        };
    }

    /* ----------------------------------------------------- formatting --- */

    /**
     * Compact axis labels — dense windows stay readable at a glance while the
     * tooltip keeps the full value, so no precision is lost, only ink.
     */
    function compactNumber(n) {
        var abs = Math.abs(n);
        if (abs >= 1e9) {
            return (n / 1e9).toFixed(abs >= 1e10 ? 0 : 1) + 'B';
        }
        if (abs >= 1e6) {
            return (n / 1e6).toFixed(abs >= 1e7 ? 0 : 1) + 'M';
        }
        if (abs >= 1e3) {
            return (n / 1e3).toFixed(abs >= 1e4 ? 0 : 1) + 'k';
        }
        return String(Math.round(n));
    }

    /**
     * Attach a compact tick callback to every y scale for value formats that
     * benefit from it ('currency', 'compact'). Small counts keep the library
     * default — abbreviating "12" to "12" is just ink.
     */
    function applyCompactTicks(scales, format) {
        if (format !== 'currency' && format !== 'compact') {
            return;
        }
        var abbreviate = function (value) {
            return format === 'currency' ? '$' + compactNumber(value) : compactNumber(value);
        };

        Object.keys(scales).forEach(function (key) {
            if (key !== 'y' && key !== 'y1') {
                return;
            }
            scales[key] = scales[key] || {};
            scales[key].ticks = scales[key].ticks || {};
            scales[key].ticks.callback = abbreviate;
        });
    }

    function formatValue(value, format) {
        var n = Number(value) || 0;

        if (typeof format === 'function') {
            return format(n);
        }
        if (format === 'currency') {
            return '$' + n.toLocaleString(undefined, { maximumFractionDigits: 0 });
        }
        if (format === 'percent') {
            return n.toFixed(1) + '%';
        }
        if (format === 'compact') {
            return n >= 1000
                ? (n / 1000).toFixed(n >= 10000 ? 0 : 1) + 'k'
                : String(n);
        }

        return n.toLocaleString();
    }

    /* --------------------------------------------------------- mount --- */

    function mount(el) {
        if (el.getAttribute('data-chart-mounted') === '1') {
            return;
        }
        el.setAttribute('data-chart-mounted', '1');

        var lib = el.getAttribute('data-esk-chart'); // 'chartjs' | 'apex'
        var t = tokens();
        var animate = !prefersReducedMotion();

        // A visible skeleton means "loading"; a resolved panel hides it.
        var skeleton = el.querySelector('[data-chart-skeleton]');
        if (skeleton) {
            skeleton.hidden = true;
        }

        if (lib === 'apex') {
            if (apexDefaults(t, animate)) {
                var spec = parse(el, 'data-chart-spec', {});
                var apex = mountApex(el, spec, t, animate);
                instances.push({ el: el, chart: apex, lib: lib });
                el.dispatchEvent(new CustomEvent('esk:chart-ready', { detail: { lib: lib } }));
            }
            return;
        }

        if (chartJsDefaults(t, animate)) {
            var spec2 = parse(el, 'data-chart-spec', {});
            var chart = mountChartJs(el, spec2, t, animate);
            instances.push({ el: el, chart: chart, lib: lib });
            el.dispatchEvent(new CustomEvent('esk:chart-ready', { detail: { lib: lib } }));
        }
    }

    /* ---------------------------------------------------- re-theming --- */

    function refreshTheme() {
        var t = tokens();
        var animate = false; // never animate a theme flip

        if (typeof window.Chart !== 'undefined') {
            chartJsDefaults(t, animate);
        }
        if (typeof window.ApexCharts !== 'undefined') {
            apexDefaults(t, animate);
        }

        instances.forEach(function (entry) {
            if (entry.lib === 'chartjs' && entry.chart) {
                entry.chart.update('none');
            } else if (entry.lib === 'apex' && entry.chart) {
                entry.chart.updateOptions(
                    {
                        chart: { foreColor: t.muted },
                        grid: { borderColor: t.border },
                        tooltip: {
                            theme:
                                document.documentElement.getAttribute('data-theme') === 'dark'
                                    ? 'dark'
                                    : 'light'
                        }
                    },
                    false,
                    true
                );
            }
        });
    }

    function resizeAll() {
        instances.forEach(function (entry) {
            if (entry.chart && typeof entry.chart.resize === 'function') {
                entry.chart.resize();
            } else if (entry.chart && entry.chart.raw && typeof entry.chart.raw.resize === 'function') {
                entry.chart.raw.resize();
            }
        });
    }

    window.ESKCharts = {
        mount: mount,
        refreshTheme: refreshTheme,
        resizeAll: resizeAll,
        tokens: tokens,
        productColors: productColors,
        variantColors: variantColors,
        formatValue: formatValue
    };
})();