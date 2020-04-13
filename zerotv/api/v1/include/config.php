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

function extract_unit($string, $start, $end) {
    $pos = stripos($string, $start);
    $str = substr($string, $pos);
    $str_two = substr($str, strlen($start));
    $second_pos = stripos($str_two, $end);
    $str_three = substr($str_two, 0, $second_pos);
    return trim($str_three);
}

function stirr_json($slug) {
    $url = "https://ott-gateway-stirr.sinclairstoryline.com/api/rest/v3/status/$slug";
    $source = file_get_contents_curl($url);
    return json_decode($source, true);
}

$player_width = "600";
$player_height = "400";
$autoplay = true;
?>
