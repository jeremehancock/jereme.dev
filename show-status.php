<html>
<head>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700|Roboto:400,400i,700" rel="stylesheet" />

    <style>
        .content {
            font-size: 2em;
            font-family: Lora;
            color: #303030;
        }

        .status {
            width: 100%;
        }
    </style>
<body>
<div class="content">

    <div class="status" id="status"></div>

    <script>
        function update() {
            $.ajax({
                url: "status.php", cache: false, success: function (result) {
                    $('#status').html(result);
                    setTimeout(function () {
                        update()
                    }, 5000);
                }
            });
        }

        update();
    </script>

</div>
</body>
</html>
