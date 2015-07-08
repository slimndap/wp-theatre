<?php
	
class WPT_Template_Placeholder {
	
	protected $placeholder;
	protected $replacement = '';
	
	public $field;
	public $field_args = array();
	public $filters = array();
	
	function __construct($placeholder, $object) {
		$this->placeholder = $placeholder;
		$this->object = $object;

		$this->field = $this->get_field();
		$this->field_args = $this->get_arguments($placeholder);
		$this->filters = $this->get_filters();
	}
	
	protected function get_field() {
		$placeholder_parts = $this->get_parts();

		if (empty($placeholder_parts[0])) {
			return false;	
		}
		
		return $this->get_function($placeholder_parts[0]);
	}
	
	protected function get_template_filter($filter_name) {
		$template_filters = $this->get_template_filters();

		foreach($this->get_template_filters() as $filter) {
			if ( $filter['name']==$filter_name) {
				return $filter;
			}
		}
		
		return false;
		
	}
	
	protected function get_field_args() {
		$placeholder_parts = $this->get_parts();

		if (empty($placeholder_parts[0])) {
			return false;	
		}
		
		return $this->get_arguments($placeholder_parts[0]);		
	}
	
	protected function is_valid_filter($filter_name) {
		$template_filters = $this->get_template_filters();

		foreach($template_filters as $filter) {
			if ( $filter['name']==$filter_name) {
				return true;
			}
		}
		
		return false;
	}
	
	protected function get_filters() {
		$placeholder_parts = $this->get_parts();

		$template_filters = array();

		$filters = array();
		if (!empty($placeholder_parts[1])) {
			$filters = $placeholder_parts;
			array_shift($filters);
		}
		
		foreach($filters as $filter) {
			$template_filters[] = new WPT_Template_Filter($this->get_function($filter), $this->get_arguments($filter), $this->object);
		}
		
		return $template_filters;
		
	}
	
	public function replace_in($html) {
		return str_replace('{{'.$this->placeholder.'}}', $this->replacement, $html);
	}
	
	public function set_replacement($replacement) {
		$this->replacement = $replacement;
	}
	
	private function get_parts() {
		$placeholder_parts = explode('|',$this->placeholder);
		return $placeholder_parts;
		if (!empty($placeholder_parts[0])) {
			$field = $placeholder_parts[0];
		}
		if (!empty($placeholder_parts[1])) {
			$filters = $placeholder_parts;
			array_shift($filters);
		}		
	}
	
	private function get_arguments($filter) {
		$arguments = array();
	
		$brackets_open = strpos($filter, '(');
		$brackets_close = strpos($filter, ')');
		if (
			$brackets_open !== false && 
			$brackets_close !== false &&
			$brackets_open < $brackets_close
		) {
			$arguments = explode(',',substr($filter, $brackets_open + 1, $brackets_close - $brackets_open -1));
			$arguments = $this->sanitize_arguments($arguments);
		}
		return $arguments;
	}

	private function get_function($filter) {
		$brackets_open = strpos($filter, '(');
		$brackets_close = strpos($filter, ')');
		if (
			$brackets_open !== false && 
			$brackets_close !== false &&
			$brackets_open < $brackets_close
		) {
			return trim(substr($filter, 0, $brackets_open));
		} else {
			return trim($filter);
		}
	}
		
	/*
	 * Sanitize arguments.
	 * Removed surrounding quotes.
	 * @param $arguments array 
	 */
	private function sanitize_arguments($arguments) {
		if (!empty($arguments) && is_array($arguments)) {
			for ($i=0;$i<count($arguments);$i++) {
				$arguments[$i] = trim($arguments[$i],'"');
				$arguments[$i] = trim($arguments[$i],"'");
			}
		}
		return $arguments;
	}
}