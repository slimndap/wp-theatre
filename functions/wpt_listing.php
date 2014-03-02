<?php
class WPT_Listing {
	public function __toString() {
		return $this->html();
	}
	
	public function __invoke($filters=array()) {
		return $this->get($filters);
	}

	function defaults() {
		return array();
	}

	function get($filters=array()) {
		$hash = md5(serialize($filters));
		if (empty($this->listing[$hash])) {
			$this->listing[$hash] = $this->load($filters);
		}
		return $this->listing[$hash];				
	}
	
	public function html($args=array()) {
		return '';
	}

	function load() {
		return array();
	}
	
}
?>