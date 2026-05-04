<article class="article">

	<?php Theme::plugins('pageBegin'); ?>

	<header class="article__header">
		<?php if ($page->categoryKey()) : ?>
			<a class="eyebrow" href="<?php echo $page->categoryPermalink() ?>">
				<?php echo $page->category() ?>
			</a>
		<?php endif ?>

		<h1 class="article__title"><?php echo $page->title() ?></h1>

		<?php if ($page->description()) : ?>
			<p class="article__lede"><?php echo $page->description() ?></p>
		<?php endif ?>

		<?php if (!$page->isStatic() && !$url->notFound()) : ?>
			<div class="article__meta">
				<span class="article__byline"><?php echo $page->user('nickname') ?: $page->user('username') ?></span>
				<span class="meta__dot"></span>
				<time datetime="<?php echo $page->dateRaw() ?>"><?php echo $page->date($site->dateFormat() . ' · ' . $site->timeFormat()) ?></time>
				<span class="meta__dot"></span>
				<span><?php echo $L->get('Reading time') . ' ' . $page->readingTime() ?></span>
			</div>
		<?php endif ?>
	</header>

	<?php if ($page->coverImage()) : ?>
		<figure class="article__cover">
			<img src="<?php echo $page->coverImage() ?>" alt="<?php echo htmlspecialchars($page->title(), ENT_QUOTES, 'UTF-8') ?>">
		</figure>
	<?php endif ?>

	<div class="article__body">
		<?php echo $page->content() ?>
	</div>

	<?php $tagsList = $page->tags(true); ?>
	<?php if (!empty($tagsList)) : ?>
		<footer class="article__footer">
			<div class="article__tags">
				<span class="article__tags-label"><?php echo $L->get('Tags') ?></span>
				<?php foreach ($tagsList as $tagKey => $tagName) : ?>
					<a class="tag-chip" href="<?php echo DOMAIN_TAGS . $tagKey ?>">#<?php echo $tagName ?></a>
				<?php endforeach ?>
			</div>
		</footer>
	<?php endif ?>

	<?php Theme::plugins('pageEnd'); ?>

</article>
