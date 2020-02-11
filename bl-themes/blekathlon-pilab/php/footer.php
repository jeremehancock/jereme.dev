<footer class="site-footer main-padding smaller-font-size" id="footbar">
    <div class="site-info main-width">
        <div class="copyright text-white text-uppercase">
            <?php echo $site->footer(); ?>
            <span class="sep"> | </span>
            <?php Theme::plugins('siteFooter'); ?>
        </div>
        <div class="backer">
                <a href="https://www.patreon.com/bludit">Bludit Backer <img src="../bl-themes/blekathlon-pilab/img/patreon.png" class="backer-logo"/></a>
        </div>
    </div>
</footer>
<script>
	var uploadsFolder = '<?php echo HTML_PATH_UPLOADS; ?>';
</script>
