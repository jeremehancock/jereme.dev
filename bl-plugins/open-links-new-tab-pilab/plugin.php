<?php
class pluginTargetBlank extends Plugin {
    public function siteBodyEnd() {
            return '<script>var links = document.querySelectorAll( \'.page-content a\' );  for (var i = 0, length = links.length; i < length; i++) {if (links[i].hostname != window.location.hostname) {links[i].target = \'_blank\';}}</script>'.PHP_EOL;
    }
}
?>