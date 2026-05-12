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
                            $target = ($asideExternalUrl !== '') ? ' target="_blank" rel="noopener noreferrer"' : '';
                            ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($asideHref, ENT_QUOTES); ?>" <?php echo $target; ?>>
                                    <?php echo $staticPage->title(); ?>
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