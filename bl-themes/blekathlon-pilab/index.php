<!DOCTYPE html>
<html>
<head>
    <?php include(THEME_DIR_PHP.'head.php'); ?>
    <?php echo Theme::javascript('js/lozad.min.js', HTML_PATH_THEME, null); ?>
</head>
<body>
	<?php Theme::plugins('siteBodyBegin'); ?>
    <?php include(THEME_DIR_PHP.'header.php'); ?>

    <?php
    if ($WHERE_AM_I == 'page') {
        include(THEME_DIR_PHP.'page.php');
    } else {
        include(THEME_DIR_PHP.'home.php');
    }
    ?>

    <?php include(THEME_DIR_PHP.'aside.php'); ?>

    <?php include(THEME_DIR_PHP.'footer.php'); ?>

    <?php echo Theme::javascript('js/bundle.min.js', HTML_PATH_THEME); ?>

    <script>
        (function () {
            // Convert any non-lozad <img> in user content to lozad (so all images
            // get lazy-loading + spinner). We target the rendered post/page body
            // and skip images that are already configured.
            var contentSelectors = [
                '.page-content',
                '.entry-content',
                '.entry-summary'
            ];
            var contentRoots = document.querySelectorAll(contentSelectors.join(','));
            contentRoots.forEach(function (root) {
                root.querySelectorAll('img').forEach(function (img) {
                    if (img.classList.contains('lozad')) return;
                    if (img.dataset.src) return;
                    var src = img.getAttribute('src');
                    if (!src) return;
                    // Skip data URIs (already inline, no network load)
                    if (src.indexOf('data:') === 0) return;
                    img.setAttribute('data-src', src);
                    img.removeAttribute('src');
                    img.classList.add('lozad');
                    // Wrap so we can show a spinner overlay around the image.
                    if (!img.parentElement || !img.parentElement.classList.contains('lozad-wrap')) {
                        var wrap = document.createElement('span');
                        wrap.className = 'lozad-wrap';
                        img.parentNode.insertBefore(wrap, img);
                        wrap.appendChild(img);
                    }
                });
            });

            function markLoaded(el) {
                el.classList.add('is-loaded');
                var wrap = el.closest && el.closest('.lozad-wrap');
                if (wrap) wrap.classList.add('is-loaded');
                var relIcon = el.closest && el.closest('.rel-item__icon');
                if (relIcon) relIcon.classList.add('is-loaded');
            }

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
                        // Background-image element: probe the URL so we only
                        // hide the spinner once the image has actually loaded.
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
        })();
    </script>

    <!--<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>-->

    <?php Theme::plugins('siteBodyEnd'); ?>

</body>
</html>
