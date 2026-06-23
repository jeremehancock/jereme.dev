<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0d0f12" media="(prefers-color-scheme: dark)">

<?php echo Theme::headTitle(); ?>
<?php echo $helper->head_description(); ?>

<?php echo Theme::favicon('img/favicon.svg', HTML_PATH_THEME); ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" onload="this.onload=null;this.rel='stylesheet'" />
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" /></noscript>

<?php echo Theme::css('css/theme.css', HTML_PATH_THEME); ?>

<script>
    // Apply theme as early as possible to avoid flash of incorrect theme.
    (function () {
        try {
            var stored = localStorage.getItem('jdpro-theme');
            var prefDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var theme = stored || (prefDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        } catch (e) { /* localStorage unavailable */ }
    })();
</script>

<?php Theme::plugins('siteHead'); ?>

<script type="application/ld+json">
    {
      "@context": "http://schema.org",
      "@type": "WebSite",
      "name": "<?php echo $site->title(); ?>",
      "alternateName": "<?php echo $site->description(); ?>",
      "url": "<?php echo $site->url(); ?>"
    }
</script>
