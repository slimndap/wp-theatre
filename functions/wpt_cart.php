<?php
/**
 * WPT_Cart class.
 * @internal
 */
class WPT_Cart {
	function __construct() {
		add_action('init',array($this,'init'));
	}
	
	function init() {
	    if( !session_id() ) {
	        session_start();				    
	    }
		if (isset($_SESSION['wpt_cart'])) {
			$this->items = $_SESSION['wpt_cart']['items'];
			$this->amount = $_SESSION['wpt_cart']['amount'];
		}
	}
	
	function save() {
		$_SESSION['wpt_cart'] = array(
			'items' => $this->items,
			'amount' => $this->amount
		);	
	}
	
	function reset() {
		$this->items = array();
		$this->amount = 0;
	}
	
	function is_empty() {
		return empty($this->items);
	}
	
	function add_item($production) {
		// check if production is already present in cart
		for ($i=0;$i<count($this->items);$i++) {
			if ($this->items[$i]['production']->ID==$production->ID) {
				$this->items[$i]['amount']++;
				return;
			}
		}
		$this->items[] = array(
			'amount'=>1,
			'production'=>$production
		);
	}
	
	function render() {
		$html = '';
		if (!$this->is_empty()) {
			foreach($this->items as $item) {
				$html.= '<div class="wpt_cart_item">';
				$html.= '<div class="wpt_cart_item_amount">'.$item['amount'].'</div>';
				$html.= $item['production']->html();
				$html.= '</div>'; // .wpt_cart_item				
			}
		}
		$html = apply_filters('wpt_cart', $html);

		return $html;
	}

}

?>