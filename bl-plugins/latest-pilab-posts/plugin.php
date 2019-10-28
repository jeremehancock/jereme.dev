<?php

class LatestPiLabPosts extends Plugin
{


    public function siteSidebar()
    {
		$html = '<h2 class="plugin-label">Latest Posts</h2>';

        $html .= '<div class="grid-container" style="display: grid; grid-template-columns: auto auto auto; padding: 2px; position: relative; left: -15px; top: -10px; margin-bottom: -15px;">';
        
        $API_KEY = "f77c596ec98f9bc1b764e5e30a86486f";

        $json = file_get_contents("https://pilab.dev/api/pages?token=$API_KEY");

        $json = json_decode($json, true);
        
        foreach ($json as $item) {
        // This allows you to limit the results. This example shows the first 9 pages in the array
        $a = array_slice($item, 0, 6);
        foreach ($a as $val) {
            if ($val{"type"} == "published") {
                $html .= "<div class='grid-item' style='padding: 12px; font-size: 30px; text-align: center;'>" . "<a href=" . $val{"permalink"} . " target='_top'><img src='" . $val{"coverImage"} . "' title='" . $val{"title"} . "' style='width: 100%; border-radius: .5em; border: 5px solid rgb(242, 242, 242); box-shadow: rgba(0, 0, 0, 0.25) 3px 4px 5px;'/></a>" . "</div>";
            }
        }
    }
        
        $html .= '</div>';

        return $html;
    }
}
