<!DOCTYPE html>
<html>
    <head>
        <?php

        require_once("../include/config.php");

        $id = $_GET["id"];

        $image_only = $_GET["image-only"];

        if (isset($id)) {
            $api = file_get_contents_curl("https://pilab.dev/zero-tv/api/v1?id=$id");
        }
        else {
            $api = file_get_contents_curl("https://pilab.dev/zero-tv/api/v1?id=24-7-retro");
        }

        $response = json_decode($api);

        $channel = $response->channel;
        $channel_id = $response->channel_id;
        $url = $response->url;
        $poster = $response->poster;
        $stream = $response->stream;

        if (isset($image_only)) {
            header("Location: $response->poster");
        }

        ?>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $channel; ?></title>
        <link href="https://unpkg.com/video.js/dist/video-js.css" rel="stylesheet">
        <link href="../assets/css/player.css" rel="stylesheet">
        <script src="https://unpkg.com/video.js/dist/video.js"></script>
        <script src="https://unpkg.com/videojs-contrib-hls/dist/videojs-contrib-hls.js"></script>
    </head>
    <body>
        <div class="video-js-responsive-container vjs-hd">
            <video id="<?php echo $channel_id; ?>" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="auto" width="<?php echo $player_width; ?>" height="<?php echo $player_height; ?>" poster="<?php echo $poster; ?>" data-setup='{"autoplay": <?php echo $autoplay; ?>}'>
                <source src="<?php echo $stream; ?>" type="application/x-mpegURL">
            </video>
        </div>
    </body>
</html>
