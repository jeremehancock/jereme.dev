(function () {
    'use strict';

    var STORAGE_KEY = 'jdpro-theme';
    var html = document.documentElement;
    var body = document.body;

    /* ---------------- Theme toggle ---------------- */

    function getCurrentTheme() {
        return html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function setTheme(theme) {
        html.setAttribute('data-theme', theme);
        try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
        var meta = document.querySelector('meta[name="theme-color"]:not([media])');
        if (meta) meta.setAttribute('content', theme === 'dark' ? '#0d0f12' : '#ffffff');
    }

    var themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            setTheme(getCurrentTheme() === 'dark' ? 'light' : 'dark');
        });
    }

    // React to OS preference change only when user hasn't explicitly set one.
    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var mqHandler = function (e) {
            try { if (localStorage.getItem(STORAGE_KEY)) return; } catch (err) {}
            setTheme(e.matches ? 'dark' : 'light');
        };
        if (mq.addEventListener) mq.addEventListener('change', mqHandler);
        else if (mq.addListener) mq.addListener(mqHandler);
    }

    /* ---------------- Mobile nav ---------------- */

    var menuBtn = document.getElementById('menu-toggle');
    var nav = document.getElementById('site-navigation');
    var navClose = document.getElementById('nav-close');
    var overlay = document.getElementById('body-overlay');

    function closeMenu() {
        body.classList.remove('menu-open', 'no-scroll');
        if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
        if (nav) nav.setAttribute('aria-hidden', 'true');
    }

    function openMenu() {
        body.classList.add('menu-open', 'no-scroll');
        if (menuBtn) menuBtn.setAttribute('aria-expanded', 'true');
        if (nav) nav.setAttribute('aria-hidden', 'false');
    }

    if (menuBtn && nav) {
        menuBtn.addEventListener('click', function () {
            if (body.classList.contains('menu-open')) closeMenu();
            else openMenu();
        });
        nav.addEventListener('click', function (e) {
            if (e.target.tagName === 'A') closeMenu();
        });
    }
    if (navClose) navClose.addEventListener('click', closeMenu);

    /* ---------------- Search panel ---------------- */

    var searchBtn = document.getElementById('search-toggle');
    var searchClose = document.getElementById('search-close');
    var searchPanel = document.getElementById('search-panel');
    var searchInput = searchPanel ? searchPanel.querySelector('.search-input') : null;
    var resultList = searchPanel ? searchPanel.querySelector('.search-result-list') : null;
    var resultMeta = searchPanel ? searchPanel.querySelector('.search-result-meta') : null;

    function closeSearch() {
        if (!searchPanel) return;
        searchPanel.setAttribute('hidden', '');
        body.classList.remove('search-open');
        if (searchBtn) searchBtn.setAttribute('aria-expanded', 'false');
    }
    function openSearch() {
        if (!searchPanel) return;
        searchPanel.removeAttribute('hidden');
        body.classList.add('search-open');
        if (searchBtn) searchBtn.setAttribute('aria-expanded', 'true');
        if (searchInput) setTimeout(function () { searchInput.focus(); }, 50);
    }

    if (searchBtn) searchBtn.addEventListener('click', function () {
        if (searchPanel.hasAttribute('hidden')) openSearch();
        else closeSearch();
    });
    if (searchClose) searchClose.addEventListener('click', closeSearch);

    /* Overlay closes either menu or search */
    if (overlay) {
        overlay.addEventListener('click', function () {
            closeMenu();
            closeSearch();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenu();
            closeSearch();
        }
        // "/" focuses search
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            openSearch();
        }
    });

    /* Search index lookup against bltsearch.json. The init.php script
       writes this file under uploads/ on each request; siteRoot/uploadsFolder
       are emitted in footer.php. */
    var searchData = null;
    var searchLoadPromise = null;
    function loadIndex() {
        if (searchLoadPromise) return searchLoadPromise;
        var url = (typeof uploadsFolder !== 'undefined' ? uploadsFolder : '/bl-content/uploads/') + 'bltsearch.json';
        searchLoadPromise = fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (data) { searchData = Array.isArray(data) ? data : []; return searchData; })
            .catch(function () { searchData = []; return searchData; });
        return searchLoadPromise;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }

    function highlight(text, query) {
        var terms = query.split(/\s+/).filter(Boolean).map(function (t) {
            return t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        });
        if (!terms.length) return escapeHtml(text);
        var re = new RegExp('(' + terms.join('|') + ')', 'gi');
        return escapeHtml(text).replace(re, '<strong>$1</strong>');
    }

    function snippet(text, query, radius) {
        radius = radius || 80;
        var lower = text.toLowerCase();
        var terms = query.toLowerCase().split(/\s+/).filter(Boolean);
        var pos = -1;
        for (var i = 0; i < terms.length; i++) {
            pos = lower.indexOf(terms[i]);
            if (pos > -1) break;
        }
        if (pos === -1) return text.slice(0, radius * 2) + (text.length > radius * 2 ? '…' : '');
        var start = Math.max(0, pos - radius);
        var end = Math.min(text.length, pos + radius);
        var s = text.slice(start, end);
        if (start > 0) s = '…' + s;
        if (end < text.length) s = s + '…';
        return s;
    }

    function performSearch(query) {
        query = query.trim();
        if (!resultList || !resultMeta) return;
        if (query.length < 3) {
            resultList.innerHTML = '';
            resultMeta.textContent = (typeof translations !== 'undefined' && translations['type-to-start-searching'])
                ? translations['type-to-start-searching']
                : 'Type at least 3 characters to search';
            return;
        }
        loadIndex().then(function (data) {
            var terms = query.toLowerCase().split(/\s+/).filter(Boolean);
            var matches = [];
            for (var i = 0; i < data.length; i++) {
                var item = data[i];
                var t = (item.title || '').toLowerCase();
                var c = (item.content || '').toLowerCase();
                var score = 0;
                var ok = true;
                for (var j = 0; j < terms.length; j++) {
                    var term = terms[j];
                    if (t.indexOf(term) !== -1) score += 5;
                    else if (c.indexOf(term) !== -1) score += 1;
                    else { ok = false; break; }
                }
                if (ok) matches.push({ item: item, score: score });
            }
            matches.sort(function (a, b) { return b.score - a.score; });
            matches = matches.slice(0, 20);

            if (!matches.length) {
                resultList.innerHTML = '';
                resultMeta.textContent = 'No results for “' + query + '”';
                return;
            }
            resultMeta.textContent = matches.length + ' result' + (matches.length === 1 ? '' : 's');
            var root = (typeof siteRoot !== 'undefined') ? siteRoot : '/';
            var html = '';
            for (var k = 0; k < matches.length; k++) {
                var m = matches[k].item;
                var title = m.title || '';
                var slug = m.slug || '';
                var snip = snippet(decodeHtmlEntities(m.content || ''), query, 80);
                html += '<li><a href="' + escapeHtml(root + slug) + '">' +
                        '<h2>' + highlight(decodeHtmlEntities(title), query) + '</h2>' +
                        '<p>' + highlight(snip, query) + '</p>' +
                        '</a></li>';
            }
            resultList.innerHTML = html;
        });
    }

    var entityDecoderEl = document.createElement('textarea');
    function decodeHtmlEntities(s) {
        entityDecoderEl.innerHTML = s;
        return entityDecoderEl.value;
    }

    if (searchInput) {
        var searchTimer = null;
        searchInput.addEventListener('input', function () {
            if (searchTimer) clearTimeout(searchTimer);
            var q = searchInput.value;
            searchTimer = setTimeout(function () { performSearch(q); }, 120);
        });
    }

    /* ---------------- Lazy load (lozad) ---------------- */

    function markLoaded(el) {
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                el.classList.add('is-loaded');
                var wrap = el.closest && el.closest('.lozad-wrap');
                if (wrap) wrap.classList.add('is-loaded');
                var relIcon = el.closest && el.closest('.related-card-thumb');
                if (relIcon) relIcon.classList.add('is-loaded');
            });
        });
    }

    // Convert any non-lozad <img> in user content to lozad.
    var contentRoots = document.querySelectorAll('.page-content, .entry-content, .post-card-excerpt');
    contentRoots.forEach(function (root) {
        root.querySelectorAll('img').forEach(function (img) {
            if (img.classList.contains('lozad')) return;
            if (img.dataset.src) return;
            var src = img.getAttribute('src');
            if (!src) return;
            if (src.indexOf('data:') === 0) return;
            if (img.complete && img.naturalWidth > 0) return;
            img.setAttribute('data-src', src);
            img.removeAttribute('src');
            img.classList.add('lozad');
            if (!img.parentElement || !img.parentElement.classList.contains('lozad-wrap')) {
                var wrap = document.createElement('span');
                wrap.className = 'lozad-wrap';
                img.parentNode.insertBefore(wrap, img);
                wrap.appendChild(img);
            }
        });
    });

    if (typeof lozad === 'function') {
        var observer = lozad('.lozad', {
            loaded: function (el) {
                if (el.tagName === 'IMG') {
                    if (el.complete && el.naturalWidth > 0) {
                        markLoaded(el);
                    } else {
                        el.addEventListener('load',  function () { markLoaded(el); }, { once: true });
                        el.addEventListener('error', function () { markLoaded(el); }, { once: true });
                    }
                } else {
                    var url = el.getAttribute('data-background-image');
                    if (!url) {
                        var bg = window.getComputedStyle(el).backgroundImage;
                        var m = bg && bg.match(/url\(["']?([^"')]+)["']?\)/);
                        if (m) url = m[1];
                    }
                    if (url) {
                        var probe = new Image();
                        probe.onload = probe.onerror = function () { markLoaded(el); };
                        probe.src = url;
                    } else {
                        markLoaded(el);
                    }
                }
            }
        });
        observer.observe();
    }
})();
