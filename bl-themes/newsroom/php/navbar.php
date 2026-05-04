<nav class="navbar-news">
	<div class="container">
		<button class="navbar-news__toggle" type="button" aria-expanded="false" aria-controls="sections" aria-label="Toggle sections">
			<i class="bi bi-list"></i>
		</button>
		<ul class="navbar-news__list" id="sections">

			<li class="navbar-news__item">
				<a class="navbar-news__link<?php echo ($WHERE_AM_I === 'home' || $WHERE_AM_I === 'blog') ? ' is-active' : '' ?>" href="<?php echo Theme::siteUrl() ?>">
					<?php echo $L->get('Home') ?>
				</a>
			</li>

			<?php
				// Categories as news sections
				global $categories;
				$categoriesDb = $categories ? $categories->db : array();
				foreach ($categoriesDb as $catKey => $catData) :
					if (empty($catData['list'])) { continue; }
					$isActive = ($WHERE_AM_I === 'category' && isset($url) && $url->slug() === $catKey);
			?>
				<li class="navbar-news__item">
					<a class="navbar-news__link<?php echo $isActive ? ' is-active' : '' ?>" href="<?php echo DOMAIN_CATEGORIES . $catKey ?>">
						<?php echo htmlspecialchars($catData['name'], ENT_QUOTES, 'UTF-8') ?>
					</a>
				</li>
			<?php endforeach ?>

		</ul>
	</div>
</nav>

<script>
(function () {
	var toggle = document.querySelector('.navbar-news__toggle');
	var list   = document.getElementById('sections');
	if (!toggle || !list) return;
	toggle.addEventListener('click', function () {
		var open = list.classList.toggle('is-open');
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
})();
</script>
