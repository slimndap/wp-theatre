<?php
/**
 * WPT_Order class.
 * @deprecated	0.15.13
 */
class WPT_Order {
	var $meta_key = THEATER_ORDER_INDEX_KEY;

	function update_post_order() {
		Theater_Event_Order::update_order_indexes();
	}

}
