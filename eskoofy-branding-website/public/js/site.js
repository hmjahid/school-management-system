/* Eskoofy branding site — counters, role tabs, FAQ accordion */
(function () {
    'use strict';

    /* Animated counters */
    function animateCount(el) {
        var target = parseInt(el.getAttribute('data-count') || '0', 10);
        var suffix = el.getAttribute('data-suffix') || '';
        var duration = 900;
        var start = null;
        function frame(ts) {
            if (!start) start = ts;
            var p = Math.min(1, (ts - start) / duration);
            el.textContent = Math.round(target * (0.2 + 0.8 * p)) + suffix;
            if (p < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }

    var counters = document.querySelectorAll('.esk-count');
    if ('IntersectionObserver' in window && counters.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    animateCount(e.target);
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.4 });
        counters.forEach(function (el) { io.observe(el); });
    } else {
        counters.forEach(animateCount);
    }

    /* Role tabs (features: Admin / Teacher / Parent) */
    var roleTabs = document.querySelector('[data-role-tabs]');
    if (roleTabs) {
        roleTabs.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-role-tab]');
            if (!btn) return;
            var key = btn.getAttribute('data-role-tab');
            roleTabs.querySelectorAll('[data-role-tab]').forEach(function (b) {
                b.classList.toggle('border-blue-600', b === btn);
                b.classList.toggle('text-blue-600', b === btn);
                b.classList.toggle('text-slate-500', b !== btn);
            });
            document.querySelectorAll('[data-role-panel]').forEach(function (p) {
                p.classList.toggle('hidden', p.getAttribute('data-role-panel') !== key);
            });
        });
    }

    /* FAQ accordion */
    document.querySelectorAll('[data-faq-toggle]').forEach(function (head) {
        head.addEventListener('click', function () {
            var item = head.closest('[data-faq-item]');
            var body = item ? item.querySelector('[data-faq-body]') : null;
            if (!body) return;
            var open = item.getAttribute('data-faq-open') === '1';
            item.setAttribute('data-faq-open', open ? '0' : '1');
            body.classList.toggle('hidden', open);
            var caret = head.querySelector('[data-faq-caret]');
            if (caret) caret.textContent = open ? '▾' : '▴';
        });
    });
})();