<?php
	
class WPT_Template {
	
	protected $template;	
	protected $filters = array();

	public $placeholders = array();
	
	function __construct($template, $object) {
		$this->template = $template;
		$this->object = $object;

		$this->init_placeholders();
	}
	
	protected function init_placeholders() {
		$placeholders_extracted = array();
		preg_match_all('~{{(.*?)}}~', $this->template, $placeholders_extracted);
		
		foreach($placeholders_extracted[1] as $placeholder) {			
			$this->placeholders[] = new WPT_Template_Placeholder($placeholder, $this->object);
		}
	}
	
	public function get_html() {
		$html = $this->template;
		foreach($this->placeholders as $placeholder) {			
			$html = $placeholder->replace_in($html);
		}
		return $html;
	}
	
}