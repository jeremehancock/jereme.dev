
<footer">
    <div class="container-fluid py-4">

        <?php $pluginControl = getPlugin('pluginHighwayControl'); ?>
        <?php if (pluginActivated('pluginHighwayControl') && $pluginControl->isFooterSectionVisible() == 'on'): ?>
            <div class="row">
                <div class="col-12 col-md-4 col-lg-2 offset-lg-1">
                    <div class="footer-header"><?php echo $pluginControl->getFooterSectionTitle1(); ?></div>
                    <ul>
                        <?php echo $pluginControl->getFooterSection1(); ?>
                    </ul>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <div class="footer-header"><?php echo $pluginControl->getFooterSectionTitle2(); ?></div>
                    <ul>
                        <?php echo $pluginControl->getFooterSection2(); ?>
                    </ul>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <div class="footer-header"><?php echo $pluginControl->getFooterSectionTitle3(); ?></div>
                    <ul>
                        <?php echo $pluginControl->getFooterSection3(); ?>
                    </ul>
                </div>
            </div>
        <?php endif ?>

        <div class="row">
            <div class="col-lg-12" style="text-align: center;">
                <p class="offset-ln-1 m-0 p-2 text-uppercase"><span class="powered-by">Powered by:  <img class="mini-logo" src="<?php echo Theme::src("img/logo.png") ?>"/><a target="_blank" class="text-purple" href="https://pilab.dev">Pi Lab</a> | <img class="mini-logo" src="<?php echo DOMAIN_THEME_IMG.'favicon.png'; ?>"/><a target="_blank" class="text-purple" href="https://www.bludit.com">Bludit</a> | <img class="mini-logo" src="<?php echo Theme::src("img/jellyio.png") ?>"/><a target="_blank" class="text-purple" href="https://www.jellyio.com">jellyio.com</a></span></p>
            </div>
        </div>
    </div>
</footer>
