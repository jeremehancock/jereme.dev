<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php echo Theme::headTitle(); ?>
<?php echo $helper->head_description(); ?>

<?php echo Theme::favicon('img/favicon.ico', HTML_PATH_THEME); ?>

<?php echo Theme::jquery(); ?>

<?php echo Theme::css('css/style.min.css', HTML_PATH_THEME); ?>
<?php echo Theme::css('css/jereme.css', HTML_PATH_THEME); ?>
<?php Theme::plugins('siteHead'); ?>

<link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700|Roboto:400,400i,700&display=swap" rel="stylesheet" />
