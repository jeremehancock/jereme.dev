<!DOCTYPE html>
<html lang="<?php echo Theme::lang(); ?>" data-theme="auto">
<head>
    <?php include(THEME_DIR_PHP.'head.php'); ?>
</head>
<body class="bl-body">
    <?php Theme::plugins('siteBodyBegin'); ?>

    <a class="skip-link" href="#main"><?php echo $L->get('Skip to content'); ?></a>

    <?php include(THEME_DIR_PHP.'header.php'); ?>
    <div class="body-overlay" id="body-overlay" aria-hidden="true"></div>

    <?php
    if ($WHERE_AM_I == 'page') {
        include(THEME_DIR_PHP.'page.php');
    } else {
        include(THEME_DIR_PHP.'home.php');
    }
    ?>

    <?php include(THEME_DIR_PHP.'aside.php'); ?>
    <?php include(THEME_DIR_PHP.'footer.php'); ?>

    <?php echo Theme::javascript('js/lozad.min.js', HTML_PATH_THEME, null); ?>
    <?php echo Theme::javascript('js/theme.js', HTML_PATH_THEME); ?>

    <?php Theme::plugins('siteBodyEnd'); ?>
</body>
</html>
