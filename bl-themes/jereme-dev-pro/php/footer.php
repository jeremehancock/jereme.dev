<footer class="site-footer" id="site-footer">
    <div class="container footer-inner">
        <div class="footer-copy">
            <?php echo $site->footer(); ?>
            <?php if (Theme::plugins('siteShowNode')): ?>
            <span class="footer-sep">&middot;</span>
            <?php Theme::plugins('siteShowNode'); ?>
            <?php endif; ?>
        </div>
        <div class="footer-meta">
            <span>&copy; <?php echo date('Y'); ?> <?php echo $site->title(); ?></span>
        </div>
    </div>
</footer>

<script>
    var uploadsFolder = '<?php echo HTML_PATH_UPLOADS; ?>';
    var siteRoot = '<?php echo rtrim($site->url(), '/') . '/'; ?>';
</script>
