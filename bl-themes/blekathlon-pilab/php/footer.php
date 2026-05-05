<footer class="site-footer main-padding smaller-font-size" id="footbar">
    <div class="site-info main-width">
        <div class="copyright text-white text-uppercase">
            <!-- <?php echo $site->footer(); ?>
            <span class="sep"> | </span>
            <?php Theme::plugins('siteShowNode'); ?> -->
            <label class="dark-toggle">
                <input id="dark-toggle" class="dark-toggle-checkbox" type="checkbox" onclick="darkmode.toggle();">
                <div class="dark-toggle-switch"></div>
                <span class="dark-toggle-label">Dark Mode</span>
            </label>
        </div>
        <div class="backer">
            <a href="https://www.patreon.com/bludit">Bludit Backer <img data-src="<?php echo HTML_PATH_THEME; ?>img/patreon.png" class="backer-logo lozad"/></a>
        </div>
    </div>
</footer>
<script>
	var uploadsFolder = '<?php echo HTML_PATH_UPLOADS; ?>', siteRoot = '<?php echo rtrim($site->url(), '/') . '/'; //site url with trailing slash ?>';
	
	if (darkmode.isActivated()) {
		document.getElementById("dark-toggle").checked = true;
	}
	else {
		document.getElementById("dark-toggle").checked = false;
	}
</script>

