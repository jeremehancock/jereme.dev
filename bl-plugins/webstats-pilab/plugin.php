<?php

class pluginWebStatsPiLab extends Plugin {

	public function init()
	{
		$this->dbFields = array(
			'devport'=>'',
			'footer'=>'',
		);
	}

	public function form()
	{
		global $L;

		$html  = '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		$html .= '<div>';
        $html .= '<h4 class="mt-3">Dev Port:</h4>';
        $html .= '<input name="devport" class="form-control" type="number" value="' . $this->getValue('devport') . '">';
		$html .= '<span style="color: #303030; font-style: italic;">  '.$L->get('insert-dev-port').'</span>';
		$html .= '</div>';

		$html .= '<div>';
        $html .= '<h4 class="mt-3">Web Stats Code:</h4>';
		$html .= '<textarea name="footer" id="jsfooter">'.$this->getValue('footer').'</textarea>';
		$html .= '<span style="color: #303030; font-style: italic;">'.$L->get('insert-code').'</span>';
		$html .= '</div>';

		return $html;
	}

    public function siteBodyEnd()
    {
        if ($_SERVER['SERVER_PORT'] != $this->getValue('devport') || $this->getValue('devport') == '') {
            return html_entity_decode($this->getValue('footer'));
        }
        else {
            return;
        }
    }
}
