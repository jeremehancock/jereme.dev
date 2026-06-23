<footer class="site-footer" id="site-footer">
    <div class="container footer-inner">
        <div class="footer-left">
            <?php if ($L->get('Bludit-Backer') !== ""): ?>
                <a class="bludit-backer" href="https://www.patreon.com/bludit" target="_blank" rel="noopener">
                    <img class="patreon-icon lozad" data-src="<?php echo HTML_PATH_THEME; ?>img/patreon.png" alt="Patreon"
                        width="20" height="20" />
                    <span><?php echo $L->get('Bludit-Backer'); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <div class="footer-right">
            <span class="footer-copy">
                <script>
                    today = new Date();
                    y0 = today.getFullYear();
                    document.write('© 2019-' + y0);
                </script>
                <?php $footerText = $site->footer();
                if (!empty($footerText)): ?>
                    <span class="footer-sep" aria-hidden="true">&middot;</span>
                    <span class="footer-custom"><?php echo $footerText; ?></span>
                <?php endif; ?>
            </span>
        </div>
    </div>
</footer>

<script>
    var uploadsFolder = '<?php echo HTML_PATH_UPLOADS; ?>';
    var siteRoot = '<?php echo rtrim($site->url(), '/') . '/'; ?>';
</script>