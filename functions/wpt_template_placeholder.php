<?php
	
class wpt_template_placeholder {
	
	public $placeholder;
	public $replacement = '';
	
	function __construct($placeholder) {
		$this->placeholder = $placeholder;
	}
	
	public function get_field() {
		$placeholder_parts = $this->get_parts();

		if (empty($placeholder_parts[0])) {
			return false;	
		}
		
		return $this->get_function($placeholder_parts[0]);
	}
	
	public function get_field_args() {
		$placeholder_parts = $this->get_parts();

		if (empty($placeholder_parts[0])) {
			return false;	
		}
		
		return $this->get_arguments($placeholder_parts[0]);		
	}
	
	public function get_filters() {
		$placeholder_parts = $this->get_parts();

		$filters = array();

		if (!empty($placeholder_parts[1])) {
			$filters = $placeholder_parts;
			array_shift($filters);
		}
		
		return $filters;
		
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