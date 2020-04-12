<?php

class pluginInstagramFeedJelly extends Plugin {

	public function init()
	{
		$this->dbFields = array(
			'title'=>'',
			'description'=>'',
			'follow_text'=>'',
			'follow_url'=>'',
			'instagram_user_id'=>'',
			'instagram_access_token'=>'',
			'section_visible'=>''
		);
	}

	public function form()
	{
		global $L;

		$html  = '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';
		
		if ($this->getValue('section_visible') == 'on')
			$section_visible = 'checked';
		else
			$section_visible = '';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('Section Visible').'</label>';
		$html .= '<input name="section_visible" type="hidden" value="false">';
		$html .= '<input name="section_visible" type="checkbox" value="on" '. $section_visible .'>';
		$html .= '<span class="tip">'.$L->get('Check to display instagram feed on home page.').'</span>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('Section Title').'</label>';
		$html .= '<input name="title" type="text" value="'.$this->getValue('title').'">';
		$html .= '<span class="tip">'.$L->get('This title will be displayed beside the feed.').'</span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('Section Description').'</label>';
		$html .= '<input name="description" type="text" value="'.$this->getValue('description').'">';
		$html .= '<span class="tip">'.$L->get('This description will be displayed beside the feed.').'</span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('Follow Me Text').'</label>';
		$html .= '<input name="follow_text" type="text" value="'.$this->getValue('follow_text').'">';
		$html .= '<span class="tip">'.$L->get('You can set a link to your instagram, or any other website you wish.').'</span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('Follow Me Url').'</label>';
		$html .= '<input name="follow_url" type="text" value="'.$this->getValue('follow_url').'">';
		$html .= '<span class="tip">'.$L->get('Url to which "Follow Me Text" will redirect to.').'</span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('Instagram User ID').'</label>';
		$html .= '<input name="instagram_user_id" type="text" value="'.$this->getValue('instagram_user_id').'">';
		$html .= '<span class="tip">'.$L->get('User ID of the feed to be displayed. Obtain it from here: https://codeofaninja.com/tools/find-instagram-user-id').'</span>';
		$html .= '</div>';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('Instagram Access Token').'</label>';
		$html .= '<input name="instagram_access_token" type="text" value="'.$this->getValue('instagram_access_token').'">';
		$html .= '<span class="tip">'.$L->get('Your developer Access Token. Create Instagram Developer account, create application and obtain access token from here: https://instagram.pixelunion.net/').'</span>';
		$html .= '</div>';

		return $html;
	}
	
	public function isSectionVisible()
	{
		return $this->getValue('section_visible');
	}
	
	public function getSectionTitle()
	{
		return $this->getValue('title');
	}

	public function getSectionDescription()
	{
		return html_entity_decode(nl2br($this->getValue('description')));
	}
	
	public function getFollowMeText()
	{
		return $this->getValue('follow_text');
	}
	
	public function getFollowMeUrl()
	{
		return $this->getValue('follow_url');
	}
	
	public function getUserId()
	{
		return $this->getValue('instagram_user_id');
	}
	
	public function getAcessToken()
	{
		return $this->getValue('instagram_access_token');
	}
}