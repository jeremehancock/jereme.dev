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
        // Lazy-load card-cover background images. <img> tags use the
        // browser's native loading="lazy" (set server-side), which respects
        // the HTTP cache — no JS, no spinner flicker on cached images.
        // Background-image has no native equivalent, so we observe those
        // here and add .is-loaded once the image is in the cache.
        (function () {
            lozad('.lozad[data-background-image]', {
                loaded: function (el) {
                    var url = el.getAttribute('data-background-image');
                    if (!url) { el.classList.add('is-loaded'); return; }
                    var probe = new Image();
                    probe.onload = probe.onerror = function () {
                        el.classList.add('is-loaded');
                    };
                    probe.src = url;
                }
            }).observe();
        })();
    </script>

    <!--<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>-->

    <?php Theme::plugins('siteBodyEnd'); ?>

</body>
</html>
