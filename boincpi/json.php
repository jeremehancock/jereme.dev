<?php

error_reporting(E_ERROR);

header('Content-Type: application/json');

function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36');

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

$url = 'https://boinc.bakerlab.org/rosetta/userw.php?id=2122121';

$xml = file_get_contents_curl($url);

$card = extract_unit($xml, "<card id=\"cd1\">", "</card>");

$card = str_replace("Account Data<br/>for", "", $card);
$card = str_replace("TotCred", "Total Credit", $card);
$card = str_replace("AvgCred", "Average Credit", $card);
$card = str_replace("Rosetta@home", "<b>Rosetta@home</b>", $card);
$card = str_replace("Pi Lab", "<b>Pi Lab</b>", $card);
$card = str_replace("Fold for Covid", "<b>Fold for Covid Stats:</b>", $card);

$usercredits = "User " . extract_unit($card, "<br/>User", "<br/>Team:");

$total = extract_unit($usercredits, "User Total Credit: ", "<br/>");
$average = extract_unit($usercredits, "User Average Credit: ", "<br/>");

$teamcredits = extract_unit($card, "<br/>Team: ", "</p>");

echo '{';
echo '   "status": "success",';
echo '"data": {';
echo '    "resultType": "vector",';
echo '"result": [';
echo '{';
echo '"metric": {';
echo '    "__name__": "null",';
echo '"instance": "null",';
echo '"job": "null"';
echo '},';
echo '"value": [';
echo '    "null",';
echo '    "'.$total.'"';
echo ']';
echo '}';
echo ']';
echo '}';
echo '}';

?>
