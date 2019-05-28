<html>
<head>
    <meta http-equiv="refresh" content="5">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700|Roboto:400,400i,700" rel="stylesheet" />

    <style>
        .content {
            font-size: 2em;
            font-family: Lora;
            color: #303030;
        }

        .reload {
            font-size: .7em !important;
            font-style: italic;
        }

        @media only screen and (max-device-width : 1005px) {
            .content {
                font-size: 1.5em;
                font-family: Lora;
                color: #303030;
            }
        }
    </style>
<body>
<div class="content">

<script>
    $(window).ready( function() {

        var time = 5;

        setInterval( function() {

            time--;

            $('#time').html(time);

            if (time === 0) {

                location.reload()
            }


        }, 1000 );

    });
</script>

<?php
    $server = $_SERVER['SERVER_ADDR'];

    $uptime = exec("uptime -p");

    if ($server == '192.168.86.140') {
        if ($_SERVER['HTTP_HOST'] == "velvethotdog.com:8080") {
            echo "<b>Node:</b> 1 (Dev Mode)";
        } else {
            echo "<b>Node:</b> 1";
        }

    } else if ($server == '192.168.86.141') {
        echo "<b>Node:</b> 2";
    } else if ($server == '192.168.86.142') {
        echo "<b>Node:</b> 3";
    }
    echo "</br>";

    echo "<b>IP:</b> " . $server;

    echo "</br>";

    echo "<b>Uptime:</b> $uptime";

    echo "</br>";

    echo exec('/home/pi/scripts/status/temp');
?>

</br>

<span class="reload">Reload in: </span><span class="reload" id="time">5</span><span class="reload"> seconds</span>

</div>
</body>
</html>

