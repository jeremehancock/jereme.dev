<?php

class pluginHighwayControl extends Plugin {

	public function init()
	{
		$this->dbFields = array(
			'categories_section_visible'=>'',
            'categories_feed_amount'=>'',
            'thumbnails_pattern'=>'',
            'footer_section_visible'=>'',
            'footer_section_title1'=>'',
            'footer_section1'=>'',
            'footer_section_title2'=>'',
            'footer_section2'=>'',
            'footer_section_title3'=>'',
            'footer_section3'=>''
		);
	}

	public function form()
	{
		global $L;

		$html  = '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Thumbnails Pattern').'</label>';
        $html .= '<input name="thumbnails_pattern" type="text" value="'.$this->getValue('thumbnails_pattern').'">';
        $html .= '<span class="tip">'.$L->get('You can specify the sizes of thumbnails on the home page. Please type in "l l m m s m l l" to specify the pattern (separate each letter with space). L is Large, M is medium and S is small. If there are more posts than in the pattern you specified, we will set the remaining thumbnails to small.').'</span>';
        $html .= '</div>';
		
		if ($this->getValue('categories_section_visible') == 'on')
			$section_visible = 'checked';
		else
			$section_visible = '';
		
		$html .= '<div>';
		$html .= '<label>'.$L->get('Categories Section Visible').'</label>';
		$html .= '<input name="categories_section_visible" type="hidden" value="false">';
		$html .= '<input name="categories_section_visible" type="checkbox" value="on" '. $section_visible .'>';
		$html .= '<span class="tip">'.$L->get('Check to display categories section on the home page.').'</span>';
		$html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Categories Feed Amount').'</label>';
        $html .= '<input name="categories_feed_amount" type="text" value="'.$this->getValue('categories_feed_amount').'">';
        $html .= '<span class="tip">'.$L->get('Each category will show up to maximum X items. Specify this number in this field.').'</span>';
        $html .= '</div>';

        if ($this->getValue('footer_section_visible') == 'on')
            $section_visible = 'checked';
        else
            $section_visible = '';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Footer Section Visible').'</label>';
        $html .= '<input name="footer_section_visible" type="hidden" value="false">';
        $html .= '<input name="footer_section_visible" type="checkbox" value="on" '. $section_visible .'>';
        $html .= '<span class="tip">'.$L->get('Check to display footer section on the home page.').'</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Footer Section Title 1').'</label>';
        $html .= '<input name="footer_section_title1" type="text" value="'.$this->getValue('footer_section_title1').'">';
        $html .= '<span class="tip">'.$L->get('Title of the first footer section. It will be displayed as side text.').'</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Footer Section 1').'</label>';
        $html .= '<textarea name="footer_section1" type="text">'.$this->getValue('footer_section1').'</textarea>';
        $html .= '<span class="tip">'.$L->get('This will display footer section. Place each new line item in &lt;li>&lt;/li> tags. You can write links for example:<br/>&lt;li>+44 435 548&lt;/li><br/>&lt;li>jellyio@gmail.com&lt;/li><br/>&lt;li>&lt;a href="#">Find a store&lt;/a>&lt;/li>').'</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Footer Section Title 2').'</label>';
        $html .= '<input name="footer_section_title2" type="text" value="'.$this->getValue('footer_section_title2').'">';
        $html .= '<span class="tip">'.$L->get('Title of the second footer section. It will be displayed as side text.').'</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Footer Section 2').'</label>';
        $html .= '<textarea name="footer_section2" type="text">'.$this->getValue('footer_section2').'</textarea>';
        $html .= '<span class="tip">'.$L->get('This will display footer section. Place each new line item in &lt;li>&lt;/li> tags. You can write links for example:<br/>&lt;li>+44 435 548&lt;/li><br/>&lt;li>jellyio@gmail.com&lt;/li><br/>&lt;li>&lt;a href="#">Find a store&lt;/a>&lt;/li>').'</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Footer Section Title 3').'</label>';
        $html .= '<input name="footer_section_title3" type="text" value="'.$this->getValue('footer_section_title3').'">';
        $html .= '<span class="tip">'.$L->get('Title of the third footer section. It will be displayed as side text.').'</span>';
        $html .= '</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('Footer Section 3').'</label>';
        $html .= '<textarea name="footer_section3" type="text">'.$this->getValue('footer_section3').'</textarea>';
        $html .= '<span class="tip">'.$L->get('This will display footer section. Place each new line item in &lt;li>&lt;/li> tags. You can write links for example:<br/>&lt;li>+44 435 548&lt;/li><br/>&lt;li>jellyio@gmail.com&lt;/li><br/>&lt;li>&lt;a href="#">Find a store&lt;/a>&lt;/li>').'</span>';
        $html .= '</div>';

		return $html;
	}
	
	public function isCategoriesSectionVisible()
	{
		return $this->getValue('categories_section_visible');
	}
	
	public function getCategoriesFeedAmount()
	{
		return $this->getValue('categories_feed_amount');
	}

	public function getThumbnailsPattern()
	{
	    $pattern = array_filter(explode(" ", $this->getValue('thumbnails_pattern')));
        return $pattern;
	}

    public function isFooterSectionVisible()
    {
        return $this->getValue('footer_section_visible');
    }

    public function getFooterSectionTitle1()
    {
        return $this->getValue('footer_section_title1');
    }

    public function getFooterSection1()
    {
        return html_entity_decode($this->getValue('footer_section1'));
        /*
        <li>+44 435 548</li>
        <li>adobexd@gmail.com</li>
        <li><a href="#">Find a store</a></li>
        */
    }

    public function getFooterSectionTitle2()
    {
        return $this->getValue('footer_section_title2');
    }

    public function getFooterSection2()
    {
        return html_entity_decode($this->getValue('footer_section2'));
    }

    public function getFooterSectionTitle3()
    {
        return $this->getValue('footer_section_title3');
    }

    public function getFooterSection3()
    {
        return html_entity_decode($this->getValue('footer_section3'));
    }

}