<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>

<style>
    .reload {
        font-size: .7em !important;
        font-style: italic;
    }
</style>

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
    echo "<b>IP:</b> " . $server;

    echo "</br>";

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

    echo "<b>Uptime:</b> " . exec('uptime -p');

    echo "</br>";

    echo exec('/home/pi/scripts/status/temp');
?>

</br>

<span class="reload">Reload in: </span><span class="reload" id="time">5</span><span class="reload"> seconds</span>
