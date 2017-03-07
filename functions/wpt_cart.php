<?php
class WPT_Cart {
	function __construct() {
		add_action('init',array($this,'init'));
	}
	
	/**
	 * Inits the cart.
	 * 
	 * Set items and amount from the current session.
	 *
	 * @since	0.?
	 * @since	0.15.17
	 * @return 	void
	 */
	function init() {

		if ( !isset( $_COOKIE[ session_name() ] ) ) {
			return;
		}
	
		$this->session_start();
        
		if (isset($_SESSION['wpt_cart'])) {
			$this->items = $_SESSION['wpt_cart']['items'];
			$this->amount = $_SESSION['wpt_cart']['amount'];
		}
	}
	
	function save() {

		$this->session_start();

		$_SESSION['wpt_cart'] = array(
			'items' => $this->items,
			'amount' => $this->amount
		);	
		
	}
	
	function reset() {

		$this->session_start();
		
		$this->items = array();
		$this->amount = 0;
		
		//$_SESSION = array();
		
		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
		    $params = session_get_cookie_params();
		    setcookie(session_name(), '', time() - 42000,
		        $params["path"], $params["domain"],
		        $params["secure"], $params["httponly"]
		    );
		}
		
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
	
	function session_start() {
		if ( PHP_SESSION_NONE === session_status() ) {
			//session_name('Theater_Cart');
	        session_start();				    
	    }
		
	}

}

?>