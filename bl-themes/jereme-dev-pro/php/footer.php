<footer class="site-footer" id="site-footer">
    <div class="container footer-inner">
        <div class="footer-left">
            <a class="bludit-backer" href="https://www.patreon.com/bludit" target="_blank" rel="noopener">
                <img class="patreon-icon lozad" data-src="<?php echo HTML_PATH_THEME; ?>img/patreon.png" alt="Patreon" width="20" height="20" />
                <span>Bludit Backer</span>
            </a>
            <span class="footer-sep" aria-hidden="true">&middot;</span>
            <span class="footer-powered">
                <?php echo $L->get('Powered by'); ?>
                <a href="https://www.bludit.com" target="_blank" rel="noopener">Bludit</a>
            </span>
        </div>

        <div class="footer-right">
            <?php $footerText = $site->footer(); if (!empty($footerText)): ?>
                <span class="footer-custom"><?php echo $footerText; ?></span>
                <span class="footer-sep" aria-hidden="true">&middot;</span>
            <?php endif; ?>
            <span class="footer-copy">&copy; <?php echo date('Y'); ?> <?php echo $site->title(); ?></span>
        </div>
    </div>
</footer>

<script>
    var uploadsFolder = '<?php echo HTML_PATH_UPLOADS; ?>';
    var siteRoot = '<?php echo rtrim($site->url(), '/') . '/'; ?>';
</script>
