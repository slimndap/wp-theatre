<?php
	
class wpt_template {
	
	public $template;
	public $placeholders = array();
	
	function __construct($template) {
		$this->template = $template;

		$this->set_placeholders();
	}
	
	public function set_placeholders() {
		$placeholders_extracted = array();
		preg_match_all('~{{(.*?)}}~', $this->template, $placeholders_extracted);
		
		foreach($placeholders_extracted[1] as $placeholder) {			
			$this->placeholders[] = new WPT_Template_Placeholder($placeholder);
		}
	}
	
	public function get_html() {
		
		$html = $this->template;
		foreach($this->placeholders as $placeholder) {
			$html = str_replace('{{'.$placeholder->placeholder.'}}', $placeholder->replacement, $html);
		}
		return $html;
	}
	
}