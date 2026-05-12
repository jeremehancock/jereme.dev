<?php
$hiddenCategories = ['archived'];
?>
<main class="site-main site-main-single" id="main" role="main">
    <article class="entry">
        <?php Theme::plugins('pageBegin'); ?>

        <?php if ($page->title() !== "404 Not Found"): ?>
            <header class="entry-header container">
                <?php if ($page->category() && !$page->isStatic()): ?>
                    <div class="entry-eyebrow">
                        <a href="<?php echo DOMAIN_CATEGORIES . $page->categoryKey(); ?>"><?php echo $page->category(); ?></a>
                    </div>
                <?php endif; ?>

                <h1 class="entry-title">
                    <?php echo $page->title(); ?>

                    <?php if ($login->isLogged() && checkRole(array('admin', 'editor'))): ?>
                        <a class="edit-link" href="<?php echo HTML_PATH_ADMIN_ROOT . 'edit-content/' . $page->slug(); ?>"
                            target="_blank" aria-label="Edit">
                            <svg viewBox="0 0 27 32" width="18" height="18" aria-hidden="true">
                                <use xlink:href="#icon-pencil"></use>
                            </svg>
                        </a>
                    <?php endif; ?>
                </h1>

                <?php if (!$page->isStatic()): ?>
                    <div class="entry-meta">
                        <time class="entry-date" datetime="<?php echo $page->dateRaw('c'); ?>">
                            <?php echo $page->date(); ?>
                        </time>

                        <?php $lastmod = $page->dateModified();
                        if (!empty($lastmod)): ?>
                            <time class="entry-updated"
                                datetime="<?php echo Date::format($lastmod, DB_DATE_FORMAT, 'c'); ?>"></time>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <div class="entry-content container">
            <?php if (!$page->isStatic() && in_array($page->categoryKey(), $hiddenCategories, true)): ?>
                <div class="archived-banner" role="note">
                    <svg class="archived-banner-icon" viewBox="0 0 16 16" aria-hidden="true" width="18" height="18">
                        <path fill-rule="evenodd"
                            d="M0 1.75C0 .784.784 0 1.75 0h12.5C15.216 0 16 .784 16 1.75v1.5A1.75 1.75 0 0 1 14.25 5H1.75A1.75 1.75 0 0 1 0 3.25v-1.5zM1.75 1.5a.25.25 0 0 0-.25.25v1.5c0 .138.112.25.25.25h12.5a.25.25 0 0 0 .25-.25v-1.5a.25.25 0 0 0-.25-.25H1.75zM1.5 6.75a.75.75 0 0 1 1.5 0v6.75c0 .138.112.25.25.25h9.5a.25.25 0 0 0 .25-.25V6.75a.75.75 0 0 1 1.5 0v6.75A1.75 1.75 0 0 1 12.75 15.5h-9.5A1.75 1.75 0 0 1 1.5 13.5V6.75zm4 .75a.75.75 0 0 0 0 1.5h5a.75.75 0 0 0 0-1.5h-5z" />
                    </svg>
                    <span>This post has been archived. The content may be out of date or no longer maintained.</span>
                </div>
            <?php endif; ?>

            <div class="page-content">
                <?php echo $helper->withImageDimensions($page->content()); ?>
            </div>
        </div>

        <footer class="entry-footer container">
            <?php if ($page->tags()): ?>
                <div class="entry-tags">
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                        <use xlink:href="#icon-tag"></use>
                    </svg>
                    <?php foreach ($page->tags(true) as $tagKey => $tagName): ?>
                        <a class="tag-pill" href="<?php echo DOMAIN_TAGS . $tagKey; ?>"
                            rel="tag"><?php echo mb_strtolower($tagName, 'UTF-8'); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            if (!empty($hiddenCategories)) {
                $allKeys = $pages->getList(1, -1, true);
                $visibleKeys = array();
                foreach ($allKeys as $k) {
                    $p = buildPage($k);
                    if ($p->isStatic())
                        continue;
                    if (in_array($p->categoryKey(), $hiddenCategories, true))
                        continue;
                    $visibleKeys[] = $k;
                }
                $currentIndex = array_search($page->key(), $visibleKeys, true);
                $prevKey = false;
                $nextKey = false;
                if ($currentIndex !== false) {
                    if (isset($visibleKeys[$currentIndex + 1]))
                        $prevKey = $visibleKeys[$currentIndex + 1];
                    if (isset($visibleKeys[$currentIndex - 1]))
                        $nextKey = $visibleKeys[$currentIndex - 1];
                }
            } else {
                $prevKey = $helper->previousKey();
                $nextKey = $helper->nextKey();
            }
            if ($prevKey || $nextKey):
                ?>
                <nav class="post-navigation" role="navigation" aria-label="Post navigation">
                    <?php if ($prevKey):
                        $prevPage = new Page($prevKey); ?>
                        <a class="nav-prev" href="<?php echo $prevPage->permalink(FALSE); ?>" rel="prev">
                            <span class="nav-direction"><svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
                                    <use xlink:href="#icon-arrow-left"></use>
                                </svg> <?php echo $L->get('Previous'); ?></span>
                            <span class="nav-title"><?php echo $prevPage->title(); ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($nextKey):
                        $nextPage = new Page($nextKey); ?>
                        <a class="nav-next" href="<?php echo $nextPage->permalink(FALSE); ?>" rel="next">
                            <span class="nav-direction"><?php echo $L->get('Next'); ?> <svg viewBox="0 0 24 24" width="14"
                                    height="14" aria-hidden="true">
                                    <use xlink:href="#icon-arrow-right"></use>
                                </svg></span>
                            <span class="nav-title"><?php echo $nextPage->title(); ?></span>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

            <?php Theme::plugins('pageEnd'); ?>
        </footer>
    </article>
</main>