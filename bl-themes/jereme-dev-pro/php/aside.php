<aside class="site-aside" role="complementary">
    <div class="container">
        <div class="aside-grid">
            <div class="aside-col aside-brand">
                <p class="aside-title"><?php echo $site->title(); ?></p>
                <p class="aside-desc"><?php echo $site->description(); ?></p>
            </div>

            <div class="aside-col">
                <h3 class="aside-heading"><?php echo $L->get('Latest posts'); ?></h3>
                <ul class="aside-list">
                    <?php
                    $listOfKeys = $pages->getList(1, 3);
                    if ($listOfKeys):
                        foreach ($listOfKeys as $key):
                            $lPage = new Page($key);
                            ?>
                            <li><a href="<?php echo $lPage->permalink(FALSE); ?>"><?php echo $lPage->title(); ?></a></li>
                        <?php endforeach; endif; ?>
                </ul>
            </div>

            <div class="aside-col">
                <h3 class="aside-heading"><?php echo $L->get('About'); ?></h3>
                <ul class="aside-list">
                    <?php foreach ($staticContent as $staticPage):
                        $asideDesc = trim($staticPage->description());
                        if ($asideDesc === '404')
                            continue;
                        $asideExternalUrl = '';
                        if (stripos($asideDesc, 'external:') === 0) {
                            $asideExternalUrl = trim(substr($asideDesc, 9));
                        }
                        $asideHref = $asideExternalUrl !== '' ? $asideExternalUrl : $staticPage->permalink(FALSE);
                        ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($asideHref, ENT_QUOTES); ?>" <?php if ($asideExternalUrl !== '')
                                    echo 'target="_blank" rel="noopener noreferrer"'; ?>>
                                <?php echo $staticPage->title(); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

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