<!DOCTYPE html>
<html lang="<?php echo Theme::lang() ?>">
<head>
<?php include(THEME_DIR_PHP.'head.php'); ?>
</head>
<body class="where-<?php echo $WHERE_AM_I ?>">

	<?php Theme::plugins('siteBodyBegin'); ?>

	<?php include(THEME_DIR_PHP.'masthead.php'); ?>
	<?php include(THEME_DIR_PHP.'navbar.php'); ?>

	<main class="site-main">
		<div class="container">
			<?php if ($WHERE_AM_I === 'page') : ?>
				<div class="layout layout--article">
					<article class="article-col">
						<?php include(THEME_DIR_PHP.'page.php'); ?>
					</article>
					<aside class="sidebar-col">
						<?php include(THEME_DIR_PHP.'sidebar.php'); ?>
					</aside>
				</div>
			<?php else : ?>
				<div class="layout layout--home">
					<section class="content-col">
						<?php include(THEME_DIR_PHP.'home.php'); ?>
					</section>
					<aside class="sidebar-col">
						<?php include(THEME_DIR_PHP.'sidebar.php'); ?>
					</aside>
				</div>
			<?php endif; ?>
		</div>
	</main>

	<?php include(THEME_DIR_PHP.'footer.php'); ?>

	<?php
		echo Theme::jquery();
		echo Theme::jsBootstrap();
	?>

	<?php Theme::plugins('siteBodyEnd'); ?>

</body>
</html>
