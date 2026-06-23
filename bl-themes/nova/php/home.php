<?php
// =============================================================
// CONFIG: Categories to hide from the homepage
// =============================================================
$hiddenCategories = ['archived'];

$itemsPerPage = (int)$site->itemsPerPage();
if ($itemsPerPage < 1) { $itemsPerPage = 6; }
$currentPage = Paginator::currentPage();
$isHome      = ($WHERE_AM_I === 'home');
$isFirstPage = ($isHome && $currentPage === 1);

// On the home, page 1 shows: 1 featured + itemsPerPage grid cards.
// On every other page (and category pages): itemsPerPage grid cards, no featured.
$pageSize = ($isFirstPage ? $itemsPerPage + 1 : $itemsPerPage);

if ($isHome) {
    // Build the visible-keys list (exclude hidden categories) and paginate ourselves
    // so the featured-card offset doesn't break page math.
    $allKeys = $pages->getList(1, -1, true);
    $filteredKeys = array();
    foreach ($allKeys as $key) {
        $p = buildPage($key);
        if (in_array($p->categoryKey(), $hiddenCategories, true)) continue;
        $filteredKeys[] = $key;
    }

    $totalItems = count($filteredKeys);

    // Total pages: page 1 holds (itemsPerPage + 1) items, every other page holds itemsPerPage.
    if ($totalItems <= $itemsPerPage + 1) {
        $totalPages = 1;
    } else {
        $totalPages = 1 + (int)ceil(($totalItems - $itemsPerPage - 1) / $itemsPerPage);
    }

    // Slice for the current page.
    if ($isFirstPage) {
        $offset = 0;
        $sliceLen = min($itemsPerPage + 1, $totalItems);
    } else {
        $offset = ($itemsPerPage + 1) + ($currentPage - 2) * $itemsPerPage;
        $sliceLen = $itemsPerPage;
    }
    $paginatedKeys = array_slice($filteredKeys, $offset, $sliceLen);

    $content = array();
    foreach ($paginatedKeys as $key) {
        $content[] = buildPage($key);
    }
} elseif ($WHERE_AM_I === 'tag') {
    // Filter out pages in hidden categories from tag listings and paginate
    // ourselves so the totals stay accurate.
    global $tags;
    $tagKey = $url->slug();
    $allTagKeys = isset($tags->db[$tagKey]['list']) ? $tags->db[$tagKey]['list'] : array();

    $filteredKeys = array();
    foreach ($allTagKeys as $key) {
        try {
            $p = buildPage($key);
        } catch (Exception $e) {
            continue;
        }
        $type = $p->type();
        if ($type !== 'published' && $type !== 'sticky' && $type !== 'static') continue;
        if (in_array($p->categoryKey(), $hiddenCategories, true)) continue;
        $filteredKeys[] = $key;
    }

    $totalItems = count($filteredKeys);
    $totalPages = max(1, (int)ceil($totalItems / $itemsPerPage));

    $offset = ($currentPage - 1) * $itemsPerPage;
    $paginatedKeys = array_slice($filteredKeys, $offset, $itemsPerPage);

    $content = array();
    foreach ($paginatedKeys as $key) {
        $content[] = buildPage($key);
    }
} else {
    $totalPages = Paginator::numberOfPages();
}
?>
<?php Theme::plugins('pageBegin'); ?>

<main class="site-main<?php echo (!$isHome && $WHERE_AM_I !== 'category' && $WHERE_AM_I !== 'tag') ? ' site-main-padded' : ''; ?>" id="main" role="main">
    <?php if ($isHome): ?>
    <section class="page-band" aria-label="Latest">
        <div class="container">
            <p class="page-band-eyebrow"><span class="band-tick" aria-hidden="true"></span><?php echo $L->get('Latest posts'); ?></p>
            <?php if ($site->slogan()): ?>
            <h1 class="page-band-title"><?php echo $helper->slogan(); ?></h1>
            <?php endif; ?>
        </div>
    </section>
    <?php elseif ($WHERE_AM_I === 'category'): ?>
    <section class="page-band" aria-label="Category">
        <div class="container">
            <p class="page-band-eyebrow"><span class="band-tick" aria-hidden="true"></span>Category</p>
            <h1 class="page-band-title"><?php echo $helper->slogan(); ?></h1>
            <?php if ($site->description()): ?>
            <p class="page-band-desc"><?php echo $helper->description(); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <?php elseif ($WHERE_AM_I === 'tag'): ?>
    <section class="page-band" aria-label="Tag">
        <div class="container">
            <p class="page-band-eyebrow"><span class="band-tick" aria-hidden="true"></span>Tag</p>
            <h1 class="page-band-title"><?php echo $helper->tagName(); ?></h1>
        </div>
    </section>
    <?php endif; ?>

    <div class="container">
        <?php $featured = ($isFirstPage && !empty($content)) ? array_shift($content) : null; ?>

        <?php if ($featured): $page = $featured; $coverImage = $helper->get_thumb(); ?>
        <article class="post-feature">
            <a class="post-feature-cover lozad" href="<?php echo $featured->permalink(FALSE); ?>"
               <?php if (!empty($coverImage)) echo 'data-background-image="'.htmlspecialchars($coverImage).'"'; ?>
               aria-label="<?php echo htmlspecialchars($featured->title()); ?>">
                <?php if (empty($coverImage)): ?>
                <span class="post-card-cover-placeholder">
                    <svg viewBox="0 0 27 32" width="64" height="64" aria-hidden="true">
                        <use xlink:href="#icon-pencil"></use>
                    </svg>
                </span>
                <?php endif; ?>
            </a>
            <div class="post-feature-body">
                <p class="post-feature-eyebrow">
                    <span class="post-feature-badge">Featured</span>
                    <?php if ($featured->category()): ?>
                    <a class="post-feature-cat" href="<?php echo DOMAIN_CATEGORIES.$featured->categoryKey(); ?>"><?php echo $featured->category(); ?></a>
                    <?php endif; ?>
                </p>
                <h2 class="post-feature-title">
                    <a href="<?php echo $featured->permalink(FALSE); ?>" rel="bookmark"><?php echo $featured->title(); ?></a>
                </h2>
                <div class="post-feature-excerpt">
                    <?php
                    if (strlen($featured->description()) > 0) {
                        echo $featured->description();
                    } else {
                        echo $helper->content2excerpt($featured->content(false), 320);
                    }
                    ?>
                </div>
                <div class="post-feature-meta">
                    <time datetime="<?php echo $featured->dateRaw('c'); ?>"><?php echo $featured->date(); ?></time>
                    <a class="post-feature-more" href="<?php echo $featured->permalink(FALSE); ?>">
                        <?php echo $L->get('Read more'); ?>
                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><use xlink:href="#icon-arrow-right"></use></svg>
                    </a>
                </div>
            </div>
        </article>
        <?php endif; ?>

        <?php if (!empty($content)): ?>
        <div class="post-grid <?php echo $featured ? 'post-grid-after-feature' : ''; ?>">
            <?php foreach ($content as $page): $coverImage = $helper->get_thumb(); ?>
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
                        <time class="post-date" datetime="<?php echo $page->dateRaw('c'); ?>"><?php echo $page->date(); ?></time>
                        <?php if ($page->category()): ?>
                        <span class="post-meta-sep">&middot;</span>
                        <a class="post-cat" href="<?php echo DOMAIN_CATEGORIES.$page->categoryKey(); ?>"><?php echo $page->category(); ?></a>
                        <?php endif; ?>
                    </div>
                    <h2 class="post-card-title">
                        <a href="<?php echo $page->permalink(FALSE); ?>" rel="bookmark"><?php echo $page->title(); ?></a>
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
                    <a class="post-card-more" href="<?php echo $page->permalink(FALSE); ?>">
                        <?php echo $L->get('Read more'); ?>
                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><use xlink:href="#icon-arrow-right"></use></svg>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

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
