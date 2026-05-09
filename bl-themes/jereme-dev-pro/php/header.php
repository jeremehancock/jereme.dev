<header class="site-header" id="site-header">
    <div class="site-header-ribbon" aria-hidden="true"></div>
    <div class="header-inner">
        <div class="site-branding">
            <a class="navbar-brand" href="<?php echo $site->url(); ?>" rel="home">
                <?php if (method_exists($site, 'logo') && $site->logo()): ?>
                    <img class="site-logo" src="<?php echo $helper->cdn_that_image($site->logo(), 320); ?>" alt="<?php echo htmlspecialchars($site->title(), ENT_QUOTES); ?>" />
                <?php else: ?>
                    <span class="site-title-text"><?php echo $site->title(); ?></span>
                    <svg class="brand-mark" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M4.1 8c0 2.1 1.7 3.9 3.9 3.9 2.1 0 3.9-1.7 3.9-3.9 0-2.1-1.7-3.9-3.9-3.9 -2.1 0-3.9 1.7-3.9 3.9Z" fill="#ff1654"/>
                        <path d="M1.6 8.7c0.3 3 2.7 5.3 5.6 5.6l0 1.5c-3.8-0.3-6.8-3.3-7.1-7.1l1.5 0Zm14.2 0c-0.3 3.8-3.3 6.8-7.1 7.1l0-1.5c3-0.3 5.3-2.7 5.6-5.6l1.5 0Zm-7.1-7.1c3 0.3 5.3 2.7 5.6 5.6l1.5 0c-0.3-3.8-3.3-6.8-7.1-7.1l0 1.5Zm-7.1 5.6c0.3-3 2.7-5.3 5.6-5.6l0-1.5c-3.8 0.3-6.8 3.3-7.1 7.1l1.5 0Z" fill="currentColor"/>
                    </svg>
                <?php endif; ?>
            </a>
        </div>

        <div class="header-actions">
            <button type="button" class="icon-btn search-toggle" id="search-toggle" aria-controls="search-panel" aria-expanded="false" aria-label="<?php echo $L->get('Search'); ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
            </button>

            <button type="button" class="icon-btn theme-toggle" id="theme-toggle" aria-label="<?php echo $L->get('Toggle light/dark mode'); ?>">
                <svg class="icon-sun" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
                </svg>
                <svg class="icon-moon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>

            <button type="button" class="icon-btn menu-toggle" id="menu-toggle" aria-controls="site-navigation" aria-expanded="false" aria-label="<?php echo $L->get('Toggle menu'); ?>">
                <span class="hamburger" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
            </button>
        </div>
    </div>

    <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="Primary">
        <div class="main-navigation-inner">
            <p class="main-navigation-title">Menu</p>
            <?php Theme::plugins('siteSidebar'); ?>
        </div>
    </nav>

    <div class="search-panel" id="search-panel" hidden>
        <div class="search-inner" role="search">
            <form class="search-form" name="search" onsubmit="return false;">
                <svg class="search-form-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input type="text" class="search-input" name="query" placeholder="<?php echo $L->get('Search'); ?>" autocorrect="off" autocomplete="off" spellcheck="false" />
                <button type="button" class="icon-btn search-close" id="search-close" aria-label="Close search">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </form>
            <div class="search-output">
                <div class="search-result" data-component="result">
                    <div class="search-result-meta">
                        <?php echo $L->get('Type to start searching'); ?>
                        <script>
                            var translations = {
                                "type-to-start-searching": "<?php echo $L->get('Type to start searching'); ?>"
                            };
                        </script>
                    </div>
                    <ol class="search-result-list"></ol>
                </div>
            </div>
        </div>
    </div>
</header>

<svg style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <symbol id="icon-pencil" viewBox="0 0 27 32">
            <path d="M6.5 27.4l1.6-1.6-4.2-4.2-1.6 1.6v1.9h2.3v2.3h1.9zM15.8 10.9q0-0.4-0.4-0.4-0.2 0-0.3 0.1l-9.7 9.7q-0.1 0.1-0.1 0.3 0 0.4 0.4 0.4 0.2 0 0.3-0.1l9.7-9.7q0.1-0.1 0.1-0.3zM14.9 7.4l7.4 7.4-14.9 14.9h-7.4v-7.4zM27.1 9.1q0 0.9-0.7 1.6l-3 3-7.4-7.4 3-2.9q0.6-0.7 1.6-0.7 0.9 0 1.6 0.7l4.2 4.2q0.7 0.7 0.7 1.6z"></path>
        </symbol>
        <symbol id="icon-twitter" viewBox="0 0 32 32">
            <path d="M31.6 5.4c-1.1 0.8-2.4 1.2-3.7 1.2 1.4-0.9 2.5-2.3 3-3.9 -1.2 1-2.7 1.6-4.2 1.7 -1.3-1.3-3-2.1-4.8-2.1 -3.7 0-6.7 3.2-6.7 7 0 0.5 0 1 0.1 1.4 -5.3-0.3-10.2-2.9-13.5-7.2 -1.7 3.1-0.8 7.1 2 9.1 -1 0-2-0.2-3-0.7 0.1 3.2 2.2 6 5.2 6.7 -1 0.3-2 0.3-3 0.1 0.9 2.8 3.4 4.8 6.2 4.9 -2.9 1.9-6.3 2.8-9.8 2.8 3 2.1 6.6 3.2 10.2 3.2 10.2 0 18.6-8.8 18.6-19.5 0-0.4 0-0.7 0-1.1 1.2-1 2.3-2.3 3.3-3.6"></path>
        </symbol>
        <symbol id="icon-github" viewBox="0 0 32 32">
            <path d="M22.1 28.4c1 0 0.8 1.1 0.8 1.1l-12.6 0c0 0-0.1-1.1 0.8-1.1 0.9 0 1.1-0.4 1.1-0.8l-0.1-3.4c-4.9 1.1-6-1.9-6-1.9 -0.8-2-1.9-2.5-1.9-2.5 -1.7-1.1 0.1-1.1 0.1-1.1 1.8 0.1 2.8 1.8 2.8 1.8 1.5 2.6 4.1 1.9 5.1 1.5 0.1-1.1 0.6-1.9 1.1-2.4 -3.9-0.4-8-1.9-8-8.5 0-1.9 0.7-3.4 1.8-4.7 -0.2-0.4-0.8-2.2 0.2-4.5 0 0 1.5-0.5 4.8 1.8 2.9-0.8 6-0.8 8.9 0 3.4-2.2 4.8-1.8 4.8-1.8 1 2.4 0.4 4.1 0.2 4.5 1.1 1.2 1.8 2.8 1.8 4.7 0 6.6-4.2 8.1-8.1 8.5 0.7 0.5 1.2 1.6 1.2 3.2l-0.1 4.7c0 0.4 0.2 0.8 1.1 0.8Z"></path>
        </symbol>
        <symbol id="icon-facebook" viewBox="0 0 32 32">
            <path d="M17.5 31.6l0-14.2 4.5 0 0.6-5.5 -5.2 0 0-3.5c0-1.6 0.4-2.7 2.6-2.7l2.8 0 0-4.9c-0.5-0.1-2.1-0.2-4-0.2 -4 0-6.7 2.5-6.7 7.3l0 4 -4.5 0 0 5.5 4.5 0 0 14.2 5.4 0Z"></path>
        </symbol>
        <symbol id="icon-codepen" viewBox="0 0 32 32">
            <path d="M32 11l0 0 0 0c0 0 0 0 0 0 0 0 0 0 0 0l0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 -15-10c0 0-1 0-2 0l-15 10 0 0 0 0 0 0 0 0 0 0 0 0 0 0c0 0 0 0 0 0l0 0 0 0c0 0 0 0 0 0l0 0c0 0 0 0 0 0l0 10c0 0 0 0 0 0l0 0c0 0 0 0 0 0l0 0c0 0 0 0 0 0l0 0c0 0 0 0 0 0l0 0c0 0 0 0 0 0l0 0 0 0c0 0 0 0 0 0l0 0 0 0 0 0 15 10c0 0 1 0 1 0 0 0 1 0 1 0l15-10 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0-10c0 0 0 0 0 0l0 0 0 0 0 0Zm-16 8l-5-3 5-3 5 3 -5 3 0 0Zm-1-9l-6 4 -5-3 11-7 0 6 0 0Zm-8 6l-3 2 0-5 3 2 0 0Zm2 2l6 4 0 6 -11-7 5-3 0 0 0 0Zm9 4l6-4 5 3 -11 7 0-6 0 0Zm8-6l3-2 0 5 -3-2 0 0Zm-2-2l-6-4 0-6 11 7 -5 3 0 0Z"></path>
        </symbol>
        <symbol id="icon-linkedin" viewBox="0 0 32 32">
            <path d="M17.7 30.5l-5.9 0 0-19.2 5.7 0 0 2.6 0.1 0c0.8-1.5 2.7-3.1 5.6-3.1 6 0 7.1 4 7.1 9.1l0 10.5 0 0 -5.9 0 0-9.3c0-2.2 0-5.1-3.1-5.1 -3.1 0-3.5 2.4-3.5 4.9l0 9.5Zm-9.6 0l-5.9 0 0-19.2 5.9 0 0 19.2Zm-3-21.8c-1.9 0-3.4-1.6-3.4-3.5 0-1.9 1.5-3.5 3.4-3.5 1.9 0 3.4 1.6 3.4 3.5 0 1.9-1.5 3.5-3.4 3.5Z"></path>
        </symbol>
        <symbol id="icon-instagram" viewBox="0 0 32 32">
            <path d="M23 1.1c4.3 0 7.8 3.5 7.8 7.8l0 14c0 4.3-3.5 7.8-7.8 7.8l-14 0c-4.3 0-7.8-3.5-7.8-7.8l0-14c0-4.3 3.5-7.8 7.8-7.8l14 0Zm-7 7.9c3.8 0 6.9 3.1 6.9 6.9 0 3.8-3.1 6.9-6.9 6.9 -3.8 0-6.9-3.1-6.9-6.9 0-3.8 3.1-6.9 6.9-6.9Zm8.7-3.7c1.1 0 1.9 0.9 1.9 1.9 0 1.1-0.9 1.9-1.9 1.9 -1.1 0-1.9-0.9-1.9-1.9 0-1 0.9-1.9 1.9-1.9Z"></path>
        </symbol>
        <symbol id="icon-folder" viewBox="0 0 24 24">
            <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
        </symbol>
        <symbol id="icon-tag" viewBox="0 0 24 24">
            <path d="M20.59 13.41 13.41 20.59a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            <circle cx="7" cy="7" r="1.5" fill="currentColor"></circle>
        </symbol>
        <symbol id="icon-arrow-left" viewBox="0 0 24 24">
            <path d="M19 12H5M12 19l-7-7 7-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
        </symbol>
        <symbol id="icon-arrow-right" viewBox="0 0 24 24">
            <path d="M5 12h14M12 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
        </symbol>
    </defs>
</svg>
