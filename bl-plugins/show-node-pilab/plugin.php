<?php

class pluginShowNode extends Plugin {

	public function init()
	{
		// JSON database
		$jsondb = json_encode(array(
			'Node 1'=>'192.168.86.140',
            'Node 2'=>'192.168.86.141',
		));

		// Fields and default values for the database of this plugin
		$this->dbFields = array(
			'label'=>'Nodes',
			'jsondb'=>$jsondb
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
		if( isset($_POST['deleteNode']) ) {
			// Values from $_POST
			$name = $_POST['deleteNode'];

			// Delete the node from the array
			unset($nodes[$name]);
		}
		elseif( isset($_POST['addNode']) ) {
			// Values from $_POST
			$name = $_POST['nodeName'];
			$ip = $_POST['nodeIP'];

			// Check empty string
			if( empty($name) ) { return false; }

			// Add the node
			$nodes[$name] = $ip;
		}

		// Encode html to store the values on the database
		$this->db['label'] = Sanitize::html($_POST['label']);
		$this->db['jsondb'] = Sanitize::html(json_encode($nodes));

		// Save the database
		return $this->save();
	}

	// Method called on plugin settings on the admin area
	public function form()
	{
		global $L;

		$html  = '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

//        $html .= '<div>';
//        $html .= '<label>'.$L->get('Label').'</label>';
//        $html .= '<input name="label" class="form-control" type="text" value="'.$this->getValue('label').'">';
//        $html .= '<span class="tip">'.$L->get('This title is almost always used in the sidebar of the site').'</span>';
//        $html .= '</div>';
//
//        $html .= '<div>';
//        $html .= '<button name="save" class="btn btn-primary my-2" type="submit">'.$L->get('Save').'</button>';
//        $html .= '</div>';

		// New node, when the user click on save button this call the method post()
		// and the new node is added to the database
		$html .= '<h4 class="mt-3">'.$L->get('Add a new node').'</h4>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('Name').'</label>';
		$html .= '<input name="nodeName" type="text" class="form-control" value="" placeholder="Node X">';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('IP').'</label>';
		$html .= '<input name="nodeIP" type="text" class="form-control" value="" placeholder="192.168.x.x">';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<button name="addNode" class="btn btn-primary my-2" type="submit">'.$L->get('Add').'</button>';
		$html .= '</div>';

		// Get the JSON DB, getValue() with the option unsanitized HTML code
		$jsondb = $this->getValue('jsondb', $unsanitized=false);
		$nodes = json_decode($jsondb, true);

		$html .= !empty($nodes) ? '<h4 class="mt-3">'.$L->get('Nodes').'</h4>' : '';

		foreach($nodes as $name=>$ip) {
			$html .= '<div class="my-2">';
			$html .= '<label>'.$L->get('Name').'</label>';
			$html .= '<input type="text" class="form-control" value="'.$name.'" disabled>';
			$html .= '</div>';

			$html .= '<div>';
			$html .= '<label>'.$L->get('IP').'</label>';
			$html .= '<input type="text" class="form-control" value="'.$ip.'" disabled>';
			$html .= '</div>';

			$html .= '<div>';
			$html .= '<button name="deleteNode" class="btn btn-secondary my-2" type="submit" value="'.$name.'">'.$L->get('Delete').'</button>';
			$html .= '</div>';
		}

		return $html;
	}

	// Method called on the sidebar of the website
	public function siteBodyEnd()
	{
		global $L;

		// HTML for sidebar
		$html  = '<div class="plugin plugin-pages">';
//		if ($this->getValue('label')) {
//			$html .= '<h2 class="plugin-label">'.$this->getValue('label').'</h2>';
//		}
		$html .= '<div class="plugin-content">';
		$html .= '<ul>';

		// Get the JSON DB, getValue() with the option unsanitized HTML code
		$jsondb = $this->getValue('jsondb', false);
		$nodes = json_decode($jsondb);

		// By default the database of categories are alphanumeric sorted
		foreach( $nodes as $name=>$ip ) {
            if ($_SERVER['SERVER_ADDR'] == $ip) {
                $html .= $name;
            }
		}

		$html .= '</ul>';
 		$html .= '</div>';
 		$html .= '</div>';

		return $html;
	}
}
