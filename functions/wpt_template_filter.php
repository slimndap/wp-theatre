<?php
	
class WPT_Template_Filter {
	
	public $name;
	protected $args;
	protected $object;
	protected $callback;
	
	function __construct($name, $args, $object) {
		$this->name = $name;
		$this->args = $args;
		$this->object = $object;
		$this->callback = $this->get_callback($name);
	}
	
	public function apply_to($content) {
		array_unshift($this->args, $content, $this->object);
		$content = call_user_func_array( $this->callback, $this->args );
		return $content;
	}
	
	protected function callback_default($value) {
		return $value;
	}
	
	protected function callback_date($content, $object, $format='') {
		if (!empty($format)) {	
			if (is_numeric($content)) {
				$timestamp = $content;
			} else {
				$timestamp = strtotime($content);								
			}
			$content = date_i18n(
				$format,
				$timestamp + get_option('gmt_offset') * HOUR_IN_SECONDS
			);
		}
		return $content;		
	}
	
	protected function callback_tickets_url($content) {
		if (!empty($content)) {
			$tickets_url_args = array(
				'html'=>true,
				'text'=> $content
			);
			if (method_exists($object, 'tickets_url')) {
				$tickets_url_content = $object->tickets_url($tickets_url_args);	
				if (!empty($tickets_url_content)) {
					$content = $tickets_url_content;
				}
			}
		}
		return $content;					
	}
	
	protected function callback_permalink($content, $object) {
		if (!empty($content)) {
			$permalink_args = array(
				'html'=>true,
				'text'=> $content,
				'inside'=>true
			);
			if (method_exists($object, 'permalink')) {
				$content = $object->permalink($permalink_args);
			}
		}
		return $content;		
	}
	
	protected function callback_wpautop($content) {
		return wpautop($content);		
	}

	protected function get_callback($name) {
		$callbacks = $this->get_callbacks();

		if (!empty($callbacks[$name])) {
			$callback = $callbacks[$name];
		} else {
			$callback = array($this, 'callback_default');
		}
		
		return $callback;
	}
	
	protected function get_callbacks() {
		$callbacks = array(
			'date' => array($this, 'callback_date'),
			'permalink' => array($this, 'callback_permalink'),
			'wpautop' => array($this, 'callback_wpautop'),
			'tickets_url' => array($this, 'callback_tickets_url'),				
		);
		
		$callbacks = apply_filters('wpt/template/filters/callbacks',$callbacks);
		
		return $callbacks;
	}
	
}