<!DOCTYPE html>
<html lang="<?php echo Theme::lang(); ?>" data-theme="auto">
<head>
    <?php include(THEME_DIR_PHP.'head.php'); ?>
</head>
<body class="bl-body">
    <?php Theme::plugins('siteBodyBegin'); ?>

    <a class="skip-link" href="#main"><?php echo $L->get('Skip to content'); ?></a>

    <?php include(THEME_DIR_PHP.'header.php'); ?>

    <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="Primary" aria-hidden="true">
        <div class="main-navigation-inner">
            <div class="main-navigation-header">
                <p class="main-navigation-title">Menu</p>
                <button type="button" class="icon-btn nav-close" id="nav-close" aria-label="Close menu">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <?php Theme::plugins('siteSidebar'); ?>
        </div>
    </nav>

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
