er<footer class="site-footer main-padding text-center smaller-font-size">
    <div class="site-info main-width">
        <div class="text-center text-white text-uppercase">
            <?php echo $site->footer(); ?>
            <span class="sep"> | </span>
            <?php Theme::plugins('siteFooter'); ?>
        </div>
    </div>
</footer>
<script>
	var uploadsFolder = '<?php echo HTML_PATH_UPLOADS; ?>';
</script>
