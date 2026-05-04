<?php
	// Hero = first item; everything else as compact rows with excerpt
	$hero      = !empty($content) ? $content[0] : null;
	$restItems = !empty($content) ? array_slice($content, 1) : array();
?>

<?php if (empty($content)) : ?>
	<div class="empty-state">
		<?php $language->p('No pages found') ?>
	</div>
<?php else : ?>

	<!-- Hero story -->
	<?php Theme::plugins('pageBegin'); ?>
	<article class="hero-story <?php echo $hero->coverImage() ? 'hero-story--has-image' : 'hero-story--no-image' ?>">
		<?php if ($hero->coverImage()) : ?>
			<a class="hero-story__media" href="<?php echo $hero->permalink() ?>" aria-label="<?php echo htmlspecialchars($hero->title(), ENT_QUOTES, 'UTF-8') ?>">
				<img src="<?php echo $hero->coverImage() ?>" alt="<?php echo htmlspecialchars($hero->title(), ENT_QUOTES, 'UTF-8') ?>">
			</a>
		<?php endif ?>
		<div class="hero-story__body">
			<?php if ($hero->categoryKey()) : ?>
				<a class="eyebrow" href="<?php echo $hero->categoryPermalink() ?>">
					<?php echo $hero->category() ?>
				</a>
			<?php endif ?>
			<h1 class="hero-story__title">
				<a href="<?php echo $hero->permalink() ?>"><?php echo $hero->title() ?></a>
			</h1>
			<?php if ($hero->contentBreak()) : ?>
				<div class="hero-story__lede"><?php echo $hero->contentBreak() ?></div>
			<?php elseif ($hero->description()) : ?>
				<p class="hero-story__lede"><?php echo $hero->description() ?></p>
			<?php endif ?>
			<div class="meta">
				<time datetime="<?php echo $hero->dateRaw() ?>"><?php echo $hero->date($site->dateFormat() . ' · ' . $site->timeFormat()) ?></time>
			</div>
		</div>
	</article>
	<?php Theme::plugins('pageEnd'); ?>

	<!-- Compact list with excerpt -->
	<?php if (!empty($restItems)) : ?>
		<div class="card-list">
			<h3 class="section-heading"><?php echo $L->get('Latest news') ?></h3>
			<?php foreach ($restItems as $item) : ?>
				<?php Theme::plugins('pageBegin'); ?>
				<article class="news-row">
					<?php if ($item->coverImage()) : ?>
						<a class="news-row__media" href="<?php echo $item->permalink() ?>" aria-label="<?php echo htmlspecialchars($item->title(), ENT_QUOTES, 'UTF-8') ?>">
							<img src="<?php echo $item->coverImage() ?>" alt="<?php echo htmlspecialchars($item->title(), ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
						</a>
					<?php endif ?>
					<div class="news-row__body">
						<?php if ($item->categoryKey()) : ?>
							<a class="eyebrow eyebrow--small" href="<?php echo $item->categoryPermalink() ?>">
								<?php echo $item->category() ?>
							</a>
						<?php endif ?>
						<h3 class="news-row__title">
							<a href="<?php echo $item->permalink() ?>"><?php echo $item->title() ?></a>
						</h3>
						<div class="news-row__excerpt">
							<?php echo $item->contentBreak() ?>
						</div>
						<div class="meta meta--small">
							<time datetime="<?php echo $item->dateRaw() ?>"><?php echo $item->date($site->dateFormat() . ' · ' . $site->timeFormat()) ?></time>
							<?php if ($item->readMore()) : ?>
								<span class="meta__dot"></span>
								<a class="news-row__more" href="<?php echo $item->permalink() ?>">
									<?php echo $L->get('Read more') ?> <i class="bi bi-arrow-right"></i>
								</a>
							<?php endif ?>
						</div>
					</div>
				</article>
				<?php Theme::plugins('pageEnd'); ?>
			<?php endforeach ?>
		</div>
	<?php endif ?>

<?php endif ?>

<!-- Pagination -->
<?php if (Paginator::numberOfPages() > 1) : ?>
	<nav class="paginator" aria-label="Pagination">
		<?php if (Paginator::showPrev()) : ?>
			<a class="paginator__btn" href="<?php echo Paginator::previousPageUrl() ?>">
				<i class="bi bi-chevron-left"></i> <?php echo $L->get('Previous') ?>
			</a>
		<?php endif ?>
		<span class="paginator__pos"><?php echo Paginator::currentPage() ?> / <?php echo Paginator::numberOfPages() ?></span>
		<?php if (Paginator::showNext()) : ?>
			<a class="paginator__btn" href="<?php echo Paginator::nextPageUrl() ?>">
				<?php echo $L->get('Next') ?> <i class="bi bi-chevron-right"></i>
			</a>
		<?php endif ?>
	</nav>
<?php endif ?>
