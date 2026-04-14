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

        $this->customHooks = array(
            'siteShowNode'
        );
    }

    // Method called when a POST request is sent
    public function post()
    {
        // Get current jsondb value from database (stored html-encoded)
        $jsondb = Sanitize::htmlDecode($this->db['jsondb']);

        // Convert JSON to array, ensure it's always an array
        $nodes = json_decode($jsondb, true);
        if (!is_array($nodes)) {
            $nodes = array();
        }

        // Check if the user clicked delete or add
        if (isset($_POST['deleteNode'])) {
            $name = $_POST['deleteNode'];
            unset($nodes[$name]);
        } elseif (isset($_POST['addNode'])) {
            $name = isset($_POST['nodeName']) ? trim($_POST['nodeName']) : '';
            $ip   = isset($_POST['nodeIP'])   ? trim($_POST['nodeIP'])   : '';

            if ($name === '') {
                return false;
            }

            $nodes[$name] = $ip;
        }

        // Only overwrite devport/devnode if they were actually submitted
        // (Add/Delete buttons don't submit those fields)
        if (isset($_POST['devport'])) {
            $this->db['devport'] = Sanitize::html($_POST['devport']);
        }
        if (isset($_POST['devnode'])) {
            $this->db['devnode'] = Sanitize::html($_POST['devnode']);
        }

        $this->db['jsondb'] = Sanitize::html(json_encode($nodes));

        return $this->save();
    }

    // Method called on plugin settings in the admin area
    public function form()
    {
        global $L;
        global $site;

        $html  = '<div class="alert alert-primary" role="alert">';
        $html .= $this->description();
        $html .= '</div>';

        $html .= '<div class="alert alert-secondary" role="alert">';
        $html .= '<h4 class="mt-3">' . $L->get('Add a dev port') . '</h4>';

        $html .= '<div>';
        $html .= '<input name="devport" class="form-control" type="number" value="' . $this->getValue('devport') . '" required>';
        $html .= '<span style="color: #303030; font-style: italic;">' . $L->get('dev port tip') . '</span>';
        $html .= '</div>';

        $html .= '<h4 class="mt-3">' . $L->get('add a dev name') . '</h4>';

        $html .= '<div>';
        $html .= '<input name="devnode" class="form-control" type="text" value="' . $this->getValue('devnode') . '" required>';
        $html .= '<span style="color: #303030; font-style: italic;">' . $L->get('dev name tip') . ' This will also link to ' . $site->url() . '/admin/</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<button name="save" class="btn btn-primary my-2" type="submit">' . $L->get('Save') . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<hr>';

        $html .= '<div class="alert alert-secondary" role="alert">';
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

        // Get the JSON DB unsanitized so json_decode works on raw JSON
        $jsondb = $this->getValue('jsondb', false);
        $nodes  = json_decode($jsondb, true);
        if (!is_array($nodes)) {
            $nodes = array();
        }

        if (!empty($nodes)) {
            $html .= '<div class="alert alert-secondary" role="alert"><h2 class="mt-3">' . $L->get('Nodes') . '</h2>';

            foreach ($nodes as $name => $ip) {
                $html .= '<div class="my-2">';
                $html .= '<b>' . htmlspecialchars($name) . '</b>: ' . htmlspecialchars($ip);
                $html .= '</div>';
                $html .= '<div>';
                $html .= '<button name="deleteNode" class="btn btn-secondary my-2" type="submit" value="' . htmlspecialchars($name) . '">' . $L->get('Delete') . '</button>';
                $html .= '</div>';
                $html .= '<hr>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    // Custom hook called from the theme
    public function siteShowNode()
    {
        global $site;

        // Always initialize — prevents "undefined variable" warnings in PHP 8
        $html = '';

        // Decode as associative array (the original decoded as object, which
        // still iterates but is inconsistent with form()/post())
        $jsondb = $this->getValue('jsondb', false);
        $nodes  = json_decode($jsondb, true);

        if (empty($nodes) || !is_array($nodes)) {
            return 'No Nodes Specified: Check Show Node Settings!';
        }

        $serverPort = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '';
        $serverAddr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';

        // Dev port takes priority — if we're on it, show the dev link and stop
        if ($serverPort == $this->getValue('devport')) {
            return '<a href="' . $site->url() . '/admin/" target="_blank">' . $this->getValue('devnode') . '</a>';
        }

        // Otherwise find the node matching this server's IP.
        // Use break so the first match wins instead of the last.
        foreach ($nodes as $name => $ip) {
            if ($serverAddr == $ip) {
                if (strpos($site->url(), 'pilab.dev') !== false) {
                    $html = '<a href="' . $site->url() . '/' . str_replace(' ', '-', $name) . '/" target="_blank" title="View ' . $name . ' Dashboard">' . $name . '</a>';
                } else {
                    $html = $name;
                }
                break;
            }
        }

        return $html;
    }
}
