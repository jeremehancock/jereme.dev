<nav class="navbar fixed-top navbar-light bg-light">
    <div>
        <a class="navbar-brand" href="<?php echo Theme::siteUrl(); ?>">
            <img src="<?php echo Theme::src("img/logo.png") ?>" width="30" height="30" class="d-inline-block align-top" alt="">
            <?php echo $site->title(); ?>
        </a>
    </div>
    <button id="navigation-button" class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsible-navbar">
        <div class="open-button"><i class="fas fa-bars px-2"></i>menu</div>
    </button>
    <script>
        $( "#navigation-button" ).click(function() {
            $('body').toggleClass('hiddenOverflow');
            $( "#collapsible-navbar" ).fadeToggle( "fast", function() {
                // Animation complete.
            });
            if ($('.close-button').length)
                $('.close-button').replaceWith('<div class="open-button"><i class="fas fa-bars px-2"></i>menu</div>');
            else
                $('.open-button').replaceWith('<div class="close-button"><i class="fas fa-arrow-left px-2"></i>close</div>');
        });
    </script>
<!--    <div id="social">-->
<!--         AddToAny BEGIN -->
<!--        <div class="a2a_kit a2a_kit_size_32 a2a_default_style">-->
<!--            <a class="a2a_button_facebook"></a>-->
<!--            <a class="a2a_button_facebook_messenger"></a>-->
<!--            <a class="a2a_button_twitter"></a>-->
<!--            <a class="a2a_button_reddit"></a>-->
<!--        </div>-->
<!--        <script src="//static.addtoany.com/menu/page.js"></script>-->
        <!-- AddToAny END -->
<!--    </div>-->
    <div class="navigation" id="collapsible-navbar">
        <div class="container-fluid">
            <div class="row">
                <div class="col-8 offset-sm-2">
                    <ul class="navbar-nav">
                        <!-- Static pages -->
                        <?php Theme::plugins('siteSidebar') ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

