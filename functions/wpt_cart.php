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
	 * @since	0.15.22	No longer starts unnecessary sessions.
	 *					Fixes #238.
	 *
	 * @uses	WPT_Cart::session_start() to start a new session.
	 * @return 	void
	 */
	function init() {

		/**
		 * Bail if no session has started yet to avoid unnecessary session_start().
		 * See: https://github.com/slimndap/wp-theatre/issues/238
		 * See: http://stackoverflow.com/a/1783257
		 */
		if ( !isset( $_COOKIE[ session_name() ] ) ) {
			return;
		}
	
		// Start a new session.
		$this->session_start();
        
        // Import cart contents from session.
		if (isset($_SESSION['wpt_cart'])) {
			$this->items = $_SESSION['wpt_cart']['items'];
			$this->amount = $_SESSION['wpt_cart']['amount'];
		}
	}
	
	/**
	 * Saves the cart contents to the session.
	 * 
	 * @since	0.?
	 *
	 * @uses	WPT_Cart::session_start() to start a new session.
	 * @return 	void
	 */
	function save() {

		$this->session_start();

		$_SESSION['wpt_cart'] = array(
			'items' => $this->items,
			'amount' => $this->amount
		);	
		
	}
	
	
	/**
	 * Resets the cart by emptying the cart contents and destroying the session.
	 * 
	 * @since	0.?
	 *
	 * @return void
	 */
	function reset() {

		$this->session_start();
		
		// Empty the cart contents.
		$this->items = array();
		$this->amount = 0;

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
	
	
	/**
	 * Checks if the cart is emtpy.
	 * 
	 * @since	0.?
	 *
	 * @return 	bool
	 */
	function is_empty() {		
		return empty($this->items);
	}
	
	/**
	 * Adds a production item to the cart.
	 * 
	 * @since	0.?
	 *
	 * @param 	WPT_Production 	$production
	 * @return 	void
	 */
	function add_item($production) {
		
		// Update item count if production is already present in cart.
		for ($i=0;$i<count($this->items);$i++) {
			if ($this->items[$i]['production']->ID==$production->ID) {
				$this->items[$i]['amount']++;
				return;
			}
		}
		
		// Add production to cart (if not present in cart yet).
		$this->items[] = array(
			'amount'=>1,
			'production'=>$production
		);
		
	}
	
	/**
	 * Outputs the cart HTML.
	 * 
	 * @since	0.?
	 *
	 * @return	string
	 */
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
			
		/**
		 * Filter the cart HTML.
		 * 
		 * @since	0.?
		 *
		 * @var 	string	$html	The cart HTML.
		 */
		$html = apply_filters('wpt_cart', $html);

		return $html;
	}
	
	/**
	 * Starts a new session if no session has started yet.
	 * 
	 * @since	0.15.22
	 * @return	void
	 */
	function session_start() {
		if ( PHP_SESSION_NONE === session_status() ) {
			//session_name('Theater_Cart');
	        session_start();				    
	    }
		
	}

}

?>