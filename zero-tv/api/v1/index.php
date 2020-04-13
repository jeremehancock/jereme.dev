<?php

error_reporting(E_ERROR);

header('Content-Type: application/json');

require_once("include/config.php");

$id = $_GET["id"];

if (isset($id)) {

    if ($id == "24-7-retro") {
        $channel = "24/7 Retro";
        $channel_id = $id;
        $url = "https://www.247retro.com/";
        $poster = "https://pilab.dev/zero-tv/api/v1/assets/posters/$channel_id.png";
        $source = file_get_contents_curl($url);
        $stream = str_replace("http", "https", extract_unit($source, "src:  \"", "\""));
    }

    else if ($id == "american-classics") {
        $channel = "American Classics";
        $channel_id = $id;
        $slug = "american-classics-07-18-2019-202011476";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "the-asylum") {
        $channel = "The Asylum";
        $channel_id = $id;
        $slug = "externallinearfeed-11-06-2019-184209249-11-06-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "britcom") {
        $channel = "BRITCOM";
        $channel_id = $id;
        $slug = "britcom-11-06-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "buzzr") {
        $channel = "Buzzr";
        $channel_id = $id;
        $slug = "buzzr-wurl-external-08-27-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "charge") {
        $channel = "Charge!";
        $channel_id = $id;
        $slug = "externallinearfeed-01-15-2020-201540715-01-15-2020";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "cheddar-news") {
        $channel = "Cheddar News";
        $channel_id = $id;
        $slug = "cheddar-wurl-external-10-31-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "cinelife") {
        $channel = "CineLife";
        $channel_id = $id;
        $slug = "externallinearfeed-01-27-2020-01-27-2020";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "comedy-dynamics") {
        $channel = "Comedy Dynamics";
        $channel_id = $id;
        $slug = "externallinearfeed-08-01-2019-08-01-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "comet") {
        $channel = "Comet";
        $channel_id = $id;
        $slug = "externallinearfeed-05-21-2019-05-21-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "con-tv") {
        $channel = "CON TV";
        $channel_id = $id;
        $slug = "contv-wurl-external-12-03-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "dove") {
        $channel = "Dove";
        $channel_id = $id;
        $slug = "dove-wurl-external-12-03-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "dust") {
        $channel = "Dust";
        $channel_id = $id;
        $slug = "dust-wurl-external-11-07-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "electric-now") {
        $channel = "Electric Channel";
        $channel_id = $id;
        $slug = "externallinearfeed-10-21-2019-10-21-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "the-film-detective") {
        $channel = "The Film Detective";
        $channel_id = $id;
        $slug = "externallinearfeed-07-18-2019-07-18-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "filmrise-free-movies") {
        $channel = "Filmrise Free Movies";
        $channel_id = $id;
        $slug = "filmrise-free-movies-09-27-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "filmrise-classic-tv") {
        $channel = "Filmrise Classic TV";
        $channel_id = $id;
        $slug = "filmrise-classic-tv-09-27-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "forensic-files") {
        $channel = "Forensic Files";
        $channel_id = $id;
        $slug = "forensics-files-10-04-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "futurism") {
        $channel = "Futurism";
        $channel_id = $id;
        $slug = "futurism-wurl-external-12-21-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "gravitas-movies") {
        $channel = "Gravitas Movies";
        $channel_id = $id;
        $slug = "gravitas-wurl-external-12-03-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "law-crime") {
        $channel = "Law & Crime";
        $channel_id = $id;
        $slug = "externallinearfeed-03-22-2019-03-22-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "moviemix") {
        $channel = "Moviemix";
        $channel_id = $id;
        $slug = "movie-mix-wurl-external-12-03-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "mst3k") {
        $channel = "MST3K";
        $channel_id = $id;
        $slug = "externallinearfeed-03-20-2020-03-20-2020";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "nasa") {
        $channel = "NASA";
        $channel_id = $id;
        $slug = "nasatv-gracenote-external-01-09-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "newsy") {
        $channel = "Newsy";
        $channel_id = $id;
        $slug = "externallinearfeed-09-27-2019-204550290-09-27-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "people-tv") {
        $channel = "People TV";
        $channel_id = $id;
        $slug = "externallinearfeed-09-26-2019-09-26-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "popstar-tv") {
        $channel = "Popstar! TV";
        $channel_id = $id;
        $slug = "externallinearfeed-02-13-2020-02-13-2020";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "revry") {
        $channel = "Revry";
        $channel_id = $id;
        $slug = "externallinearfeed-02-04-2020-02-04-2020";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "shout-factory-tv") {
        $channel = "Shout Factory TV";
        $channel_id = $id;
        $slug = "externallinearfeed-03-08-2019-03-08-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "space-1999") {
        $channel = "Space 1999";
        $channel_id = $id;
        $slug = "space-1999-11-21-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
     else if ($id == "stirr-black-cinema") {
        $channel = "Stirr Black Cinema";
        $channel_id = $id;
        $slug = "stirr-black-cinema-09-30-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
 
     else if ($id == "stirr-comedy") {
        $channel = "Stirr Comedy";
        $channel_id = $id;
        $slug = "stirr-comedy";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "stirr-movies") {
        $channel = "Stirr Movies";
        $channel_id = $id;
        $slug = "stirr-movies-wurl-external-10-06-2018";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "stirr-travel") {
        $channel = "Stirr Travel";
        $channel_id = $id;
        $slug = "stirr-travel-10-10-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else if ($id == "stirr-tv-mix") {
        $channel = "Stirr TV Mix";
        $channel_id = $id;
        $slug = "stirr-retro-04-26-2019";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }
    
    else if ($id == "stirr-westerns") {
        $channel = "Stirr Westerns";
        $channel_id = $id;
        $slug = "stirr-westerns";
        $url = "https://stirr.com/watchnow/$slug";
        $json = stirr_json("$slug");
        $poster = $json["rss"]["channel"]["item"]["media:content"]["media:thumbnail"][3]["url"];
        $stream = $json["rss"]["channel"]["item"]["media:content"]["url"];
    }

    else {
        $channel = "24/7 Retro";
        $channel_id = "24-7-retro";
        $url = "https://www.247retro.com/";
        $poster = "https://pilab.dev/zero-tv/api/v1/posters/$channel_id.png";
        $source = file_get_contents_curl($url);
        $stream = str_replace("http", "https", extract_unit($source, "src:  \"", "\""));
    }

    echo '{"channel":"'.$channel.'", "channel_id":"'.$channel_id.'", "url":"'.$url.'", "poster":"'.$poster.'", "stream":"'.$stream.'"}';

}

else {
    $channels = array("24-7-retro", "american-classics", "the-asylum", "britcom", "buzzr", "charge", "cheddar-news",
        "cinelife", "comedy-dynamics", "comet", "con-tv", "dove", "dust", "electric-now", "the-film-detective",
        "filmrise-free-movies", "filmrise-classic-tv", "forensic-files", "futurism", "gravatas-movies", "law-crime",
        "moviemix", "mst3k", "nasa", "newsy", "people-tv", "popstar-tv", "revry", "shout-factory-tv", "space-1999",
        "stirr-black-cinema", "stirr-comedy", "stirr-movies", "stirr-travel", "stirr-tv-mix", "stirr-westers");

    echo '{"available_ids": [';
    foreach ($channels as $value) {
        $items .= '{"Channel": "'.$value.'", "API-URL": "https://pilab.dev/'.$_SERVER["REQUEST_URI"].'?id='.$value.'"
        , "Player-URL": "https://pilab.dev/'.$_SERVER["REQUEST_URI"].'player?id='.$value.'"
        , "Poster-URL": "https://pilab.dev/'.$_SERVER["REQUEST_URI"].'player?id='.$value.'&image-only"},';
    }

    echo substr_replace($items, "", -1);
    echo ']}';
}

?>
