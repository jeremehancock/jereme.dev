<?php if (empty($content)): ?>
	<div class="text-center p-4">
	<?php $language->p('No pages found') ?>
	</div>
<?php endif ?>

<!-- Print all the content -->
<div class="grid-container">
    <?php
    $controlPlugin = getPlugin('pluginHighwayControl');
    $count = 0;
    if (!empty($controlPlugin->getThumbnailsPattern()))
        $pattern = $controlPlugin->getThumbnailsPattern();

    foreach ($content as $page):

        if (!empty($controlPlugin->getThumbnailsPattern())) {
            $currentSize = array_shift($pattern);
            if ($currentSize == 'l')
                $size = 'large';
            else if ($currentSize == 'm')
                $size = 'medium';
            else
                $size = '';
        }
        else {
            if ($count >= 0 && $count < 2)
                $size = 'large';
            else if ($count >= 2 && $count < 10 && $count % 2 == 0)
                $size = 'medium';
            else
                $size = '';
        }
            ?>
        <div class="grid-item <?php echo $size ?>">
            <a href="<?php echo $page->permalink(); ?>">
                <div class="grid-item-content">
                    <img class="grid-image" src="https://m7lib.dev/free-live-tv/image.php?id=<?php echo $page->slug(); ?>" alt="cover image" />
                    <div class="grid-title"><?php echo $page->title(); ?></div>
                </div>
            </a>
        </div>
    <?php
    $count++;
    endforeach ?>
</div>

<!-- Pagination -->
<?php if (Paginator::numberOfPages()>1): ?>
<nav class="paginator">
	<ul class="pagination flex-wrap justify-content-center">

		<!-- Previous button -->
		<li class="page-item <?php if (!Paginator::showPrev()) echo 'disabled' ?>">
			<a class="page-link" href="<?php echo Paginator::previousPageUrl() ?>" tabindex="-1"><?php echo $L->get('Previous'); ?></a>
		</li>

		<!-- Home button -->
		<?php if (Paginator::currentPage() > 1): ?>
		<li class="page-item">
			<a class="page-link" href="<?php echo Theme::siteUrl() ?>">Home</a>
		</li>
		<?php endif ?>

		<!-- Next button -->
		<li class="page-item <?php if (!Paginator::showNext()) echo 'disabled' ?>">
			<a class="page-link" href="<?php echo Paginator::nextPageUrl() ?>"><?php echo $L->get('Next'); ?></a>
		</li>

	</ul>
</nav>
<?php endif ?>

<?php $plugin = getPlugin('pluginInstagramFeedJelly'); ?>
<?php if (pluginActivated('pluginInstagramFeedJelly') && $plugin->isSectionVisible() == 'on'): ?>
    <section class="instagram py-5 clear-spacing-mobile">
        <div class="container-fluid py-4">
            <div class="row align-items-center">
                <div class="col-md-12 col-lg-2 offset-lg-1 pb-4 mobile-device-center">
                    <h1 class="underlined-heading white-h1"><?php if (!empty($plugin->getSectionTitle())) echo $plugin->getSectionTitle() ?></h1>
                    <p><?php if (!empty($plugin->getSectionDescription())) echo $plugin->getSectionDescription() ?></p>
                    <a class="btn btn-success" href="<?php if (!empty($plugin->getFollowMeUrl())) echo $plugin->getFollowMeUrl() ?>" role="button"><?php if (!empty($plugin->getFollowMeText())) echo $plugin->getFollowMeText() ?></a>
                </div>
                <?php
                    if (!empty($plugin->getUserId()) && !empty($plugin->getAcessToken())) {
                        $connection_c = curl_init(); // initializing
                        curl_setopt($connection_c, CURLOPT_URL, 'https://api.instagram.com/v1/users/' . $plugin->getUserId() . '/media/recent/?access_token=' . $plugin->getAcessToken() . '&count=4');
                        curl_setopt($connection_c, CURLOPT_RETURNTRANSFER, 1); // return the result, do not print
                        curl_setopt($connection_c, CURLOPT_TIMEOUT, 20);
                        $json_return = curl_exec($connection_c); // connect and get json data
                        curl_close($connection_c); // close connection
                        $latest_feed = json_decode($json_return, true);
                        foreach ($latest_feed['data'] as $element) {
                            echo '
                    <div class="instagram-tile col-6 col-md-3 col-lg-2">
                        <a href="' . $element['link'] . '"><img class="img-fluid" src="' . $element['images']['standard_resolution']['url'] . '" alt="instagram image" /></a>
                    </div>';
                        }
                    }
                ?>
            </div>
        </div>
    </section>
<?php endif ?>

<?php if (pluginActivated('pluginHighwayControl') && $controlPlugin->isCategoriesSectionVisible() == 'on'): ?>
<section class="categories py-5 clear-spacing-mobile">
    <div class="container-fluid py-5">
        <div class="row">
            <div class="col-md-12 col-lg-3 offset-lg-1">
                <h1 class="underlined-heading green-h1">Categories</h1>
            </div>
        </div>
        <div class="row">
            <?php
                $items = getCategories();
                $firstRun = true;
                foreach ($items as $category) {
                    // Each category is an Category-Object
                    if (count($category->pages()) > 0) {
                        echo '<div class="col-12 col-md-4 col-lg-2 ', $firstRun ? 'offset-lg-1' : '', '">';
                        echo ' <h2 class="small-h2">' . $category->name() . '</h2>';
                        echo '<ul>';

                        // The method $category->pages() returns all the pages keys releated to the category
                        $i = 0;
                        $MAX_ITEMS = (empty($controlPlugin->getCategoriesFeedAmount()) ? '5' : $controlPlugin->getCategoriesFeedAmount());
                        foreach ($category->pages() as $pageKey) {
                            $page = new Page($pageKey);
                            echo '<li><a href="#">' . $page->title() . '</a></li>';
                            $i++;
                            if ($i >= $MAX_ITEMS) // Give option to set number by the user
                                break;
                        }
                        echo '</ul>';
                        echo '</div>';
                        $firstRun = false;
                    }
                }
            ?>
        </div>
    </div>
</section>
<?php endif ?>