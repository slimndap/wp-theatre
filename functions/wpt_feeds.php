<?php
	/**
	 * Add feeds for events and productions.
	 *
	 * Adds two new feeds: upcoming_productions and upcoming_events.
	 * Adds a link to both feeds, plus the productions archive feed to header.
	 *
	 * @since 0.7
	 *
	 */
	class WPT_Feeds {
		function __construct() {
			add_action('init', array($this,'init'));
			add_action('wp_head', array($this,'wp_head'));
		}
				
		function init() {
			add_feed('upcoming_productions',array($this,'upcoming_productions'));
			add_feed('upcoming_events',array($this,'upcoming_events'));
		}
			
		function wp_head() {
			$feed = get_post_type_archive_feed_link(WPT_Production::post_type_name);
			$html = array();
			
			$html[] = '<link rel="alternate" type="application/rss+xml" title="'.__('New productions','wp_theatre').'" href="'.$feed.'" />';
			$html[] = '<link rel="alternate" type="application/rss+xml" title="'.__('Upcoming productions','wp_theatre').'" href="'.site_url('/upcoming_productions').'" />';
			$html[] = '<link rel="alternate" type="application/rss+xml" title="'.__('Upcoming events','wp_theatre').'" href="'.site_url('/upcoming_events').'" />';
			echo implode("\n",$html)."\n";
		}
		
		function upcoming_productions() {			
			header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
			echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
			echo $this->get_upcoming_productions();				
		}
		
		function get_upcoming_productions() {
			global $wp_theatre;
	
			$args = array(
				'upcoming'=>true
			);		
			$productions = $wp_theatre->productions($args);
			
			$items = '';
			foreach ($productions as $production) {
				$item = '';
				$item.= '<item>';
				$item.= '<title>'.$production->title().'</title>';
				$item.= '<link>'.get_permalink($production->ID).'</link>';
				$item.= '<description><![CDATA['.$production->summary().']]></description>';
				$item.= '</item>';

				$items.=$item;				
			}
			
			
			$feed = '';
			$feed.=	'<rss version="2.0">';

			$feed.= '<channel>';
			$feed.= '<title>'.get_bloginfo_rss('name').' '.__('Upcoming productions','wp_theatre').'</title>';
			$feed.= '<link>'.get_bloginfo_rss('url').'</link>';
			$feed.= '<description>'.get_bloginfo_rss("description").'</description>';

			$feed.= $items;

			$feed.= '</channel>';

			$feed.=	'</rss>';

			return $feed;

		}
		
		function upcoming_events($echo=true) {
			global $wp_theatre;
	
			$args = array(
				'upcoming'=>true
			);		
			$events = $wp_theatre->events($args);
			
			$items = '';
			foreach ($events as $event) {
				$item = '';
				$item.= '<item>';
				$item.= '<title>'.$event->production()->title().'</title>';
				$item.= '<link>'.get_permalink($event->production()->ID).'</link>';
				$item.= '<description><![CDATA['.$event->production()->summary().']]></description>';
				$item.= '</item>';

				$items.=$item;				
			}
			
			$feed = '';
			$feed.=	'<rss version="2.0">';

			$feed.= '<channel>';
			$feed.= '<title>'.get_bloginfo_rss('name').' '.__('Upcoming events','wp_theatre').'</title>';
			$feed.= '<link>'.get_bloginfo_rss('url').'</link>';
			$feed.= '<description>'.get_bloginfo_rss("description").'</description>';

			$feed.= $items;

			$feed.= '<channel>';

			$feed.=	'</rss>';
			
			header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);

			echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
			echo $feed;
		}
		
		
	}
?>