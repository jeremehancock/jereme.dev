<?php
    $server = $_SERVER['SERVER_ADDR'];
    echo "IP: " . $server;

    echo "</br>";

    if ($server == '192.168.86.140') {
        if ($_SERVER['HTTP_HOST'] == "velvethotdog.com:8080") {
            echo "Node: 1 (Dev Mode)";
        } else {
            echo "Node: 1";
        }

    } else if ($server == '192.168.86.141') {
        echo "Node: 2";
    } else if ($server == '192.168.86.142') {
        echo "Node: 3";
    }
    echo "</br>";

    echo "Uptime: " . exec('uptime -p');

    echo "</br>";

    echo exec('/home/pi/scripts/status/temp');
?>
