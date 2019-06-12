<!DOCTYPE html>
<html>
<head>

    <link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700|Roboto:400,400i,700" rel="stylesheet"/>

    <style>
        .grid-container {
            display: grid;
            grid-template-columns: auto auto auto;
            background-color: rgb(55, 55, 55);
            padding: 10px;
        }

        .grid-item {
            background-color: rgb(242, 242, 242);
            border: 1px solid rgba(0, 0, 0, 0.8);
            padding: 20px;
            font-size: 30px;
            text-align: center;
        }

        .page-thumb {
            width: 100px;
            border-radius: .5em;
            border: 5px solid rgb(242, 242, 242);
            background-color: rgb(242, 242, 242);
            transition: all 0.3s ease 0s;
            box-shadow: rgba(0, 0, 0, 0.25) 3px 4px 5px;
        }

        .page-thumb:hover {
            transition: all 0.3s ease 0s;
            transform: scale(1.04);
        }

        h2 {
            font-family: "Lora", Palatino, Georgia, Times, serif;
            font-style: italic;
            font-size: 2.0em;
            margin-bottom: 5px;
        }

        .tooltip {
            position: relative;
            display: inline-block;
            border-bottom: 1px dotted black;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: rgba(0, 0, 0, 0.8);
            color: rgb(255, 255, 255);
            text-align: center;
            border-radius: 6px;
            padding: 5px 0;
            position: absolute;
            top: 110px;
            left: 17px;
            z-index: 1;
            opacity: 0;
            transition: opacity 1s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>

<h2>Latest from Pi Lab:</h2>
<div class="grid-container">


    <?php

    $API_KEY = "f77c596ec98f9bc1b764e5e30a86486f";

    $json = file_get_contents("https://pilab.dev/api/pages?token=$API_KEY");

    $json = json_decode($json, true);

    foreach ($json as $item) {
        $a = array_slice($item, 0, 9);
        foreach ($a as $val) {
            if ($val{"type"} == "published") {
                echo "<div class='tooltip'><span class='tooltiptext'>" . $val{"title"} . "</span><div class='grid-item'>" . "<a href=" . $val{"permalink"} . " target='_blank'><img src='" . $val{"coverImage"} . "' class='page-thumb'/></a>" . "</div></div>";
            }
        }
    }

    ?>

</div>
</body>
</html>
