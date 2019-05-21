<?php
class pluginNode extends Plugin {
	public function siteBodyEnd() {
        /** Server Check */
        $server = $_SERVER['SERVER_ADDR'];

        if ($server == '192.168.86.140') {
            if ($_SERVER['HTTP_HOST'] == "velvethotdog.com:8080") {
                echo "Dev Mode";
            }
            else {
                echo "Node 1";
            }

        }

        else if ($server == '192.168.86.141') {
            echo "Node 2";
        }

        else if ($server == '192.168.86.142') {
            echo "Node 3";
        }
	}
}
?>