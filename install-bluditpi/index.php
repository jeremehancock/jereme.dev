<?php
header('Content-type: text/x-shellscript');
$build = file_get_contents('https://raw.githubusercontent.com/mhancoc7/Bludit-Pi/master/build.sh');
echo $build;
?>
