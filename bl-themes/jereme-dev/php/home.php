<?php
// =============================================================
// CONFIG: Categories to hide from the homepage
// Use the category KEY (slug), not the display name.
// Example: ['archived']  or  ['archived', 'drafts']
// Leave empty to disable filtering: []
// =============================================================
$hiddenCategories = ['archived'	];

// --- Filter logic (no need to edit below) ---
if (!empty($hiddenCategories) && $WHERE_AM_I === 'home') {
    $itemsPerPage = (int)$site->itemsPerPage();
    $currentPage  = Paginator::currentPage();

    // Get keys of ALL published pages
    $allKeys = $pages->getList(1, -1, true);

    // Filter out hidden categories
    $filteredKeys = array();
    foreach ($allKeys as $key) {
        $p = buildPage($key);
        if (!in_array($p->categoryKey(), $hiddenCategories, true)) {
            $filteredKeys[] = $key;
        }
    }

    // Compute pagination from filtered list
    $totalItems    = count($filteredKeys);
    $totalPages    = (int)max(1, ceil($totalItems / $itemsPerPage));
    $offset        = ($currentPage - 1) * $itemsPerPage;
    $paginatedKeys = array_slice($filteredKeys, $offset, $itemsPerPage);

    // Replace $content with our filtered, paginated set
    $content = array();
    foreach ($paginatedKeys as $key) {
        $content[] = buildPage($key);
    }
} else {
    // No filtering — fall back to Bludit's defaults
    $currentPage = Paginator::currentPage();
    $totalPages  = Paginator::numberOfPages();
}
?>
<?php Theme::plugins('pageBegin'); ?>
<div class="content-area site-content main-padding">
	<main class="site-main main-width blog-wrapper body-padding" role="main">
		<?php if ($WHERE_AM_I === 'category'): ?>
		<header class="page-header">
			<!-- Site title -->
			<?php if ($site->slogan()): ?>
			<h1 class="page-title small-margin-bottom text-center text-italic">
				<?php echo $helper->slogan(); ?>
			</h1>
			<?php endif ?>
			<?php if ($site->description()): ?>
			<p class="site-description custom-margin-bottom text-center">
				<?php echo $helper->description(); ?>
			</p>
			<?php endif ?>
		</header>
		<?php endif ?>
		<?php foreach ($content as $page): ?>
		<article class="home-page hentry hentry-border">
			<?php $coverImage = $helper->get_thumb();?>
			<div class="entry-header-bg lozad" <?php if(!empty($coverImage)) echo "data-background-image=\"$coverImage\""?>>
				<a class="entry-header-bg-link" href="<?php echo $page->permalink(FALSE) ?>" rel="bookmark">
					<?php if(empty($coverImage)):?>
					<svg class="icon icon-pencil" aria-hidden="true" role="img">
						<use xlink:href="#icon-pencil"></use>
					</svg>
					<?php endif ?>
					<span class="screen-reader-text">
						<?php echo $L->get('Continue reading') . ' ' . $page->title() . PHP_EOL ?>
					</span>
				</a>
			</div>
			<div class="entry-inner">

				<!-- Page title -->
				<header class="entry-header">
					<div class="entry-meta soft-color medium-font-weight smaller-font-size">
						<span class="posted-on">
							<span class="screen-reader-text">Posted on</span>
							<time class="entry-date published" datetime="<?php echo $page->dateRaw('c') ?>">
								<?php echo $page->date() ?>
							</time>
						</span>
					</div>
					<h2 class="entry-title title-font text-italic">
						<a href="<?php echo $page->permalink(FALSE) ?>" rel="bookmark">
							<?php echo $page->title() ?>
						</a>
					</h2>
				</header>

				<div class="entry-summary">
					<?php if(strlen($page->description())>0 ){
                              echo  $page->description();
                          }
                          else{
                              echo $helper->content2excerpt($page->content(false));
                          }
                    ?>
				</div>

				<div class="entry-comment grid-same-line">
					<a class="more-link underline-link medium-font-weight italic-link" href="<?php echo $page->permalink(FALSE) ?>" role="button">
						<?php echo $L->get('Read more'); ?>
					</a>
				</div>
			</div>
		</article>
		<?php endforeach ?>

		<?php if ($totalPages > 1): ?>

		<nav class="navigation pagination" role="navigation" aria-label="Page navigation">
			<h3 class="screen-reader-text">Posts navigation</h3>
			<div class="nav-links">

				<?php if ($currentPage > 1): ?>
				<a class="prev page-numbers" href="<?php echo Paginator::numberUrl($currentPage - 1) ?>" tabindex="-1">
                    <span id="mobile-text"><?php echo $L->get('Previous'); ?></span>
                    <svg id="mobile-arrows" class="icon icon-arrow-circle-left" aria-hidden="true" role="img" viewBox="0 0 27 32">
                        <path d="M23 17v-2q0 0 0-1t-1 0h-9l3-3q0 0 0-1t0-1l-2-2q0 0-1 0t-1 0l-8 8q0 0 0 1t0 1l8 8q0 0 1 0t1 0l2-2q0 0 0-1t0-1l-3-3h9q0 0 1 0t0-1zM27 16q0 4-2 7t-5 5-7 2-7-2-5-5-2-7 2-7 5-5 7-2 7 2 5 5 2 7z"></path>
                    </svg>
				</a>
				<?php endif ?>

				<?php
                  //max 9 pages with move
                  $pmax = max($currentPage + 4, 9);
                  $pmin = min($currentPage - 4, $totalPages - 8);
                ?>
				<?php for ($i = max(1, $pmin); $i <= min($pmax, $totalPages); $i++): ?>
				<?php if($currentPage == $i): ?>
				<span class="page-numbers current">
					<?php echo $i ?>
				</span>
				<?php else: ?>
				<a class="page-numbers" href="<?php echo Paginator::numberUrl($i) ?>">
					<?php echo $i ?>
				</a>
				<?php endif; ?>
				<?php endfor; ?>

				<?php if ($currentPage < $totalPages): ?>
				<a class="next page-numbers" href="<?php echo Paginator::numberUrl($currentPage + 1) ?>">
                    <span id="mobile-text"><?php echo $L->get('Next'); ?></span>
                    <svg id="mobile-arrows" class="icon icon-arrow-circle-right" aria-hidden="true" role="img" viewBox="0 0 27 32">
                        <path d="M23 16q0 0 0-1l-8-8q0 0-1 0t-1 0l-2 2q0 0 0 1t0 1l3 3h-9q0 0-1 0t0 1v2q0 0 0 1t1 0h9l-3 3q0 0 0 1t0 1l2 2q0 0 1 0t1 0l8-8q0 0 0-1zM27 16q0 4-2 7t-5 5-7 2-7-2-5-5-2-7 2-7 5-5 7-2 7 2 5 5 2 7z"></path>
                    </svg>
				</a>
				<?php endif ?>

			</div>
		</nav>
		<?php endif ?>
		<?php //Theme::plugins('pageEnd'); ?>
	</main>
</div>
