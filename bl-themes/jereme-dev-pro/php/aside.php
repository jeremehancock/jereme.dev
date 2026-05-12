<aside class="site-aside" role="complementary">
    <div class="container">
        <div class="aside-grid">
            <div class="aside-col aside-brand">
                <p class="aside-title"><?php echo $site->title(); ?></p>
                <p class="aside-desc"><?php echo $site->description(); ?></p>
            </div>

            <?php
            // Use the global helper to see if the plugin is actually enabled
            $companion = null;
            if (function_exists('pluginActivated') && pluginActivated('pluginJeremeDevProCompanion')) {
                global $plugins;
                $companion = $plugins['all']['pluginJeremeDevProCompanion'];
            }
            ?>

            <?php if ($companion && $companion->getValue('latestEnabled')): ?>
                <div class="aside-col">
                    <h3 class="aside-heading"><?php echo $companion->getValue('latestLabel'); ?></h3>
                    <ul class="aside-list">
                        <?php
                        $numItems = $companion->getValue('latestNumberOfItems');
                        $listOfKeys = $pages->getList(1, $numItems);
                        if ($listOfKeys):
                            foreach ($listOfKeys as $key):
                                $lPage = new Page($key);
                                ?>
                                <li><a href="<?php echo $lPage->permalink(FALSE); ?>"><?php echo $lPage->title(); ?></a></li>
                            <?php endforeach; endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($companion && $companion->getValue('staticEnabled')): ?>
                <div class="aside-col">
                    <h3 class="aside-heading"><?php echo $companion->getValue('staticLabel'); ?></h3>
                    <ul class="aside-list">
                        <?php foreach ($staticContent as $staticPage):
                            $asideDesc = trim($staticPage->description());
                            if ($asideDesc === '404')
                                continue;

                            $asideExternalUrl = '';
                            if (stripos($asideDesc, 'external:') === 0) {
                                $asideExternalUrl = trim(substr($asideDesc, 9));
                            }

                            $asideHref = ($asideExternalUrl !== '') ? $asideExternalUrl : $staticPage->permalink(FALSE);
                            $isExternal = ($asideExternalUrl !== '');
                            $target = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
                            ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($asideHref, ENT_QUOTES); ?>" <?php echo $target; ?>
                                    style="display: inline-flex; align-items: baseline; gap: 0.35em;">
                                    <?php echo $staticPage->title(); ?>

                                    <?php if ($isExternal): ?>
                                        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                                            style="display: block;">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                            <polyline points="15 3 21 3 21 9"></polyline>
                                            <line x1="10" y1="14" x2="21" y2="3"></line>
                                        </svg>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="aside-col aside-bludit-col">
                <a class="aside-bludit-link" href="https://www.bludit.com" target="_blank" rel="noopener">
                    <img class="aside-bludit-logo lozad" data-src="<?php echo HTML_PATH_THEME; ?>img/bludit.png"
                        alt="Bludit" width="140" />
                    <p class="aside-powered">
                        <?php echo $L->get('Powered by'); ?>
                    </p>
                </a>
            </div>
        </div>
    </div>
</aside>