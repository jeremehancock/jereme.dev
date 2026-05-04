<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="generator" content="Bludit">

<?php echo Theme::metaTags('title'); ?>
<?php echo Theme::metaTags('description'); ?>

<?php
	// Robots directives based on page settings
	if ($WHERE_AM_I === 'page' && isset($page) && $page) {
		$robots = array();
		if ($page->noindex())   { $robots[] = 'noindex'; }
		if ($page->nofollow())  { $robots[] = 'nofollow'; }
		if ($page->noarchive()) { $robots[] = 'noarchive'; }
		if (!empty($robots)) {
			echo '<meta name="robots" content="' . implode(',', $robots) . '">' . PHP_EOL;
		}
	}
?>

<?php
	// Canonical URL
	$canonical = '';
	if ($WHERE_AM_I === 'page' && isset($page) && $page) {
		$canonical = $page->permalink();
	} else {
		$canonical = rtrim(Theme::siteUrl(), '/') . $_SERVER['REQUEST_URI'];
	}
?>
<link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

<?php
	// Open Graph / Twitter metadata
	$ogType        = ($WHERE_AM_I === 'page') ? 'article' : 'website';
	$ogTitle       = $site->title();
	$ogDescription = $site->description();
	$ogImage       = '';

	if ($WHERE_AM_I === 'page' && isset($page) && $page) {
		$ogTitle       = $page->title();
		$ogDescription = $page->description() ?: $site->description();
		if ($page->coverImage()) {
			$ogImage = $page->coverImage();
		}
	}
?>
<meta property="og:type" content="<?php echo $ogType ?>">
<meta property="og:site_name" content="<?php echo htmlspecialchars($site->title(), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($ogImage) : ?>
<meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
<?php endif ?>

<meta name="twitter:card" content="<?php echo $ogImage ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($ogImage) : ?>
<meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
<?php endif ?>

<?php if ($WHERE_AM_I === 'page' && isset($page) && $page && !$page->isStatic()) : ?>
<meta property="article:published_time" content="<?php echo $page->dateRaw() ?>">
<?php $articleTags = $page->tags(true); if (!empty($articleTags)) : ?>
	<?php foreach ($articleTags as $tagKey => $tagName) : ?>
<meta property="article:tag" content="<?php echo htmlspecialchars($tagName, ENT_QUOTES, 'UTF-8') ?>">
	<?php endforeach ?>
<?php endif ?>
<?php if ($page->categoryKey()) : ?>
<meta property="article:section" content="<?php echo htmlspecialchars($page->category(), ENT_QUOTES, 'UTF-8') ?>">
<?php endif ?>
<?php endif ?>

<link rel="alternate" type="application/rss+xml" title="<?php echo htmlspecialchars($site->title(), ENT_QUOTES, 'UTF-8') ?> RSS" href="<?php echo Theme::rssUrl() ?>">

<?php echo Theme::favicon('img/favicon.png'); ?>

<?php echo Theme::cssBootstrap(); ?>
<?php echo Theme::cssBootstrapIcons(); ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<?php echo Theme::css('css/style.css'); ?>

<?php Theme::plugins('siteHead'); ?>
