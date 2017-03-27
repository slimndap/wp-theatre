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

		session_name('theater_cart');

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
		if (isset($_SESSION['items'])) {
			$this->items = $_SESSION['items'];
			$this->amount = $_SESSION['amount'];
		}
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
	 * Empties the cart.
	 * 
	 * @since	0.?
	 * @since	0.15.22	Renamed from 'reset()'.
	 *
	 * @return void
	 */
	function empty() {

		// Empty the cart contents.
		$this->items = array();
		$this->amount = 0;
		
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
	 * @deprecated	0.15.22.
	 */
	function reset() {
		$this->empty();
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

		$_SESSION = array(
			'items' => $this->items,
			'amount' => $this->amount
		);
				
	}

	/**
	 * Starts a new session if no session has started yet.
	 * 
	 * @since	0.15.22
	 * @return	void
	 */
	function session_start() {
		if ( PHP_SESSION_NONE === session_status() ) {
	        session_start();				    
	    }
		
	}

}

?>