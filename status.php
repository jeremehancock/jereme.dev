<html>
<head>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700|Roboto:400,400i,700" rel="stylesheet" />

    <style>
        html {
            background-color: #f2f2f2;
        }
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
    $ip = $_SERVER['SERVER_ADDR'];

    $uptime = exec("uptime -p | sed 's/\up//g'");
    
    $hostname = gethostname();

    $node = substr($hostname, -1);

    echo "<b>Node: $node</b>";
    
    echo "</br>";

    echo "<b>IP:</b> " . $ip;

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

