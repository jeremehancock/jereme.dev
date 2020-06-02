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
        const observer = lozad(); // lazy loads elements with default selector as '.lozad'
        observer.observe();
    </script>

    <!--<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>-->

    <?php Theme::plugins('siteBodyEnd'); ?>

</body>
</html>
