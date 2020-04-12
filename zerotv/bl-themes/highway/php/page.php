<section class="page">

    <!-- Page cover image -->
    <?php if ($page->type() != "static"): ?>
        <div class="page-cover-image py-6 mb-4" style="background-image: url('<?php echo Theme::src("img/loading.gif") ?>');">
            <div class="coverimage-block">
                <iframe src="https://m7lib.dev/free-live-tv/?id=<?php echo $page->slug(); ?>"  class="iframe" allowfullscreen="true"></iframe>
            </div>
        </div>
    <?php endif ?>

    <?php if ($page->type() == "static"): ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 mx-auto">
                    <!-- Load Bludit Plugins: Page Begin -->
                    <?php Theme::plugins('pageBegin'); ?>

                    <!-- Page content -->
                    <div class="page-content py-5">
                    <?php echo $page->content(); ?>
                    </div>

                    <!-- Load Bludit Plugins: Page End -->
                    <?php Theme::plugins('pageEnd'); ?>
                </div>
            </div>
        </div>
    <?php endif ?>
</section>
