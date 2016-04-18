<?php
class WPT_Tags {
	
	function __construct() {
		add_action( 'set_object_terms', array($this,'set_event_tags'),20, 6);		
		add_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
		add_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
	}
	
	function set_event_tags($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ('post_tag'==$taxonomy && WPT_Production::post_type_name==get_post_type($object_id)) {
			$production = new WPT_Production($object_id);
			$events = $production->events();
			$tags = wp_get_post_tags($object_id, array( 'fields' => 'slugs' ));
			foreach ($events as $event) {
				wp_set_post_tags($event->ID, $tags);
			}
		}
	}

	function updated_post_meta($meta_id, $object_id, $meta_key, $meta_value) {
		global $wp_theatre;
		
		// An event is attached to a production.
		if ($meta_key==WPT_Production::post_type_name) {
			$event = new WPT_Event($object_id);

			// avoid loops
			remove_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
			remove_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
			
			// inherit tags from production
			$tags = wp_get_post_tags($meta_value, array( 'fields' => 'slugs' ));

			wp_set_post_tags($event->ID, $tags);

			add_action('updated_post_meta', array($this,'updated_post_meta'), 20 ,4);
			add_action('added_post_meta', array($this,'updated_post_meta'), 20 ,4);
		}

	}
}