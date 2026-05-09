<?php
// =============================================================
// CONFIG: Categories to hide from the homepage
// Use the category KEY (slug), not the display name.
// =============================================================
$hiddenCategories = ['archived'];

if (!empty($hiddenCategories) && $WHERE_AM_I === 'home') {
    $itemsPerPage = (int)$site->itemsPerPage();
    $currentPage  = Paginator::currentPage();

    $allKeys = $pages->getList(1, -1, true);

    $filteredKeys = array();
    foreach ($allKeys as $key) {
        $p = buildPage($key);
        if (!in_array($p->categoryKey(), $hiddenCategories, true)) {
            $filteredKeys[] = $key;
        }
    }

    $totalItems    = count($filteredKeys);
    $totalPages    = (int)max(1, ceil($totalItems / $itemsPerPage));
    $offset        = ($currentPage - 1) * $itemsPerPage;
    $paginatedKeys = array_slice($filteredKeys, $offset, $itemsPerPage);

    $content = array();
    foreach ($paginatedKeys as $key) {
        $content[] = buildPage($key);
    }
} else {
    $currentPage = Paginator::currentPage();
    $totalPages  = Paginator::numberOfPages();
}
?>
<?php Theme::plugins('pageBegin'); ?>

<main class="site-main" id="main" role="main">
    <div class="container">
        <?php if ($WHERE_AM_I === 'category'): ?>
        <header class="page-intro">
            <?php if ($site->slogan()): ?>
            <h1 class="page-intro-title"><?php echo $helper->slogan(); ?></h1>
            <?php endif ?>
            <?php if ($site->description()): ?>
            <p class="page-intro-desc"><?php echo $helper->description(); ?></p>
            <?php endif ?>
        </header>
        <?php endif ?>

        <div class="post-grid">
            <?php foreach ($content as $page): ?>
                <?php $coverImage = $helper->get_thumb(); ?>
                <article class="post-card">
                    <a class="post-card-cover lozad" href="<?php echo $page->permalink(FALSE); ?>"
                       <?php if (!empty($coverImage)) echo 'data-background-image="'.htmlspecialchars($coverImage).'"'; ?>
                       aria-label="<?php echo htmlspecialchars($page->title()); ?>">
                        <?php if (empty($coverImage)): ?>
                            <span class="post-card-cover-placeholder">
                                <svg viewBox="0 0 27 32" width="40" height="40" aria-hidden="true">
                                    <use xlink:href="#icon-pencil"></use>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="post-card-body">
                        <div class="post-meta">
                            <time class="post-date" datetime="<?php echo $page->dateRaw('c'); ?>">
                                <?php echo $page->date(); ?>
                            </time>
                            <?php if ($page->category()): ?>
                                <span class="post-meta-sep">&middot;</span>
                                <a class="post-cat" href="<?php echo DOMAIN_CATEGORIES.$page->categoryKey(); ?>">
                                    <?php echo $page->category(); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <h2 class="post-card-title">
                            <a href="<?php echo $page->permalink(FALSE); ?>" rel="bookmark">
                                <?php echo $page->title(); ?>
                            </a>
                        </h2>
                        <div class="post-card-excerpt">
                            <?php
                            if (strlen($page->description()) > 0) {
                                echo $page->description();
                            } else {
                                echo $helper->content2excerpt($page->content(false));
                            }
                            ?>
                        </div>
                        <a class="post-card-more" href="<?php echo $page->permalink(FALSE); ?>" aria-label="<?php echo $L->get('Continue reading'); ?> <?php echo htmlspecialchars($page->title()); ?>">
                            <?php echo $L->get('Read more'); ?>
                            <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
                                <use xlink:href="#icon-arrow-right"></use>
                            </svg>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="pagination" role="navigation" aria-label="Page navigation">
            <h3 class="screen-reader-text">Posts navigation</h3>
            <div class="pagination-links">
                <?php if ($currentPage > 1): ?>
                <a class="page-btn page-btn-arrow" href="<?php echo Paginator::numberUrl($currentPage - 1); ?>" rel="prev" aria-label="<?php echo $L->get('Previous'); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><use xlink:href="#icon-arrow-left"></use></svg>
                    <span class="page-btn-text"><?php echo $L->get('Previous'); ?></span>
                </a>
                <?php endif; ?>

                <?php
                $pmax = max($currentPage + 4, 9);
                $pmin = min($currentPage - 4, $totalPages - 8);
                for ($i = max(1, $pmin); $i <= min($pmax, $totalPages); $i++):
                ?>
                    <?php if ($currentPage == $i): ?>
                    <span class="page-btn current" aria-current="page"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a class="page-btn" href="<?php echo Paginator::numberUrl($i); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                <a class="page-btn page-btn-arrow" href="<?php echo Paginator::numberUrl($currentPage + 1); ?>" rel="next" aria-label="<?php echo $L->get('Next'); ?>">
                    <span class="page-btn-text"><?php echo $L->get('Next'); ?></span>
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><use xlink:href="#icon-arrow-right"></use></svg>
                </a>
                <?php endif; ?>
            </div>
        </nav>
        <?php endif; ?>
    </div>
</main>
