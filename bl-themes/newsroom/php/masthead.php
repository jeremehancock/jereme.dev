<header class="masthead">
	<div class="container masthead__inner">
		<div class="masthead__date">
			<?php echo Date::current('l, F j, Y'); ?>
		</div>
		<a class="masthead__brand" href="<?php echo Theme::siteUrl() ?>">
			<?php if ($site->logo()) : ?>
				<img class="masthead__logo" src="<?php echo $site->logo() ?>" alt="<?php echo htmlspecialchars($site->title(), ENT_QUOTES, 'UTF-8') ?>">
			<?php else : ?>
				<span class="masthead__title"><?php echo $site->title() ?></span>
			<?php endif ?>
		</a>
		<div class="masthead__social">
			<?php foreach (Theme::socialNetworks() as $key => $label) : ?>
				<a class="masthead__social-link" href="<?php echo $site->{$key}(); ?>" target="_blank" rel="noopener" aria-label="<?php echo $label ?>">
					<img src="<?php echo DOMAIN_THEME . 'img/' . $key . '.svg' ?>" alt="<?php echo $label ?>">
				</a>
			<?php endforeach ?>
		</div>
	</div>
</header>
