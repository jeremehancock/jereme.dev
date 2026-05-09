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
                        if (Text::stringContains($staticPage->key(), '404')) continue;
                    ?>
                    <li>
                        <a href="<?php echo $staticPage->permalink(FALSE); ?>" <?php if ($staticPage->title() === 'Homelab' || $staticPage->title() === 'DumbProjects' || $staticPage->title() === 'Resumé') echo 'target="_blank" rel="noopener"'; ?>>
                            <?php echo $staticPage->title(); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="aside-col aside-social">
                <nav class="social-nav" aria-label="Social Menu">
                    <ul class="social-list">
                        <?php foreach (Theme::socialNetworks() as $key => $label): ?>
                        <li>
                            <a class="social-link" href="<?php echo $site->{$key}(); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo $label; ?>">
                                <svg class="social-icon" aria-hidden="true" role="img">
                                    <use xlink:href="#icon-<?php echo $key; ?>"></use>
                                </svg>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <p class="aside-powered">
                    <?php echo $L->get('Powered by'); ?>
                    <a target="_blank" rel="noopener" href="https://www.bludit.com">Bludit</a>
                </p>
            </div>
        </div>
    </div>
</aside>
