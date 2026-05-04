<div class="sidebar">

	<?php if (!empty($staticContent)) : ?>
		<section class="sidebar__block">
			<h3 class="sidebar__title"><?php echo $L->get('Featured') ?></h3>
			<ul class="sidebar__list">
				<?php foreach ($staticContent as $staticPage) : ?>
					<li class="sidebar__item">
						<?php if ($staticPage->coverImage()) : ?>
							<a class="sidebar__thumb" href="<?php echo $staticPage->permalink() ?>" aria-hidden="true" tabindex="-1">
								<img src="<?php echo $staticPage->coverImage() ?>" alt="" loading="lazy">
							</a>
						<?php endif ?>
						<div class="sidebar__content">
							<a class="sidebar__link" href="<?php echo $staticPage->permalink() ?>">
								<?php echo $staticPage->title() ?>
							</a>
							<?php if ($staticPage->description()) : ?>
								<p class="sidebar__desc"><?php echo $staticPage->description() ?></p>
							<?php endif ?>
						</div>
					</li>
				<?php endforeach ?>
			</ul>
		</section>
	<?php endif ?>

	<?php
		// Plugin slot (e.g. Simple Stats, Tag Cloud, Newsletter, etc.)
		ob_start();
		Theme::plugins('siteSidebar');
		$pluginOutput = ob_get_clean();
		if (trim($pluginOutput) !== '') :
	?>
		<section class="sidebar__block sidebar__block--plugins">
			<?php echo $pluginOutput ?>
		</section>
	<?php endif ?>

</div>
