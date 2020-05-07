<html>
<head>
<link rel="stylesheet" type="text/css" href="../bl-themes/blekathlon-pilab/css/style.min.css">
<link rel="stylesheet" type="text/css" href="../bl-themes/blekathlon-pilab/css/pilab.css">
<link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700|Roboto:400,400i,700&display=swap" rel="stylesheet" />
</head>
<body>
<?php
function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

function get_json($url) {
    $source = file_get_contents_curl($url);
    return json_decode($source, true);
}

$api = "https://m7lib.dev/api/v1/channels/";
$json = get_json($api);
?>
<div class="table-border">
<div class="table-wrapper">
<table>
    <thead>
    <tr>
        <th>Channel</th>
        <th></th>
        <th>Channel Count: <?php echo count($json); ?></th>
    </tr>
    </thead>
    <tbody>
<?php

foreach ($json as $key => $values) {
    $player = $values[endpoints][player];
	echo '<tr>';
	echo "<td>$values[name]</td>";
	echo "<td></td>";
	echo "<td><a href=\"$player\">Watch Now</a></td>";
	echo '</tr>';
}
	
?>
    </tbody>
</table>
</div>
</div>
