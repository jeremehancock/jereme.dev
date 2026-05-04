<footer class="site-footer">
	<div class="container site-footer__inner">
		<div class="site-footer__left">
			<p class="site-footer__copy"><?php echo $site->footer() ?></p>
		</div>
		<?php if (!defined('BLUDIT_PRO')) : ?>
			<div class="site-footer__right">
				<p>Powered by <a href="https://www.bludit.com" target="_blank" rel="noopener"><strong>Bludit</strong></a></p>
			</div>
		<?php endif ?>
	</div>
</footer>
