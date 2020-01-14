<?php

class pluginShowNodePiLab extends Plugin
{

    public function init()
    {
        // JSON database
        $jsondb = json_encode(array(
            'Node 1' => '127.0.0.1',
        ));

        // Fields and default values for the database of this plugin
        $this->dbFields = array(
            'devport' => '8000',
            'devnode' => 'Dev Node',
            'jsondb' => $jsondb
        );

        // Disable default Save and Cancel button
        $this->formButtons = false;
    }

    // Method called when a POST request is sent
    public function post()
    {
        // Get current jsondb value from database
        // All data stored in the database is html encoded
        $jsondb = $this->db['jsondb'];
        $jsondb = Sanitize::htmlDecode($jsondb);

        // Convert JSON to Array
        $nodes = json_decode($jsondb, true);

        // Check if the user click on the button delete or add
        if (isset($_POST['deleteNode'])) {
            // Values from $_POST
            $name = $_POST['deleteNode'];

            // Delete the node from the array
            unset($nodes[$name]);
        } elseif (isset($_POST['addNode'])) {
            // Values from $_POST
            $name = $_POST['nodeName'];
            $ip = $_POST['nodeIP'];

            // Check empty string
            if (empty($name)) {
                return false;
            }

            // Add the node
            $nodes[$name] = $ip;
        }

        // Encode html to store the values on the database
        $this->db['devport'] = Sanitize::html($_POST['devport']);
        $this->db['devnode'] = Sanitize::html($_POST['devnode']);
        $this->db['jsondb'] = Sanitize::html(json_encode($nodes));

        // Save the database
        return $this->save();
    }

    // Method called on plugin settings on the admin area
    public function form()
    {
        global $L;
        global $site;

        $html = '<div class="alert alert-primary" role="alert">';
        $html .= $this->description();
        $html .= '</div>';

        $html .= '<div class="alert alert-secondary" role="alert">';
        $html .= '<h4 class="mt-3">' . $L->get('Add a dev port') . '</h4>';

        $html .= '<div>';
        $html .= '<input name="devport" class="form-control" type="number" value="' . $this->getValue('devport') . '">';
        $html .= '<span style="color: #303030; font-style: italic;">' . $L->get('dev port tip') . '</span>';
        $html .= '</div>';

        $html .= '<h4 class="mt-3">' . $L->get('add a dev name') . '</h4>';

        $html .= '<div>';
        $html .= '<input name="devnode" class="form-control" type="text" value="' . $this->getValue('devnode') . '">';
        $html .= '<span style="color: #303030; font-style: italic;">' . $L->get('dev name tip') . ' This will also link to ' .  $site->url(). '/admin/</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<button name="save" class="btn btn-primary my-2" type="submit">' . $L->get('Save') . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<hr>';

        $html .= '<div class="alert alert-secondary" role="alert">';

        // New node, when the user click on save button this call the method post()
        // and the new node is added to the database
        $html .= '<h4 class="mt-3">' . $L->get('add a new node') . '</h4>';

        $html .= '<div>';
        $html .= '<input name="nodeName" type="text" class="form-control" value="" placeholder="Node X">';
        $html .= '<span style="color: #303030; font-style: italic;">' . $L->get('add node tip') . '</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<div>&nbsp;</div>';
        $html .= '<input name="nodeIP" type="text" class="form-control" value="" placeholder="192.168.x.x">';
        $html .= '<span style="color: #303030; font-style: italic;">' . $L->get('ip tip') . '</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<button name="addNode" class="btn btn-primary my-2" type="submit">' . $L->get('Add') . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<hr>';

        // Get the JSON DB, getValue() with the option unsanitized HTML code
        $jsondb = $this->getValue('jsondb', $unsanitized = false);
        $nodes = json_decode($jsondb, true);

        $html .= !empty($nodes) ? '<div class="alert alert-secondary" role="alert"><h2 class="mt-3">' . $L->get('Nodes') . '</h2>' : '';

        foreach ($nodes as $name => $ip) {
            $html .= '<div class="my-2">';
            $html .= '<b>' . $name . '</b>: ' . $ip;
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<button name="deleteNode" class="btn btn-secondary my-2" type="submit" value="' . $name . '">' . $L->get('Delete') . '</button>';
            $html .= '</div>';
            $html .= '<hr>';
        }

        $html .= !empty($nodes) ? '</div>' : '';

        return $html;
    }

    // Method called on the siteSidebar of the website
    // Customized for Pi Lab
    public function siteFooter()
    {
        global $site;

        // Get the JSON DB, getValue() with the option unsanitized HTML code
        $jsondb = $this->getValue('jsondb', false);
        $nodes = json_decode($jsondb);

        if(empty($nodes)) {
            if ($this->getValue('devport') == '' || $this->getValue('devnode') == '') {
                $html = "No Nodes Specified - Check Show Node Settings!";
            }
            else {
                $html = '<a href="'. $site->url() . '/admin/" target="_blank">' . $this->getValue('devnode') . '</a>';
            }
        }
        else {
            foreach ($nodes as $name => $ip) {
                if ($_SERVER['SERVER_PORT'] == $this->getValue('devport')) {
                    $html = '<a href="'. $site->url() . '/admin/" target="_blank">' . $this->getValue('devnode') . '</a>';
                }
                elseif ($_SERVER['SERVER_ADDR'] == $ip) {
                    if (strpos($site->url(), 'pilab.dev') !== false) {
                        $html = '<a href="'. $site->url() . '/' . str_replace(' ', '-', $name) .'/" target="_blank" title="View '. $name . ' Dashboard">' . $name . '</a>';
                    }
                    else {
                        $html = $name;
                    }
                }
            }
        }

        return $html;
    }
}
