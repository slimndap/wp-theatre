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
		$filters = wp_parse_args( $filters, $this->defaults() );
		return $this->load($filters);				
	}
	
	public function html($args=array()) {
		return '';
	}

	function load() {
		return array();
	}
}
?>