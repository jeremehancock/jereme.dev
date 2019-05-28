<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>

<script>
    $(window).ready( function() {

        var time = 5

        setInterval( function() {

            time--;

            $('#time').html(time);

            if (time === 0) {

                location.reload()
            }


        }, 1000 );

    });
</script>

<span>Reload in: </span><span id="time">5</span><span> seconds</span>
</br>
</br>
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
