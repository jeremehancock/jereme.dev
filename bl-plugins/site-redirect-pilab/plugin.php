<?php

class pluginSiteRedirect extends Plugin {

	public function init()
	{
		$this->dbFields = array(
			'enable'=>false,
            'port'=>'8000',
			'url'=>'https://randomgoat.com'
		);
	}

	public function form()
	{
		global $L;

		$html  = '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('enable site redirect').'</label>';
		$html .= '<select name="enable">';
		$html .= '<option value="true" '.($this->getValue('enable')===true?'selected':'').'>Enabled</option>';
		$html .= '<option value="false" '.($this->getValue('enable')===false?'selected':'').'>Disabled</option>';
		$html .= '</select>';
		$html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('port').'</label>';
        $html .= '<input class="form-control" name="port" id="jsport" type="number" value="'.$this->getValue('port').'">';
        $html .= '<span style="color: #303030; font-style: italic;">' . $L->get('port tip') . '</span>';
        $html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('url').'</label>';
		$html .= '<input class="form-control" name="url" id="jsurl" type="url" value="'.$this->getValue('url').'" required>';
		$html .= '</div>';

		return $html;
	}

	public function beforeAll()
	{
		if ($this->getValue('enable')) {
		    if ($this->getValue('port') == '') {
                header('Location:' . $this->getValue('url') . $_SERVER['REQUEST_URI']);
                die();
            }
            elseif ($this->getValue('port') != '' && $this->getValue('url') != '' && $_SERVER['SERVER_PORT'] == $this->getValue('port')) {
                header('Location:' . $this->getValue('url') . $_SERVER['REQUEST_URI']);
                die();
            }
		}
	}
}